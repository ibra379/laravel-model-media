<?php

namespace DialloIbrahima\HasMedia;

use DialloIbrahima\HasMedia\Plugins\Glide\GlideHelper;
use DialloIbrahima\HasMedia\Plugins\Glide\GlideResponseFactory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FilesystemOperator;
use League\Glide\ServerFactory;
use League\Glide\Signatures\Signature;
use League\Glide\Urls\UrlBuilderFactory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelModelMediaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-model-media')
            ->hasConfigFile('laravel-model-media')
            ->hasConfigFile('model-media-glide');
    }

    public function packageRegistered(): void
    {
        if (! $this->isGlideAvailable()) {
            return;
        }

        $this->registerSignature();
        $this->registerGlideServer();
        $this->registerUrlBuilder();
        $this->registerGlideHelper();
    }

    public function packageBooted(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/laravel-model-media.php' => config_path('laravel-model-media.php'),
            ], 'laravel-model-media-config');

            if ($this->isGlideAvailable()) {
                $this->publishes([
                    __DIR__.'/../config/model-media-glide.php' => config_path('model-media-glide.php'),
                ], 'model-media-glide-config');
            }
        }

        if ($this->isGlideAvailable()) {
            $this->registerGlideRoutes();
        }
    }

    // -------------------------------------------------------------------------
    // Singleton registrations
    // -------------------------------------------------------------------------

    private function registerSignature(): void
    {
        $this->app->singleton(Signature::class, function () {
            return new Signature(config('model-media-glide.signature_key', ''));
        });
    }

    private function registerGlideServer(): void
    {
        $this->app->singleton('media.glide', function () {
            $config = config('model-media-glide', []);

            return ServerFactory::create([
                'response' => new GlideResponseFactory(request()),
                'source' => $this->resolveFilesystem($config['source_disk'] ?? 'public'),
                'source_path_prefix' => $config['source_path_prefix'] ?? '',
                'cache' => $this->resolveFilesystem($config['cache_disk'] ?? 'local'),
                'cache_path_prefix' => $config['cache_path'] ?? 'glide-cache',
                'watermarks' => $this->resolveFilesystem($config['watermarks_disk'] ?? 'local'),
                'watermarks_path_prefix' => $config['watermarks_path'] ?? 'watermarks',
                'driver' => $config['driver'] ?? 'imagick',
                'max_image_size' => $config['max_image_size'] ?? 4000 * 4000,
                'presets' => $config['presets'] ?? [],
            ]);
        });
    }

    private function registerUrlBuilder(): void
    {
        $this->app->singleton('media.glide.url', function () {
            $baseUrl = '/'.ltrim(config('model-media-glide.route_prefix', 'media'), '/');
            $signKey = config('model-media-glide.secure', false)
                ? config('model-media-glide.signature_key')
                : null;

            return UrlBuilderFactory::create($baseUrl, $signKey);
        });
    }

    private function registerGlideHelper(): void
    {
        $this->app->singleton('media.glide.helper', fn () => new GlideHelper);
    }

    // -------------------------------------------------------------------------
    // Routes
    // -------------------------------------------------------------------------

    private function registerGlideRoutes(): void
    {
        if (! config('model-media-glide.routes_enabled', true)) {
            return;
        }

        Route::middleware(config('model-media-glide.middleware', ['web']))
            ->prefix(config('model-media-glide.route_prefix', 'media'))
            ->as('media.')
            ->group(fn () => require __DIR__.'/Plugins/Glide/routes/glide.php');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve a Flysystem instance from a Laravel disk name.
     * Glide 2.x expects a League\Flysystem\FilesystemOperator instance.
     */
    private function resolveFilesystem(string $disk): FilesystemOperator
    {
        return Storage::disk($disk)->getDriver();
    }

    /**
     * Check if the Glide package is installed.
     */
    private function isGlideAvailable(): bool
    {
        return class_exists(ServerFactory::class);
    }
}
