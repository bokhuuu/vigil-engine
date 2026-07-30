<?php

namespace App\Console\Commands;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class EveningActAlertCommand extends Command
{
    protected $signature = 'vigil:act-alert';

    protected $description = 'Send incomplete ACT alerts to responsible teams';

    public function handle(): void
    {
        $airtable = new AirtableService(new AirtableClient);
        $telegram = new TelegramService;

        $this->info('Fetching incomplete ACTs...');
        $incomplete = $airtable->getIncompleteActs();

        if (empty($incomplete)) {
            $this->info('No incomplete ACTs.');
            $telegram->sendToMe('✅ არასრული აქტი არ არის.');
            $telegram->sendToGroup('✅ არასრული აქტი არ არის.');

            return;
        }

        $count = count($incomplete);

        // Load teams
        $teams = $airtable->getTeams();
        $teamByName = [];
        foreach ($teams as $team) {
            $name = $team['fields']['👤 სახელი'] ?? null;
            if ($name) {
                $teamByName[$name] = $team;
            }
        }

        // Group by responsible team
        $byTeam = [];
        foreach ($incomplete as $record) {
            $responsible = $record['fields']['👤პასუხისმგებელი აქტზე'] ?? [];
            foreach ($responsible as $team) {
                $byTeam[$team][] = $record;
            }
        }

        // Individual alerts per team
        foreach ($byTeam as $teamName => $acts) {
            $teamRecord = $teamByName[$teamName] ?? null;
            $telegramId = $teamRecord['fields']['✈️ Telegram'] ?? null;

            $message = "<b>შეხსენება</b>\n"
                ."გუნდი: <b>{$teamName}</b>\n\n";

            foreach ($acts as $act) {
                $fields = $act['fields'];
                $number = $fields['🔖 აქტის ნომერი'] ?? '—';
                $sector = implode(', ', $fields['📍სექტორი'] ?? []);
                $missing = $fields['❗რა აკლია აქტს'] ?? null;
                $actUrl = $airtable->recordUrl(config('vigil.airtable.tables.acts'), $act['id']);

                $message .= "🔗 <a href=\"{$actUrl}\">აქტი {$number}</a> | სექტ: {$sector}\n";
                if ($missing) {
                    $message .= "❌ {$missing}\n";
                }
                $message .= "\n";

                $airtable->logNotification(
                    "აქტი {$number} — {$teamName}",
                    'Telegram',
                    $act['id'],
                    $teamRecord['id'] ?? null
                );

                $airtable->markActNotified($act['id']);
            }

            $message .= 'გთხოვთ შეავსოთ და წარადგინოთ.';

            if ($telegramId) {
                $telegram->send($telegramId, $message);
                $this->info("Sent to {$teamName} ({$telegramId})");
            } else {
                $telegram->sendToMe("⚠️ {$teamName} — Telegram არ აქვს.\n\n".$message);
                $this->warn("No Telegram for {$teamName} — forwarded to Zura");
            }
        }

        // Group chat — count per team
        $groupMessage = "🛡️ <b>არასრული აქტები</b>\n\n";

        foreach ($byTeam as $teamName => $acts) {
            $groupMessage .= "{$teamName}: ".count($acts)."\n";
        }

        $telegram->sendToGroup($groupMessage);

        $this->info("Done. {$count} incomplete ACTs processed.");
    }
}
