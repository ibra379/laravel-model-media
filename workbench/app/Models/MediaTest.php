<?php

namespace Workbench\App\Models;

use DialloIbrahima\HasMedia\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Workbench\Database\Factories\MediaTestFactory;

/**
 * @property-read int $id
 * @property-read string|null $name
 * @property-read string|null $name_with_id
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
            column: 'name',
            directory: 'documents',
            fileName: 'slug'
        );

        self::registerMediaForColumn(
            column: 'name_with_id',
            directory: 'documents',
            fileName: fn ($model) => $model->id.'-'.Str::random()
        );
    }
}
