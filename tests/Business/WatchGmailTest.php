<?php

declare(strict_types=1);

namespace Tests\Business;

use App\Business\Models\Business;
use App\Console\Commands\Business\WatchGmail;
use App\Services\GmailService;
use App\Services\GoogleDriveService;
use App\Services\TelegramService;
use Google\Service\Gmail\Message;

class WatchGmailTest extends BusinessTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $clientPath = config('services.google_drive.oauth_client');
        $tokenPath = config('services.google_drive.oauth_token');

        if ($clientPath) {
            @mkdir(dirname($clientPath), 0755, true);
            file_put_contents($clientPath, json_encode([
                'installed' => [
                    'client_id' => 'fake.apps.googleusercontent.com',
                    'client_secret' => 'fake-secret',
                    'redirect_uris' => ['urn:ietf:wg:oauth:2.0:oob'],
                    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
                    'token_uri' => 'https://oauth2.googleapis.com/token',
                    'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
                ],
            ]));
        }

        if ($tokenPath) {
            @mkdir(dirname($tokenPath), 0755, true);
            file_put_contents($tokenPath, json_encode([
                'access_token' => 'fake-access-token',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'fake-refresh-token',
                'created' => time() + 99999,
            ]));
        }
    }

    public function test_matched_email_creates_correspondence_and_sends_telegram(): void
    {
        $business = Business::factory()->create(['name' => 'შპს ტესტი', 'registration_code' => '123456789']);

        $message = \Mockery::mock(Message::class);
        $message->shouldReceive('getId')->andReturn('msg-001');

        $gmail = \Mockery::mock(GmailService::class);
        $gmail->shouldReceive('getOrCreateLabel')->once()->andReturn('label-id');
        $gmail->shouldReceive('getUnreadMessages')->once()->andReturn([$message]);
        $gmail->shouldReceive('getMessage')->once()->andReturn($message);
        $gmail->shouldReceive('getMessageSubject')->once()->andReturn('შპს ტესტი — პასუხი');
        $gmail->shouldReceive('getMessageFrom')->once()->andReturn('sender@example.com');
        $gmail->shouldReceive('getMessageBody')->once()->andReturn('123456789');
        $gmail->shouldReceive('applyLabel')->once();
        $gmail->shouldReceive('markAsRead')->once();
        $gmail->shouldReceive('getAttachments')->once()->andReturn([]);

        $telegram = \Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('sendToMe')->once();

        $drive = \Mockery::mock(GoogleDriveService::class);
        $command = new WatchGmail($gmail, $telegram, $drive);
        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $this->artisan('business:watch-gmail')->assertSuccessful();

        $this->assertDatabaseHas('business_correspondence', [
            'business_id' => $business->id,
            'round' => 1,
            'status' => 'pending',
            'notes' => 'შპს ტესტი — პასუხი',
        ], 'business');
    }

    public function test_unmatched_email_sends_telegram_and_marks_read(): void
    {
        $message = \Mockery::mock(Message::class);
        $message->shouldReceive('getId')->andReturn('msg-002');

        $gmail = \Mockery::mock(GmailService::class);
        $gmail->shouldReceive('getOrCreateLabel')->once()->andReturn('label-id');
        $gmail->shouldReceive('getUnreadMessages')->once()->andReturn([$message]);
        $gmail->shouldReceive('getMessage')->once()->andReturn($message);
        $gmail->shouldReceive('getMessageSubject')->once()->andReturn('უცხო ელ-ფოსტა');
        $gmail->shouldReceive('getMessageFrom')->once()->andReturn('unknown@example.com');
        $gmail->shouldReceive('getMessageBody')->once()->andReturn('no match here');
        $gmail->shouldReceive('markAsRead')->once();

        $telegram = \Mockery::mock(TelegramService::class);
        $telegram->shouldReceive('sendToMe')->once();

        $drive = \Mockery::mock(GoogleDriveService::class);
        $command = new WatchGmail($gmail, $telegram, $drive);
        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);

        $this->artisan('business:watch-gmail')->assertSuccessful();

        $this->assertDatabaseCount('business_correspondence', 0, 'business');
    }
}
