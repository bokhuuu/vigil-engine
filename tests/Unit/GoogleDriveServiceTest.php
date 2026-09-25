<?php

declare(strict_types=1);

use App\Services\ActRequirements;
use App\Services\GoogleDriveService;
use Mockery\MockInterface;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['services.google_drive.act_folder_id' => 'root']);
});

function fakeDrive(): MockInterface
{
    return Mockery::mock(GoogleDriveService::class, [new ActRequirements])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
}

test('new ACT gets its folder and required subfolders', function () {
    $drive = fakeDrive();
    $drive->shouldReceive('findChildFolder')->with('329', 'root')->andReturn(null);
    $drive->shouldReceive('createFolder')->with('329', 'root')->once()->andReturn('act1');
    $drive->shouldReceive('listChildFolders')->with('act1')->andReturn([]);
    $drive->shouldReceive('createFolder')->with('სკანი', 'act1')->once()->andReturn('s1');
    $drive->shouldReceive('createFolder')->with('ფოტოები', 'act1')->once()->andReturn('p1');

    expect($drive->ensureActFolders('329', []))
        ->toBe('https://drive.google.com/drive/folders/act1');
});

test('existing ACT folder found by name is reused, not duplicated', function () {
    $drive = fakeDrive();
    $drive->shouldReceive('findChildFolder')->with('329', 'root')->andReturn('act1');
    $drive->shouldReceive('listChildFolders')->with('act1')->andReturn(['სკანი' => 's1', 'ფოტოები' => 'p1']);
    $drive->shouldNotReceive('createFolder');

    expect($drive->ensureActFolders('329', []))
        ->toBe('https://drive.google.com/drive/folders/act1');
});

test('flag added later creates only the missing subfolders', function () {
    $drive = fakeDrive();
    $drive->shouldNotReceive('findChildFolder'); // URL known → no search
    $drive->shouldReceive('listChildFolders')->with('act1')->andReturn(['სკანი' => 's1', 'ფოტოები' => 'p1']);
    $drive->shouldReceive('createFolder')->with('შიდა ნახაზები', 'act1')->once()->andReturn('d1');
    $drive->shouldReceive('createFolder')->with('ხარჯთაღრიცხვა', 'act1')->once()->andReturn('c1');

    $url = $drive->ensureActFolders(
        '329',
        ['🏠 შენობა/ნაგებობები' => true],
        'https://drive.google.com/drive/folders/act1'
    );

    expect($url)->toBe('https://drive.google.com/drive/folders/act1');
});

test('second business linked later creates only ბიზნესი-2', function () {
    $drive = fakeDrive();
    $drive->shouldReceive('listChildFolders')->with('act1')->andReturn([
        'სკანი' => 's1',
        'ფოტოები' => 'p1',
        'ბიზნესი' => 'b1',
    ]);
    $drive->shouldReceive('listChildFolders')->with('b1')->andReturn(['ბიზნესი-1' => 'x1']);
    $drive->shouldReceive('createFolder')->with('ბიზნესი-2', 'b1')->once()->andReturn('x2');

    $url = $drive->ensureActFolders(
        '329',
        ['🏢 ბიზნესი' => ['recA', 'recB']],
        'https://drive.google.com/drive/folders/act1'
    );

    expect($url)->toBe('https://drive.google.com/drive/folders/act1');
});

test('correspondence folder is created only once', function () {
    $drive = fakeDrive();
    $drive->shouldReceive('listChildFolders')->with('act1')->andReturn([
        'სკანი' => 's1',
        'ფოტოები' => 'p1',
        'კორესპონდენცია' => 'k1',
    ]);
    $drive->shouldNotReceive('createFolder');

    $url = $drive->ensureActFolders(
        '329',
        ['📨 კორესპონდენცია' => ['recC']],
        'https://drive.google.com/drive/folders/act1'
    );

    expect($url)->toBe('https://drive.google.com/drive/folders/act1');
});

test('emptyRequiredSubfolders returns nothing when all required folders have files', function () {
    $drive = fakeDrive();
    $drive->shouldReceive('listChildFolders')->with('act1')->andReturn(['სკანი' => 's1', 'ფოტოები' => 'p1']);
    $drive->shouldReceive('folderHasFiles')->andReturn(true);

    expect($drive->emptyRequiredSubfolders('https://drive.google.com/drive/folders/act1', []))
        ->toBe([]);
});

test('emptyRequiredSubfolders lists empty required folders', function () {
    $drive = fakeDrive();
    $drive->shouldReceive('listChildFolders')->with('act1')->andReturn([
        'სკანი' => 's1',
        'ფოტოები' => 'p1',
        'ხარჯთაღრიცხვა' => 'c1',
    ]);
    $drive->shouldReceive('folderHasFiles')->with('s1')->andReturn(false);
    $drive->shouldReceive('folderHasFiles')->with('p1')->andReturn(true);
    $drive->shouldReceive('folderHasFiles')->with('c1')->andReturn(false);

    expect($drive->emptyRequiredSubfolders(
        'https://drive.google.com/drive/folders/act1',
        ['🏠 ღობე - ჭიშკარი' => true]
    ))->toBe(['სკანი', 'ხარჯთაღრიცხვა']);
});

test('emptyRequiredSubfolders counts a missing required folder as empty', function () {
    $drive = fakeDrive();
    $drive->shouldReceive('listChildFolders')->with('act1')->andReturn(['სკანი' => 's1', 'ფოტოები' => 'p1']);
    $drive->shouldReceive('folderHasFiles')->andReturn(true);

    expect($drive->emptyRequiredSubfolders(
        'https://drive.google.com/drive/folders/act1',
        ['🏠 შენობა/ნაგებობები' => true]
    ))->toBe(['შიდა ნახაზები', 'ხარჯთაღრიცხვა']);
});

test('emptyRequiredSubfolders rejects an invalid folder URL', function () {
    expect(fn () => fakeDrive()->emptyRequiredSubfolders('not-a-url', []))
        ->toThrow(InvalidArgumentException::class);
});

test('prune trashes empty unneeded tracked folders and never touches untracked ones', function () {
    $drive = fakeDrive();
    $drive->shouldReceive('listChildFolders')->with('act1')->andReturn([
        'სკანი' => 's1',
        'ფოტოები' => 'p1',
        'გამიჯვნები' => 'g1',
        'კორესპონდენცია' => 'k1',
    ]);
    $drive->shouldReceive('folderHasFiles')->with('g1')->andReturn(false);
    $drive->shouldReceive('trashFolder')->with('g1')->once();

    expect($drive->pruneUnneededSubfolders('https://drive.google.com/drive/folders/act1', []))
        ->toBe([]);
});

test('prune keeps unneeded folders that have files and reports them', function () {
    $drive = fakeDrive();
    $drive->shouldReceive('listChildFolders')->with('act1')->andReturn([
        'სკანი' => 's1',
        'ფოტოები' => 'p1',
        'გამიჯვნები' => 'g1',
    ]);
    $drive->shouldReceive('folderHasFiles')->with('g1')->andReturn(true);
    $drive->shouldNotReceive('trashFolder');

    expect($drive->pruneUnneededSubfolders('https://drive.google.com/drive/folders/act1', []))
        ->toBe(['გამიჯვნები']);
});

test('prune never checks or trashes required folders', function () {
    $drive = fakeDrive();
    $drive->shouldReceive('listChildFolders')->with('act1')->andReturn([
        'სკანი' => 's1',
        'ფოტოები' => 'p1',
        'გამიჯვნები' => 'g1',
    ]);
    $drive->shouldNotReceive('folderHasFiles');
    $drive->shouldNotReceive('trashFolder');

    expect($drive->pruneUnneededSubfolders(
        'https://drive.google.com/drive/folders/act1',
        ['📋 გამიჯვნა საჭიროა' => true]
    ))->toBe([]);
});
