<?php

declare(strict_types=1);

namespace DialloIbrahima\HasMedia\Plugins\Glide\Observers;

use DialloIbrahima\HasMedia\Plugins\Glide\Facades\Glide;
use Illuminate\Database\Eloquent\Model;

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
        if (! $this->hasGlideSupport($model)) {
            return;
        }

        $mappings = $model->getMediaMappings();

        foreach ($mappings as $column => $mapping) {
            // Check if this column is being updated
            if ($model->isDirty($column)) {
                $originalFileName = $model->getOriginal($column);

                if ($originalFileName) {
                    Glide::deleteCache($mapping->getDirectory().'/'.$originalFileName);
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
        if (! $this->hasGlideSupport($model)) {
            return;
        }

        $mappings = $model->getMediaMappings();

        foreach ($mappings as $column => $mapping) {
            $fileName = $model->getAttribute($column);

            if ($fileName) {
                Glide::deleteCache($mapping->getDirectory().'/'.$fileName);
            }
        }
    }

    /**
     * Check if model has Glide support.
     */
    protected function hasGlideSupport(Model $model): bool
    {
        return method_exists($model, 'getMediaMappings')
            && method_exists($model, 'hasImageMedia')
            && app()->bound('media.glide');
    }
}
