<?php

namespace DialloIbrahima\HasMedia\Contracts;

/**
 * MediaPlugin Interface
 *
 * Contract for media manipulation plugins
 */
interface MediaPlugin
{
    /**
     * Register plugin services in the container
     *
     * @return void
     */
    public function register(): void;

    /**
     * Bootstrap plugin services (routes, views, etc.)
     *
     * @return void
     */
    public function boot(): void;

    /**
     * Check if plugin dependencies are installed
     *
     * @return bool
     */
    public function isAvailable(): bool;
}
