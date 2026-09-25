<?php

declare(strict_types=1);

use App\Services\AirtableService;
use App\Services\GoogleDriveService;

beforeEach(function () {
    config([
        'vigil.dashboard.user' => 'vigil',
        'vigil.dashboard.password' => 'test-password',
    ]);
    $this->auth = ['Authorization' => 'Basic '.base64_encode('vigil:test-password')];
});

test('folder button requires dashboard login', function () {
    $this->post('/dashboard/acts/rec329/folder')->assertStatus(401);
});

test('folder button creates the folder and saves its URL', function () {
    $this->mock(AirtableService::class, function ($mock) {
        $mock->shouldReceive('getAct')->with('rec329')
            ->andReturn(['id' => 'rec329', 'fields' => ['🔖 აქტის ნომერი' => '329']]);
        $mock->shouldReceive('updateActDriveFolder')
            ->with('rec329', 'https://drive.google.com/drive/folders/new329')->once();
    });
    $this->mock(GoogleDriveService::class)
        ->shouldReceive('ensureActFolders')->with('329', Mockery::type('array'))->once()
        ->andReturn('https://drive.google.com/drive/folders/new329');

    $this->withHeaders($this->auth)
        ->from('/dashboard')
        ->post('/dashboard/acts/rec329/folder')
        ->assertRedirect('/dashboard')
        ->assertSessionHas('status');
});

test('folder button does nothing when the ACT already has a folder', function () {
    $this->mock(AirtableService::class, function ($mock) {
        $mock->shouldReceive('getAct')->andReturn(['id' => 'rec329', 'fields' => [
            '🔖 აქტის ნომერი' => '329',
            '💼 საქაღალდე' => 'https://drive.google.com/drive/folders/f329',
        ]]);
        $mock->shouldNotReceive('updateActDriveFolder');
    });
    $this->mock(GoogleDriveService::class)->shouldNotReceive('ensureActFolders');

    $this->withHeaders($this->auth)
        ->from('/dashboard')
        ->post('/dashboard/acts/rec329/folder')
        ->assertRedirect('/dashboard')
        ->assertSessionHas('status');
});

test('folder button shows an error when Drive fails', function () {
    $this->mock(AirtableService::class, function ($mock) {
        $mock->shouldReceive('getAct')
            ->andReturn(['id' => 'rec329', 'fields' => ['🔖 აქტის ნომერი' => '329']]);
        $mock->shouldNotReceive('updateActDriveFolder');
    });
    $this->mock(GoogleDriveService::class)
        ->shouldReceive('ensureActFolders')->andThrow(new RuntimeException('Drive down'));

    $this->withHeaders($this->auth)
        ->from('/dashboard')
        ->post('/dashboard/acts/rec329/folder')
        ->assertRedirect('/dashboard')
        ->assertSessionHas('error');
});

test('folder button rejects non-Airtable record ids', function () {
    $this->withHeaders($this->auth)
        ->post('/dashboard/acts/not-a-record/folder')
        ->assertNotFound();
});
