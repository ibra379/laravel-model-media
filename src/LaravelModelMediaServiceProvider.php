<?php

namespace DialloIbrahima\HasMedia;

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
}
