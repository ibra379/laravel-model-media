<?php

use Illuminate\Http\UploadedFile;
use Workbench\App\Models\MediaTest;

beforeEach(function () {
    \Illuminate\Support\Facades\Storage::fake('public');
});

describe('HasMedia trait', function () {
    describe('attachMedia method', function () {
        it('stores file with correct filename and verifies disk storage', function () {
            $model = new MediaTest;
            $model->save();

            $model->attachMedia(
                UploadedFile::fake()->create('test.jpg', 100),
                'name'
            );

            expect($model->name)->toBe($model->id.'.jpg')
                ->and(\Illuminate\Support\Facades\Storage::disk('public')
                    ->exists('documents/'.$model->name))->toBeTrue();
        });
    });
});
