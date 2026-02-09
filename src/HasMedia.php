<?php

namespace DialloIbrahima\HasMedia;

use Closure;
use DialloIbrahima\HasMedia\Observers\MediaObserver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HasMedia
{
    /** @var MediaMapping[] */
    private static array $mediaMappings = [];

    public static function bootHasMedia(): void
    {
        self::observe(MediaObserver::class);
    }

    protected static function registerMediaForColumn(
        string         $column,
        string         $directory,
        string|Closure $fileName,
        string         $disk = 'public'
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
        $mapping = self::getMediaMappingForColumn($column);

        $directory = $mapping->getDirectory();
        $oldFileName = $this->getAttribute($column);
        $newFileName = $mapping->getFileName($this, $file);

        $stored = $file->storeAs(
            $directory,
            $newFileName,
            $mapping->getDisk()
        );

        if ($stored) {
            // Delete an old file only if storage succeeded AND filenames differ
            if ($oldFileName && $oldFileName !== $newFileName) {
                Storage::disk($mapping->getDisk())->delete(sprintf('%s/%s', $directory, $oldFileName));
            }
            $this->setAttribute($column, $newFileName);
        }

        return boolval($stored);
    }

    public function detachMedia(?string $column): bool
    {
        if (! $column) {
            return true;
        }

        $mapping = self::getMediaMappingForColumn($column);

        $fileName = $this->getAttribute($column);
        if (! $fileName) {
            return true;
        }
        $directory = $mapping->getDirectory();
        Storage::disk($mapping->getDisk())->delete(sprintf('%s/%s', $directory, $fileName));
        $this->update([$column => null]);

        return true;
    }

    public function getMediaUrl(string $column): ?string
    {
        $mapping = self::getMediaMappingForColumn($column);

        $fileName = $this->getAttribute($column);
        if (! $fileName) {
            return null;
        }

        $directory = $mapping->getDirectory();

        return Storage::disk($mapping->getDisk())->url(sprintf('%s/%s', $directory, $fileName));
    }

    public function getMediaMappings(): array
    {
        return self::$mediaMappings;
    }

    private static function getMediaMappingForColumn(string $column): ?MediaMapping
    {
        $mapping = self::$mediaMappings[$column] ?? null;
        assert($mapping instanceof MediaMapping, 'No media mapping found for column: '.$column);
        return $mapping;
    }
}
