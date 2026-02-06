<?php

declare(strict_types=1);

namespace DialloIbrahima\HasMedia\Plugins\Glide;

use DialloIbrahima\HasMedia\Contracts\MediaPlugin;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use League\Glide\ServerFactory;
use League\Glide\Urls\UrlBuilderFactory;

/**
 * Glide Plugin
 *
 * Provides image manipulation capabilities using League\Glide
 * Only works with image files (validates MIME type)
 */
class GlidePlugin implements MediaPlugin
{
    /**
     * Check if Glide package is installed
     *
     * @return bool
     */
    public function isAvailable(): bool
    {
        return class_exists(ServerFactory::class);
    }

    /**
     * Register Glide server instance in the container
     *
     * Glide server processes images on-the-fly based on URL parameters
     *
     * @return void
     */
    public function register(): void
    {
        if (!$this->isAvailable()) {
            return;
        }

        // Register Glide server as singleton
        App::singleton('media.glide', function ($app) {
            $config = config('model-media-glide', []);

            return ServerFactory::create([
                'response' => new GlideResponseFactory(), // Custom response factory for Laravel
                'source' => $config['source'] ?? storage_path('app/public'),
                'cache' => $config['cache'] ?? storage_path('app/glide-cache'),
                'max_image_size' => $config['max_image_size'] ?? 2000 * 2000,
                'presets' => $config['presets'] ?? [],
                'driver' => $config['driver'] ?? 'gd',
                'watermarks' => $config['watermarks'] ?? storage_path('app/watermarks'),
            ]);
        });

        // Register UrlBuilder as singleton for URL generation
        App::singleton('media.glide.url', function ($app) {
            $baseUrl = '/' . ltrim(config('model-media-glide.route_prefix', 'media'), '/');
            $signKey = config('model-media-glide.secure', false)
                ? config('model-media-glide.signature_key')
                : null;

            return UrlBuilderFactory::create($baseUrl, $signKey);
        });
    }

    /**
     * Bootstrap plugin (load routes if enabled)
     *
     * @return void
     */
    public function boot(): void
    {
        if (!$this->isAvailable()) {
            return;
        }

        // Load routes if enabled in config
        if (config('model-media-glide.routes_enabled', true)) {
            $this->loadRoutes();
        }
    }

    /**
     * Load plugin routes
     *
     * Routes handle image transformation requests
     * Example: GET /media/avatars/user123.jpg?w=200&h=200&fit=crop
     *
     * @return void
     */
    protected function loadRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            require __DIR__ . '/routes/glide.php';
        });
    }

    /**
     * Get route configuration
     *
     * @return array
     */
    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('model-media-glide.route_prefix', 'media'),
            'middleware' => config('model-media-glide.middleware', ['web']),
            'as' => 'media.',
        ];
    }
}
