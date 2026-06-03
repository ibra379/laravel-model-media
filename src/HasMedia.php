<?php

namespace DialloIbrahima\HasMedia;

use Closure;
use DialloIbrahima\HasMedia\Observers\MediaObserver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait HasMedia
{
    /** @var array<class-string, array<string, MediaMapping>> */
    private static array $mediaMappings = [];

    public static function bootHasMedia(): void
    {
        self::observe(MediaObserver::class);
    }

    protected static function registerMediaForColumn(
        string $column,
        string $directory,
        string|Closure $fileName,
        string $disk = 'public'
    ): void {
        $mapping = new MediaMapping(
            column: $column,
            directory: $directory,
            fileName: $fileName,
            disk: $disk
        );

        self::$mediaMappings[static::class][$column] = $mapping;
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
            // Persist the new filename first. Only after the DB row is safely
            // updated do we delete the old file, so a failing save() can never
            // leave the model pointing at a file we already removed.
            $this->setAttribute($column, $newFileName);
            $this->save();

            if ($oldFileName && $oldFileName !== $newFileName) {
                Storage::disk($mapping->getDisk())->delete(sprintf('%s/%s', $directory, $oldFileName));
            }
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
        $GLOBALS['detach_log'][] = 'detach '.$column.'/'.$fileName.' from '.collect(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 8))->map(fn ($f) => ($f['class'] ?? '').($f['type'] ?? '').($f['function'] ?? ''))->implode(' <- ');
        Storage::disk($mapping->getDisk())->delete(sprintf('%s/%s', $directory, $fileName));

        if ($this->exists) {
            $this->setAttribute($column, null);
            $this->save();
        }

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

    /**
     * Get all media mappings.
     *
     * @return array<string, MediaMapping>
     */
    public function getMediaMappings(): array
    {
        return self::$mediaMappings[static::class] ?? [];
    }

    private static function getMediaMappingForColumn(string $column): MediaMapping
    {
        $mapping = self::$mediaMappings[static::class][$column] ?? null;

        if (! $mapping instanceof MediaMapping) {
            throw new \RuntimeException('No media mapping found for column: '.$column);
        }

        return $mapping;
    }
}
