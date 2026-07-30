<?php

namespace App\Console\Commands;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\TelegramService;
use Illuminate\Console\Command;

class DailySummaryCommand extends Command
{
    protected $signature = 'vigil:daily-summary';

    protected $description = 'Send daily summary to Zura';

    public function handle(): void
    {
        $airtable = new AirtableService(new AirtableClient);
        $telegram = new TelegramService;

        $this->info('Fetching counts from Airtable...');
        $counts = $airtable->getSummaryCounts();

        // ACTs section
        $actsSection = "<b>📋 აქტები</b>\n"
            ."სულ: {$counts['total']} | ✅ {$counts['done']} | ❌ {$counts['incomplete']}\n";

        // Only show teams with incomplete ACTs
        $teamLines = '';
        foreach ($counts['by_team'] as $team => $count) {
            if ($count > 0) {
                $teamLines .= "{$team}: {$count}\n";
            }
        }

        if ($teamLines) {
            $actsSection .= $teamLines;
        }

        // Correspondence section
        $corrSection = "<b>📨 კორესპონდენცია</b>\n";
        $corrSection .= $counts['corr_overdue'] > 0
            ? "⚠️ ვადაგადაცილებული: {$counts['corr_overdue']}"
            : '✅ ყველა დროულია';

        $message = "<b>დღის შეჯამება</b>\n\n"
            .$actsSection."\n"
            .$corrSection;

        $telegram->sendToMe($message);

        $this->info('Daily summary sent.');
    }
}
