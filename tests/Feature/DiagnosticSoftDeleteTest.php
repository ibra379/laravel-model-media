<?php

use DialloIbrahima\HasMedia\Observers\MediaObserver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Workbench\App\Models\SoftDeleteMediaTest;

beforeEach(function () {
    Storage::fake('public');
});

afterEach(function () {
    Storage::disk('public')->deleteDirectory('avatars');
});

it('DIAGNOSTIC dumps soft delete observer facts', function () {
    MediaObserver::$diag = [];

    $root = Storage::disk('public')->path('');
    $model = SoftDeleteMediaTest::factory()->create();
    $model->attachMedia(UploadedFile::fake()->create('t.jpg', 100), 'avatar');

    $pre = 'pre:avatar=['.$model->avatar.'],files=['.implode(',', array_map(fn ($p) => str_replace($root, '', $p), File::allFiles($root))).']';

    $model->delete(); // soft

    $post = 'post:avatar=['.$model->avatar.'],files=['.(File::isDirectory($root) ? implode(',', array_map(fn ($p) => str_replace($root, '', $p), File::allFiles($root))) : '<gone>').']';

    expect($pre.' || OBS=['.implode(' ;; ', MediaObserver::$diag).'] || '.$post)->toBe('___DIAGNOSTIC___');
});
