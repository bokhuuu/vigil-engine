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
        $roleMembers = [];
        foreach ($teams as $team) {
            $name = $team['fields']['👤 სახელი'] ?? null;
            $role = $team['fields']['🏷️ როლი'] ?? null;
            $telegramId = $team['fields']['✈️ Telegram'] ?? null;
            if ($name) {
                $teamByName[$name] = $team;
            }
            if ($role && $telegramId) {
                $roleMembers[$role][] = $telegramId;
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
                    $message .= "❌ აკლია: {$missing}\n";
                }
                $message .= "\n";

                $airtable->markActNotified($act['id']);
            }

            $message .= 'გთხოვთ შეავსოთ და წარადგინოთ.';

            // Determine recipients based on team name
            $recipients = [];

            if ($teamName === 'ამზომველები') {
                $recipients = $roleMembers['ამზომველი'] ?? [];
            } elseif ($teamName === 'აგრონომები') {
                $recipients = $roleMembers['აგრონომი'] ?? [];
            } elseif ($telegramId) {
                $recipients = [$telegramId];
            }

            if (! empty($recipients)) {
                foreach ($recipients as $recipient) {
                    $telegram->send($recipient, $message);
                }
                $this->info("Sent to {$teamName} (".count($recipients).' recipients)');
            } else {
                $telegram->sendToMe("⚠️ {$teamName} — Telegram არ აქვს.\n\n".$message);
                $this->warn("No Telegram for {$teamName} — forwarded to Zura");
            }
        }

        // Group chat — per ACT
        $groupMessage = "🛡️ <b>არასრული აქტები</b>\n\n";

        $shownActs = [];
        foreach ($incomplete as $act) {
            $actId = $act['id'];
            $fields = $act['fields'];
            $responsible = $fields['👤პასუხისმგებელი აქტზე'] ?? [];

            if (empty($responsible) || isset($shownActs[$actId])) {
                continue;
            }

            $shownActs[$actId] = true;
            $number = $fields['🔖 აქტის ნომერი'] ?? '—';
            $sector = implode(', ', $fields['📍სექტორი'] ?? []);
            $missing = $fields['❗რა აკლია აქტს'] ?? null;
            $actUrl = $airtable->recordUrl(config('vigil.airtable.tables.acts'), $actId);
            $teams = implode(', ', $responsible);

            $groupMessage .= "🔗 <a href=\"{$actUrl}\">{$number}</a> | სექტ: {$sector}\n";
            if ($missing) {
                $groupMessage .= "❌ აკლია: {$missing}\n";
            }
            $groupMessage .= "\n";
        }
        $telegram->sendToGroup($groupMessage);

        $this->info("Done. {$count} incomplete ACTs processed.");
    }
}
