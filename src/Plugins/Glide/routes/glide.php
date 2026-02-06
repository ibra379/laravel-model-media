<?php


use DialloIbrahima\HasMedia\Plugins\Glide\Controllers\GlideController;




/**
 * Glide Image Manipulation Routes
 *
 * Serves transformed images through Glide server
 * Only processes image files - returns 415 error for non-images
 *
 * URL Format:
 * /media/{directory}/{filename}?w=300&h=200&fit=crop&fm=webp&s=signature
 *
 * Examples:
 * /media/avatars/user123.jpg?w=200&h=200&fit=crop&fm=webp
 * /media/covers/post456.png?w=800&h=600&fit=contain&fm=webp&q=85
 *
 * Error Responses:
 * - 404: File not found
 * - 403: Invalid signature (when secure mode enabled)
 * - 415: Unsupported file type (not an image)
 * - 422: Corrupted or invalid image
 * - 500: Processing error
 *
 * The {path} parameter captures the entire path including directory and filename
 * This allows for nested directories like: covers/2024/01/post123.jpg
 */
Route::get('/{path}', [GlideController::class, 'show'])
    ->where('path', '.*') // Match any path including slashes
    ->name('glide');
