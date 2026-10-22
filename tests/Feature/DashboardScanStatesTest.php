<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;

it('returns empty array when state file does not exist', function () {
    $path = storage_path('app/google/drive-watcher-state.json');
    $backup = $path.'.bak';
    $existed = file_exists($path);
    if ($existed) {
        rename($path, $backup);
    }

    $controller = app(DashboardController::class);
    $method = new ReflectionMethod($controller, 'loadDriveStates');
    $method->setAccessible(true);
    $result = $method->invoke($controller);

    if ($existed) {
        rename($backup, $path);
    }

    expect($result)->toBe([]);
});

it('returns empty array when state file is invalid json', function () {
    $path = storage_path('app/google/drive-watcher-state.json');
    $backup = $path.'.bak';
    $existed = file_exists($path);
    if ($existed) {
        rename($path, $backup);
    }

    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, 'not-json');

    $controller = app(DashboardController::class);
    $method = new ReflectionMethod($controller, 'loadDriveStates');
    $method->setAccessible(true);
    $result = $method->invoke($controller);

    unlink($path);
    if ($existed) {
        rename($backup, $path);
    }

    expect($result)->toBe([]);
});

it('maps airtable record id to all subfolder states via folder_index', function () {
    $path = storage_path('app/google/drive-watcher-state.json');
    $backup = $path.'.bak';
    $existed = file_exists($path);
    if ($existed) {
        rename($path, $backup);
    }

    $fixture = [
        'page_token' => '12345',
        'folder_index' => [
            'drive-folder-aaa' => ['act_number' => '101', 'act_record_id' => 'recABC'],
            'drive-folder-bbb' => ['act_number' => '102', 'act_record_id' => 'recDEF'],
        ],
        'subfolder_index' => [
            'sub-scan-1' => ['act_folder_id' => 'drive-folder-aaa', 'name' => 'სკანი'],
            'sub-photo-1' => ['act_folder_id' => 'drive-folder-aaa', 'name' => 'ფოტოები'],
            'sub-scan-2' => ['act_folder_id' => 'drive-folder-bbb', 'name' => 'სკანი'],
        ],
        'subfolder_states' => [
            'sub-scan-1' => 'full',
            'sub-photo-1' => 'full',
            'sub-scan-2' => 'empty',
        ],
        'folder_colors' => [],
    ];

    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, json_encode($fixture));

    $controller = app(DashboardController::class);
    $method = new ReflectionMethod($controller, 'loadDriveStates');
    $method->setAccessible(true);
    $result = $method->invoke($controller);

    unlink($path);
    if ($existed) {
        rename($backup, $path);
    }

    expect($result)->toBe([
        'recABC' => [
            'სკანი' => 'full',
            'ფოტოები' => 'full',
        ],
        'recDEF' => [
            'სკანი' => 'empty',
        ],
    ]);
});

it('ignores subfolders that have no matching folder_index entry', function () {
    $path = storage_path('app/google/drive-watcher-state.json');
    $backup = $path.'.bak';
    $existed = file_exists($path);
    if ($existed) {
        rename($path, $backup);
    }

    $fixture = [
        'page_token' => '1',
        'folder_index' => [],
        'subfolder_index' => [
            'sub-orphan' => ['act_folder_id' => 'drive-folder-unknown', 'name' => 'სკანი'],
        ],
        'subfolder_states' => ['sub-orphan' => 'full'],
        'folder_colors' => [],
    ];

    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, json_encode($fixture));

    $controller = app(DashboardController::class);
    $method = new ReflectionMethod($controller, 'loadDriveStates');
    $method->setAccessible(true);
    $result = $method->invoke($controller);

    unlink($path);
    if ($existed) {
        rename($backup, $path);
    }

    expect($result)->toBe([]);
});
