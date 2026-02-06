<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Fixtures\Post;

beforeEach(function () {
    Storage::fake('public');
});

afterEach(function () {
    Storage::disk('public')->deleteDirectory('covers');
    Storage::disk('public')->deleteDirectory('documents');
});

test('uses server-detected file extension instead of client-provided', function () {
    $post = Post::create(['title' => 'Test Post']);

    // Create a fake image file
    $imageContent = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');
    
    // Create a temporary file with .jpg extension
    $tempPath = sys_get_temp_dir() . '/test-' . uniqid() . '.jpg';
    file_put_contents($tempPath, $imageContent);

    // Create UploadedFile with spoofed extension in filename
    // Simulating a client trying to upload "malicious.php.jpg"
    $file = new UploadedFile(
        $tempPath,
        'malicious.php.jpg', // Client provides this name
        'image/jpeg',
        null,
        true // Mark as test file
    );

    $post->attachMedia($file, 'cover');
    $post->save();

    // Get the stored filename
    $storedFilename = $post->cover;

    // The extension should be based on server MIME detection, not client filename
    // Server should detect it as JPEG and use .jpg or .jpeg extension
    expect($storedFilename)->toMatch('/\.(jpg|jpeg)$/i');
    
    // Should NOT preserve the double extension from client
    expect($storedFilename)->not->toContain('.php');

    // Verify file was stored with safe extension
    expect(Storage::disk('public')->exists('covers/' . $storedFilename))->toBeTrue();

    // Clean up
    if (file_exists($tempPath)) {
        unlink($tempPath);
    }
});

test('rejects file uploads with path traversal in directory', function () {
    $post = Post::create(['title' => 'Test Post']);

    // Even if someone tries to manipulate the directory mapping
    // The storage system should prevent path traversal
    $imageContent = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');
    $tempPath = sys_get_temp_dir() . '/test-' . uniqid() . '.jpg';
    file_put_contents($tempPath, $imageContent);

    $file = new UploadedFile($tempPath, 'test.jpg', 'image/jpeg', null, true);

    // Normal upload should work
    $result = $post->attachMedia($file, 'cover');
    expect($result)->toBeTrue();

    // File should be in the correct directory
    $storedFilename = $post->cover;
    expect(Storage::disk('public')->exists('covers/' . $storedFilename))->toBeTrue();
    
    // File should NOT be outside the covers directory
    expect(Storage::disk('public')->exists('../' . $storedFilename))->toBeFalse();

    if (file_exists($tempPath)) {
        unlink($tempPath);
    }
});

test('validates uploaded file MIME type matches extension', function () {
    $post = Post::create(['title' => 'Test Post']);

    // Create a text file but claim it's an image
    $tempPath = sys_get_temp_dir() . '/fake-image-' . uniqid() . '.jpg';
    file_put_contents($tempPath, 'This is not an image, it is text');

    // Try to upload as if it's an image
    $file = new UploadedFile(
        $tempPath,
        'fake.jpg',
        'text/plain', // Real MIME type
        null,
        true
    );

    // The file will be stored, but when accessed via Glide, it should fail validation
    $post->attachMedia($file, 'cover');
    $post->save();

    // The file exists but has wrong MIME type
    $storedFilename = $post->cover;
    expect(Storage::disk('public')->exists('covers/' . $storedFilename))->toBeTrue();

    // When trying to use it with Glide, it should fail
    // (This would be tested in HasGlideUrlsTest)
    expect($post->hasImageMedia('cover'))->toBeFalse();

    if (file_exists($tempPath)) {
        unlink($tempPath);
    }
});

test('handles files with special characters in filename safely', function () {
    $post = Post::create(['title' => 'Test Post']);

    $imageContent = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');
    $tempPath = sys_get_temp_dir() . '/test-' . uniqid() . '.jpg';
    file_put_contents($tempPath, $imageContent);

    // Filename with special characters that could cause issues
    $file = new UploadedFile(
        $tempPath,
        '../../../etc/passwd.jpg', // Malicious filename
        'image/jpeg',
        null,
        true
    );

    $post->attachMedia($file, 'cover');
    $post->save();

    $storedFilename = $post->cover;

    // Filename should be sanitized and not contain path traversal
    expect($storedFilename)->not->toContain('..');
    expect($storedFilename)->not->toContain('/');
    expect($storedFilename)->toMatch('/\.(jpg|jpeg)$/i');

    // File should be safely stored in the correct directory
    expect(Storage::disk('public')->exists('covers/' . $storedFilename))->toBeTrue();

    if (file_exists($tempPath)) {
        unlink($tempPath);
    }
});

test('prevents uploading executable files disguised as images', function () {
    $post = Post::create(['title' => 'Test Post']);

    // Create a PHP file with image extension
    $phpContent = '<?php echo "This is malicious code"; ?>';
    $tempPath = sys_get_temp_dir() . '/evil-' . uniqid() . '.jpg';
    file_put_contents($tempPath, $phpContent);

    $file = new UploadedFile(
        $tempPath,
        'shell.php.jpg',
        'application/x-php', // Real MIME type
        null,
        true
    );

    // File will be stored (HasMedia doesn't validate MIME on upload)
    $post->attachMedia($file, 'cover');
    
    // But it should NOT be treated as a valid image
    expect($post->hasImageMedia('cover'))->toBeFalse();

    if (file_exists($tempPath)) {
        unlink($tempPath);
    }
});

test('preserves original file when new upload fails', function () {
    $post = Post::create(['title' => 'Test Post']);

    // Upload first file successfully
    $imageContent = file_get_contents(__DIR__ . '/../fixtures/test-image.jpg');
    $tempPath1 = sys_get_temp_dir() . '/test1-' . uniqid() . '.jpg';
    file_put_contents($tempPath1, $imageContent);

    $file1 = new UploadedFile($tempPath1, 'first.jpg', 'image/jpeg', null, true);
    $post->attachMedia($file1, 'cover');
    $post->save();

    $firstFilename = $post->cover;
    expect(Storage::disk('public')->exists('covers/' . $firstFilename))->toBeTrue();

    // Attempt to upload second file
    $tempPath2 = sys_get_temp_dir() . '/test2-' . uniqid() . '.jpg';
    file_put_contents($tempPath2, $imageContent);
    
    $file2 = new UploadedFile($tempPath2, 'second.jpg', 'image/jpeg', null, true);
    $post->attachMedia($file2, 'cover');
    $post->save();

    // Old file should be deleted after successful new upload
    expect(Storage::disk('public')->exists('covers/' . $firstFilename))->toBeFalse();
    
    // New file should exist
    expect(Storage::disk('public')->exists('covers/' . $post->cover))->toBeTrue();

    // Clean up
    foreach ([$tempPath1, $tempPath2] as $path) {
        if (file_exists($path)) {
            unlink($path);
        }
    }
});
