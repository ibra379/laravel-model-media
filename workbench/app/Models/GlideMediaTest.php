<?php

namespace Workbench\App\Models;

use DialloIbrahima\HasMedia\HasMedia;
use DialloIbrahima\HasMedia\Plugins\Glide\HasGlideUrls;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Workbench\Database\Factories\GlideMediaTestFactory;

/**
 * Model per testare HasGlideUrls trait
 * 
 * Usa le stesse colonne di MediaTest ma con HasGlideUrls trait aggiunto
 *
 * @property-read int $id
 * @property-read string|null $name
 * @property-read string|null $name_with_id
 * @property-read string $slug
 */
class GlideMediaTest extends Model
{
    use HasFactory, HasMedia, HasGlideUrls;

    protected static function newFactory(): GlideMediaTestFactory
    {
        return GlideMediaTestFactory::new();
    }

    protected $guarded = [];

    protected $table = 'media_tests';

    protected static function booted(): void
    {
        // Usa 'name' come colonna immagine (esistente nel DB)
        self::registerMediaForColumn(
            column: 'name',
            directory: 'images',
            fileName: 'slug'
        );

        // Usa 'name_with_id' per testare file non-immagine
        self::registerMediaForColumn(
            column: 'name_with_id',
            directory: 'documents',
            fileName: 'slug'
        );
    }
}
