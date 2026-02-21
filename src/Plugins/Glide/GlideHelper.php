<?php

namespace DialloIbrahima\HasMedia\Plugins\Glide;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Glide\Urls\UrlBuilderFactory;

class GlideHelper
{
    /**
     * Allowed image MIME types for Glide processing
     *
     * @var list<string>
     */
    public const array ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
        'image/svg+xml',
    ];

    /**
     * Generate a Glide URL for a given path
     *
     * @param  string  $path  Relative path to the image
     * @param  array<string, int|string>  $params  Transformation parameters
     * @param  bool  $validate  Whether to validate that the file is an image
     */
    public function url(string $path, array $params = [], bool $validate = true): ?string
    {
        if (empty($path)) {
            return null;
        }

        if ($validate) {
            $absolutePath = $this->resolveSourcePath($path);

            if (! $this->isImage($absolutePath) || ! $this->isValid($absolutePath)) {
                return null;
            }
        }

        $baseUrl = config('model-media-glide.route_prefix', 'media');
        $signKey = config('model-media-glide.secure', false)
            ? config('model-media-glide.signature_key')
            : null;

        $urlBuilder = UrlBuilderFactory::create($baseUrl, $signKey);

        return url($urlBuilder->getUrl($path, $params));
    }

    /**
     * Generate a Glide URL using a preset
     *
     * @param  string  $path  Relative path to the image
     * @param  string  $preset  Preset name from config
     */
    public function preset(string $path, string $preset): ?string
    {
        $presets = config('model-media-glide.presets', []);

        if (! isset($presets[$preset])) {
            return null;
        }

        return $this->url($path, $presets[$preset]);
    }

    /**
     * Generate an image srcset attribute
     *
     * @param  string  $path  Relative path to the image
     * @param  array  $widths  Array of image widths
     * @param  bool  $validate  Whether to validate that the file is an image
     */
    public function srcset(string $path, array $widths = [400, 800, 1200, 1600], bool $validate = true): ?string
    {
        $srcset = [];

        foreach ($widths as $width) {
            $url = $this->url($path, ['w' => $width, 'fm' => 'webp'], $validate);

            if ($url) {
                $srcset[] = "{$url} {$width}w";
            }
        }

        return ! empty($srcset) ? implode(', ', $srcset) : null;
    }

    /**
     * Check if a file is an image based on its path
     */
    public function isImage(string $absolutePath): bool
    {
        if (! file_exists($absolutePath)) {
            return false;
        }

        $mimeType = @mime_content_type($absolutePath);

        return in_array($mimeType, self::ALLOWED_MIME_TYPES);
    }

    /**
     * Check if an image is valid and readable
     */
    public function isValid(string $absolutePath): bool
    {
        if (! $this->isImage($absolutePath)) {
            return false;
        }

        // SVG files cannot be validated with getimagesize()
        $mimeType = @mime_content_type($absolutePath);
        if ($mimeType === 'image/svg+xml') {
            return true;
        }

        try {
            $imageInfo = @getimagesize($absolutePath);

            return $imageInfo !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clear Glide cache for a specific file path.
     *
     * @param  string  $path  Relative path to the source file
     */
    public function deleteCache(string $path): void
    {
        $cacheDisk = config('model-media-glide.cache_disk', 'local');
        $cachePath = config('model-media-glide.cache_path', 'glide-cache');
        $cacheDir = Storage::disk($cacheDisk)->path($cachePath);

        $normalizedPath = ltrim($path, DIRECTORY_SEPARATOR);

        // 1. Manual cleanup (find and delete all cached versions)
        $fullCachePath = $cacheDir.DIRECTORY_SEPARATOR.$normalizedPath;
        $cacheFileDir = dirname($fullCachePath);

        if (File::isDirectory($cacheFileDir)) {
            $baseName = pathinfo($path, PATHINFO_FILENAME);
            $files = File::glob($cacheFileDir.DIRECTORY_SEPARATOR.$baseName.'*');

            foreach ($files as $file) {
                if (File::isFile($file)) {
                    File::delete($file);
                }
            }
        }

        // 2. Native Glide cleanup
        if (app()->bound('media.glide')) {
            try {
                $server = app('media.glide');
                $server->deleteCache($normalizedPath);
            } catch (Exception $e) {
                // Silently fail - cache cleanup is not critical
            }
        }
    }

    /**
     * Resolve the absolute source path for a relative image path.
     */
    protected function resolveSourcePath(string $path): string
    {
        $disk = config('model-media-glide.source_disk', 'public');
        $prefix = config('model-media-glide.source_path_prefix', '');

        $relativePath = $prefix ? $prefix.'/'.$path : $path;

        return Storage::disk($disk)->path($relativePath);
    }
}
