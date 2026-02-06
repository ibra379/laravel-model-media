# Security Policy

## Supported Versions

We release security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x     | :white_check_mark: |

## Security Best Practices

### 1. Enable Glide URL Signature Verification (CRITICAL)

**Always enable signature verification in production environments** to prevent:
- Denial of Service (DoS) attacks from unlimited image generation
- Disk space exhaustion from cached variations
- Excessive bandwidth and CPU consumption

Add to your `.env` file:

```env
GLIDE_SECURE=true
GLIDE_SIGNATURE_KEY=your-32-character-random-string
```

Generate a secure key:

```bash
php artisan tinker
>>> Str::random(32)
```

### 2. File Upload Validation

The package automatically validates uploaded files using server-side MIME type detection to prevent:
- Extension spoofing attacks (e.g., `malicious.php.jpg`)
- Non-image file uploads
- Corrupted or malicious images

**Best practices:**
- Always validate file sizes in your controllers before calling `attachMedia()`
- Consider implementing additional file validation based on your use case
- Restrict upload permissions to authenticated users only

### 3. Path Traversal Protection

The package implements multiple layers of protection against path traversal attacks:
- Automatic path normalization
- `realpath()` validation to ensure files stay within intended directories
- Rejection of `../` sequences in file paths

**No additional configuration required** - these protections are enabled by default.

### 4. SVG File Handling

SVG files can contain malicious JavaScript. The package automatically:
- Validates SVG content for dangerous patterns
- Blocks SVG files with `<script>` tags, event handlers, or JavaScript URLs
- Prevents XSS attacks through SVG injection

**Recommendation:** If you don't need SVG support, remove `image/svg+xml` from allowed MIME types in your application.

### 5. Storage Configuration

Configure appropriate storage disks in `config/filesystems.php`:

```php
// For public images (avatars, covers)
'public' => [
    'driver' => 'local',
    'root' => storage_path('app/public'),
    'url' => env('APP_URL').'/storage',
    'visibility' => 'public',
],

// For private documents
'private' => [
    'driver' => 'local',
    'root' => storage_path('app/private'),
    'visibility' => 'private',
],
```

### 6. MIME Type Detection

The package uses PHP's `finfo_file()` function for reliable MIME type detection instead of:
- Client-provided extensions (can be spoofed)
- File extensions (unreliable)
- Deprecated `mime_content_type()` function

**No configuration required** - secure MIME detection is enabled by default.

### 7. Rate Limiting

Consider implementing rate limiting on image transformation endpoints to prevent abuse:

```php
// In routes/web.php or your route service provider
Route::middleware(['throttle:60,1'])->group(function () {
    // Glide routes are automatically registered here
});
```

Or configure in `config/model-media-glide.php`:

```php
'middleware' => ['web', 'throttle:60,1'],
```

## Reporting a Vulnerability

If you discover a security vulnerability, please email **idiallo379@gmail.com** directly.

**Please do not:**
- Open a public GitHub issue for security vulnerabilities
- Disclose the vulnerability publicly before we've had a chance to address it

**What to include in your report:**
1. Description of the vulnerability
2. Steps to reproduce
3. Potential impact
4. Suggested fix (if available)

**Response timeline:**
- Initial response: Within 48 hours
- Status update: Within 7 days
- Fix release: Depends on severity, typically within 14-30 days

## Security Changelog

### Version 1.x (Current)
- ✅ Path traversal protection with `realpath()` validation
- ✅ Server-side MIME type detection using `finfo_file()`
- ✅ SVG content validation to prevent XSS attacks
- ✅ Extension validation to prevent spoofing
- ✅ URL signature support for DoS protection
- ✅ Path boundary validation in all file operations

## Security Audit History

Last security audit: February 2026
- Comprehensive code review completed
- All critical and high-severity vulnerabilities addressed
- Path traversal vulnerabilities: FIXED
- MIME type validation: ENHANCED
- SVG XSS prevention: IMPLEMENTED

## Additional Resources

- [OWASP File Upload Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)
- [Laravel Security Best Practices](https://laravel.com/docs/master/security)
- [Glide Security Documentation](https://glide.thephpleague.com/2.0/config/security/)
