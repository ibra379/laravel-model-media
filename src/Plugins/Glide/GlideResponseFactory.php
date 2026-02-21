<?php

declare(strict_types=1);

namespace DialloIbrahima\HasMedia\Plugins\Glide;

use DateTimeImmutable;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use League\Glide\Responses\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GlideResponseFactory implements ResponseFactoryInterface
{
    public function __construct(
        private readonly ?Request $request = null
    ) {}

    /**
     * Create a streamed response for the cached image.
     *
     * @throws \RuntimeException
     */
    public function create(FilesystemOperator $cache, string $path): Response
    {
        try {
            $lastModified = new DateTimeImmutable(sprintf('@%d', $cache->lastModified($path)));

            $response = new StreamedResponse;

            $response->headers->set('Content-Type', $cache->mimeType($path));
            $response->headers->set('Content-Length', (string) $cache->fileSize($path));
            $response->headers->set('Content-Disposition', 'inline');

            $response->setPublic();
            $response->setMaxAge(31_536_000);
            $response->setExpires(new DateTimeImmutable('+1 year'));
            $response->setLastModified($lastModified);
            $response->setEtag(md5($path.$lastModified->getTimestamp()));

            // Use the current request (not the one from boot time)
            $currentRequest = $this->request ?? request();

            // Return 304 Not Modified without reading the stream
            if ($currentRequest && $response->isNotModified($currentRequest)) {
                return $response;
            }

            // Lazy stream: opened only when Symfony actually sends the body (not on 304)
            $response->setCallback(function () use ($cache, $path): void {
                $stream = $cache->readStream($path);
                fpassthru($stream);
                fclose($stream);
            });

            return $response;
        } catch (FilesystemException $e) {
            throw new \RuntimeException(
                'Error while reading cached image: '.$e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}
