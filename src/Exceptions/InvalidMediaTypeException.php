<?php

namespace DialloIbrahima\HasMedia\Exceptions;

use Exception;

/**
 * InvalidMediaTypeException
 *
 * Lanciata quando si tenta di usare Glide su file non-immagine
 */
class InvalidMediaTypeException extends Exception
{
    /**
     * Create exception for non-image file
     *
     * @param string $column Column name
     * @param string $mimeType Actual file MIME type
     * @return self
     */
    public static function notAnImage(string $column, string $mimeType): self
    {
        return new self(
            "Cannot generate Glide URL for column '{$column}': file type '{$mimeType}' is not an image. " .
            "Glide only works with images (image/jpeg, image/png, image/gif, image/webp, etc.)"
        );
    }

    /**
     * Create exception for missing file
     *
     * @param string $column Column name
     * @return self
     */
    public static function fileNotFound(string $column): self
    {
        return new self(
            "Cannot generate Glide URL for column '{$column}': file not found. " .
            "Make sure the file exists before generating transformation URLs."
        );
    }

    /**
     * Create exception for corrupted image
     *
     * @param string $column Column name
     * @param string $path File path
     * @return self
     */
    public static function corruptedImage(string $column, string $path): self
    {
        return new self(
            "Cannot process image for column '{$column}' at path '{$path}': " .
            "file appears to be corrupted or not a valid image format."
        );
    }
}
