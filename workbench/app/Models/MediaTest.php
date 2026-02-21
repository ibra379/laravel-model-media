<?php

namespace Workbench\App\Models;

use DialloIbrahima\HasMedia\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Workbench\Database\Factories\MediaTestFactory;

/**
 * @property-read int $id
 * @property-read string|null $avatar
 * @property-read string|null $cover_image
 * @property-read string $slug
 */
class MediaTest extends Model
{
    use HasFactory, HasMedia;

    protected static function newFactory(): MediaTestFactory
    {
        return MediaTestFactory::new();
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
            fileName: fn ($model) => $model->id.'-'.Str::random()
        );
    }
}
