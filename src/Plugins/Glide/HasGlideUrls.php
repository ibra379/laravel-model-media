<?php

namespace DialloIbrahima\HasMedia\Plugins\Glide;

use DialloIbrahima\HasMedia\Exceptions\InvalidMediaTypeException;
use DialloIbrahima\HasMedia\HasMedia;
use DialloIbrahima\HasMedia\MediaMapping;
use DialloIbrahima\HasMedia\Plugins\Glide\Observers\GlideCacheObserver;
use Exception;
use Illuminate\Support\Facades\Storage;
use League\Glide\ServerFactory;
use League\Glide\Signatures\Signature;
use League\Glide\Urls\UrlBuilder;

trait HasGlideUrls
{
    /**
     * Boot the HasGlideUrls trait.
     *
     * Registers the GlideCacheObserver to automatically clean up
     * cached images when the model is updated or deleted.
     *
     * @return void
     */
    public static function bootHasGlideUrls(): void
    {
        if (app()->bound('media.glide') || class_exists(ServerFactory::class)) {
            static::observe(GlideCacheObserver::class);
        }
    }

    /**
     * Allowed image MIME types for Glide processing
     *
     * @var array
     */
    protected array $glideAllowedMimeTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
        'image/svg+xml',
    ];

    /**
     * Get Glide URL for media column with custom transformation parameters
     *
     * Generates URL for transformed image based on the column's MediaMapping
     *
     * IMPORTANT: Only works with image files
     * - Validates MIME type before generating URL
     * - Throws InvalidMediaTypeException for non-image files
     * - Returns null if file doesn't exist or Glide is not available
     *
     * @param string $column Column name (e.g., 'avatar', 'cover')
     * @param array $params Glide transformation parameters
     * @param bool $throwOnError If true, throws exception on error; if false, returns null
     * @return string|null URL to transformed image or null if not available
     * @throws InvalidMediaTypeException If file is not an image (when $throwOnError = true)
     *
     * @example
     * // Basic resize
     * $user->getGlideUrl('avatar', ['w' => 200, 'h' => 200])
     *
     * // Crop to square
     * $user->getGlideUrl('avatar', ['w' => 300, 'h' => 300, 'fit' => 'crop'])
     *
     * // With error suppression (returns null instead of throwing)
     * $user->getGlideUrl('document', ['w' => 400], throwOnError: false) // returns null for PDF
     */
    public function getGlideUrl(string $column, array $params = [], bool $throwOnError = false): ?string
    {
        if (!app()->bound('media.glide')) {
            return null;
        }

        if (!$this->hasMediaTrait()) {
            return null;
        }

        // Resolve media path information
        $mediaPath = $this->resolveMediaPath($column);

        if (!$mediaPath) {
            if ($throwOnError) {
                throw InvalidMediaTypeException::fileNotFound($column);
            }
            return null;
        }

        $absolutePath = $mediaPath['absolutePath'];
        $fullPath = $mediaPath['fullPath'];

        if (!$this->isImageFile($absolutePath)) {
            $mimeType = mime_content_type($absolutePath);

            if ($throwOnError) {
                throw InvalidMediaTypeException::notAnImage($column, $mimeType);
            }
            return null;
        }

        // Additional validation: check if the image is readable
        if (!$this->isValidImage($absolutePath)) {
            if ($throwOnError) {
                throw InvalidMediaTypeException::corruptedImage($column, $fullPath);
            }
            return null;
        }

        // Build path for Glide (relative to source directory)
        $path = $fullPath;

        // Use UrlBuilder to generate URL (handles signing automatically)
        return $this->buildGlideUrl($path, $params);
    }

    /**
     * Get Glide URL using predefined preset
     *
     * Presets are defined in config/model-media-glide.php
     * Only works with image files - validates MIME type
     *
     * @param string $column Column name
     * @param string $preset Preset name (e.g., 'thumbnail', 'medium', 'large')
     * @param bool $throwOnError If true, throws exception on error
     * @return string|null URL to transformed image or null if preset not found
     * @throws InvalidMediaTypeException If file is not an image (when $throwOnError = true)
     *
     * @example
     * // Use thumbnail preset (200x200 crop)
     * $user->getGlidePresetUrl('avatar', 'thumbnail')
     *
     * // Use medium preset (800x600 contain)
     * $post->getGlidePresetUrl('cover', 'medium')
     */
    public function getGlidePresetUrl(string $column, string $preset, bool $throwOnError = false): ?string
    {
        $presets = config('model-media-glide.presets', []);

        if (!isset($presets[$preset])) {
            return null;
        }

        return $this->getGlideUrl($column, $presets[$preset], $throwOnError);
    }

    /**
     * Get responsive image srcset attribute value
     *
     * Generates multiple image URLs at different widths for responsive images
     * Only works with image files - validates MIME type
     *
     * @param string $column Column name
     * @param array $widths Array of image widths to generate
     * @param bool $throwOnError If true, throws exception on error
     * @return string|null Srcset attribute value or null if unavailable
     * @throws InvalidMediaTypeException If file is not an image (when $throwOnError = true)
     *
     * @example
     * // Default widths
     * $post->getGlideSrcset('cover')
     *
     * // Custom widths
     * $post->getGlideSrcset('hero', [375, 768, 1024, 1920])
     */
    public function getGlideSrcset(string $column, array $widths = [400, 800, 1200, 1600], bool $throwOnError = false): ?string
    {
        if (!app()->bound('media.glide')) {
            return null;
        }

        $srcset = [];

        foreach ($widths as $width) {
            $url = $this->getGlideUrl($column, ['w' => $width, 'fm' => 'webp'], $throwOnError);

            if ($url) {
                $srcset[] = "{$url} {$width}w";
            }
        }

        return !empty($srcset) ? implode(', ', $srcset) : null;
    }

    /**
     * Check if column contains an image file
     *
     * Useful for conditional rendering in Blade templates
     *
     * @param string $column Column name
     * @return bool True if column contains a valid image file
     *
     * @example
     * @if($user->hasImageMedia('avatar'))
     *     <img src="{{ $user->getGlideUrl('avatar', ['w' => 200]) }}">
     * @else
     *     <img src="{{ asset('images/default-avatar.jpg') }}">
     * @endif
     */
    public function hasImageMedia(string $column): bool
    {
        // Check if HasMedia trait is used
        if (!$this->hasMediaTrait()) {
            return false;
        }

        // Resolve media path information
        $mediaPath = $this->resolveMediaPath($column);

        if (!$mediaPath) {
            return false;
        }

        // Check if it's a valid image
        return $this->validateImageFile($mediaPath['absolutePath']);
    }

    /**
     * Check if file is an image based on MIME type
     *
     * @param string $path Absolute file path
     * @return bool True if file is an allowed image type
     */
    protected function isImageFile(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        // Use finfo instead of deprecated mime_content_type for better security
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);

        if ($mimeType === false) {
            return false;
        }

        return in_array($mimeType, $this->glideAllowedMimeTypes);
    }

    /**
     * Validate image can be processed
     *
     * Uses getimagesize() to verify file is a valid, readable image
     *
     * @param string $path Absolute file path
     * @return bool True if image is valid and readable
     */
    protected function isValidImage(string $path): bool
    {
        try {
            $imageInfo = @getimagesize($path);
            return $imageInfo !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Build Glide URL using UrlBuilder
     *
     * Uses the UrlBuilder singleton which handles both signed and unsigned URLs
     * based on the configuration. The UrlBuilder is registered in GlidePlugin.
     *
     * @param string $path Relative image path
     * @param array $params Transformation parameters
     * @return string|null Complete URL or null on error
     */
    protected function buildGlideUrl(string $path, array $params = []): ?string
    {
        // Try to use the registered UrlBuilder
        if (app()->bound('media.glide.url')) {
            try {
                /** @var UrlBuilder $urlBuilder */
                $urlBuilder = app('media.glide.url');

                return url($urlBuilder->getUrl($path, $params));
            } catch (Exception $e) {
                logger()->debug('Failed to build Glide URL with UrlBuilder', [
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Fallback: build URL manually without signing
        $baseUrl = config('model-media-glide.route_prefix', 'media');
        $url = url($baseUrl . '/' . $path);

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * Check if HasMedia trait is used
     *
     * @return bool
     */
    private function hasMediaTrait(): bool
    {
        return in_array(HasMedia::class, class_uses_recursive($this))
            && method_exists($this, 'getAttribute');
    }

    /**
     * Get filename from column
     *
     * @param string $column Column name
     * @return string|null
     */
    private function getColumnFileName(string $column): ?string
    {
        $fileName = $this->getAttribute($column);

        return $fileName ?: null;
    }

    /**
     * Resolve media path information for a column
     *
     * @param string $column Column name
     * @return array{mapping: MediaMapping, fullPath: string, disk: string, absolutePath: string}|null
     */
    private function resolveMediaPath(string $column): ?array
    {
        $fileName = $this->getColumnFileName($column);

        if (!$fileName) {
            return null;
        }

        // Get mapping from HasMedia trait
        if (!method_exists($this, 'getMediaMappings')) {
            return null;
        }

        $mappings = $this->getMediaMappings();
        $mapping = $mappings[$column] ?? null;

        if (!$mapping) {
            return null;
        }

        $directory = $mapping->getDirectory();
        $disk = $mapping->getDisk();
        $fullPath = $directory . '/' . $fileName;

        if (!Storage::disk($disk)->exists($fullPath)) {
            return null;
        }

        $absolutePath = Storage::disk($disk)->path($fullPath);
        
        // Security: Verify the resolved absolute path is within the expected directory
        $baseDirectory = Storage::disk($disk)->path($directory);
        $realBasePath = realpath($baseDirectory);
        $realFilePath = realpath($absolutePath);
        
        // If realpath fails or path is outside the base directory, reject it
        if ($realBasePath === false || $realFilePath === false) {
            return null;
        }
        
        if (!str_starts_with($realFilePath, $realBasePath . DIRECTORY_SEPARATOR)) {
            return null;
        }

        return [
            'mapping' => $mapping,
            'fullPath' => $fullPath,
            'disk' => $disk,
            'absolutePath' => $realFilePath,
        ];
    }

    /**
     * Validate that file at path is a valid image
     *
     * @param string $absolutePath Absolute file path
     * @return bool
     */
    private function validateImageFile(string $absolutePath): bool
    {
        return $this->isImageFile($absolutePath) && $this->isValidImage($absolutePath);
    }
}
