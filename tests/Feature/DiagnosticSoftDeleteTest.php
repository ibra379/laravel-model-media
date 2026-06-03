<?php

use Illuminate\Database\Eloquent\SoftDeletes;
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
    $model = SoftDeleteMediaTest::factory()->create();

    $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true) ? 1 : 0;

    $root = Storage::disk('public')->path('');

    $stored = $model->attachMedia(UploadedFile::fake()->create('t.jpg', 100), 'avatar') ? 1 : 0;
    $path = 'avatars/'.$model->avatar;

    $existedBefore = Storage::disk('public')->exists($path) ? 1 : 0;
    $allFilesBefore = implode(',', array_map(fn ($p) => str_replace($root, '', $p), File::allFiles($root)));

    $model->delete(); // soft

    $existedAfter = Storage::disk('public')->exists($path) ? 1 : 0;
    $rootExistsAfter = File::isDirectory($root) ? 1 : 0;
    $allFilesAfter = $rootExistsAfter ? implode(',', array_map(fn ($p) => str_replace($root, '', $p), File::allFiles($root))) : '<root-gone>';

    $report = "uses={$usesSoftDeletes} stored={$stored} avatar=[{$model->avatar}] slug=[{$model->slug}] root=[{$root}] before={$existedBefore} after={$existedAfter} rootAfter={$rootExistsAfter} filesBefore=[{$allFilesBefore}] filesAfter=[{$allFilesAfter}]";

    expect($report)->toBe('___DIAGNOSTIC___');
});
