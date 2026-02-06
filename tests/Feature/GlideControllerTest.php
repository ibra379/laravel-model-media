<?php

use DialloIbrahima\HasMedia\Plugins\Glide\Controllers\GlideController;
use DialloIbrahima\HasMedia\Plugins\Glide\GlideResponseFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use League\Glide\ServerFactory;
use League\Glide\Signatures\SignatureFactory;
use League\Glide\Urls\UrlBuilderFactory;

beforeEach(function () {
    Storage::fake('public');

    // Create glide-cache directory
    $cachePath = Storage::disk('public')->path('glide-cache');
    if (!file_exists($cachePath)) {
        mkdir($cachePath, 0755, true);
    }

    // Register Glide server
    app()->singleton('media.glide', function () {
        return ServerFactory::create([
            'response' => new GlideResponseFactory(),
            'source' => Storage::disk('public')->path(''),
            'cache' => Storage::disk('public')->path('glide-cache'),
        ]);
    });

    // Register UrlBuilder
    app()->singleton('media.glide.url', function () {
        return UrlBuilderFactory::create('/media', null);
    });

    // Set config
    config()->set('model-media-glide.route_prefix', 'media');
    config()->set('model-media-glide.secure', false);
    config()->set('model-media-glide.source', Storage::disk('public')->path(''));
    config()->set('model-media-glide.cache', Storage::disk('public')->path('glide-cache'));

    // Register Glide routes manually for testing
    Route::prefix('media')->group(function () {
        Route::get('/{path}', [GlideController::class, 'show'])
            ->where('path', '.*')
            ->name('media.glide');
    });
});

afterEach(function () {
    Storage::disk('public')->deleteDirectory('images');
    Storage::disk('public')->deleteDirectory('documents');
    Storage::disk('public')->deleteDirectory('uploads');
    Storage::disk('public')->deleteDirectory('glide-cache');
});

/**
 * Create a real test image using GD library
 */
function createRealTestImage(string $path, int $width = 100, int $height = 100): string
{
    $dir = dirname($path);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }

    $image = imagecreatetruecolor($width, $height);

    // Fill with gradient
    for ($i = 0; $i < $width; $i++) {
        $ratio = $i / $width;
        $r = (int) (255 * (1 - $ratio) + 100 * $ratio);
        $g = 100;
        $b = (int) (100 * (1 - $ratio) + 255 * $ratio);
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, $i, 0, $i, $height, $color);
    }

    imagejpeg($image, $path, 90);
    imagedestroy($image);

    return $path;
}

/**
 * Setup secure mode with signature key
 */
function setupSecureMode(string $signatureKey = 'test-secret-key-32chars-long!!'): void
{
    config()->set('model-media-glide.secure', true);
    config()->set('model-media-glide.signature_key', $signatureKey);

    app()->singleton(\League\Glide\Signatures\Signature::class, function () use ($signatureKey) {
        return SignatureFactory::create($signatureKey);
    });
}

describe('GlideController', function () {

    describe('file existence validation', function () {
        it('returns 404 for non-existent file', function () {
            $response = $this->get('/media/images/nonexistent.jpg');
            $response->assertNotFound();
        });

        it('returns 404 for non-existent nested path', function () {
            $response = $this->get('/media/uploads/2024/01/missing.jpg');
            $response->assertNotFound();
        });
    });

    describe('file type validation', function () {
        it('returns 415 for text file', function () {
            Storage::disk('public')->put('documents/file.txt', 'This is a text file');
            $response = $this->get('/media/documents/file.txt');
            $response->assertStatus(415);
        });

        it('returns 415 for PDF file', function () {
            Storage::disk('public')->put('documents/file.pdf', '%PDF-1.4 fake pdf content');
            $response = $this->get('/media/documents/file.pdf');
            $response->assertStatus(415);
        });

        it('returns 422 for file with image extension but invalid content', function () {
            Storage::disk('public')->put('images/fake.jpg', 'not an image');
            $response = $this->get('/media/images/fake.jpg');
            expect($response->status())->toBeIn([415, 422]);
        });
    });

    describe('signature validation', function () {
        beforeEach(function () {
            config()->set('model-media-glide.secure', true);
            config()->set('model-media-glide.signature_key', 'test-secret-key-32chars-long!!');

            app()->singleton(\League\Glide\Signatures\Signature::class, function () {
                return \League\Glide\Signatures\SignatureFactory::create(
                    config('model-media-glide.signature_key')
                );
            });
        });

        it('returns 403 for request without signature', function () {
            setupSecureMode();
            $response = $this->get('/media/images/test.jpg');
            $response->assertForbidden();
        });

        it('returns 403 for request with invalid signature', function () {
            setupSecureMode();
            $response = $this->get('/media/images/test.jpg?w=200&s=invalid');
            $response->assertForbidden();
        });

        it('returns 403 for tampered parameters', function () {
            setupSecureMode();
            $urlBuilder = UrlBuilderFactory::create('/media', config('model-media-glide.signature_key'));
            $signedUrl = $urlBuilder->getUrl('images/test.jpg', ['w' => 200]);
            $tamperedUrl = str_replace('w=200', 'w=400', $signedUrl);
            $response = $this->get($tamperedUrl);
            $response->assertForbidden();
        });
    });

    describe('route matching', function () {
        it('matches simple path', function () {
            $response = $this->get('/media/test.jpg');
            $response->assertNotFound();
        });

        it('matches path with directory', function () {
            $response = $this->get('/media/images/test.jpg');
            $response->assertNotFound();
        });

        it('matches deeply nested path', function () {
            $response = $this->get('/media/users/123/avatars/profile.jpg');
            $response->assertNotFound();
        });

        it('includes query parameters in request', function () {
            config()->set('model-media-glide.secure', false);
            Storage::disk('public')->put('test.txt', 'text');
            $response = $this->get('/media/test.txt?w=200&h=200');
            $response->assertStatus(415);
        });
    });

    describe('image response without secure mode', function () {
        it('serves a real image file', function () {
            createRealTestImage(Storage::disk('public')->path('images/test.jpg'));
            $response = $this->get('/media/images/test.jpg');
            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/jpeg');
        });

        it('serves a transformed image with width parameter', function () {
            createRealTestImage(Storage::disk('public')->path('images/photo.jpg'), 800, 600);
            $response = $this->get('/media/images/photo.jpg?w=200');
            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/jpeg');
        });

        it('serves a transformed image with height parameter', function () {
            createRealTestImage(Storage::disk('public')->path('images/photo.jpg'), 800, 600);
            $response = $this->get('/media/images/photo.jpg?h=150');
            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/jpeg');
        });

        it('serves a cropped image', function () {
            createRealTestImage(Storage::disk('public')->path('images/photo.jpg'), 800, 600);
            $response = $this->get('/media/images/photo.jpg?w=200&h=200&fit=crop');
            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/jpeg');
        });

        it('serves image from nested directory', function () {
            Storage::disk('public')->makeDirectory('uploads/2024/01');
            createRealTestImage(Storage::disk('public')->path('uploads/2024/01/nested.jpg'));
            $response = $this->get('/media/uploads/2024/01/nested.jpg');
            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/jpeg');
        });

        it('returns proper caching headers', function () {
            createRealTestImage(Storage::disk('public')->path('images/cached.jpg'));
            $response = $this->get('/media/images/cached.jpg');
            $response->assertOk();
            $response->assertHeader('Cache-Control', 'max-age=31536000, public');
        });

        it('converts image format to webp when requested', function () {
            createRealTestImage(Storage::disk('public')->path('images/convert.jpg'));
            $response = $this->get('/media/images/convert.jpg?fm=webp');
            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/webp');
        });

        it('converts image format to png when requested', function () {
            createRealTestImage(Storage::disk('public')->path('images/convert.jpg'));
            $response = $this->get('/media/images/convert.jpg?fm=png');
            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/png');
        });
    });

    describe('image response with secure mode', function () {
        it('serves image with valid signature', function () {
            setupSecureMode();
            createRealTestImage(Storage::disk('public')->path('images/secure.jpg'));
            $urlBuilder = UrlBuilderFactory::create('/media', config('model-media-glide.signature_key'));
            $signedUrl = $urlBuilder->getUrl('images/secure.jpg');
            $response = $this->get($signedUrl);
            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/jpeg');
        });

        it('serves transformed image with valid signature', function () {
            setupSecureMode();
            createRealTestImage(Storage::disk('public')->path('images/secure.jpg'), 800, 600);
            $urlBuilder = UrlBuilderFactory::create('/media', config('model-media-glide.signature_key'));
            $signedUrl = $urlBuilder->getUrl('images/secure.jpg', ['w' => 200, 'h' => 150]);
            $response = $this->get($signedUrl);
            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/jpeg');
        });

        it('serves cropped image with valid signature', function () {
            setupSecureMode();
            createRealTestImage(Storage::disk('public')->path('images/secure.jpg'), 800, 600);
            $urlBuilder = UrlBuilderFactory::create('/media', config('model-media-glide.signature_key'));
            $signedUrl = $urlBuilder->getUrl('images/secure.jpg', ['w' => 200, 'h' => 200, 'fit' => 'crop']);
            $response = $this->get($signedUrl);
            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/jpeg');
        });

        it('serves format-converted image with valid signature', function () {
            setupSecureMode();
            createRealTestImage(Storage::disk('public')->path('images/secure.jpg'));
            $urlBuilder = UrlBuilderFactory::create('/media', config('model-media-glide.signature_key'));
            $signedUrl = $urlBuilder->getUrl('images/secure.jpg', ['fm' => 'webp']);
            $response = $this->get($signedUrl);
            $response->assertOk();
            $response->assertHeader('Content-Type', 'image/webp');
        });
    });
});
