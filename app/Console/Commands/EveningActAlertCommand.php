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

                $message .= "📋 <a href=\"{$actUrl}\">{$number}</a>\n";
                $message .= "📍 სექტ: {$sector}\n";
                if ($missing) {
                    $message .= "\n❌ აკლია:\n{$missing}\n";
                }
                $message .= "\n";
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
            $sectorIds = $fields['📍სექტორი'] ?? [];
            $sector = implode(', ', array_map(fn ($id) => $sectorById[$id] ?? '—', $sectorIds));
            $missing = $fields['❗რა აკლია აქტს'] ?? null;
            $actUrl = $airtable->recordUrl(config('vigil.airtable.tables.acts'), $actId);

            $groupMessage .= "📋 <a href=\"{$actUrl}\">{$number}</a>\n";
            $groupMessage .= "📍 სექტ: {$sector}\n";
            if ($missing) {
                $groupMessage .= "\n❌ აკლია:\n{$missing}\n";
            }
            $groupMessage .= "\n";
        }

        $telegram->sendToGroup($groupMessage);

        $this->info("Done. {$count} incomplete ACTs processed.");
    }
}
