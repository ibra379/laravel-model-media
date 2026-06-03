<?php

namespace DialloIbrahima\HasMedia\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MediaObserver
{
    /**
     * Handle the Model "deleted" event.
     *
     * For soft-deleting models the physical files are kept until the record is
     * force-deleted, so a restore never resurrects a row whose files are gone.
     * The "deleted" event still fires on forceDelete() (with isForceDeleting()
     * true), so no separate forceDeleted handler is needed.
     */
    public function deleted(Model $model): void
    {
        if ($this->isSoftDeleting($model)) {
            return;
        }

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
     * Determine whether this is a soft delete (not a force delete).
     */
    private function isSoftDeleting(Model $model): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($model), true)
            && method_exists($model, 'isForceDeleting')
            && ! $model->isForceDeleting();
    }
}
