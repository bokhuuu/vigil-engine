<?php

declare(strict_types=1);

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
            $telegramId2 = $team['fields']['✈️ Telegram 2'] ?? null;
            if ($name) {
                $teamByName[$name] = $team;
            }
            if ($role && $telegramId) {
                $roleMembers[$role][] = $telegramId;
            }
            if ($role && $telegramId2) {
                $roleMembers[$role][] = $telegramId2;
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

        // Shared view links
        $sharedViews = config('vigil.shared_views.teams');
        $sectorsLink = config('vigil.shared_views.sectors');

        // Helper to send chunked message
        $sendChunked = function (string $msg, callable $sendFn): void {
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

        // Group by responsible team — split inspectors by linked გუნდი
        $byTeam = [];
        foreach ($incomplete as $record) {
            $responsible = $record['fields']['👤პასუხისმგებელი აქტზე'] ?? [];
            foreach ($responsible as $team) {
                if ($team === 'ინსპექტორები') {
                    $linkedIds = $record['fields']['👥 გუნდი'] ?? [];
                    $linkedName = ! empty($linkedIds) ? ($teamById[$linkedIds[0]] ?? 'ინსპექტორები') : 'ინსპექტორები';
                    $byTeam['inspector_'.$linkedName][] = $record;
                } else {
                    $byTeam[$team][] = $record;
                }
            }
        }

        // Individual alerts per team
        foreach ($byTeam as $teamName => $acts) {
            $teamCount = count($acts);

            $isInspector = str_starts_with($teamName, 'inspector_');
            $displayName = $isInspector ? str_replace('inspector_', '', $teamName) : $teamName;
            $viewLink = $sharedViews[$displayName] ?? null;

            // Build message
            $message = "👥 <b>{$displayName}</b>\n\n";
            $message .= "⏳ არასრული აქტები: {$teamCount}\n";
            if ($viewLink) {
                $message .= "🔗 <a href=\"{$viewLink}\">იხილეთ სია</a>\n";
            }
            if ($isInspector) {
                $inspectorSectorLink = config('vigil.shared_views.inspector_sectors')[$displayName] ?? $sectorsLink;
                $message .= "\n📍 სექტორების სტატუსი\n";
                $message .= "🔗 <a href=\"{$inspectorSectorLink}\">იხილეთ სია</a>\n";
                $fullInfoLink = config('vigil.shared_views.inspector_full')[$displayName] ?? null;
                if ($fullInfoLink) {
                    $message .= "\n📊 სრული ინფო\n";
                    $message .= "🔗 <a href=\"{$fullInfoLink}\">გუნდის სტატისტიკა</a>\n";
                }
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
                                ($isInspector && $role === 'ინსპექტორი') ||
                                ($teamName === 'ამზომველები' && $role === 'ამზომველი') ||
                                ($teamName === 'აგრონომები' && $role === 'აგრონომი') ||
                                ($teamName === 'ხარჯთაღმრიცხველები' && $role === 'ხარჯთაღმრიცხველი')
                            );
                            if ($roleMatch) {
                                $tid = $team['fields']['✈️ Telegram'] ?? null;
                                $tid2 = $team['fields']['✈️ Telegram 2'] ?? null;
                                if ($tid && ! in_array($tid, $recipients)) {
                                    $recipients[] = $tid;
                                }
                                if ($tid2 && ! in_array($tid2, $recipients)) {
                                    $recipients[] = $tid2;
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
                } elseif ($isInspector) {
                    $teamRecord = $teamByName[$displayName] ?? null;
                    $tid = $teamRecord['fields']['✈️ Telegram'] ?? null;
                    $tid2 = $teamRecord['fields']['✈️ Telegram 2'] ?? null;
                    if ($tid) {
                        $recipients[] = $tid;
                    }
                    if ($tid2) {
                        $recipients[] = $tid2;
                    }
                }
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
        $groupMessage .= "\n🔗 <a href=\"{$sectorsLink}\">სექტორების სტატუსი</a>\n\n";

        $sendChunked($groupMessage, fn ($chunk) => $telegram->sendToGroup($chunk));

        // Manager message
        $doneCount = count($airtable->getDoneActs());
        $incompleteCount = $count;
        $totalCount = $doneCount + $incompleteCount;

        $managerMessage = "📋 აქტები\n\n"
            ."✅ დასრულებული: {$doneCount}\n"
            .'🔗 <a href="'.config('vigil.shared_views.managers.acts_complete')."\">იხილეთ სია</a>\n\n"
            ."⏳ მიმდინარე: {$incompleteCount}\n"
            .'🔗 <a href="'.config('vigil.shared_views.managers.acts_incomplete')."\">იხილეთ სია</a>\n\n"
            ."📍 სექტორების სტატუსი\n"
            .'🔗 <a href="'.config('vigil.shared_views.sectors')."\">იხილეთ სია</a>\n\n"
            ."სულ: {$totalCount}\n\n"
            ."👥 ჩადუ & გივიკო\n"
            .'🔗 <a href="'.config('vigil.shared_views.inspector_full')['ჩადუ & გივიკო']."\">გუნდის სტატისტიკა</a>\n\n"
            ."👥 გიო & სანდრიკა\n"
            .'🔗 <a href="'.config('vigil.shared_views.inspector_full')['გიო & სანდრიკა']."\">გუნდის სტატისტიკა</a>\n\n"
            ."👥 მურო & რევაზა\n"
            .'🔗 <a href="'.config('vigil.shared_views.inspector_full')['მურო & რევაზა'].'">გუნდის სტატისტიკა</a>';

        $managers = $airtable->getManagers();
        foreach ($managers as $manager) {
            $telegramId = $manager['fields']['✈️ Telegram'] ?? null;
            if ($telegramId) {
                $sendChunked($managerMessage, fn ($chunk) => $telegram->send($telegramId, $chunk));
            }
        }

        // Forward manager message to Zura for monitoring
        $sendChunked($managerMessage, fn ($chunk) => $telegram->sendToMe($chunk));

        $this->info("Done. {$count} incomplete ACTs processed.");
    }
}
