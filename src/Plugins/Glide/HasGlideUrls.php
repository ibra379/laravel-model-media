<?php

namespace DialloIbrahima\HasMedia\Plugins\Glide;

use DialloIbrahima\HasMedia\Exceptions\InvalidMediaTypeException;
use DialloIbrahima\HasMedia\HasMedia;
use DialloIbrahima\HasMedia\MediaMapping;
use DialloIbrahima\HasMedia\Plugins\Glide\Observers\GlideCacheObserver;
use Illuminate\Support\Facades\Storage;
use League\Glide\ServerFactory;

trait HasGlideUrls
{
    /**
     * Boot the HasGlideUrls trait.
     *
     * Registers the GlideCacheObserver to automatically clean up
     * cached images when the model is updated or deleted.
     */
    public static function bootHasGlideUrls(): void
    {
        if (app()->bound('media.glide') || class_exists(ServerFactory::class)) {
            static::observe(GlideCacheObserver::class);
        }
    }

    /**
     * Get Glide URL for a media column with custom transformation parameters
     *
     * Generates URL for a transformed image based on the column's MediaMapping
     *
     * IMPORTANT: Only works with image files
     * - Validates MIME type before generating URL
     * - Throws InvalidMediaTypeException for non-image files
     * - Returns null if a file doesn't exist or Glide is not available
     *
     * @param  string  $column  Column name (e.g., 'avatar', 'cover')
     * @param  array  $params  Glide transformation parameters
     * @param  bool  $throwOnError  If true, throws exception on error; if false, returns null
     * @return string|null URL to transformed image or null if not available
     *
     * @throws InvalidMediaTypeException If a file is not an image (when $throwOnError = true)
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
        $mediaPath = $this->resolveAndValidateImageMedia($column, $throwOnError);

        if (! $mediaPath) {
            return null;
        }

        return $this->getGlideHelper()->url($mediaPath['fullPath'], $params, validate: false);
    }

    /**
     * Get Glide URL using predefined preset
     *
     * Presets are defined in config/model-media-glide.php
     * Only works with image files - validates MIME type
     *
     * @param  string  $column  Column name
     * @param  string  $preset  Preset name (e.g., 'thumbnail', 'medium', 'large')
     * @param  bool  $throwOnError  If true, throws exception on error
     * @return string|null URL to transformed image or null if preset not found
     *
     * @throws InvalidMediaTypeException If a file is not an image (when $throwOnError = true)
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
        if (! $this->hasMediaTrait()) {
            return null;
        }

        $presets = config('model-media-glide.presets', []);

        if (! isset($presets[$preset])) {
            return null;
        }

        $mediaPath = $this->resolveMediaPath($column);

        if (! $mediaPath) {
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
     * @param  string  $column  Column name
     * @param  array  $widths  Array of image widths to generate
     * @param  bool  $throwOnError  If true, throws exception on error
     * @return string|null Srcset attribute value or null if unavailable
     *
     * @throws InvalidMediaTypeException If a file is not an image (when $throwOnError = true)
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
        $mediaPath = $this->resolveAndValidateImageMedia($column, $throwOnError);

        if (! $mediaPath) {
            return null;
        }

        return $this->getGlideHelper()->srcset($mediaPath['fullPath'], $widths, validate: false);
    }

    /**
     * Check if a column contains an image file
     *
     * Useful for conditional rendering in Blade templates
     *
     * @param  string  $column  Column name
     * @return bool True if the column contains a valid image file
     *
     * @example
     *
     * @if($user->hasImageMedia('avatar'))
     *     <img src="{{ $user->getGlideUrl('avatar', ['w' => 200]) }}">
     *
     * @else
     *     <img src="{{ asset('images/default-avatar.jpg') }}">
     *
     * @endif
     */
    public function hasImageMedia(string $column): bool
    {
        // Check if HasMedia trait is used
        if (! $this->hasMediaTrait()) {
            return false;
        }

        // Resolve media path information
        $mediaPath = $this->resolveMediaPath($column);

        if (! $mediaPath) {
            return false;
        }

        // Check if it's a valid image
        return $this->validateImageFile($mediaPath['absolutePath']);
    }

    /**
     * Check if a file is an image based on a MIME type
     *
     * @param  string  $path  Absolute file path
     * @return bool True if the file is an allowed image type
     */
    protected function isImageFile(string $path): bool
    {
        return $this->getGlideHelper()->isImage($path);
    }

    /**
     * Validate image can be processed
     *
     * Uses getimagesize() to verify a file is a valid, readable image
     *
     * @param  string  $path  Absolute file path
     * @return bool True if the image is valid and readable
     */
    protected function isValidImage(string $path): bool
    {
        return $this->getGlideHelper()->isValid($path);
    }

    /**
     * Build Glide URL using GlideHelper
     *
     * @param  string  $path  Relative image path
     * @param  array  $params  Transformation parameters
     * @return string|null Complete URL or null on error
     */
    protected function buildGlideUrl(string $path, array $params = []): ?string
    {
        return $this->getGlideHelper()->url($path, $params);
    }

    /**
     * Resolve and validate the media path for a column
     *
     * @param  string  $column  Column name
     * @param  bool  $throwOnError  If true, throws exception on error
     * @return array{mapping: MediaMapping, fullPath: string, disk: string, absolutePath: string}|null
     *
     * @throws InvalidMediaTypeException
     */
    protected function resolveAndValidateImageMedia(string $column, bool $throwOnError = false): ?array
    {
        if (! $this->hasMediaTrait()) {
            return null;
        }

        $mediaPath = $this->resolveMediaPath($column);

        if (! $mediaPath) {
            if ($throwOnError) {
                throw InvalidMediaTypeException::fileNotFound($column);
            }

            return null;
        }

        $absolutePath = $mediaPath['absolutePath'];
        $fullPath = $mediaPath['fullPath'];
        $helper = $this->getGlideHelper();

        if (! $helper->isImage($absolutePath)) {
            $mimeType = mime_content_type($absolutePath);

            if ($throwOnError) {
                throw InvalidMediaTypeException::notAnImage($column, $mimeType);
            }

            return null;
        }

        if (! $helper->isValid($absolutePath)) {
            if ($throwOnError) {
                throw InvalidMediaTypeException::corruptedImage($column, $fullPath);
            }

            return null;
        }

        return $mediaPath;
    }

    /**
     * Get the GlideHelper instance
     */
    protected function getGlideHelper(): GlideHelper
    {
        return app('media.glide.helper');
    }

    /**
     * Check if HasMedia trait is used
     */
    private function hasMediaTrait(): bool
    {
        return in_array(HasMedia::class, class_uses_recursive($this))
            && method_exists($this, 'getAttribute');
    }

    /**
     * Get filename from a column
     *
     * @param  string  $column  Column name
     */
    private function getColumnFileName(string $column): ?string
    {
        $fileName = $this->getAttribute($column);

        return $fileName ?: null;
    }

    /**
     * Resolve media path information for a column
     *
     * @param  string  $column  Column name
     * @return array{mapping: MediaMapping, fullPath: string, disk: string, absolutePath: string}|null
     */
    private function resolveMediaPath(string $column): ?array
    {
        $fileName = $this->getColumnFileName($column);

        if (! $fileName) {
            return null;
        }

        // Get mapping from HasMedia trait
        if (! method_exists($this, 'getMediaMappings')) {
            return null;
        }

        $mappings = $this->getMediaMappings();
        $mapping = $mappings[$column] ?? null;

        if (! $mapping) {
            return null;
        }

        $directory = $mapping->getDirectory();
        $disk = $mapping->getDisk();
        $fullPath = $directory.'/'.$fileName;

        if (! Storage::disk($disk)->exists($fullPath)) {
            return null;
        }

        return [
            'mapping' => $mapping,
            'fullPath' => $fullPath,
            'disk' => $disk,
            'absolutePath' => Storage::disk($disk)->path($fullPath),
        ];
    }

    /**
     * Validate that a file at a path is a valid image
     *
     * @param  string  $absolutePath  Absolute file path
     */
    private function validateImageFile(string $absolutePath): bool
    {
        return $this->isImageFile($absolutePath) && $this->isValidImage($absolutePath);
    }
}
