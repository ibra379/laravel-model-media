# Changelog

All notable changes to `laravel-model-media` will be documented in this file.

## v3.0.0 - 2026-02-21

### Bug Fixes
- **Fixed**: Removed `league/glide` from `suggest` block — already a hard dependency in `require`.
- **Fixed**: Static `$mediaMappings` now keyed by `static::class` to prevent cross-model collisions.
- **Fixed**: Replaced `assert()` with `RuntimeException` in `getMediaMappingForColumn()` — `assert()` is disabled in production.
- **Fixed**: `detachMedia()` no longer calls `setAttribute()`/`save()` on deleted models (only updates when `$this->exists`).
- **Fixed**: Removed invalid `image/jpg` MIME type from `ALLOWED_MIME_TYPES` (correct type is `image/jpeg`).
- **Fixed**: SVG files no longer rejected by `getimagesize()` validation — bypassed in `GlideHelper` and `GlideController`.
- **Fixed**: `GlideResponseFactory` now receives the Symfony `Request` for proper `304 Not Modified` support.
- **Fixed**: `GlideController` resolves source paths via `Storage::disk()` instead of hardcoded paths, enabling S3/R2 compatibility.
- **Fixed**: `GlideHelper::preset()` returns `null` for unknown presets instead of generating a URL without parameters.

### Improvements
- **Improved**: Default image driver changed from `gd` to `imagick` (sharper Lanczos resampling).
- **Improved**: Default `max_image_size` raised from `2000*2000` to `4000*4000` (16MP) for high-resolution photos.
- **Improved**: All presets now include `'fm' => 'webp'` and `'or' => 'auto'` for better compression and EXIF orientation.
- **Improved**: `GlideController` uses `GlideHelper::ALLOWED_MIME_TYPES` constant instead of duplicated array.
- **Improved**: Facade alias added to `composer.json` for IDE auto-discovery.

### New
- **Added**: `GlideResponseFactory` with streaming, 1-year cache headers, ETag, and `304 Not Modified`.
- **Added**: `InvalidMediaTypeException` with named constructors: `notAnImage()`, `fileNotFound()`, `corruptedImage()`.
- **Added**: 14 new tests (77 total).
- **Added**: Full README rewrite reflecting the current architecture.

### Breaking Changes
- **Changed**: Config keys renamed from `source`/`cache` to `source_disk`, `cache_disk`, `cache_path` — republish with `php artisan vendor:publish --tag=model-media-glide-config`.
- **Changed**: `GlideHelper::preset()` returns `null` for unknown presets (previously returned URL without parameters).

## v2.1.0 - 2026-02-11

- **Added**: Standalone `Glide` facade and `GlideHelper` service for image manipulation anywhere in the application.
- **Added**: Centralized `Glide::deleteCache($path)` method for manual and automatic cache cleanup.
- **Improved**: `Glide::url()` now automatically validates file existence and image MIME types.
- **Refactored**: Moved all Glide processing logic from models and observers into `GlideHelper` for better maintainability.
- **Improved**: Codebase optimization using PHP 8.3 features (typed array constants for MIME types).
- **Updated**: Comprehensive README update for standalone facade usage and cache management.

## v2.0.6 - 2026-02-10

- **Improved**: Complete documentation refactor with premium look and clearer configuration instructions.
- **Added**: Explicit configuration publishing commands and tags documentation.

## v2.0.5 - 2026-02-10

- **Fixed**: Glide signature loss after `php artisan optimize`.
- **Refactored**: Centralized Service Provider registration for better performance and caching stability.
- **Improved**: Restored default Glide presets in configuration.

## v2.0.4 - 2026-02-09

- **Fixed**: Initial fix for Glide signature validation by using `request()->query()`.

## v2.0.3 - 2026-02-09

- **Improved**: `attachMedia` now automatically calls `$this->save()`.
- **Updated**: README documentation for auto-saving behavior.

## v2.0.0 - 2026-02-05

- **Feature**: Added powerful Glide integration for on-the-fly image manipulation.
- **Feature**: Added responsive images support via `getGlideSrcset()`.
- **Feature**: Added `GlideCacheObserver` for automatic image cache cleanup.

## 1.0.0 - 2026-02-03

- Initial release
- Lightweight media management for Eloquent models
- Zero-table architecture (uses existing model columns)
- Automatic file cleanup on model update/delete
- Support for Closure-based filename generators

