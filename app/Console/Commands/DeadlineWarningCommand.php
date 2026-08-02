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
        $actWarnings = [];

        foreach ($deadlines as $record) {
            $fields = $record['fields'];
            $promisedRaw = $fields['📅 დაპირებული თარიღი'] ?? null;

            if (! $promisedRaw) {
                continue;
            }

            $promised = Carbon::parse($promisedRaw)->startOfDay();
            $daysLeft = (int) $today->diffInDays($promised, false);
            $title = $fields['🔖 სათაური'] ?? '—';
            $url = $airtable->recordUrl(config('vigil.airtable.tables.deadlines'), $record['id']);

            $warning = null;

            if ($daysLeft < 0) {
                $airtable->markDeadlineOverdue($record['id']);
                $warning = "❌ ვადა გავიდა\n📋 <a href=\"{$url}\">{$title}</a>";
            } elseif ($daysLeft === 0) {
                $warning = "🚨 დღეს ვადა\n📋 <a href=\"{$url}\">{$title}</a>";
            } elseif ($daysLeft === 1) {
                $warning = "🔴 ხვალ ვადა\n📋 <a href=\"{$url}\">{$title}</a>";
            } elseif ($daysLeft === 2) {
                $warning = "⚠️ 2 დღე რჩება\n📋 <a href=\"{$url}\">{$title}</a>";
            }

            if ($warning) {
                $actWarnings[] = $warning;

                // Send directly to responsible team
                $teamRecordIds = $fields['👥 გუნდი'] ?? [];
                if (! empty($teamRecordIds)) {
                    $teams = $airtable->getTeams();
                    foreach ($teams as $team) {
                        if (in_array($team['id'], $teamRecordIds)) {
                            $telegramId = $team['fields']['✈️ Telegram'] ?? null;
                            if ($telegramId) {
                                $telegram->send($telegramId, $warning);
                            }
                        }
                    }
                }
            }
        }

        // ── Correspondence promised dates ─────────────────────────────────
        $corrList = $airtable->getCorrespondenceWithPromisedDates();
        $corrWarnings = [];

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
                $corrWarnings[] = "❌ ვადა გავიდა\n📋 <a href=\"{$url}\">{$subject}</a>";
            } elseif ($daysLeft === 0) {
                $corrWarnings[] = "🚨 დღეს ვადა\n📋 <a href=\"{$url}\">{$subject}</a>";
            } elseif ($daysLeft === 1) {
                $corrWarnings[] = "🔴 ხვალ ვადა\n📋 <a href=\"{$url}\">{$subject}</a>";
            } elseif ($daysLeft === 2) {
                $corrWarnings[] = "⚠️ 2 დღე რჩება\n📋 <a href=\"{$url}\">{$subject}</a>";
            }
        }

        // ── Send to Zura ──────────────────────────────────────────────────
        $allWarnings = array_merge($actWarnings, $corrWarnings);

        if (empty($allWarnings)) {
            $telegram->sendToMe('✅ ვადები — პრობლემა არ არის.');
            $this->info('No deadline warnings.');

            return;
        }

        $message = "<b>ვადების შეხსენება</b>\n\n"
            .implode("\n\n", $allWarnings);

        $telegram->sendToMe($message);

        // Send correspondence warnings to managers
        if (! empty($corrWarnings)) {
            $corrMessage = "<b>ვადების შეხსენება</b>\n\n"
                .implode("\n\n", $corrWarnings);

            $managers = $airtable->getManagers();
            foreach ($managers as $manager) {
                $telegramId = $manager['fields']['✈️ Telegram'] ?? null;
                if ($telegramId) {
                    $telegram->send($telegramId, $corrMessage);
                }
            }
        }

        $this->info('Deadline warnings sent: '.count($allWarnings));
    }
}
