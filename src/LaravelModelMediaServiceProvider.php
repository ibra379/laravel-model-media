<?php

namespace DialloIbrahima\HasMedia;

use DialloIbrahima\HasMedia\Plugins\Glide\GlidePlugin;
use League\Glide\ServerFactory;
use League\Glide\Signatures\Signature;
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
            ->hasConfigFile();
    }

    public function register(): void
    {
        parent::register();

        // Register Glide Plugin configuration and services directly here
        // to avoid issues with php artisan optimize (nested providers)
        $this->mergeConfigFrom(
            __DIR__ . '/Plugins/Glide/config/glide.php',
            'model-media-glide'
        );

        if (class_exists(ServerFactory::class)) {
            // Register Glide Signature singleton
            $this->app->singleton(Signature::class, function ($app) {
                $signatureKey = config('model-media-glide.signature_key');

                return new Signature($signatureKey ?? '');
            });

            // Register plugin instance as singleton
            $this->app->singleton(GlidePlugin::class);

            // Register plugin services (Glide server)
            $plugin = $this->app->make(GlidePlugin::class);
            $plugin->register();
        }
    }

    public function packageBooted(): void
    {
        // Bootstrap Glide plugin (load routes)
        if (class_exists(ServerFactory::class)) {
            $plugin = $this->app->make(GlidePlugin::class);
            $plugin->boot();

            // Publish Glide config
            if ($this->app->runningInConsole()) {
                $this->publishes([
                    __DIR__ . '/Plugins/Glide/config/glide.php' => config_path('model-media-glide.php'),
                ], 'model-media-glide-config');
            }
        }
    }
}
