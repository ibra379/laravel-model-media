<?php

namespace DialloIbrahima\HasMedia\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaObserver
{
    /**
     * Handle the Model "deleted" event.
     *
     * For soft-deleting models the physical files are kept on a soft delete, so
     * a restore never resurrects a row whose files are gone. Their cleanup runs
     * on the "forceDeleted" event instead.
     */
    public function deleted(Model $model): void
    {
        if ($this->usesSoftDeletes($model)) {
            return;
        }

        $this->purgeMedia($model);
    }

    /**
     * Handle the Model "forceDeleted" event.
     *
     * Only fires for soft-deleting models; this is where their files are removed.
     */
    public function forceDeleted(Model $model): void
    {
        $this->purgeMedia($model);
    }

    /**
     * Delete every media file mapped on the model.
     */
    private function purgeMedia(Model $model): void
    {
        if (
            method_exists($model, 'getMediaMappings') &&
            method_exists($model, 'detachMedia')
        ) {
            foreach ($model->getMediaMappings() as $column => $mapping) {
                $model->detachMedia($column);
            }
        }
    }

    /**
     * Determine whether the model uses the SoftDeletes trait.
     */
    private function usesSoftDeletes(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true);
    }
}
