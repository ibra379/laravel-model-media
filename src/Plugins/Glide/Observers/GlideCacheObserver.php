<?php

declare(strict_types=1);

namespace DialloIbrahima\HasMedia\Plugins\Glide\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

/**
 * GlideCacheObserver
 *
 * Automatically cleans up Glide cached images when:
 * - Model is updated (clears cache for changed media columns)
 * - Model is deleted (clears all cached images for the model)
 *
 * This prevents stale cached images from being served after
 * the original image has been updated or deleted.
 */
class GlideCacheObserver
{
    /**
     * Handle the Model "updating" event.
     *
     * Clears Glide cache for any media columns that have changed.
     */
    public function updating(Model $model): void
    {
        if (!$this->hasGlideSupport($model)) {
            return;
        }

        $mappings = $model->getMediaMappings();

        foreach ($mappings as $column => $mapping) {
            // Check if this column is being updated
            if ($model->isDirty($column)) {
                $originalFileName = $model->getOriginal($column);

                if ($originalFileName) {
                    $this->clearCacheForFile(
                        $mapping->getDirectory() . '/' . $originalFileName
                    );
                }
            }
        }
    }

    /**
     * Handle the Model "deleted" event.
     *
     * Clears Glide cache for all media files associated with the model.
     */
    public function deleted(Model $model): void
    {
        if (!$this->hasGlideSupport($model)) {
            return;
        }

        $mappings = $model->getMediaMappings();

        foreach ($mappings as $column => $mapping) {
            $fileName = $model->getAttribute($column);

            if ($fileName) {
                $this->clearCacheForFile(
                    $mapping->getDirectory() . '/' . $fileName
                );
            }
        }
    }

    /**
     * Clear Glide cache for a specific file path.
     *
     * Glide stores cached files in a directory structure based on the
     * source file path. This method removes all cached versions of a file.
     *
     * @param string $filePath Relative path to the source file
     */
    protected function clearCacheForFile(string $filePath): void
    {
        $cacheDir = config('model-media-glide.cache', storage_path('app/glide-cache'));

        // Glide creates cache directories based on the file path
        // The cache structure is: {cache_dir}/{path_hash}/{filename}
        // We need to find and delete all cached versions

        // Security: Properly normalize the path using preg_replace
        // Remove leading slashes and collapse multiple slashes
        $normalizedPath = preg_replace('#^/+#', '', $filePath);
        $normalizedPath = preg_replace('#/+#', '/', $normalizedPath);
        
        // Security: Prevent path traversal attacks
        if (str_contains($normalizedPath, '..')) {
            logger()->warning('Path traversal attempt detected in cache cleanup', [
                'path' => $filePath,
            ]);
            return;
        }

        // Get the cache path for this file
        $cachePath = $cacheDir . '/' . $normalizedPath;

        // Delete the cached file directory if it exists
        $cacheFileDir = dirname($cachePath);

        if (File::isDirectory($cacheFileDir)) {
            // Find all files matching the base filename with any extension/params
            $baseName = pathinfo($filePath, PATHINFO_FILENAME);
            $files = File::glob($cacheFileDir . '/' . $baseName . '*');

            foreach ($files as $file) {
                if (File::isFile($file)) {
                    File::delete($file);
                }
            }
        }

        // Also try to clear using Glide's server deleteCache method if available
        if (app()->bound('media.glide')) {
            try {
                $server = app('media.glide');

                if (method_exists($server, 'deleteCache')) {
                    $server->deleteCache($normalizedPath);
                }
            } catch (\Exception $e) {
                // Silently fail - cache cleanup is not critical
                logger()->debug('Failed to clear Glide cache', [
                    'path' => $filePath,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Check if model has Glide support.
     *
     * @param Model $model
     * @return bool
     */
    protected function hasGlideSupport(Model $model): bool
    {
        return method_exists($model, 'getMediaMappings')
            && method_exists($model, 'hasImageMedia')
            && app()->bound('media.glide');
    }
}
