<?php

namespace DialloIbrahima\HasMedia;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final readonly class MediaMapping
{
    public function __construct(
        private string $column,
        private string $directory,
        private string|Closure $fileName,
        private string $disk = 'public'
    ) {}

    public function getColumn(): string
    {
        return $this->column;
    }

    public function getDirectory(): string
    {
        return $this->directory;
    }

    public function getFileName(Model $model, UploadedFile $file): string
    {
        $fileName = is_string($this->fileName)
            ? $model->getAttribute($this->fileName)
            : ($this->fileName)($model, $file);

        return sprintf('%s.%s', $fileName, $file->getClientOriginalExtension());
    }

    public function getDisk(): string
    {
        return $this->disk;
    }
}
