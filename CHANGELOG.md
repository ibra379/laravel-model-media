# Changelog

All notable changes to `laravel-model-media` will be documented in this file.

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

