<?php

declare(strict_types=1);

namespace DialloIbrahima\HasMedia\Plugins\Glide;

use Exception;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Glide\Responses\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Custom Glide Response Factory
 *
 * Creates streamed responses for transformed images with proper caching headers.
 * This factory is used by Glide server to return image responses.
 */
final class GlideResponseFactory implements ResponseFactoryInterface
{
    /**
     * MIME types mapping for common image formats
     */
    private const MIME_TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'bmp' => 'image/bmp',
        'svg' => 'image/svg+xml',
    ];

    /**
     * Create a response with the given image.
     *
     * @param FilesystemOperator $cache The cache file system.
     * @param string $path The cached file path.
     * @return Response The response object.
     *
     * @throws Exception
     */
    public function create(FilesystemOperator $cache, string $path): Response
    {
        try {
            $contentType = $this->getContentType($cache, $path);
            $lastModified = $cache->lastModified($path);

            $response = new StreamedResponse();

            // Set appropriate headers
            $response->headers->set('Content-Type', $contentType);
            $response->headers->set('Content-Disposition', 'inline');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', $lastModified) . ' GMT');
            $response->headers->set('Cache-Control', 'public, max-age=31536000');

            // Set streaming callback
            $response->setCallback(function () use ($cache, $path) {
                $stream = $cache->readStream($path);
                fpassthru($stream);
                fclose($stream);
            });

            return $response;
        } catch (FilesystemException $e) {
            throw new Exception('Error while reading file: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Get the content type from the cached file.
     *
     * First tries to get the MIME type from the filesystem,
     * then falls back to extension-based detection.
     *
     * @param FilesystemOperator $cache The cache file system.
     * @param string $path The cached file path.
     * @return string The content type.
     */
    private function getContentType(FilesystemOperator $cache, string $path): string
    {
        // Try to get MIME type from filesystem
        try {
            $mimeType = $cache->mimeType($path);
            if ($mimeType && $mimeType !== 'application/octet-stream') {
                return $mimeType;
            }
        } catch (FilesystemException) {
            // Fall through to extension-based detection
        }

        // Fallback to extension-based detection
        if (preg_match('/\.(jpe?g|png|gif|webp|avif|bmp|svg)/i', $path, $matches)) {
            $extension = mb_strtolower($matches[1]);

            if (isset(self::MIME_TYPES[$extension])) {
                return self::MIME_TYPES[$extension];
            }
        }

        return 'application/octet-stream';
    }
}
