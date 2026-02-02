<?php

namespace Workbench\App\Models;

use DialloIbrahima\HasMedia\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read int $id
 * @property-read string $name
 * @property-read string $slug
 */
class MediaTest extends Model
{
    use HasFactory, HasMedia;

    protected $guarded = [];

    protected $table = 'media_tests';

    protected static function booted(): void
    {
        self::registerMediaForColumn(
            column: 'name',
            directory: 'documents',
            fileName: 'id'
        );
    }
}
