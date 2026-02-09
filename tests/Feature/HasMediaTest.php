<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Workbench\App\Models\MediaTest;

beforeEach(function () {
    Storage::fake('public');

    \Illuminate\Support\Str::createRandomStringsUsing(fn () => 'random'); // To make tests predictable when using Str::random()
});

afterEach(function () {
    Storage::disk('public')->deleteDirectory('documents');
    Mockery::close();
    \Illuminate\Support\Str::createRandomStringsNormally();
});

describe('HasMedia trait', function () {
    it('stores uploaded file to disk with generated filename', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'name'
        );

        expect($model->name)->toBe($model->slug.'.jpg')
            ->and(Storage::disk('public')
                ->exists('documents/'.$model->name))->toBeTrue();
    });

    it('deletes previous file when attaching new media to same property', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create(['slug' => 'initial-slug']);

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'name'
        );
        $firstFileName = $model->name;

        Storage::disk('public')->assertExists('documents/'.$firstFileName);

        $model->update(['slug' => 'new-slug']);

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'name'
        );
        Storage::disk('public')->assertMissing('documents/'.$firstFileName);
        Storage::disk('public')->assertExists('documents/'.$model->name);
        expect($model->name)->toBe($model->slug.'.jpg');
    });

    it('preserves previous file when upload fails', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create(['slug' => 'initial-slug']);

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'name'
        );
        $firstFileName = $model->name;

        Storage::disk('public')->assertExists('documents/'.$firstFileName);

        $model->fill(['slug' => $newSlug = 'new-slug']);

        $file = UploadedFile::fake()->create('test.jpg', 100);
        $mock = Mockery::mock($file)->makePartial();
        $mock->shouldReceive('storeAs')->andReturn(false);
        /** @var UploadedFile $mock */
        $model->attachMedia($mock, 'name');
        Storage::disk('public')->assertExists('documents/'.$firstFileName);
        Storage::disk('public')->assertMissing('documents/'.$newSlug.'jpg');
        expect($model->name)->toBe($firstFileName);
    });

    it('removes associated file when model is deleted', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'name'
        );

        Storage::disk('public')->assertExists('documents/'.$model->name);
        $model->delete();
        Storage::disk('public')->assertMissing('documents/'.$model->name);
    });

    it('supports callable filename generator', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'name_with_id'
        );

        expect($model->name_with_id)->toBe($model->id.'-random.jpg')
            ->and(Storage::disk('public')
                ->exists('documents/'.$model->name_with_id))->toBeTrue();
    });

    it('returns correct URL for media file', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'name_with_id'
        );

        expect($model->name_with_id)->toBe($model->id.'-random.jpg');

        $url = $model->getMediaUrl('name_with_id');
        expect($url)->toBe(Storage::disk('public')->url('documents/'.$model->name_with_id));
    });

    it('preserves file when attaching media with identical filename', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create(['slug' => 'same-slug']);

        // Attach initial file
        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'name'
        );
        $fileName = $model->name;

        Storage::disk('public')->assertExists('documents/'.$fileName);

        // Attach another file with same slug (will generate same filename)
        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'name'
        );

        // File should still exist (not deleted because filenames are identical)
        Storage::disk('public')->assertExists('documents/'.$fileName);
        expect($model->name)->toBe($fileName);
    });
});
