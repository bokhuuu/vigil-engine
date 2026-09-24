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
