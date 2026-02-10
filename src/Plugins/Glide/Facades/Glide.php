<?php

namespace DialloIbrahima\HasMedia\Plugins\Glide\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string|null url(string $path, array $params = [])
 * @method static string|null preset(string $path, string $preset)
 * @method static string|null srcset(string $path, array $widths = [400, 800, 1200, 1600])
 * @method static bool isImage(string $absolutePath)
 * @method static bool isValid(string $absolutePath)
 * @method static void deleteCache(string $path)
 *
 * @see \DialloIbrahima\HasMedia\Plugins\Glide\GlideHelper
 */
class Glide extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'media.glide.helper';
    }
}
