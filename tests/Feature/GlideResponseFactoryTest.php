<?php

use DialloIbrahima\HasMedia\Plugins\Glide\GlideResponseFactory;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

beforeEach(function () {
    Storage::fake('public');

    // Create a real test image in the fake disk
    $img = imagecreatetruecolor(10, 10);
    ob_start();
    imagejpeg($img);
    $content = ob_get_clean();
    imagedestroy($img);

    Storage::disk('public')->put('test-image.jpg', $content);
});

describe('GlideResponseFactory', function () {
    it('creates a streamed response with correct headers', function () {
        $cache = Storage::disk('public')->getDriver();
        $factory = new GlideResponseFactory;

        $response = $factory->create($cache, 'test-image.jpg');

        expect($response)->toBeInstanceOf(StreamedResponse::class)
            ->and($response->headers->get('Content-Type'))->toBe('image/jpeg')
            ->and($response->headers->get('Content-Disposition'))->toBe('inline')
            ->and($response->headers->get('Content-Length'))->not->toBeNull()
            ->and($response->getMaxAge())->toBe(31536000);
    });

    it('sets proper ETag and Last-Modified headers', function () {
        $cache = Storage::disk('public')->getDriver();
        $factory = new GlideResponseFactory;

        $response = $factory->create($cache, 'test-image.jpg');

        expect($response->getEtag())->not->toBeNull()
            ->and($response->getLastModified())->not->toBeNull();
    });

    it('returns 304 Not Modified when request matches ETag', function () {
        $cache = Storage::disk('public')->getDriver();

        // First request to get the ETag
        $factory = new GlideResponseFactory;
        $response = $factory->create($cache, 'test-image.jpg');
        $etag = $response->getEtag();

        // Second request with matching ETag
        $request = Request::create('/media/test-image.jpg', 'GET');
        $request->headers->set('If-None-Match', $etag);

        $factory = new GlideResponseFactory($request);
        $response = $factory->create($cache, 'test-image.jpg');

        expect($response->getStatusCode())->toBe(304);
    });

    it('sets public cache control', function () {
        $cache = Storage::disk('public')->getDriver();
        $factory = new GlideResponseFactory;

        $response = $factory->create($cache, 'test-image.jpg');

        expect($response->headers->getCacheControlDirective('public'))->toBeTrue();
    });
});
