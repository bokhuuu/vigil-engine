<?php

declare(strict_types=1);

use App\Services\ActCompletionService;
use App\Services\AirtableService;
use App\Services\GoogleDriveService;
use App\Services\ManagerNotifier;
use App\Services\TelegramService;

function mockAct(array $fields): void
{
    test()->mock(AirtableService::class, function ($mock) use ($fields) {
        $mock->shouldReceive('getAct')->with('rec329')->andReturn(['id' => 'rec329', 'fields' => $fields]);
        $mock->shouldReceive('recordUrl')->andReturn('https://airtable.com/app/tbl/rec329');
    });
}

$complete = [
    '🔖 აქტის ნომერი' => '329',
    '🚦სტატუსი' => '✅ დასრულებული',
    '📥 ხელმოწერები' => true,
    '💼 საქაღალდე' => 'https://drive.google.com/drive/folders/act1',
];

test('verified complete ACT notifies managers', function () use ($complete) {
    mockAct($complete);
    $this->mock(GoogleDriveService::class)->shouldReceive('emptyRequiredSubfolders')->andReturn([]);
    $this->mock(ManagerNotifier::class)->shouldReceive('notify')
        ->once()->withArgs(fn ($msg) => str_contains($msg, 'ACT 329') && str_contains($msg, 'დასრულდა'));
    $this->mock(TelegramService::class)->shouldNotReceive('sendToMe');

    expect(app(ActCompletionService::class)->check('rec329'))->toBe([]);
});

test('empty folder warns Zura only', function () use ($complete) {
    mockAct($complete);
    $this->mock(GoogleDriveService::class)->shouldReceive('emptyRequiredSubfolders')->andReturn(['სკანი']);
    $this->mock(ManagerNotifier::class)->shouldNotReceive('notify');
    $this->mock(TelegramService::class)->shouldReceive('sendToMe')
        ->once()->withArgs(fn ($msg) => str_contains($msg, 'სკანი'));

    expect(app(ActCompletionService::class)->check('rec329'))->toBe(['სკანი']);
});

test('missing signatures and folder are both reported', function () {
    mockAct(['🔖 აქტის ნომერი' => '329', '🚦სტატუსი' => '✅ დასრულებული']);
    $this->mock(GoogleDriveService::class)->shouldNotReceive('emptyRequiredSubfolders');
    $this->mock(ManagerNotifier::class)->shouldNotReceive('notify');
    $this->mock(TelegramService::class)->shouldReceive('sendToMe')->once();

    expect(app(ActCompletionService::class)->check('rec329'))
        ->toBe(['ხელმოწერები', 'Drive საქაღალდე']);
});

test('Drive error never produces a complete message', function () use ($complete) {
    mockAct($complete);
    $this->mock(GoogleDriveService::class)->shouldReceive('emptyRequiredSubfolders')
        ->andThrow(new RuntimeException('Drive down'));
    $this->mock(ManagerNotifier::class)->shouldNotReceive('notify');
    $this->mock(TelegramService::class)->shouldReceive('sendToMe')->once();

    expect(app(ActCompletionService::class)->check('rec329'))->toBe(['Drive ვერ შემოწმდა']);
});

test('ACT not marked complete is ignored', function () {
    mockAct(['🔖 აქტის ნომერი' => '329', '🚦სტატუსი' => '⏳ მიმდინარე']);
    $this->mock(GoogleDriveService::class)->shouldNotReceive('emptyRequiredSubfolders');
    $this->mock(ManagerNotifier::class)->shouldNotReceive('notify');
    $this->mock(TelegramService::class)->shouldNotReceive('sendToMe');

    expect(app(ActCompletionService::class)->check('rec329'))->toBeNull();
});
