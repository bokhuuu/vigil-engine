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

            $message = "🛡️ <b>Vigil Alert</b>\n"
                ."გუნდი: <b>{$teamName}</b> | ".count($acts)." აქტი\n\n";

            foreach ($acts as $act) {
                $fields = $act['fields'];
                $number = $fields['🔖 აქტის ნომერი'] ?? '—';
                $sector = implode(', ', $fields['📍სექტორი'] ?? []);
                $actUrl = $airtable->recordUrl(config('vigil.airtable.tables.acts'), $act['id']);

                $message .= "📋 <b><a href=\"{$actUrl}\">აქტი {$number}</a></b> | სექტ: {$sector}\n\n";

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

        // Group chat — per ACT with responsible teams
        $groupMessage = "🛡️ <b>Vigil — არასრული აქტები</b>\n\n";

        $actTeams = [];
        foreach ($incomplete as $act) {
            $responsible = $act['fields']['👤პასუხისმგებელი აქტზე'] ?? [];
            if (! empty($responsible)) {
                $actTeams[] = [
                    'act' => $act,
                    'teams' => $responsible,
                ];
            }
        }

        foreach ($actTeams as $data) {
            $act = $data['act'];
            $fields = $act['fields'];
            $number = $fields['🔖 აქტის ნომერი'] ?? '—';
            $sector = implode(', ', $fields['📍სექტორი'] ?? []);
            $actUrl = $airtable->recordUrl(config('vigil.airtable.tables.acts'), $act['id']);
            $teams = implode(', ', $data['teams']);

            $groupMessage .= "📋 <a href=\"{$actUrl}\">{$number}</a> | სექტ: {$sector}\n";
            $groupMessage .= "👤 {$teams}\n\n";
        }

        $groupMessage .= "სულ: {$count}";
        $telegram->sendToGroup($groupMessage);

        // Summary to Zura
        $telegram->sendToMe("🛡️ <b>Act Alert გაგზავნილია</b>\nსულ არასრული: {$count}");

        $this->info("Done. {$count} incomplete ACTs processed.");
    }
}
