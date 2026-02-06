<?php

namespace DialloIbrahima\HasMedia\Plugins\Glide;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use League\Glide\ServerFactory;
use League\Glide\Signatures\Signature;


/**
 * Glide Service Provider
 *
 * Registers Glide plugin and its dependencies
 *
 * The Signature singleton is registered here for:
 * - Centralized configuration
 * - Easy dependency injection
 * - Consistent URL signing across the app
 */
class GlideServiceProvider extends ServiceProvider
{
    /**
     * Register plugin services
     *
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        // Check if Glide is installed
        if (!class_exists(ServerFactory::class)) {
            return;
        }

        $this->mergeConfigFrom(
            __DIR__ . '/config/glide.php',
            'model-media-glide'
        );

        // Register Glide Signature singleton
        // Signature is used to sign URLs and prevent unauthorized image manipulation
        $this->app->singleton(Signature::class, function (Application $app) {
            $signatureKey = config('model-media-glide.signature_key');

            return new Signature($signatureKey ?? '');
        });

        // Register plugin instance as singleton
        $this->app->singleton(GlidePlugin::class);

        // Register plugin services (Glide server)
        $plugin = $this->app->make(GlidePlugin::class);
        $plugin->register();
    }

    /**
     * Bootstrap plugin services
     *
     * @return void
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        // Check if Glide is installed
        if (!class_exists(ServerFactory::class)) {
            return;
        }

        // Publish config file when running in console
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/glide.php' => config_path('model-media-glide.php'),
            ], 'model-media-glide-config');
        }

        // Bootstrap plugin (load routes)
        $plugin = $this->app->make(GlidePlugin::class);
        $plugin->boot();
    }
}
