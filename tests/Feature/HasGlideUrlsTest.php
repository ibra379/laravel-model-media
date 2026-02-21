<?php

use DialloIbrahima\HasMedia\Exceptions\InvalidMediaTypeException;
use DialloIbrahima\HasMedia\Plugins\Glide\GlideResponseFactory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Glide\ServerFactory;
use League\Glide\Urls\UrlBuilderFactory;
use Workbench\App\Models\GlideMediaTest;

beforeEach(function () {
    Storage::fake('public');

    // Register fake Glide server
    app()->singleton('media.glide', function () {
        return ServerFactory::create([
            'response' => new GlideResponseFactory,
            'source' => Storage::disk('public')->path(''),
            'cache' => Storage::disk('public')->path('glide-cache'),
        ]);
    });

    // Register UrlBuilder for URL generation
    app()->singleton('media.glide.url', function () {
        return UrlBuilderFactory::create('/media', null);
    });

    // Set config
    config()->set('model-media-glide.source_disk', 'public');
    config()->set('model-media-glide.source_path_prefix', '');
    config()->set('model-media-glide.route_prefix', 'media');
    config()->set('model-media-glide.secure', false);
    config()->set('model-media-glide.presets', [
        'thumbnail' => ['w' => 200, 'h' => 200, 'fit' => 'crop'],
        'medium' => ['w' => 800, 'h' => 600, 'fit' => 'contain'],
    ]);
});

afterEach(function () {
    Storage::disk('public')->deleteDirectory('avatars');
    Storage::disk('public')->deleteDirectory('covers');
    Storage::disk('public')->deleteDirectory('glide-cache');
});

describe('HasGlideUrls trait', function () {

    describe('getGlideUrl', function () {
        it('returns null when glide server is not bound', function () {
            // Remove glide binding completely
            app()->offsetUnset('media.glide');

            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            // Set a filename directly without creating the actual file
            $model->fill(['avatar' => 'test-image.jpg']);
            $model->save();

            // Even with a filename set, should return null because glide is not bound
            expect($model->getGlideUrl('avatar'))->toBeNull();
        });

        it('returns null when column has no file', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();

            expect($model->getGlideUrl('avatar'))->toBeNull();
        });

        it('returns url for valid image file', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->image('test.jpg', 100, 100),
                'avatar'
            );

            $url = $model->getGlideUrl('avatar', ['w' => 200, 'h' => 200]);

            expect($url)->not->toBeNull()
                ->and($url)->toContain('media/avatars/')
                ->and($url)->toContain('w=200')
                ->and($url)->toContain('h=200');
        });

        it('returns null for non-image file when throwOnError is false', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
                'cover_image'
            );

            $url = $model->getGlideUrl('cover_image', ['w' => 200]);

            expect($url)->toBeNull();
        });

        it('throws InvalidMediaTypeException for non-image file when throwOnError is true', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
                'cover_image'
            );

            expect(fn () => $model->getGlideUrl('cover_image', ['w' => 200], throwOnError: true))
                ->toThrow(InvalidMediaTypeException::class);
        });

        it('throws InvalidMediaTypeException for missing file when throwOnError is true', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            // Manually set the column without creating a file
            $model->fill(['avatar' => 'nonexistent.jpg']);
            $model->save();

            expect(fn () => $model->getGlideUrl('avatar', ['w' => 200], throwOnError: true))
                ->toThrow(InvalidMediaTypeException::class);
        });
    });

    describe('getGlidePresetUrl', function () {
        it('returns null for unknown preset', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->image('test.jpg', 100, 100),
                'avatar'
            );

            expect($model->getGlidePresetUrl('avatar', 'unknown'))->toBeNull();
        });

        it('returns url with preset parameters', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->image('test.jpg', 100, 100),
                'avatar'
            );

            $url = $model->getGlidePresetUrl('avatar', 'thumbnail');

            expect($url)->not->toBeNull()
                ->and($url)->toContain('w=200')
                ->and($url)->toContain('h=200')
                ->and($url)->toContain('fit=crop');
        });

        it('returns null when column has no file', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();

            expect($model->getGlidePresetUrl('avatar', 'thumbnail'))->toBeNull();
        });

        it('throws InvalidMediaTypeException for non-image file when throwOnError is true', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
                'cover_image'
            );

            expect(fn () => $model->getGlidePresetUrl('cover_image', 'thumbnail', throwOnError: true))
                ->toThrow(InvalidMediaTypeException::class);
        });
    });

    describe('getGlideSrcset', function () {
        it('returns null when glide server is not bound', function () {
            // Remove glide binding completely
            app()->offsetUnset('media.glide');

            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            // Set a filename directly without creating the actual file
            $model->fill(['avatar' => 'test-image.jpg']);
            $model->save();

            // Even with a filename set, should return null because glide is not bound
            expect($model->getGlideSrcset('avatar'))->toBeNull();
        });

        it('returns srcset with default widths', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->image('test.jpg', 100, 100),
                'avatar'
            );

            $srcset = $model->getGlideSrcset('avatar');

            expect($srcset)->not->toBeNull()
                ->and($srcset)->toContain('400w')
                ->and($srcset)->toContain('800w')
                ->and($srcset)->toContain('1200w')
                ->and($srcset)->toContain('1600w');
        });

        it('returns srcset with custom widths', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->image('test.jpg', 100, 100),
                'avatar'
            );

            $srcset = $model->getGlideSrcset('avatar', [320, 640, 1024]);

            expect($srcset)->not->toBeNull()
                ->and($srcset)->toContain('320w')
                ->and($srcset)->toContain('640w')
                ->and($srcset)->toContain('1024w')
                ->and($srcset)->not->toContain('400w');
        });

        it('returns null for non-image file', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
                'cover_image'
            );

            expect($model->getGlideSrcset('cover_image'))->toBeNull();
        });

        it('throws InvalidMediaTypeException for non-image file when throwOnError is true', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
                'cover_image'
            );

            expect(fn () => $model->getGlideSrcset('cover_image', throwOnError: true))
                ->toThrow(InvalidMediaTypeException::class);
        });
    });

    describe('hasImageMedia', function () {
        it('returns false when column has no file', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();

            expect($model->hasImageMedia('avatar'))->toBeFalse();
        });

        it('returns false when file does not exist', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->fill(['avatar' => 'nonexistent.jpg']);
            $model->save();

            expect($model->hasImageMedia('avatar'))->toBeFalse();
        });

        it('returns true for valid image file', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->image('test.jpg', 100, 100),
                'avatar'
            );

            expect($model->hasImageMedia('avatar'))->toBeTrue();
        });

        it('returns false for non-image file', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
                'cover_image'
            );

            expect($model->hasImageMedia('cover_image'))->toBeFalse();
        });
    });

    describe('helper methods', function () {
        it('resolveMediaPath returns correct path info for existing file', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->attachMedia(
                UploadedFile::fake()->image('test.jpg', 100, 100),
                'avatar'
            );

            // Use reflection to test private method
            $reflection = new ReflectionClass($model);
            $method = $reflection->getMethod('resolveMediaPath');

            $result = $method->invoke($model, 'avatar');

            expect($result)->not->toBeNull()
                ->and($result)->toHaveKey('mapping')
                ->and($result)->toHaveKey('fullPath')
                ->and($result)->toHaveKey('disk')
                ->and($result)->toHaveKey('absolutePath')
                ->and($result['fullPath'])->toContain('avatars/');
        });

        it('resolveMediaPath returns null for missing file', function () {
            /** @var GlideMediaTest $model */
            $model = GlideMediaTest::factory()->create();
            $model->fill(['avatar' => 'nonexistent.jpg']);
            $model->save();

            $reflection = new ReflectionClass($model);
            $method = $reflection->getMethod('resolveMediaPath');
            $method->setAccessible(true);

            $result = $method->invoke($model, 'avatar');

            expect($result)->toBeNull();
        });
    });

});
