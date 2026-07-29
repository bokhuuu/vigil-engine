<?php

namespace App\Console\Commands;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class SectorProgressCommand extends Command
{
    protected $signature = 'vigil:sector-progress';

    protected $description = 'Send daily sector completion progress to group chat';

    public function handle(): void
    {
        $airtable = new AirtableService(new AirtableClient);
        $telegram = new TelegramService;

        $this->info('Fetching sector progress...');

        $allActs = $airtable->getAllActs();
        $sectors = $airtable->getSectors();
        $teams = $airtable->getTeams();

        if (empty($sectors)) {
            $this->info('No sectors configured yet.');

            return;
        }

        // Build team name map by record ID
        $teamById = [];
        foreach ($teams as $team) {
            $teamById[$team['id']] = $team['fields']['👤 სახელი'] ?? '—';
        }

        // Count done/total ACTs per sector name
        $actCounts = [];
        foreach ($allActs as $act) {
            $actSectors = $act['fields']['📍სექტორი'] ?? [];
            $status = $act['fields']['🚦სტატუსი'] ?? '';
            foreach ($actSectors as $sectorName) {
                if (! isset($actCounts[$sectorName])) {
                    $actCounts[$sectorName] = ['total' => 0, 'done' => 0];
                }
                $actCounts[$sectorName]['total']++;
                if ($status === '✅ დასრულებული') {
                    $actCounts[$sectorName]['done']++;
                }
            }
        }

        // Only show sectors that have at least 1 done ACT
        $hasProgress = false;
        $message = "📊 <b>სექტორების პროგრესი</b>\n\n";
        $celebrations = [];

        foreach ($sectors as $sectorRecord) {
            $fields = $sectorRecord['fields'];
            $name = $fields['🔖 სახელი'] ?? '—';
            $totalExp = (int) ($fields['📊 სულ აქტები'] ?? 0);
            $celebrated = $fields['🎉 აღინიშნა'] ?? false;
            $teamIds = $fields['👥 გუნდი'] ?? [];
            $teamName = ! empty($teamIds) ? ($teamById[$teamIds[0]] ?? '—') : '—';

            $done = $actCounts[$name]['done'] ?? 0;
            $total = $totalExp > 0 ? $totalExp : ($actCounts[$name]['total'] ?? 0);

            if ($done === 0) {
                continue;
            }

            $hasProgress = true;
            $percent = $total > 0 ? round(($done / $total) * 100) : 0;
            $filled = (int) round($percent / 10);
            $empty = 10 - $filled;
            $bar = str_repeat('█', $filled).str_repeat('░', $empty);

            $message .= "📍 <b>{$name}</b> | {$teamName}\n";
            $message .= "{$bar} {$percent}% ({$done}/{$total})\n\n";

            // Check for sector completion
            if ($percent >= 100 && ! $celebrated) {
                $celebrations[] = [
                    'id' => $sectorRecord['id'],
                    'name' => $name,
                    'team' => $teamName,
                    'total' => $total,
                ];
            }
        }

        if (! $hasProgress) {
            $this->info('No sectors with progress yet.');

            return;
        }

        $message .= '💪 გაგრძელეთ კარგი მუშაობა!';
        $telegram->sendToGroup($message);

        // Send celebration for completed sectors
        foreach ($celebrations as $sector) {
            $celebration = "🎉 <b>სექტორი {$sector['name']} დასრულდა!</b>\n\n"
                ."გუნდი: <b>{$sector['team']}</b>\n"
                ."აქტები: {$sector['total']}/{$sector['total']} ✅\n\n"
                .'გილოცავთ! შესანიშნავი მუშაობა! 🏆';

            $telegram->sendToGroup($celebration);
            $airtable->markSectorCelebrated($sector['id']);
        }

        $this->info('Sector progress sent.');
    }
}
