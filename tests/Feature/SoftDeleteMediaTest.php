<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Workbench\App\Models\SoftDeleteMediaTest;

beforeEach(function () {
    Storage::fake('public');
});

afterEach(function () {
    Storage::disk('public')->deleteDirectory('avatars');
});

describe('HasMedia with SoftDeletes', function () {

    it('keeps the file on soft delete so a restore still has its media', function () {
        /** @var SoftDeleteMediaTest $model */
        $model = SoftDeleteMediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'avatar'
        );

        $path = 'avatars/'.$model->avatar;
        Storage::disk('public')->assertExists($path);

        $model->delete();

        // Soft delete must not touch the physical file.
        Storage::disk('public')->assertExists($path);
        expect($model->trashed())->toBeTrue();
    });

    it('removes the file on force delete', function () {
        /** @var SoftDeleteMediaTest $model */
        $model = SoftDeleteMediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'avatar'
        );

        $path = 'avatars/'.$model->avatar;
        Storage::disk('public')->assertExists($path);

        $model->forceDelete();

        Storage::disk('public')->assertMissing($path);
    });

    it('removes the file when a soft-deleted model is later force deleted', function () {
        /** @var SoftDeleteMediaTest $model */
        $model = SoftDeleteMediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'avatar'
        );

        $path = 'avatars/'.$model->avatar;

        $model->delete();
        Storage::disk('public')->assertExists($path);

        $model->forceDelete();
        Storage::disk('public')->assertMissing($path);
    });
});
