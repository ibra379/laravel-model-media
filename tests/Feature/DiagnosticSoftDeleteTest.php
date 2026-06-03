<?php

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\UploadedFile;
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

    $deletedFired = 0;
    $forceDeletedFired = 0;
    SoftDeleteMediaTest::deleted(function ($m) use (&$deletedFired) {
        $deletedFired = $m->isForceDeleting() ? 2 : 1; // 1=soft, 2=force-flag-during-deleted
    });
    SoftDeleteMediaTest::forceDeleted(function () use (&$forceDeletedFired) {
        $forceDeletedFired = 1;
    });

    $model->attachMedia(UploadedFile::fake()->create('t.jpg', 100), 'avatar');
    $path = 'avatars/'.$model->avatar;
    $existedBefore = Storage::disk('public')->exists($path) ? 1 : 0;

    $model->delete(); // soft

    $existedAfter = Storage::disk('public')->exists($path) ? 1 : 0;

    $report = "usesSoftDeletes={$usesSoftDeletes} deletedFired={$deletedFired} forceDeletedFired={$forceDeletedFired} existedBefore={$existedBefore} existedAfter={$existedAfter}";

    expect($report)->toBe('___DIAGNOSTIC___');
});
