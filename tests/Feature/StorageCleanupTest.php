<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('deletes ZIPs older than 24 hours', function () {
    $dir = storage_path('app/downloads');
    @mkdir($dir, 0755, true);

    $oldFile = $dir.'/old-test.zip';
    file_put_contents($oldFile, 'old');
    touch($oldFile, now()->subHours(25)->timestamp);

    Artisan::call('storage:cleanup');

    expect(file_exists($oldFile))->toBeFalse();
});

it('keeps ZIPs newer than 24 hours', function () {
    $dir = storage_path('app/downloads');
    @mkdir($dir, 0755, true);

    $newFile = $dir.'/new-test.zip';
    file_put_contents($newFile, 'new');
    touch($newFile, now()->subHours(1)->timestamp);

    Artisan::call('storage:cleanup');

    expect(file_exists($newFile))->toBeTrue();

    @unlink($newFile);
});

it('handles missing downloads directory gracefully', function () {
    $result = Artisan::call('storage:cleanup');
    expect($result)->toBe(0);
});
