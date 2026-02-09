<?php

namespace DialloIbrahima\HasMedia\Plugins\Glide\Controllers;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use League\Glide\Signatures\Signature;
use League\Glide\Signatures\SignatureException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class GlideController extends Controller
{
    /**
     * Allowed image MIME types for Glide processing
     *
     * @var array
     */
    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/bmp',
        'image/svg+xml',
    ];

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
     * @param string $path Relative path to image (e.g., 'avatars/user123.jpg')
     * @return \Symfony\Component\HttpFoundation\Response Image response with appropriate Content-Type header
     * @throws HttpException
     */
    public function show(string $path): \Symfony\Component\HttpFoundation\Response
    {
        // Validate signature if security is enabled
        if (config('model-media-glide.secure', false)) {
            $this->validateSignature($path);
        }

        // Verify file exists
        $this->verifyFileExists($path);

        // Validate file is an image
        $this->validateImageFile($path);

        // Get Glide server from container
        $server = app('media.glide');

        try {
            // Process and return transformed image
            // Glide automatically caches the result
            return $server->getImageResponse($path, request()->all());
        } catch (Exception $e) {
            // Handle Glide processing errors
            abort(500, 'Error processing image: ' . $e->getMessage());
        }
    }

    /**
     * Validate request signature
     *
     * Prevents unauthorized image manipulation by requiring valid signatures
     *
     * Security benefits:
     * - Prevents DoS attacks from generating unlimited image variations
     * - Prevents unauthorized bandwidth usage
     * - Ensures only app-generated URLs work
     *
     * @param string $path Image path
     * @return void
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
            $routePrefix = '/' . ltrim(config('model-media-glide.route_prefix', 'media'), '/');
            $fullPath = $routePrefix . '/' . ltrim($path, '/');

            app(Signature::class)->validateRequest($fullPath, request()->all());
        } catch (SignatureException) {
            abort(403, 'Invalid or missing signature. This URL requires a valid signature.');
        }
    }

    /**
     * Verify that the requested file exists
     *
     * @param string $path Relative path to file
     * @return void
     * @throws HttpException
     */
    protected function verifyFileExists(string $path): void
    {
        $sourcePath = config('model-media-glide.source', storage_path('app/public'));
        $fullPath = $sourcePath . '/' . $path;

        if (!file_exists($fullPath)) {
            abort(404, "Image file not found: $path");
        }
    }

    /**
     * Validate that the file is an image
     *
     * Checks MIME type to ensure Glide can process it
     * Prevents processing of non-image files (PDFs, videos, documents, etc.)
     *
     * @param string $path Relative path to file
     * @return void
     * @throws HttpException
     */
    protected function validateImageFile(string $path): void
    {
        $sourcePath = config('model-media-glide.source', storage_path('app/public'));
        $fullPath = $sourcePath . '/' . $path;

        // Get MIME type
        $mimeType = mime_content_type($fullPath);

        // Check if file is an allowed image type
        if (!in_array($mimeType, $this->allowedMimeTypes)) {
            abort(
                415,
                "Cannot process file '$path': " .
                "MIME type '$mimeType' is not supported. " .
                "Glide only works with images (jpeg, png, gif, webp, bmp). " .
                "Allowed types: " . implode(', ', $this->allowedMimeTypes)
            );
        }

        // Additional validation: try to get image info
        try {
            $imageInfo = @getimagesize($fullPath);

            if ($imageInfo === false) {
                abort(
                    422,
                    "Cannot process file '$path': " .
                    "file appears to be corrupted or not a valid image format."
                );
            }
        } catch (Exception $e) {
            abort(
                422,
                "Cannot validate image '$path': " . $e->getMessage()
            );
        }
    }

}
