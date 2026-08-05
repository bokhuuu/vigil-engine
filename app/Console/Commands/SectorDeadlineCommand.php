<?php

namespace App\Console\Commands;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SectorDeadlineCommand extends Command
{
    protected $signature = 'vigil:sector-deadline';

    protected $description = 'Warn teams and managers about sector completion deadlines';

    public function handle(): void
    {
        $airtable = new AirtableService(new AirtableClient);
        $telegram = new TelegramService;

        $today = Carbon::now()->startOfDay();

        $sectors = $airtable->getSectors();
        $teams = $airtable->getTeams();

        // Build team map by record ID
        $teamById = [];
        foreach ($teams as $team) {
            $teamById[$team['id']] = $team;
        }

        // Get managers
        $managers = $airtable->getManagers();

        foreach ($sectors as $sector) {
            $fields = $sector['fields'];
            $name = $fields['🔖 სახელი'] ?? '—';
            $deadline = $fields['📅 ვადა'] ?? null;

            if (empty($deadline)) {
                continue;
            }

            $deadlineDate = Carbon::parse($deadline)->startOfDay();
            $diff = (int) $today->diffInDays($deadlineDate, false);
            // $diff > 0 = days remaining, $diff < 0 = days overdue, $diff = 0 = today

            // Skip if deadline is in future but not yet tomorrow
            if ($diff > 0) {
                // Days remaining — notify team daily
                $teamIds = $fields['👥 გუნდი'] ?? [];
                $teamRecord = ! empty($teamIds) ? ($teamById[$teamIds[0]] ?? null) : null;
                $telegramId = $teamRecord['fields']['✈️ Telegram'] ?? null;
                $teamName = $teamRecord['fields']['👤 სახელი'] ?? '—';

                $message = "⏳ <b>სექტორის ვადის შეხსენება</b>\n\n"
                    ."📍 სექტორი: <b>{$name}</b>\n"
                    ."👥 გუნდი: <b>{$teamName}</b>\n"
                    ."📅 ვადა: {$deadlineDate->format('d/m/Y')}\n"
                    ."⏰ რჩება: <b>{$diff} დღე</b>";

                if ($telegramId) {
                    $telegram->send($telegramId, $message);
                } else {
                    $telegram->sendToMe("⚠️ {$teamName} — Telegram არ აქვს.\n\n".$message);
                }

                $this->info("Warned team {$teamName} — {$diff} days left for {$name}");
            } elseif ($diff === 0) {
                // Today is deadline — notify team
                $teamIds = $fields['👥 გუნდი'] ?? [];
                $teamRecord = ! empty($teamIds) ? ($teamById[$teamIds[0]] ?? null) : null;
                $telegramId = $teamRecord['fields']['✈️ Telegram'] ?? null;
                $teamName = $teamRecord['fields']['👤 სახელი'] ?? '—';

                $message = "🚨 <b>დღეს სექტორის ვადა!</b>\n\n"
                    ."📍 სექტორი: <b>{$name}</b>\n"
                    ."👥 გუნდი: <b>{$teamName}</b>\n"
                    ."📅 ვადა: {$deadlineDate->format('d/m/Y')}";

                if ($telegramId) {
                    $telegram->send($telegramId, $message);
                } else {
                    $telegram->sendToMe("⚠️ {$teamName} — Telegram არ აქვს.\n\n".$message);
                }

                $this->info("Deadline today for {$name}");
            } else {
                // Overdue — notify team + managers daily
                $overdueDays = abs($diff);

                $teamIds = $fields['👥 გუნდი'] ?? [];
                $teamRecord = ! empty($teamIds) ? ($teamById[$teamIds[0]] ?? null) : null;
                $telegramId = $teamRecord['fields']['✈️ Telegram'] ?? null;
                $teamName = $teamRecord['fields']['👤 სახელი'] ?? '—';

                $message = "🔴 <b>სექტორის ვადა გადაცილებულია!</b>\n\n"
                    ."📍 სექტორი: <b>{$name}</b>\n"
                    ."👥 გუნდი: <b>{$teamName}</b>\n"
                    ."📅 ვადა იყო: {$deadlineDate->format('d/m/Y')}\n"
                    ."❌ გადაცილება: <b>{$overdueDays} დღე</b>";

                // Notify team
                if ($telegramId) {
                    $telegram->send($telegramId, $message);
                } else {
                    $telegram->sendToMe("⚠️ {$teamName} — Telegram არ აქვს.\n\n".$message);
                }

                // Notify managers
                foreach ($managers as $manager) {
                    $managerTelegram = $manager['fields']['✈️ Telegram'] ?? null;
                    if ($managerTelegram) {
                        $telegram->send($managerTelegram, $message);
                    }
                }

                $this->info("Overdue {$overdueDays} days — notified team + managers for {$name}");
            }
        }

        $this->info('Sector deadline check complete.');
    }
}
