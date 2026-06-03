<?php

namespace Workbench\App\Models;

use DialloIbrahima\HasMedia\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Workbench\Database\Factories\SoftDeleteMediaTestFactory;

/**
 * @property-read int $id
 * @property-read string|null $avatar
 * @property-read string|null $cover_image
 * @property-read string $slug
 */
class SoftDeleteMediaTest extends Model
{
    use HasFactory, HasMedia, SoftDeletes;

    protected static function newFactory(): SoftDeleteMediaTestFactory
    {
        return SoftDeleteMediaTestFactory::new();
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
    }
}
