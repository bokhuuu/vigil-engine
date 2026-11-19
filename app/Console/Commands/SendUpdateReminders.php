<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SendUpdateReminders extends Command
{
    protected $signature = 'vigil:send-update-reminders';

    protected $description = 'Send განახლებები view link to inspector teams 15 min after first submission today';

    public function handle(): void
    {
        if (! config('vigil.telegram.notifications_enabled')) {
            $this->info('Notifications disabled — skipping.');

            return;
        }

        $telegram = new TelegramService;
        $updateViews = config('vigil.shared_views.updates');

        // Check cache first — only fetch Airtable if something is pending
        $pending = [];
        foreach ($updateViews as $teamName => $viewLink) {
            $cacheKey = 'update_reminder_'.date('Y-m-d').'_'.md5($teamName);
            $cached = Cache::get($cacheKey);

            if (! $cached) {
                continue;
            }

            $scheduledAt = Carbon::parse($cached['scheduled_at']);

            if (Carbon::now()->lt($scheduledAt->addMinutes(15))) {
                $this->info("Skipping {$teamName} — less than 15 min passed");

                continue;
            }

            $pending[$teamName] = ['viewLink' => $viewLink, 'cacheKey' => $cacheKey];
        }

        if (empty($pending)) {
            $this->info('No pending reminders.');

            return;
        }

        // Only fetch teams from Airtable when there's actual work to do
        $airtable = new AirtableService(new AirtableClient);
        $teams = $airtable->getTeams();
        $teamByName = [];
        foreach ($teams as $team) {
            $name = $team['fields']['👤 სახელი'] ?? null;
            if ($name) {
                $teamByName[$name] = $team;
            }
        }

        foreach ($pending as $teamName => $data) {
            $viewLink = $data['viewLink'];
            $cacheKey = $data['cacheKey'];

            // Build message
            $message = "📋 <b>დღევანდელი განახლებები</b>\n\n"
                ."👥 {$teamName}\n\n"
                ."🔗 <a href=\"{$viewLink}\">იხილეთ გაგზავნილი განახლებები</a>";

            // Get telegram IDs — same pattern as act-alert inspector fallback
            $teamRecord = $teamByName[$teamName] ?? null;
            $recipients = [];

            if ($teamRecord) {
                $tid = $teamRecord['fields']['✈️ Telegram'] ?? null;
                $tid2 = $teamRecord['fields']['✈️ Telegram 2'] ?? null;
                if ($tid) {
                    $recipients[] = $tid;
                }
                if ($tid2) {
                    $recipients[] = $tid2;
                }
            }

            if (! empty($recipients)) {
                foreach ($recipients as $recipient) {
                    $telegram->send($recipient, $message);
                }
                $telegram->sendToMe($message);
                $this->info("Sent update reminder to {$teamName}");
            } else {
                $telegram->sendToMe('⚠️ '.e($teamName).' — Telegram არ აქვს (განახლებების შეხსენება)');
                $this->warn("No Telegram for {$teamName}");
            }

            // Clear cache — don't send again today
            Cache::forget($cacheKey);
            Log::info("Update reminder sent and cleared for {$teamName}");
        }

        $this->info('Done.');
    }
}
