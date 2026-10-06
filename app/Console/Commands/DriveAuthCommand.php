<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Gmail;
use Illuminate\Console\Command;

class DriveAuthCommand extends Command
{
    protected $signature = 'vigil:drive-auth';

    protected $description = 'One-time OAuth setup: authorise Vigil Engine to access Google Drive as the folder owner.';

    public function __construct(private readonly Client $client)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $clientFile = config('services.google_drive.oauth_client');
        $tokenFile = config('services.google_drive.oauth_token');

        if (! file_exists($clientFile)) {
            $this->error("OAuth client file not found: {$clientFile}");
            $this->line('Download it from Google Cloud Console → APIs & Services → Credentials.');

            return self::FAILURE;
        }

        $this->client->setAuthConfig($clientFile);
        $this->client->addScope(Drive::DRIVE);
        $this->client->addScope(Gmail::GMAIL_MODIFY);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('consent');

        $authUrl = $this->client->createAuthUrl();

        $this->info('Open this URL in your browser, logged in as zura.colliers.bypass@gmail.com:');
        $this->line('');
        $this->line($authUrl);
        $this->line('');
        $this->info('After you click Allow, the browser will try to open localhost and show an error.');
        $this->info('That is normal. Copy the "code" value from the URL in the address bar.');
        $this->line('');

        $code = $this->ask('Paste the code here');

        if (blank($code)) {
            $this->error('No code entered.');

            return self::FAILURE;
        }

        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            $this->error('Google returned an error: '.($token['error_description'] ?? $token['error']));

            return self::FAILURE;
        }

        file_put_contents($tokenFile, json_encode($token, JSON_PRETTY_PRINT));
        $this->info("✅ Token saved to: {$tokenFile}");
        $this->line('The engine will now refresh it automatically on every run.');

        return self::SUCCESS;
    }
}
