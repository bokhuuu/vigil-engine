<?php

namespace App\Console\Commands;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeadlineWarningCommand extends Command
{
    protected $signature = 'vigil:deadline-warning';

    protected $description = 'Send deadline warnings for ACT commitments and correspondence';

    public function handle(): void
    {
        $airtable = new AirtableService(new AirtableClient);
        $telegram = new TelegramService;
        $today = Carbon::now()->startOfDay();

        $this->info('Checking deadlines...');

        // ── ACT commitment deadlines ──────────────────────────────────────
        $deadlines = $airtable->getUpcomingDeadlines();
        $warnings = [];

        foreach ($deadlines as $record) {
            $fields = $record['fields'];
            $promisedRaw = $fields['📅 დაპირებული თარიღი'] ?? null;

            if (! $promisedRaw) {
                continue;
            }

            $promised = Carbon::parse($promisedRaw)->startOfDay();
            $daysLeft = (int) $today->diffInDays($promised, false);
            $title = $fields['🔖 სათაური'] ?? '—';
            $notes = $fields['📝 შენიშვნა'] ?? '';
            $url = $airtable->recordUrl(config('vigil.airtable.tables.deadlines'), $record['id']);

            if ($daysLeft < 0) {
                $airtable->markDeadlineOverdue($record['id']);
                $warnings[] = "❌ <b>ვადა გავიდა:</b> <a href=\"{$url}\">{$title}</a>\n   {$notes}";
            } elseif ($daysLeft === 0) {
                $warnings[] = "🚨 <b>დღეს ვადა:</b> <a href=\"{$url}\">{$title}</a>\n   {$notes}";
            } elseif ($daysLeft === 1) {
                $warnings[] = "🔴 <b>ხვალ ვადა:</b> <a href=\"{$url}\">{$title}</a>\n   {$notes}";
            } elseif ($daysLeft === 2) {
                $warnings[] = "⚠️ <b>2 დღე რჩება:</b> <a href=\"{$url}\">{$title}</a>\n   {$notes}";
            }

            // Send directly to responsible team
            $teamRecordIds = $fields['👥 გუნდი'] ?? [];
            if (! empty($teamRecordIds) && ! empty($warnings)) {
                $teams = $airtable->getTeams();
                foreach ($teams as $team) {
                    if (in_array($team['id'], $teamRecordIds)) {
                        $telegramId = $team['fields']['✈️ Telegram'] ?? null;
                        if ($telegramId) {
                            $telegram->send($telegramId, end($warnings));
                        }
                    }
                }
            }
        }

        // ── Correspondence promised dates ─────────────────────────────────
        $corrList = $airtable->getCorrespondenceWithPromisedDates();

        foreach ($corrList as $record) {
            $fields = $record['fields'];
            $promisedRaw = $fields['📅 დაპირებული თარიღი'] ?? null;

            if (! $promisedRaw) {
                continue;
            }

            $promised = Carbon::parse($promisedRaw)->startOfDay();
            $daysLeft = (int) $today->diffInDays($promised, false);
            $subject = $fields['📋 საკითხი'] ?? '—';
            $url = $airtable->recordUrl(config('vigil.airtable.tables.correspondence'), $record['id']);

            if ($daysLeft < 0) {
                $warnings[] = "❌ <b>კორ. ვადა გავიდა:</b> <a href=\"{$url}\">{$subject}</a>";
            } elseif ($daysLeft === 0) {
                $warnings[] = "🚨 <b>კორ. დღეს ვადა:</b> <a href=\"{$url}\">{$subject}</a>";
            } elseif ($daysLeft === 1) {
                $warnings[] = "🔴 <b>კორ. ხვალ ვადა:</b> <a href=\"{$url}\">{$subject}</a>";
            } elseif ($daysLeft === 2) {
                $warnings[] = "⚠️ <b>კორ. 2 დღე რჩება:</b> <a href=\"{$url}\">{$subject}</a>";
            }
        }

        // ── Send to Zura ──────────────────────────────────────────────────
        if (empty($warnings)) {
            $telegram->sendToMe('✅ ვადები — პრობლემა არ არის.');
            $this->info('No deadline warnings.');

            return;
        }

        $message = "⏰ <b>ვადების შეხსენება</b>\n\n"
            .implode("\n\n", $warnings);

        $telegram->sendToMe($message);

        // Send correspondence warnings to managers
        $managers = $airtable->getManagers();
        foreach ($managers as $manager) {
            $telegramId = $manager['fields']['✈️ Telegram'] ?? null;
            if ($telegramId) {
                $telegram->send($telegramId, $message);
            }
        }

        $this->info('Deadline warnings sent: '.count($warnings));
    }
}
