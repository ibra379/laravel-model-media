<?php

namespace DialloIbrahima\HasMedia;

use Illuminate\Http\UploadedFile;

trait HasMedia
{
    /** @var MediaMapping[] */
    private static array $mediaMappings = [];

    protected static function registerMediaForColumn(
        string $column,
        string $directory,
        string $fileName,
        string $disk = 'public'
    ): void {
        $mapping = new MediaMapping(
            column: $column,
            directory: $directory,
            fileName: $fileName,
            disk: $disk
        );

        self::$mediaMappings[$column] = $mapping;
    }

    public function attachMedia(UploadedFile $file, string $column): bool
    {
        $mapping = self::$mediaMappings[$column] ?? null;
        assert($mapping instanceof MediaMapping, 'No media mapping found for column: '.$column);

        $fileName = $mapping->getFileName($this, $file);
        $stored = $file->storeAs(
            $mapping->getDirectory(),
            $fileName,
            $mapping->getDisk()
        );
        $this->setAttribute($column, $fileName);

        return boolval($stored);
    }
}
