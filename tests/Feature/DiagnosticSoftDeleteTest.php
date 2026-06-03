<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
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
    $GLOBALS['detach_log'] = [];
    $events = [];
    Event::listen('eloquent.*', function ($name, $data) use (&$events) {
        if (str_contains($name, 'SoftDeleteMediaTest')) {
            $events[] = str_replace('eloquent.', '', explode(':', $name)[0]);
        }
    });

    $root = Storage::disk('public')->path('');
    $model = SoftDeleteMediaTest::factory()->create();
    $model->attachMedia(UploadedFile::fake()->create('t.jpg', 100), 'avatar');

    $pre = "pre:avatar=[{$model->avatar}]";
    $events = []; // reset, only capture delete-phase events

    $model->delete(); // soft

    $files = File::isDirectory($root) ? implode(',', array_map(fn ($p) => str_replace($root, '', $p), File::allFiles($root))) : '<gone>';
    $report = "{$pre} || EVENTS=[".implode(',', $events)."] || DETACH=[".implode(' ;; ', $GLOBALS['detach_log'])."] || post:avatar=[{$model->avatar}],files=[{$files}]";

    expect($report)->toBe('___DIAGNOSTIC___');
});
