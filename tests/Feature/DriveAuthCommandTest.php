<?php

declare(strict_types=1);

use Google\Client;

it('fails if oauth client file does not exist', function (): void {
    config()->set('services.google_drive.oauth_client', '/nonexistent/path.json');
    config()->set('services.google_drive.oauth_token', '/tmp/oauth-token.json');

    $this->artisan('vigil:drive-auth')
        ->expectsOutputToContain('OAuth client file not found')
        ->assertExitCode(1);
});

it('fails if google returns an error for the code', function (): void {
    $clientFile = tempnam(sys_get_temp_dir(), 'oauth-client');

    config()->set('services.google_drive.oauth_client', $clientFile);
    config()->set('services.google_drive.oauth_token', '/tmp/oauth-token.json');

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('setAuthConfig')->once();
    $mockClient->shouldReceive('addScope')->twice();
    $mockClient->shouldReceive('setAccessType')->once();
    $mockClient->shouldReceive('setPrompt')->once();
    $mockClient->shouldReceive('createAuthUrl')->once()->andReturn('https://accounts.google.com/fake');
    $mockClient->shouldReceive('fetchAccessTokenWithAuthCode')
        ->once()
        ->andReturn(['error' => 'invalid_grant', 'error_description' => 'Code expired']);

    $this->app->instance(Client::class, $mockClient);

    $this->artisan('vigil:drive-auth')
        ->expectsOutputToContain('accounts.google.com')
        ->expectsQuestion('Paste the code here', 'fake-code')
        ->expectsOutputToContain('Code expired')
        ->assertExitCode(1);

    unlink($clientFile);
});

it('saves token file on success', function (): void {
    $clientFile = tempnam(sys_get_temp_dir(), 'oauth-client');
    $tokenFile = tempnam(sys_get_temp_dir(), 'oauth-token');

    config()->set('services.google_drive.oauth_client', $clientFile);
    config()->set('services.google_drive.oauth_token', $tokenFile);

    $mockClient = Mockery::mock(Client::class);
    $mockClient->shouldReceive('setAuthConfig')->once();
    $mockClient->shouldReceive('addScope')->twice();
    $mockClient->shouldReceive('setAccessType')->once();
    $mockClient->shouldReceive('setPrompt')->once();
    $mockClient->shouldReceive('createAuthUrl')->once()->andReturn('https://accounts.google.com/fake');
    $mockClient->shouldReceive('fetchAccessTokenWithAuthCode')
        ->once()
        ->andReturn(['access_token' => 'ya29.fake', 'refresh_token' => 'fake-refresh']);

    $this->app->instance(Client::class, $mockClient);

    $this->artisan('vigil:drive-auth')
        ->expectsQuestion('Paste the code here', 'fake-code')
        ->expectsOutputToContain('Token saved')
        ->assertExitCode(0);

    expect(file_exists($tokenFile))->toBeTrue();
    $saved = json_decode(file_get_contents($tokenFile), true);
    expect($saved['refresh_token'])->toBe('fake-refresh');

    unlink($clientFile);
    unlink($tokenFile);
});
