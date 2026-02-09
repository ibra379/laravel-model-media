<?php

namespace DialloIbrahima\HasMedia;

use League\Glide\ServerFactory;
use League\Glide\Signatures\Signature;
use League\Glide\Urls\UrlBuilderFactory;
use DialloIbrahima\HasMedia\Plugins\Glide\GlideResponseFactory;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelModelMediaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/ibra379/laravel-model-media
         */
        $package
            ->name('laravel-model-media')
            ->hasConfigFile('laravel-model-media')
            ->hasConfigFile('model-media-glide');
    }

    public function packageRegistered(): void
    {
        if (class_exists(ServerFactory::class)) {
            // Register Glide Signature singleton
            $this->app->singleton(Signature::class, function ($app) {
                $config = config('model-media-glide');
                return new Signature($config['signature_key'] ?? '');
            });

            // Register Glide server instance as singleton ('media.glide')
            $this->app->singleton('media.glide', function ($app) {
                $config = config('model-media-glide', []);

                return ServerFactory::create([
                    'response' => new GlideResponseFactory(),
                    'source' => $config['source'] ?? storage_path('app/public'),
                    'cache' => $config['cache'] ?? storage_path('app/glide-cache'),
                    'max_image_size' => $config['max_image_size'] ?? 2000 * 2000,
                    'presets' => $config['presets'] ?? [],
                    'driver' => $config['driver'] ?? 'gd',
                    'watermarks' => $config['watermarks'] ?? storage_path('app/watermarks'),
                ]);
            });

            // Register UrlBuilder as singleton ('media.glide.url')
            $this->app->singleton('media.glide.url', function ($app) {
                $baseUrl = '/' . ltrim(config('model-media-glide.route_prefix', 'media'), '/');
                $signKey = config('model-media-glide.secure', false)
                    ? config('model-media-glide.signature_key')
                    : null;

                return UrlBuilderFactory::create($baseUrl, $signKey);
            });
        }
    }

    public function packageBooted(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laravel-model-media.php' => config_path('laravel-model-media.php'),
            ], 'laravel-model-media-config');

            if (class_exists(ServerFactory::class)) {
                $this->publishes([
                    __DIR__ . '/../config/model-media-glide.php' => config_path('model-media-glide.php'),
                ], 'model-media-glide-config');
            }
        }

        if (class_exists(ServerFactory::class)) {
            // Load Glide routes
            $this->registerGlideRoutes();
        }
    }

    protected function registerGlideRoutes(): void
    {
        if (!config('model-media-glide.routes_enabled', true)) {
            return;
        }

        Route::middleware(config('model-media-glide.middleware', ['web']))
            ->prefix(config('model-media-glide.route_prefix', 'media'))
            ->as('media.')
            ->group(function () {
                require __DIR__ . '/Plugins/Glide/routes/glide.php';
            });
    }
}
