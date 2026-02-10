<?php

namespace DialloIbrahima\HasMedia\Tests\Feature;

use DialloIbrahima\HasMedia\Plugins\Glide\Facades\Glide;
use DialloIbrahima\HasMedia\Plugins\Glide\GlideHelper;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    // Create a test image
    $img = imagecreatetruecolor(10, 10);
    ob_start();
    imagejpeg($img);
    $content = ob_get_clean();
    imagedestroy($img);

    Storage::disk('public')->put('avatars/user1.jpg', $content);

    // Set config
    Config::set('model-media-glide.source', Storage::disk('public')->path(''));
    Config::set('model-media-glide.route_prefix', 'media');
    Config::set('model-media-glide.secure', false);
});

describe('GlideHelper', function () {
    it('can generate a glide url', function () {
        $path = 'avatars/user1.jpg';
        $url = Glide::url($path, ['w' => 100]);

        expect($url)->toContain('/media/avatars/user1.jpg')
            ->toContain('w=100');
    });

    it('returns null if file is not an image', function () {
        Storage::disk('public')->put('not-an-image.txt', 'hello world');

        $url = Glide::url('not-an-image.txt', ['w' => 100]);

        expect($url)->toBeNull();
    });

    it('can generate a glide preset url', function () {
        Config::set('model-media-glide.presets.thumb', ['w' => 50, 'h' => 50]);

        $path = 'avatars/user1.jpg';
        $url = Glide::preset($path, 'thumb');

        expect($url)->toContain('w=50')
            ->toContain('h=50');
    });

    it('can generate a glide srcset', function () {
        $path = 'avatars/user1.jpg';
        $srcset = Glide::srcset($path, [100, 200]);

        expect($srcset)->toContain('100w')
            ->toContain('200w')
            ->toContain('fm=webp');
    });

    it('validates image files correctly', function () {
        $helper = new GlideHelper;

        // Create a dummy image
        $tempDir = sys_get_temp_dir();
        $imagePath = $tempDir.'/test.jpg';
        $notImagePath = $tempDir.'/test.txt';

        $img = imagecreatetruecolor(10, 10);
        imagejpeg($img, $imagePath);
        imagedestroy($img);

        file_put_contents($notImagePath, 'not an image');

        expect($helper->isImage($imagePath))->toBeTrue();
        expect($helper->isImage($notImagePath))->toBeFalse();
        expect($helper->isValid($imagePath))->toBeTrue();
        expect($helper->isValid($notImagePath))->toBeFalse();

        unlink($imagePath);
        unlink($notImagePath);
    });

    it('returns null for empty paths', function () {
        expect(Glide::url(''))->toBeNull();
    });

    it('handles missing presets by returning basic url', function () {
        $path = 'avatars/user1.jpg';
        $url = Glide::preset($path, 'non-existent');

        expect($url)->toContain('/media/avatars/user1.jpg');
    });

    it('can delete cache for a file', function () {
        $path = 'avatars/user1.jpg';
        $cacheDir = sys_get_temp_dir().'/glide-cache';
        Config::set('model-media-glide.cache', $cacheDir);

        // Create dummy cache files
        $cachePath = $cacheDir.'/avatars';
        if (! is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }

        file_put_contents($cachePath.'/user1.jpg.hash1', 'cache1');
        file_put_contents($cachePath.'/user1.jpg.hash2', 'cache2');

        expect(is_file($cachePath.'/user1.jpg.hash1'))->toBeTrue();
        expect(is_file($cachePath.'/user1.jpg.hash2'))->toBeTrue();

        Glide::deleteCache($path);

        expect(is_file($cachePath.'/user1.jpg.hash1'))->toBeFalse();
        expect(is_file($cachePath.'/user1.jpg.hash2'))->toBeFalse();

        // Cleanup
        rmdir($cachePath);
        rmdir($cacheDir);
    });
});
