# 🖼️ Laravel Model Media

<p align="center">
<a href="https://packagist.org/packages/dialloibrahima/laravel-model-media"><img src="https://img.shields.io/packagist/v/dialloibrahima/laravel-model-media.svg?style=flat-square" alt="Latest Version on Packagist"></a>
<a href="https://github.com/ibra379/laravel-model-media/actions?query=workflow%3Arun-tests+branch%3Amain"><img src="https://img.shields.io/github/actions/workflow/status/ibra379/laravel-model-media/run-tests.yml?branch=main&label=tests&style=flat-square" alt="GitHub Tests Action Status"></a>
<a href="https://packagist.org/packages/dialloibrahima/laravel-model-media"><img src="https://img.shields.io/packagist/dt/dialloibrahima/laravel-model-media.svg?style=flat-square" alt="Total Downloads"></a>
<a href="https://www.php.net/"><img src="https://img.shields.io/badge/php-%5E8.4-8892BF.svg?style=flat-square" alt="PHP Version"></a>
<a href="https://laravel.com/"><img src="https://img.shields.io/badge/laravel-11.x%20%7C%2012.x-FF2D20.svg?style=flat-square" alt="Laravel Version"></a>
</p>

A lightweight, zero-boilerplate media management trait for Laravel Eloquent models. Attach files directly to your existing model attributes without adding any extra database tables or complex relationships.

**Includes a powerful Glide plugin** for on-the-fly image manipulation: resize, crop, convert formats, and generate responsive images.

---

## 📋 Table of Contents

- [The Problem](#-the-problem)
- [The Solution](#-the-solution)
- [Comparison: Spatie vs. Model Media](#️-comparison-spatie-medialibrary-vs-laravel-model-media)
- [Installation](#-installation)
- [Quick Start](#-quick-start)
- [API Reference](#-api-reference)
  - [HasMedia Trait](#hasmedia-trait)
  - [HasGlideUrls Trait](#hasglideurls-trait)
- [Advanced Usage](#-advanced-usage)
  - [Dynamic Filenames](#dynamic-filenames-via-closure)
  - [Multiple Disks](#multiple-storage-disks)
  - [Automatic Cleanup](#automatic-cleanup)
- [Image Manipulation with Glide](#-image-manipulation-with-glide)
  - [Installation](#glide-installation)
  - [Basic Usage](#glide-basic-usage)
  - [Transformation Parameters](#transformation-parameters)
  - [Presets](#using-presets)
  - [Responsive Images](#responsive-images-srcset)
  - [Configuration](#glide-configuration)
  - [Security](#security)
- [How It Works](#️-how-it-works)
- [Testing](#-testing)
- [Contributing](#-contributing)

---

## 😰 The Problem

Most media management packages for Laravel (like Spatie MediaLibrary) are powerful but "heavy". They often require:
- A new `media` table in your database.
- Complex Polymorphic relationships.
- Manual cleanup of files when models are deleted.
- Overkill for simple use cases where you just want to store a profile picture or a document path directly on a model.

## ✨ The Solution

**Laravel Model Media** keeps it simple. It uses your **existing database columns** to store file names.
- ✅ **No Extra Tables**: Uses the columns you already have.
- ✅ **Automatic Cleanup**: Deletes old files when you re-upload or delete the model.
- ✅ **Smart Filenames**: Use model attributes or dynamic Closures for naming.
- ✅ **Zero Config**: Just add the trait and register your media.
- ✅ **Image Manipulation**: Built-in Glide plugin for resizing, cropping, and format conversion.
- ✅ **Responsive Images**: Generate srcsets for `<picture>` elements automatically.

---

## ⚖️ Comparison: Spatie MediaLibrary vs. Laravel Model Media

| Feature | Spatie MediaLibrary | Laravel Model Media |
| :--- | :---: | :---: |
| **Philosophy** | "One table for everything" | "Keep it on the model" |
| **New Tables** | `media` (Polymorphic) | **None** |
| **Complexity** | High (Conversions, Collections) | **Low** (Simple & Fast) |
| **Performance** | Extra Join/Query for each model | **Zero extra queries** |
| **Setup** | Migrations + Trait + Interface | **Trait only** |
| **Image Manipulation** | Built-in conversions | **Glide plugin** (optional) |
| **Ideal for** | Complex CMS, Multiple galleries | Profile pics, Single documents, Simple uploads |

---

## 📦 Installation

```bash
composer require dialloibrahima/laravel-model-media
```

For image manipulation, install the optional Glide plugin:

```bash
composer require league/glide
```

---

## ⚡ Quick Start

### 1. Prepare your Model

Add the `HasMedia` trait and register which column should handle media.

```php
namespace App\Models;

use DialloIbrahima\HasMedia\HasMedia;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasMedia;

    protected static function booted()
    {
        self::registerMediaForColumn(
            column: 'profile_photo',    // Your DB column
            directory: 'avatars',       // Storage folder
            fileName: 'id',             // Name file after a model attribute
            disk: 'public'              // Optional: storage disk (default is 'public')
        );
    }
}
```

### 2. Handle Uploads

Call `attachMedia()` in your controller.

```php
public function update(Request $request, User $user)
{
    if ($request->hasFile('photo')) {
        $user->attachMedia($request->file('photo'), 'profile_photo');
        $user->save();
    }

    return back();
}
```

### 3. Retrieve URLs

```blade
<img src="{{ $user->getMediaUrl('profile_photo') }}">
```

### 4. Delete Media

```php
// Remove media and clear the column
$user->detachMedia('profile_photo');

// Media is also automatically deleted when the model is deleted
$user->delete(); // Files are cleaned up automatically
```

---

## 📚 API Reference

### HasMedia Trait

The core trait that provides media management capabilities.

#### `registerMediaForColumn()`

Register a media mapping for a model column.

```php
protected static function registerMediaForColumn(
    string $column,              // Database column name
    string $directory,           // Storage directory
    string|Closure $fileName,    // Attribute name or Closure for filename
    string $disk = 'public'      // Storage disk
): void
```

#### `attachMedia()`

Attach an uploaded file to a column.

```php
public function attachMedia(UploadedFile $file, string $column): bool
```

**Returns:** `true` if storage was successful, `false` otherwise.

**Behavior:**
- Stores the file to the configured disk and directory
- Automatically deletes any previously attached file
- Updates the model attribute with the new filename

#### `detachMedia()`

Remove media from a column and delete the file.

```php
public function detachMedia(?string $column): bool
```

**Returns:** `true` when complete.

#### `getMediaUrl()`

Get the public URL for a media file.

```php
public function getMediaUrl(string $column): ?string
```

**Returns:** The full URL to the file, or `null` if no file is attached.

#### `getMediaMappings()`

Get all registered media mappings for the model.

```php
public function getMediaMappings(): array
```

---

### HasGlideUrls Trait

Adds Glide image manipulation capabilities. Requires `league/glide` package.

> ⚠️ **Important:** This trait only works with image files. It validates MIME types and throws `InvalidMediaTypeException` for non-image files.

#### `getGlideUrl()`

Generate a URL for a transformed image.

```php
public function getGlideUrl(
    string $column,              // Column name
    array $params = [],          // Glide transformation parameters
    bool $throwOnError = false   // Throw exception on error
): ?string
```

**Returns:** URL to the transformed image, or `null` if unavailable.

```php
// Basic resize
$user->getGlideUrl('avatar', ['w' => 200, 'h' => 200]);

// Crop to square
$user->getGlideUrl('avatar', ['w' => 300, 'h' => 300, 'fit' => 'crop']);

// Convert to WebP
$user->getGlideUrl('avatar', ['w' => 400, 'fm' => 'webp', 'q' => 85]);

// With error handling
try {
    $url = $user->getGlideUrl('document', ['w' => 200], throwOnError: true);
} catch (InvalidMediaTypeException $e) {
    // Handle non-image file
}
```

#### `getGlidePresetUrl()`

Generate a URL using a predefined preset.

```php
public function getGlidePresetUrl(
    string $column,              // Column name
    string $preset,              // Preset name from config
    bool $throwOnError = false   // Throw exception on error
): ?string
```

```php
// Use thumbnail preset (defined in config)
$user->getGlidePresetUrl('avatar', 'thumbnail');

// Use medium preset
$user->getGlidePresetUrl('cover', 'medium');
```

#### `getGlideSrcset()`

Generate a srcset attribute for responsive images.

```php
public function getGlideSrcset(
    string $column,                               // Column name
    array $widths = [400, 800, 1200, 1600],       // Image widths
    bool $throwOnError = false                    // Throw exception on error
): ?string
```

```php
// Default widths
$srcset = $user->getGlideSrcset('cover');
// Result: "http://...?w=400&fm=webp 400w, http://...?w=800&fm=webp 800w, ..."

// Custom widths for mobile-first
$srcset = $user->getGlideSrcset('hero', [375, 768, 1024, 1920]);
```

**Usage in Blade:**

```blade
<img 
    src="{{ $post->getGlideUrl('cover', ['w' => 800]) }}"
    srcset="{{ $post->getGlideSrcset('cover') }}"
    sizes="(max-width: 768px) 100vw, 800px"
    alt="{{ $post->title }}"
>
```

#### `hasImageMedia()`

Check if a column contains a valid image file.

```php
public function hasImageMedia(string $column): bool
```

```blade
@if($user->hasImageMedia('avatar'))
    <img src="{{ $user->getGlideUrl('avatar', ['w' => 200]) }}">
@else
    <img src="{{ asset('images/default-avatar.jpg') }}">
@endif
```

---

## 🔧 Advanced Usage

### Dynamic Filenames via Closure

If you need complex naming logic, use a Closure:

```php
use Illuminate\Support\Str;

self::registerMediaForColumn(
    column: 'invoice_pdf',
    directory: 'invoices',
    fileName: fn ($model, $file) => "invoice-{$model->number}-" . Str::random(5)
);
// Result: invoice-INV-001-x7kP2.pdf
```

The Closure receives:
- `$model` - The Eloquent model instance
- `$file` - The `UploadedFile` instance

### Multiple Storage Disks

```php
// Store avatars on public disk
self::registerMediaForColumn(
    column: 'avatar',
    directory: 'avatars',
    fileName: 'id',
    disk: 'public'
);

// Store private documents on S3
self::registerMediaForColumn(
    column: 'contract',
    directory: 'contracts',
    fileName: fn ($model) => "contract-{$model->id}",
    disk: 's3'
);
```

### Automatic Cleanup

You don't need to do anything! Laravel Model Media automatically:
- **Deletes the old file** when you re-upload a new one to the same column.
- **Deletes all associated files** when the model is deleted (via Model Observer).

---

## 🖼️ Image Manipulation with Glide

The package includes a powerful Glide plugin for on-the-fly image manipulation.

### Glide Installation

```bash
composer require league/glide
```

Then add the `HasGlideUrls` trait to your model:

```php
use DialloIbrahima\HasMedia\HasMedia;
use DialloIbrahima\HasMedia\Plugins\Glide\HasGlideUrls;

class User extends Model
{
    use HasMedia, HasGlideUrls;
    
    // ...
}
```

Publish the Glide configuration (optional):

```bash
php artisan vendor:publish --tag=model-media-glide-config
```

### Glide Basic Usage

```php
// Resize to 200x200
$url = $user->getGlideUrl('avatar', ['w' => 200, 'h' => 200]);

// Crop to fit
$url = $user->getGlideUrl('avatar', ['w' => 300, 'h' => 300, 'fit' => 'crop']);

// Convert to WebP with quality
$url = $user->getGlideUrl('avatar', ['fm' => 'webp', 'q' => 85]);
```

### Transformation Parameters

| Parameter | Description | Example |
|-----------|-------------|---------|
| `w` | Width in pixels | `['w' => 200]` |
| `h` | Height in pixels | `['h' => 200]` |
| `fit` | Fit mode: `contain`, `max`, `fill`, `stretch`, `crop` | `['fit' => 'crop']` |
| `fm` | Format: `jpg`, `png`, `gif`, `webp` | `['fm' => 'webp']` |
| `q` | Quality (0-100) | `['q' => 85]` |
| `blur` | Blur (0-100) | `['blur' => 5]` |
| `bri` | Brightness (-100 to 100) | `['bri' => 10]` |
| `con` | Contrast (-100 to 100) | `['con' => 10]` |
| `gam` | Gamma (0.1 to 9.99) | `['gam' => 1.5]` |
| `sharp` | Sharpen (0-100) | `['sharp' => 10]` |
| `flip` | Flip: `v` (vertical), `h` (horizontal), `both` | `['flip' => 'h']` |
| `or` | Orientation: `auto`, `0`, `90`, `180`, `270` | `['or' => 'auto']` |
| `border` | Border in format `width,color,method` | `['border' => '5,FFFFFF,overlay']` |

See the [Glide documentation](https://glide.thephpleague.com/) for all available parameters.

### Using Presets

Presets allow you to define reusable transformation sets:

```php
// config/model-media-glide.php
'presets' => [
    'thumbnail' => [
        'w' => 200,
        'h' => 200,
        'fit' => 'crop',
        'fm' => 'webp',
        'q' => 90,
    ],
    'medium' => [
        'w' => 800,
        'h' => 600,
        'fit' => 'contain',
        'fm' => 'webp',
        'q' => 85,
    ],
    'og-image' => [
        'w' => 1200,
        'h' => 630,
        'fit' => 'crop',
        'fm' => 'jpg',
        'q' => 85,
    ],
],
```

Then use them in your code:

```php
$thumbnailUrl = $user->getGlidePresetUrl('avatar', 'thumbnail');
$ogImageUrl = $post->getGlidePresetUrl('cover', 'og-image');
```

### Responsive Images (srcset)

Generate responsive image sets automatically:

```blade
<picture>
    <source 
        srcset="{{ $post->getGlideSrcset('cover', [375, 768, 1024, 1920]) }}"
        sizes="(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw"
    >
    <img 
        src="{{ $post->getGlideUrl('cover', ['w' => 800]) }}"
        alt="{{ $post->title }}"
        loading="lazy"
    >
</picture>
```

### Glide Configuration

```php
// config/model-media-glide.php
return [
    // Routes
    'routes_enabled' => true,
    'route_prefix' => 'media',
    'middleware' => ['web'],

    // Security
    'secure' => env('GLIDE_SECURE', false),
    'signature_key' => env('GLIDE_SIGNATURE_KEY'),

    // Server
    'source' => storage_path('app/public'),
    'cache' => storage_path('app/glide-cache'),
    'driver' => 'gd', // or 'imagick'
    'max_image_size' => 2000 * 2000,

    // Presets
    'presets' => [
        'thumbnail' => ['w' => 200, 'h' => 200, 'fit' => 'crop'],
        // ...
    ],
];
```

### Security

Enable URL signing to prevent unauthorized image manipulation (DoS protection):

```env
GLIDE_SECURE=true
GLIDE_SIGNATURE_KEY=your-32-character-random-string
```

Generate a secure key:

```bash
php artisan tinker
>>> Str::random(32)
```

When secure mode is enabled, all Glide URLs will be cryptographically signed.

### Automatic Cache Cleanup

The `HasGlideUrls` trait automatically registers a `GlideCacheObserver` that cleans up cached images when:

- **Image is updated**: When you attach a new image to a column, the old cached versions are deleted
- **Model is deleted**: All cached images for the model are removed

This happens automatically - you don't need to configure anything. The observer:

1. Detects when a media column changes
2. Finds cached transformations for the old image
3. Deletes them from the Glide cache directory

This prevents stale cached images from being served and saves disk space.

**Manual Cache Clearing**

If you need to manually clear the cache for all images:

```bash
# Clear entire Glide cache
rm -rf storage/app/glide-cache/*
```


---

## 🏗️ How It Works

```mermaid
graph TD
    subgraph Upload ["📤 1. ATTACH (Upload)"]
        A[File Uploaded] --> B[attachMedia Method]
        B --> C[Retrieve Mapping]
        C --> D[Generate Filename]
        D --> E[Store to Disk]
        E --> F[Update Model Attribute]
    end

    subgraph Update ["🔄 2. UPDATE (Auto-Cleanup)"]
        G[Replace File] --> H[Identify Old File]
        H --> I[Delete Old File from Disk]
        I --> J[Proceed with New Upload]
    end

    subgraph Delete ["🗑️ 3. DELETE (Full Cleanup)"]
        K[Model Deleted] --> L[MediaObserver Triggers]
        L --> M[Fetch All Mappings]
        M --> N[Delete All Files from Disk]
    end

    subgraph Glide ["🖼️ 4. GLIDE (Image Transform)"]
        O[getGlideUrl Called] --> P[Validate Image Type]
        P --> Q[Build Transform URL]
        Q --> R[Glide Server Processes]
        R --> S[Return Cached/Generated Image]
    end

    F -.-> G
    J --> B
    F -.-> O
```

---

## 🧪 Testing

The package includes a robust test suite. You can run it via composer:

```bash
composer test
```

We test for:
- ✅ Correct storage path and filename generation.
- ✅ Automatic deletion of old media on update.
- ✅ Garbage collection of files on model deletion.
- ✅ Glide URL generation and transformation.
- ✅ Glide preset and srcset generation.
- ✅ Image type validation (only images allowed for Glide).
- ✅ Error handling for non-image files.

---

## 🤝 Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## 📄 License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## 👨‍💻 Credits

- [Ibrahima Diallo](https://github.com/ibra379)
- [All Contributors](../../contributors)
