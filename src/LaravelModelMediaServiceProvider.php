<?php

namespace DialloIbrahima\HasMedia;

use DialloIbrahima\HasMedia\Plugins\Glide\GlideServiceProvider;
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

        // Register Glide plugin service provider
        $this->app->register(GlideServiceProvider::class);
    }
}
