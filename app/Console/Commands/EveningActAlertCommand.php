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

        // Build sector name to team name map
        $sectorTeam = [];
        foreach ($sectors as $sector) {
            $sName = $sector['fields']['🔖 სახელი'] ?? '—';
            $teamIds = $sector['fields']['👥 გუნდები'] ?? [];
            $sectorTeam[$sName] = ! empty($teamIds) ? ($teamById[$teamIds[0]] ?? '—') : '—';
        }

        // Group by responsible team
        $byTeam = [];
        foreach ($incomplete as $record) {
            $responsible = $record['fields']['👤პასუხისმგებელი აქტზე'] ?? [];
            foreach ($responsible as $team) {
                $byTeam[$team][] = $record;
            }
        }

        // Shared view links
        $sharedViews = config('vigil.shared_views.teams');
        $sectorsLink = config('vigil.shared_views.sectors');

        // Helper to send chunked message
        $sendChunked = function (string $msg, callable $sendFn) {
            $lines = explode("\n", $msg);
            $chunk = '';
            foreach ($lines as $line) {
                if (mb_strlen($chunk.$line."\n") > 4000) {
                    $sendFn($chunk);
                    $chunk = '';
                }
                $chunk .= $line."\n";
            }
            if (! empty(trim($chunk))) {
                $sendFn($chunk);
            }
        };

        // Individual alerts per team
        foreach ($byTeam as $teamName => $acts) {
            $teamCount = count($acts);

            // Determine linked team name for inspectors
            $linkedTeamName = null;
            if ($teamName === 'ინსპექტორები') {
                foreach ($acts as $act) {
                    $linkedIds = $act['fields']['👥 გუნდი'] ?? [];
                    if (! empty($linkedIds)) {
                        $linkedTeamName = $teamById[$linkedIds[0]] ?? null;
                        break;
                    }
                }
            }

            $displayName = $linkedTeamName ?? $teamName;
            $viewLink = $sharedViews[$displayName] ?? ($sharedViews[$teamName] ?? null);
            $isInspector = $teamName === 'ინსპექტორები';

            // Build message
            $message = "👥 {$displayName}\n";
            $message .= "⏳ არასრული აქტები: {$teamCount}\n";
            if ($viewLink) {
                $message .= "🔗 <a href=\"{$viewLink}\">იხილეთ სია</a>\n";
            }
            if ($isInspector && $sectorsLink) {
                $message .= "\n📍 სექტორების სტატუსი\n";
                $message .= "🔗 <a href=\"{$sectorsLink}\">იხილეთ სია</a>\n";
            }

            // Determine recipients
            $recipients = [];
            foreach ($acts as $act) {
                $linkedTeamIds = $act['fields']['👥 გუნდი'] ?? [];
                foreach ($linkedTeamIds as $linkedId) {
                    foreach ($teams as $team) {
                        if ($team['id'] === $linkedId) {
                            $role = $team['fields']['🏷️ როლი'] ?? null;
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

            // Fallback to role-based
            if (empty($recipients)) {
                if ($teamName === 'ამზომველები') {
                    $recipients = $roleMembers['ამზომველი'] ?? [];
                } elseif ($teamName === 'აგრონომები') {
                    $recipients = $roleMembers['აგრონომი'] ?? [];
                } elseif ($teamName === 'ხარჯთაღმრიცხველები') {
                    $recipients = $roleMembers['ხარჯთაღმრიცხველი'] ?? [];
                } else {
                    $teamRecord = $teamByName[$teamName] ?? null;
                    $telegramId = $teamRecord['fields']['✈️ Telegram'] ?? null;
                    if ($telegramId) {
                        $recipients = [$telegramId];
                    }
                }
            }

            if (! empty($recipients)) {
                foreach ($recipients as $recipient) {
                    $sendChunked($message, fn ($chunk) => $telegram->send($recipient, $chunk));
                }
                // Forward copy to Zura for monitoring
                $sendChunked($message, fn ($chunk) => $telegram->sendToMe($chunk));
                $this->info("Sent to {$displayName} (".count($recipients).' recipients)');
            } else {
                $sendChunked("⚠️ {$displayName} — Telegram არ აქვს.\n\n".$message, fn ($chunk) => $telegram->sendToMe($chunk));
                $this->warn("No Telegram for {$displayName} — forwarded to Zura");
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

        // Sort — inspectors first, then others
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

        $sendChunked($groupMessage, fn ($chunk) => $telegram->sendToGroup($chunk));

        // Manager message
        $allActs = $airtable->getAllActs() ?? $incomplete;
        $doneCount = count(array_filter($allActs, fn ($a) => ($a['fields']['🚦სტატუსი'] ?? '') === '✅ დასრულებული'));
        $incompleteCount = count(array_filter($allActs, fn ($a) => ($a['fields']['🚦სტატუსი'] ?? '') === '⏳ მიმდინარე'));
        $totalCount = count($allActs);

        $managerMessage = "📋 აქტები\n\n"
            ."✅ დასრულებული: {$doneCount}\n"
            .'🔗 <a href="'.config('vigil.shared_views.managers.acts_complete')."\">იხილეთ სია</a>\n\n"
            ."⏳ მიმდინარე: {$incompleteCount}\n"
            .'🔗 <a href="'.config('vigil.shared_views.managers.acts_incomplete')."\">იხილეთ სია</a>\n\n"
            ."📍 სექტორების სტატუსი\n"
            .'🔗 <a href="'.config('vigil.shared_views.sectors')."\">იხილეთ სია</a>\n\n"
            ."სულ: {$totalCount}";

        $managers = $airtable->getManagers();
        foreach ($managers as $manager) {
            $telegramId = $manager['fields']['✈️ Telegram'] ?? null;
            if ($telegramId) {
                $sendChunked($managerMessage, fn ($chunk) => $telegram->send($telegramId, $chunk));
            }
        }
        // Forward manager message to Zura
        $sendChunked("📋 მენეჯერების შეტყობინება:\n".$managerMessage, fn ($chunk) => $telegram->sendToMe($chunk));

        // Forward manager message to Zura for monitoring
        $sendChunked($managerMessage, fn ($chunk) => $telegram->sendToMe($chunk));
        $this->info("Done. {$count} incomplete ACTs processed.");
    }
}
