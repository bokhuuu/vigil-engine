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

            // Group acts by sector, sorted by ACT number within each sector
            $bySectorActs = [];
            foreach ($acts as $act) {
                $sectorIds = $act['fields']['📍სექტორი'] ?? [];
                $sectorName = ! empty($sectorIds) ? ($sectorById[$sectorIds[0]] ?? '—') : '—';
                $bySectorActs[$sectorName][] = $act;
            }
            ksort($bySectorActs);

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
                } elseif ($teamName === 'ხარჯთაღმრიცხველები') {
                    $recipients = $roleMembers['ხარჯთაღმრიცხველი'] ?? [];
                } elseif ($telegramId) {
                    $recipients = [$telegramId];
                }
            }

            // Send one message per sector
            $sendMessage = function (string $msg) use ($telegram, $recipients, $teamName) {
                if (! empty($recipients)) {
                    foreach ($recipients as $recipient) {
                        $lines = explode("\n", $msg);
                        $chunk = '';
                        foreach ($lines as $line) {
                            if (mb_strlen($chunk.$line."\n") > 4000) {
                                $telegram->send($recipient, $chunk);
                                $chunk = '';
                            }
                            $chunk .= $line."\n";
                        }
                        if (! empty(trim($chunk))) {
                            $telegram->send($recipient, $chunk);
                        }
                    }
                } else {
                    $fullMsg = "⚠️ {$teamName} — Telegram არ აქვს.\n\n".$msg;
                    $lines = explode("\n", $fullMsg);
                    $chunk = '';
                    foreach ($lines as $line) {
                        if (mb_strlen($chunk.$line."\n") > 4000) {
                            $telegram->sendToMe($chunk);
                            $chunk = '';
                        }
                        $chunk .= $line."\n";
                    }
                    if (! empty(trim($chunk))) {
                        $telegram->sendToMe($chunk);
                    }
                }
            };

            foreach ($bySectorActs as $sectorName => $sectorActs) {
                // Sort by ACT number within sector
                usort($sectorActs, fn ($a, $b) => strnatcmp(
                    $a['fields']['🔖 აქტის ნომერი'] ?? '',
                    $b['fields']['🔖 აქტის ნომერი'] ?? ''
                ));

                $message = "👥 {$teamName} — {$sectorName}:\n\n";
                foreach ($sectorActs as $act) {
                    $fields = $act['fields'];
                    $number = $fields['🔖 აქტის ნომერი'] ?? '—';
                    $actUrl = $airtable->recordUrl(config('vigil.airtable.tables.acts'), $act['id']);
                    $message .= "📋 <a href=\"{$actUrl}\">აქტი {$number}</a>\n";
                }
                $sendMessage($message);
            }

            if (! empty($recipients)) {
                $this->info("Sent to {$teamName} (".count($recipients).' recipients)');
            } else {
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

        // Sort — inspectors first (by role), then others
        $inspectors = [];
        $others = [];
        foreach ($groupByTeam as $team => $cnt) {
            $teamRecord = $teamByName[$team] ?? null;
            $role = $teamRecord['fields']['🏷️ როლი'] ?? null;
            if ($role === 'ინსპექტორი') {
                $inspectors[$team] = $cnt;
            } else {
                $others[$team] = $cnt;
            }
        }
        $ordered = array_merge($inspectors, $others);
        $total = $count;

        $groupMessage = "🛡️ <b>არასრული აქტები</b>\n"
            ."სულ: {$total}\n\n";
        foreach ($ordered as $team => $cnt) {
            $groupMessage .= "👥 {$team}: {$cnt}\n";
        }
        $groupMessage .= "\n📩 თითოეულ გუნდს გაეგზავნა დეტალური სია პირადად.";

        $telegram->sendToGroup($groupMessage);

        $this->info("Done. {$count} incomplete ACTs processed.");
    }
}
