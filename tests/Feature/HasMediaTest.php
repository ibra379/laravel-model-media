<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Workbench\App\Models\MediaTest;

beforeEach(function () {
    Storage::fake('public');

    \Illuminate\Support\Str::createRandomStringsUsing(fn () => 'random'); // To make tests predictable when using Str::random()
});

afterEach(function () {
    Storage::disk('public')->deleteDirectory('avatars');
    Storage::disk('public')->deleteDirectory('covers');
    Mockery::close();
    \Illuminate\Support\Str::createRandomStringsNormally();
});

describe('HasMedia trait', function () {
    it('stores uploaded file to disk with generated filename', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'avatar'
        );

        expect($model->avatar)->toBe($model->slug.'.jpg')
            ->and(Storage::disk('public')
                ->exists('avatars/'.$model->avatar))->toBeTrue();
    });

    it('deletes previous file when attaching new media to same property', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create(['slug' => 'initial-slug']);

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'avatar'
        );
        $firstFileName = $model->avatar;

        Storage::disk('public')->assertExists('avatars/'.$firstFileName);

        $model->update(['slug' => 'new-slug']);

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'avatar'
        );
        Storage::disk('public')->assertMissing('avatars/'.$firstFileName);
        Storage::disk('public')->assertExists('avatars/'.$model->avatar);
        expect($model->avatar)->toBe($model->slug.'.jpg');
    });

    it('preserves previous file when upload fails', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create(['slug' => 'initial-slug']);

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'avatar'
        );
        $firstFileName = $model->avatar;

        Storage::disk('public')->assertExists('avatars/'.$firstFileName);

        $model->fill(['slug' => $newSlug = 'new-slug']);

        $file = UploadedFile::fake()->create('test.jpg', 100);
        $mock = Mockery::mock($file)->makePartial();
        $mock->shouldReceive('storeAs')->andReturn(false);
        /** @var UploadedFile $mock */
        $model->attachMedia($mock, 'avatar');
        Storage::disk('public')->assertExists('avatars/'.$firstFileName);
        Storage::disk('public')->assertMissing('avatars/'.$newSlug.'.jpg');
        expect($model->avatar)->toBe($firstFileName);
    });

    it('removes associated file when model is deleted', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'avatar'
        );

        Storage::disk('public')->assertExists('avatars/'.$model->avatar);
        $model->delete();
        Storage::disk('public')->assertMissing('avatars/'.$model->avatar);
    });

    it('supports callable filename generator', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'cover_image'
        );

        expect($model->cover_image)->toBe($model->id.'-random.jpg')
            ->and(Storage::disk('public')
                ->exists('covers/'.$model->cover_image))->toBeTrue();
    });

    it('returns correct URL for media file', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'cover_image'
        );

        expect($model->cover_image)->toBe($model->id.'-random.jpg');

        $url = $model->getMediaUrl('cover_image');
        expect($url)->toBe(Storage::disk('public')->url('covers/'.$model->cover_image));
    });

    it('detaches media and deletes file from disk', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'avatar'
        );

        $fileName = $model->avatar;
        Storage::disk('public')->assertExists('avatars/'.$fileName);

        $result = $model->detachMedia('avatar');

        expect($result)->toBeTrue()
            ->and($model->avatar)->toBeNull();
        Storage::disk('public')->assertMissing('avatars/'.$fileName);
    });

    it('returns true when detaching null column', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        expect($model->detachMedia(null))->toBeTrue();
    });

    it('returns true when detaching column with no file', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        expect($model->detachMedia('avatar'))->toBeTrue();
    });

    it('returns null for getMediaUrl when column has no file', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create();

        expect($model->getMediaUrl('avatar'))->toBeNull();
    });

    it('returns media mappings for the model', function () {
        $model = MediaTest::factory()->create();
        $mappings = $model->getMediaMappings();

        expect($mappings)->toBeArray()
            ->toHaveKey('avatar')
            ->toHaveKey('cover_image');
    });

    it('throws RuntimeException for unmapped column', function () {
        $model = MediaTest::factory()->create();

        expect(fn () => $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'nonexistent_column'
        ))->toThrow(\RuntimeException::class, 'No media mapping found for column: nonexistent_column');
    });

    it('preserves file when attaching media with identical filename', function () {
        /** @var MediaTest $model */
        $model = MediaTest::factory()->create(['slug' => 'same-slug']);

        // Attach initial file
        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'avatar'
        );
        $fileName = $model->avatar;

        Storage::disk('public')->assertExists('avatars/'.$fileName);

        // Attach another file with same slug (will generate same filename)
        $model->attachMedia(
            UploadedFile::fake()->create('test.jpg', 100),
            'avatar'
        );

        // File should still exist (not deleted because filenames are identical)
        Storage::disk('public')->assertExists('avatars/'.$fileName);
        expect($model->avatar)->toBe($fileName);
    });
});
