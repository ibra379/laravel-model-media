<?php

namespace DialloIbrahima\HasMedia\Plugins\Glide\Controllers;

use DialloIbrahima\HasMedia\Plugins\Glide\GlideHelper;
use Exception;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use League\Glide\Signatures\Signature;
use League\Glide\Signatures\SignatureException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GlideController extends Controller
{
    /**
     * Serve transformed image
     *
     * Process:
     * 1. Validate signature (if security enabled)
     * 2. Verify file exists
     * 3. Validate file is an image
     * 4. Get Glide server from container
     * 5. Process image with requested transformations
     * 6. Return image response with proper headers
     *
     * @param  string  $path  Relative path to image (e.g., 'avatars/user123.jpg')
     * @return \Symfony\Component\HttpFoundation\Response Image response with appropriate Content-Type header
     *
     * @throws HttpException
     */
    public function show(string $path): \Symfony\Component\HttpFoundation\Response
    {
        // Validate signature if security is enabled
        if (config('model-media-glide.secure', false)) {
            $this->validateSignature($path);
        }

        $fullPath = $this->resolveSourcePath($path);

        // Verify file exists
        if (! file_exists($fullPath)) {
            abort(404, "Image file not found: $path");
        }

        // Validate file is an image
        $this->validateImageFile($path, $fullPath);

        // Get Glide server from container
        $server = app('media.glide');

        try {
            // Process and return transformed image
            // Glide automatically caches the result
            return $server->getImageResponse($path, request()->all());
        } catch (Exception $e) {
            // Log the internals, but never leak them to the client.
            Log::error('Glide image processing failed', [
                'path' => $path,
                'exception' => $e,
            ]);

            abort(500, 'Error processing image.');
        }
    }

    /**
     * Resolve the absolute source path for a relative image path.
     */
    protected function resolveSourcePath(string $path): string
    {
        $disk = config('model-media-glide.source_disk', 'public');
        $prefix = config('model-media-glide.source_path_prefix', '');

        $relativePath = $prefix ? $prefix.'/'.$path : $path;

        return Storage::disk($disk)->path($relativePath);
    }

    /**
     * Validate request signature
     *
     * Prevents unauthorized image manipulation by requiring valid signatures
     *
     * @param  string  $path  Image path
     *
     * @throws HttpException
     */
    protected function validateSignature(string $path): void
    {
        $signatureKey = config('model-media-glide.signature_key');

        // Check if signature key is configured
        if (empty($signatureKey)) {
            abort(500, 'Glide signature key not configured. Set GLIDE_SIGNATURE_KEY in your .env file.');
        }

        try {
            // UrlBuilder generates signatures using full path with route prefix
            // e.g., "/media/images/photo.jpg" instead of just "images/photo.jpg"
            // We must validate using the same path format
            $routePrefix = '/'.ltrim(config('model-media-glide.route_prefix', 'media'), '/');
            $fullPath = $routePrefix.'/'.ltrim($path, '/');

            app(Signature::class)->validateRequest($fullPath, request()->all());
        } catch (SignatureException) {
            abort(403, 'Invalid or missing signature. This URL requires a valid signature.');
        }
    }

    /**
     * Validate that the file is an image
     *
     * Checks MIME type to ensure Glide can process it
     * Prevents processing of non-image files (PDFs, videos, documents, etc.)
     *
     * @param  string  $path  Relative path to file
     * @param  string  $fullPath  Absolute path to file
     *
     * @throws HttpException
     */
    protected function validateImageFile(string $path, string $fullPath): void
    {
        // Get MIME type
        $mimeType = mime_content_type($fullPath);

        // Check if file is an allowed image type
        if (! in_array($mimeType, GlideHelper::ALLOWED_MIME_TYPES)) {
            abort(
                415,
                "Cannot process file '$path': ".
                "MIME type '$mimeType' is not supported. ".
                'Glide only works with images (jpeg, png, gif, webp, bmp, svg). '.
                'Allowed types: '.implode(', ', GlideHelper::ALLOWED_MIME_TYPES)
            );
        }

        // SVG files cannot be validated with getimagesize()
        if ($mimeType === 'image/svg+xml') {
            return;
        }

        // Additional validation: try to get image info
        try {
            $imageInfo = @getimagesize($fullPath);

            if ($imageInfo === false) {
                abort(
                    422,
                    "Cannot process file '$path': ".
                    'file appears to be corrupted or not a valid image format.'
                );
            }
        } catch (Exception $e) {
            abort(
                422,
                "Cannot validate image '$path': ".$e->getMessage()
            );
        }
    }
}
