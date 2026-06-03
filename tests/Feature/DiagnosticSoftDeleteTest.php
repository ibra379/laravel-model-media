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
    $GLOBALS['diag'] = [];

    SoftDeleteMediaTest::deleting(function ($m) {
        $GLOBALS['diag'][] = 'deleting:uses='.(in_array(SoftDeletes::class, class_uses_recursive($m), true) ? 1 : 0).',forcing='.($m->isForceDeleting() ? 1 : 0).',avatar=['.$m->avatar.']';
    });
    SoftDeleteMediaTest::deleted(function ($m) {
        $GLOBALS['diag'][] = 'deleted:uses='.(in_array(SoftDeletes::class, class_uses_recursive($m), true) ? 1 : 0).',forcing='.($m->isForceDeleting() ? 1 : 0).',avatar=['.$m->avatar.']';
    });
    SoftDeleteMediaTest::forceDeleted(function ($m) {
        $GLOBALS['diag'][] = 'forceDeleted';
    });

    $root = Storage::disk('public')->path('');
    $model = SoftDeleteMediaTest::factory()->create();
    $model->attachMedia(UploadedFile::fake()->create('t.jpg', 100), 'avatar');

    $GLOBALS['diag'][] = 'preDelete:avatar=['.$model->avatar.'],files=['.implode(',', array_map(fn ($p) => str_replace($root, '', $p), File::allFiles($root))).']';

    $model->delete(); // soft

    $GLOBALS['diag'][] = 'postDelete:avatar=['.$model->avatar.'],files=['.(File::isDirectory($root) ? implode(',', array_map(fn ($p) => str_replace($root, '', $p), File::allFiles($root))) : '<gone>').']';

    expect(implode(' || ', $GLOBALS['diag']))->toBe('___DIAGNOSTIC___');
});
