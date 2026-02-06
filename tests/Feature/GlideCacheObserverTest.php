<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use League\Glide\ServerFactory;
use Workbench\App\Models\GlideMediaTest;

beforeEach(function () {
    Storage::fake('public');

    // Set cache directory path
    $this->cacheDir = Storage::disk('public')->path('glide-cache');

    // Create cache directory
    if (!File::isDirectory($this->cacheDir)) {
        File::makeDirectory($this->cacheDir, 0755, true);
    }

    // Register fake Glide server
    app()->singleton('media.glide', function () {
        return ServerFactory::create([
            'source' => Storage::disk('public')->path(''),
            'cache' => Storage::disk('public')->path('glide-cache'),
        ]);
    });

    // Set config
    config()->set('model-media-glide.route_prefix', 'media');
    config()->set('model-media-glide.secure', false);
    config()->set('model-media-glide.cache', Storage::disk('public')->path('glide-cache'));
});

afterEach(function () {
    Storage::disk('public')->deleteDirectory('images');
    Storage::disk('public')->deleteDirectory('documents');
    Storage::disk('public')->deleteDirectory('glide-cache');
    Mockery::close();
});

describe('GlideCacheObserver', function () {

    describe('on model update', function () {
        it('clears cache when image column is updated', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();

            // Attach initial image
            $model->attachMedia(
                UploadedFile::fake()->image('original.jpg', 100, 100),
                'name'
            );
            $model->save();

            $originalFileName = $model->name;

            // Create fake cache files for the original image
            $cacheDir = Storage::disk('public')->path('glide-cache/images');
            if (!File::isDirectory($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }

            $baseName = pathinfo($originalFileName, PATHINFO_FILENAME);
            $cachedFile1 = $cacheDir . '/' . $baseName . '_w200_h200.jpg';
            $cachedFile2 = $cacheDir . '/' . $baseName . '_w400.webp';

            File::put($cachedFile1, 'fake cache content 1');
            File::put($cachedFile2, 'fake cache content 2');

            expect(File::exists($cachedFile1))->toBeTrue();
            expect(File::exists($cachedFile2))->toBeTrue();

            // Update with new image
            $model->attachMedia(
                UploadedFile::fake()->image('updated.jpg', 200, 200),
                'name'
            );
            $model->save();

            // Cached files for old image should be deleted
            expect(File::exists($cachedFile1))->toBeFalse();
            expect(File::exists($cachedFile2))->toBeFalse();
        });

        it('does not clear cache when non-media column is updated', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();

            // Attach image
            $model->attachMedia(
                UploadedFile::fake()->image('test.jpg', 100, 100),
                'name'
            );
            $model->save();

            $fileName = $model->name;

            // Create fake cache file
            $cacheDir = Storage::disk('public')->path('glide-cache/images');
            if (!File::isDirectory($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }

            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            $cachedFile = $cacheDir . '/' . $baseName . '_w200.jpg';
            File::put($cachedFile, 'fake cache content');

            expect(File::exists($cachedFile))->toBeTrue();

            // Update a non-media column
            $model->slug = 'new-slug';
            $model->save();

            // Cache should still exist
            expect(File::exists($cachedFile))->toBeTrue();
        });
    });

    describe('on model delete', function () {
        it('clears cache when model is deleted', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();

            // Attach image
            $model->attachMedia(
                UploadedFile::fake()->image('test.jpg', 100, 100),
                'name'
            );
            $model->save();

            $fileName = $model->name;

            // Create fake cache files
            $cacheDir = Storage::disk('public')->path('glide-cache/images');
            if (!File::isDirectory($cacheDir)) {
                File::makeDirectory($cacheDir, 0755, true);
            }

            $baseName = pathinfo($fileName, PATHINFO_FILENAME);
            $cachedFile1 = $cacheDir . '/' . $baseName . '_thumbnail.jpg';
            $cachedFile2 = $cacheDir . '/' . $baseName . '_medium.webp';

            File::put($cachedFile1, 'fake cache 1');
            File::put($cachedFile2, 'fake cache 2');

            expect(File::exists($cachedFile1))->toBeTrue();
            expect(File::exists($cachedFile2))->toBeTrue();

            // Delete the model
            $model->delete();

            // Cached files should be deleted
            expect(File::exists($cachedFile1))->toBeFalse();
            expect(File::exists($cachedFile2))->toBeFalse();
        });

        it('clears cache for multiple media columns', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();

            // Attach images to both columns
            $model->attachMedia(
                UploadedFile::fake()->image('avatar.jpg', 100, 100),
                'name'
            );
            $model->attachMedia(
                UploadedFile::fake()->image('document.png', 200, 200),
                'name_with_id'
            );
            $model->save();

            $fileName1 = $model->name;
            $fileName2 = $model->name_with_id;

            // Create cache directories
            $imagesCacheDir = Storage::disk('public')->path('glide-cache/images');
            $docsCacheDir = Storage::disk('public')->path('glide-cache/documents');

            if (!File::isDirectory($imagesCacheDir)) {
                File::makeDirectory($imagesCacheDir, 0755, true);
            }
            if (!File::isDirectory($docsCacheDir)) {
                File::makeDirectory($docsCacheDir, 0755, true);
            }

            // Create cached files for both columns
            $baseName1 = pathinfo($fileName1, PATHINFO_FILENAME);
            $baseName2 = pathinfo($fileName2, PATHINFO_FILENAME);

            $cachedFile1 = $imagesCacheDir . '/' . $baseName1 . '_w200.jpg';
            $cachedFile2 = $docsCacheDir . '/' . $baseName2 . '_w400.png';

            File::put($cachedFile1, 'fake cache 1');
            File::put($cachedFile2, 'fake cache 2');

            expect(File::exists($cachedFile1))->toBeTrue();
            expect(File::exists($cachedFile2))->toBeTrue();

            // Delete the model
            $model->delete();

            // Both cached files should be deleted
            expect(File::exists($cachedFile1))->toBeFalse();
            expect(File::exists($cachedFile2))->toBeFalse();
        });
    });

    describe('edge cases', function () {
        it('handles missing cache directory gracefully', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();

            // Attach image
            $model->attachMedia(
                UploadedFile::fake()->image('test.jpg', 100, 100),
                'name'
            );
            $model->save();

            // Ensure cache directory does not exist
            $cacheDir = Storage::disk('public')->path('glide-cache');
            if (File::isDirectory($cacheDir)) {
                File::deleteDirectory($cacheDir);
            }

            // Delete should not throw an exception
            expect(fn () => $model->delete())->not->toThrow(Exception::class);
        });

        it('handles empty media column gracefully', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();

            // No media attached - name column is null

            // Delete should not throw an exception
            expect(fn () => $model->delete())->not->toThrow(Exception::class);
        });
    });
});
