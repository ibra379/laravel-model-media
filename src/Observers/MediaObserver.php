<?php

namespace DialloIbrahima\HasMedia\Observers;

use Illuminate\Database\Eloquent\Model;

class MediaObserver
{
    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        if (
            method_exists($model, 'getMediaMappings') &&
            method_exists($model, 'detachMedia')
        ) {
            $mappings = $model->getMediaMappings();

            foreach ($mappings as $column => $mapping) {
                $model->detachMedia($column);
            }
        }
    }
}
