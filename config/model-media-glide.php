<?php

// config/model-media-glide.php
return [
    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    */
    'routes_enabled' => env('GLIDE_ROUTES_ENABLED', true),
    'route_prefix' => env('GLIDE_ROUTE_PREFIX', 'media'),
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | When 'secure' is true, all image URLs must include a valid cryptographic
    | signature to prevent unauthorized image manipulation attacks.
    |
    | This prevents:
    | - DoS attacks from generating unlimited image variations
    | - Unauthorized bandwidth usage
    | - Cache pollution
    |
    | Generate a secure key:
    | php artisan tinker
    | >>> Str::random(32)
    |
    | IMPORTANT: Enable in production!
    |
    */
    'secure' => env('GLIDE_SECURE', false),
    'signature_key' => env('GLIDE_SIGNATURE_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Storage Disks
    |--------------------------------------------------------------------------
    |
    | Use Laravel disk names instead of local paths.
    | This allows full compatibility with S3, R2, and any other Flysystem driver.
    |
    | Set in your .env:
    | GLIDE_SOURCE_DISK=s3
    | GLIDE_CACHE_DISK=local
    |
    */
    'source_disk' => env('GLIDE_SOURCE_DISK', 'public'),
    'source_path_prefix' => env('GLIDE_SOURCE_PATH_PREFIX', ''),

    'cache_disk' => env('GLIDE_CACHE_DISK', 'local'),
    'cache_path' => env('GLIDE_CACHE_PATH', 'glide-cache'),

    'watermarks_disk' => env('GLIDE_WATERMARKS_DISK', 'local'),
    'watermarks_path' => env('GLIDE_WATERMARKS_PATH', 'watermarks'),

    /*
    |--------------------------------------------------------------------------
    | Glide Server Configuration
    |--------------------------------------------------------------------------
    |
    | driver: 'imagick' is strongly recommended over 'gd'.
    | imagick uses Lanczos resampling which produces sharper, cleaner results.
    | gd uses bilinear interpolation which causes blurry/degraded output.
    |
    | Verify imagick is available: php -m | grep imagick
    |
    | max_image_size: raised to 16MP to avoid silent pre-resize on
    | high-resolution photos (e.g. smartphone cameras at 12MP+).
    | A too-low limit causes Glide to downscale BEFORE applying your
    | transformations, resulting in quality loss.
    |
    */
    'driver' => env('GLIDE_DRIVER', 'imagick'),
    'max_image_size' => env('GLIDE_MAX_IMAGE_SIZE', 4000 * 4000),

    /*
    |--------------------------------------------------------------------------
    | Image Presets
    |--------------------------------------------------------------------------
    |
    | All presets include:
    | - 'fm' => 'webp'  : avoids double JPEG compression artifacts
    | - 'or' => 'auto'  : respects EXIF orientation from mobile photos
    |
    | Exception: og-image uses 'jpg' because Facebook and LinkedIn
    | do not support webp in social preview cards.
    |
    */
    'presets' => [
        'avatar' => [
            'w' => 110,
            'h' => 110,
            'fit' => 'crop',
            'fm' => 'webp',
            'q' => 90,
            'or' => 'auto',
        ],
        'thumbnail' => [
            'w' => 200,
            'h' => 200,
            'fit' => 'crop',
            'fm' => 'webp',
            'q' => 90,
            'or' => 'auto',
        ],
        'small' => [
            'w' => 400,
            'h' => 300,
            'fit' => 'contain',
            'fm' => 'webp',
            'q' => 85,
            'or' => 'auto',
        ],
        'medium' => [
            'w' => 800,
            'h' => 600,
            'fit' => 'contain',
            'fm' => 'webp',
            'q' => 85,
            'or' => 'auto',
        ],
        'large' => [
            'w' => 1600,
            'h' => 1200,
            'fit' => 'contain',
            'fm' => 'webp',
            'q' => 80,
            'or' => 'auto',
        ],
        'og-image' => [
            'w' => 1200,
            'h' => 630,
            'fit' => 'crop',
            'fm' => 'jpg',
            'q' => 85,
            'or' => 'auto',
        ],
    ],
];
