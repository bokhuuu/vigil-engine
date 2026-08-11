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

        // Build sector name map by record ID
        $sectors = $airtable->getSectors();
        $sectorById = [];
        foreach ($sectors as $sector) {
            $sectorById[$sector['id']] = $sector['fields']['🔖 სახელი'] ?? '—';
        }

        // Build team name map by record ID
        $teamById = [];
        foreach ($teams as $team) {
            $teamById[$team['id']] = $team['fields']['👤 სახელი'] ?? '—';
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

            $message = "👥 {$teamName}\n\n";

            foreach ($acts as $act) {
                $fields = $act['fields'];
                $number = $fields['🔖 აქტის ნომერი'] ?? '—';
                $sectorIds = $fields['📍სექტორი'] ?? [];
                $sector = implode(', ', array_map(fn ($id) => $sectorById[$id] ?? '—', $sectorIds));
                $missing = $fields['❗რა აკლია აქტს'] ?? null;
                $actUrl = $airtable->recordUrl(config('vigil.airtable.tables.acts'), $act['id']);

                $message .= "📋 <b><a href=\"{$actUrl}\">აქტი {$number}</a></b>\n";
                if ($missing) {
                    $message .= "❗ რა აკლია: {$missing}\n";
                }
                $message .= "—————————————\n";
            }

            // Determine recipients — use linked გუნდი field for direct Telegram
            $recipients = [];
            foreach ($acts as $act) {
                $linkedTeamIds = $act['fields']['👥 გუნდი'] ?? [];
                foreach ($linkedTeamIds as $linkedId) {
                    foreach ($teams as $team) {
                        if ($team['id'] === $linkedId) {
                            $role = $team['fields']['🏷️ როლი'] ?? null;
                            // Only use if role matches current team context
                            $roleMatch = (
                                ($teamName === 'ინსპექტორები' && $role === 'ინსპექტორი') ||
                                ($teamName === 'ამზომველები' && $role === 'ამზომველი') ||
                                ($teamName === 'აგრონომები' && $role === 'აგრონომი') ||
                                ($teamName === 'ხარჯთაღმრიცხველები' && $role === 'ხარჯთაღმრიცხველი')
                            );
                            if ($roleMatch) {
                                $tid = $team['fields']['✈️ Telegram'] ?? null;
                                if ($tid && ! in_array($tid, $recipients)) {
                                    $recipients[] = $tid;
                                }
                            }
                        }
                    }
                }
            }

            // Fallback to role-based or name-based if no linked team
            if (empty($recipients)) {
                if ($teamName === 'ამზომველები') {
                    $recipients = $roleMembers['ამზომველი'] ?? [];
                } elseif ($teamName === 'აგრონომები') {
                    $recipients = $roleMembers['აგრონომი'] ?? [];
                } elseif ($telegramId) {
                    $recipients = [$telegramId];
                }
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

        // Group chat — counts by team
        $groupByTeam = [];
        foreach ($incomplete as $act) {
            $fields = $act['fields'];
            $responsible = $fields['👤პასუხისმგებელი აქტზე'] ?? [];
            if (empty($responsible)) {
                continue;
            }
            $linkedTeamIds = $fields['👥 გუნდი'] ?? [];

            if (in_array('ინსპექტორები', $responsible) && ! empty($linkedTeamIds)) {
                $linkedName = $teamById[$linkedTeamIds[0]] ?? null;
                if ($linkedName) {
                    if (! isset($groupByTeam[$linkedName])) {
                        $groupByTeam[$linkedName] = 0;
                    }
                    $groupByTeam[$linkedName]++;
                }
            }

            foreach ($responsible as $role) {
                if ($role !== 'ინსპექტორები') {
                    if (! isset($groupByTeam[$role])) {
                        $groupByTeam[$role] = 0;
                    }
                    $groupByTeam[$role]++;
                }
            }
        }

        $groupMessage = "🛡️ <b>არასრული აქტები</b>\n\n";
        foreach ($groupByTeam as $team => $cnt) {
            $groupMessage .= "👥 {$team}: {$cnt}\n";
        }
        $groupMessage .= "\n📩 თითოეულ გუნდს გაეგზავნა დეტალური სია პირადად.";

        $telegram->sendToGroup($groupMessage);

        $this->info("Done. {$count} incomplete ACTs processed.");
    }
}
