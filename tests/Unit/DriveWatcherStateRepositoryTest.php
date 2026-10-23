<?php

declare(strict_types=1);

use App\Repositories\DriveWatcherStateRepository;

beforeEach(function (): void {
    $this->path = sys_get_temp_dir().'/drive-watcher-state-test-'.uniqid().'.json';
    $this->repo = new DriveWatcherStateRepository($this->path);
});

afterEach(function (): void {
    if (file_exists($this->path)) {
        unlink($this->path);
    }
});

it('returns null page token when state file does not exist', function (): void {
    expect($this->repo->getPageToken())->toBeNull();
});

it('saves and retrieves page token', function (): void {
    $this->repo->setPageToken('abc123');
    expect($this->repo->getPageToken())->toBe('abc123');
});

it('saves and retrieves act folder entry', function (): void {
    $this->repo->addActFolder('folderId1', '15', 'recABC');
    $act = $this->repo->getActByFolderId('folderId1');

    expect($act['act_number'])->toBe('15')
        ->and($act['act_record_id'])->toBe('recABC');
});

it('returns null for unknown folder id', function (): void {
    expect($this->repo->getActByFolderId('unknown'))->toBeNull();
});

it('saves and retrieves subfolder entry', function (): void {
    $this->repo->addSubfolder('subId1', 'folderId1', 'ფოტოები');
    $sub = $this->repo->getSubfolder('subId1');

    expect($sub['act_folder_id'])->toBe('folderId1')
        ->and($sub['name'])->toBe('ფოტოები');
});

it('saves and retrieves subfolder state', function (): void {
    $this->repo->setSubfolderState('subId1', 'full');
    expect($this->repo->getSubfolderState('subId1'))->toBe('full');
});

it('saves and retrieves folder color', function (): void {
    $this->repo->setFolderColor('folderId1', '#00843D');
    expect($this->repo->getFolderColor('folderId1'))->toBe('#00843D');
});

it('persists state across instances', function (): void {
    $this->repo->setPageToken('token1');
    $this->repo->addActFolder('f1', '42', 'recXYZ');

    $repo2 = new DriveWatcherStateRepository($this->path);

    expect($repo2->getPageToken())->toBe('token1')
        ->and($repo2->getActByFolderId('f1')['act_number'])->toBe('42');
});

it('replaces stale subfolder entry when same act folder and name registered with new id', function (): void {
    $this->repo->addSubfolder('oldId', 'actFolder1', 'ფოტოები');
    $this->repo->setSubfolderState('oldId', 'full');
    $this->repo->setFolderColor('oldId', '#00843D');

    $this->repo->addSubfolder('newId', 'actFolder1', 'ფოტოები');

    expect($this->repo->getSubfolder('oldId'))->toBeNull();

    $sub = $this->repo->getSubfolder('newId');
    expect($sub['act_folder_id'])->toBe('actFolder1')
        ->and($sub['name'])->toBe('ფოტოები');

    expect($this->repo->getSubfolderState('newId'))->toBe('full');
    expect($this->repo->getFolderColor('newId'))->toBe('#00843D');
});

it('does not re-add subfolder when same id already registered', function (): void {
    $this->repo->addSubfolder('subId1', 'actFolder1', 'ფოტოები');
    $this->repo->setSubfolderState('subId1', 'full');

    $this->repo->addSubfolder('subId1', 'actFolder1', 'ფოტოები');

    expect($this->repo->getSubfolderState('subId1'))->toBe('full');
    expect(count($this->repo->getSubfolderIndex()))->toBe(1);
});
