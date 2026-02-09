<?php


// src/Plugins/Glide/config/glide.php
return [
    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Control whether Glide routes are automatically registered and customize
    | the route prefix and middleware.
    |
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
    'signature_key' => env('GLIDE_SIGNATURE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Glide Server Configuration
    |--------------------------------------------------------------------------
    */

    // Source directory where original images are stored
    'source' => env('GLIDE_SOURCE', storage_path('app/public')),

    // Cache directory for transformed images
    'cache' => env('GLIDE_CACHE', storage_path('app/glide-cache')),

    // Image processing driver: 'gd' or 'imagick'
    'driver' => env('GLIDE_DRIVER', 'gd'),

    // Maximum allowed image size (width * height)
    'max_image_size' => env('GLIDE_MAX_IMAGE_SIZE', 2000 * 2000),

    // Watermarks directory
    'watermarks' => env('GLIDE_WATERMARKS', storage_path('app/watermarks')),

    /*
    |--------------------------------------------------------------------------
    | Image Presets
    |--------------------------------------------------------------------------
    */
    'presets' => [
        'thumbnail' => [
            'w' => 200,
            'h' => 200,
            'fit' => 'crop',
            'fm' => 'webp',
            'q' => 90,
        ],
        'small' => [
            'w' => 400,
            'h' => 300,
            'fit' => 'contain',
            'fm' => 'webp',
            'q' => 85,
        ],
        'medium' => [
            'w' => 800,
            'h' => 600,
            'fit' => 'contain',
            'fm' => 'webp',
            'q' => 85,
        ],
        'large' => [
            'w' => 1600,
            'h' => 1200,
            'fit' => 'contain',
            'fm' => 'webp',
            'q' => 80,
        ],
        'og-image' => [
            'w' => 1200,
            'h' => 630,
            'fit' => 'crop',
            'fm' => 'jpg',
            'q' => 85,
        ],
    ],
];
