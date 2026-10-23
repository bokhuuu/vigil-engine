<?php

declare(strict_types=1);

use App\Repositories\DriveWatcherStateRepository;

it('reports healthy when state is clean', function (): void {
    $path = sys_get_temp_dir().'/drive-health-test-'.uniqid().'.json';
    $repo = new DriveWatcherStateRepository($path);

    $repo->addActFolder('actFolder1', '5', 'recABC');
    $repo->addSubfolder('subId1', 'actFolder1', 'ფოტოები');
    $repo->setSubfolderState('subId1', 'full');

    app()->instance(DriveWatcherStateRepository::class, $repo);

    $this->artisan('vigil:drive-health')
        ->expectsOutputToContain('healthy')
        ->assertExitCode(0);

    unlink($path);
});

it('reports missing state entry', function (): void {
    $path = sys_get_temp_dir().'/drive-health-test-'.uniqid().'.json';
    $repo = new DriveWatcherStateRepository($path);

    $repo->addActFolder('actFolder1', '5', 'recABC');
    $repo->addSubfolder('subId1', 'actFolder1', 'ფოტოები');

    app()->instance(DriveWatcherStateRepository::class, $repo);

    $this->artisan('vigil:drive-health')
        ->expectsOutputToContain('MISSING STATE')
        ->assertExitCode(1);

    unlink($path);
});

it('reports orphaned subfolder', function (): void {
    $path = sys_get_temp_dir().'/drive-health-test-'.uniqid().'.json';
    $repo = new DriveWatcherStateRepository($path);

    $repo->addSubfolder('subId1', 'unknownActFolder', 'ფოტოები');
    $repo->setSubfolderState('subId1', 'full');

    app()->instance(DriveWatcherStateRepository::class, $repo);

    $this->artisan('vigil:drive-health')
        ->expectsOutputToContain('ORPHAN')
        ->assertExitCode(1);

    unlink($path);
});
