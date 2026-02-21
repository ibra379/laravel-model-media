<?php

namespace Workbench\App\Models;

use DialloIbrahima\HasMedia\HasMedia;
use DialloIbrahima\HasMedia\Plugins\Glide\HasGlideUrls;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\Database\Factories\GlideMediaTestFactory;

/**
 * @property-read int $id
 * @property-read string|null $avatar
 * @property-read string|null $cover_image
 * @property-read string $slug
 */
class GlideMediaTest extends Model
{
    use HasFactory, HasGlideUrls, HasMedia;

    protected static function newFactory(): GlideMediaTestFactory
    {
        return GlideMediaTestFactory::new();
    }

    protected $guarded = [];

    protected $table = 'media_tests';

    protected static function booted(): void
    {
        self::registerMediaForColumn(
            column: 'avatar',
            directory: 'avatars',
            fileName: 'slug'
        );

        self::registerMediaForColumn(
            column: 'cover_image',
            directory: 'covers',
            fileName: 'slug'
        );
    }
}
