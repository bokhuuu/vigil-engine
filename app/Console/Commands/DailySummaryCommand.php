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
            ."✅ {$counts['done']}   ❌ {$counts['incomplete']}   სულ: {$counts['total']}\n\n"
            ."<i>👥 არასრული აქტები გუნდების მიხედვით:</i>\n";

        // Only show teams with incomplete ACTs
        $teamLines = '';
        foreach ($counts['by_team'] as $team => $count) {
            if ($count > 0) {
                $teamLines .= "• {$team}: {$count}\n";
            }
        }

        // Correspondence section
        $corrSection = '';
        if ($counts['corr_overdue'] > 0) {
            $corrSection = "\n<b>📨 კორესპონდენცია</b>\n"
                ."⚠️ ვადაგადაცილებული: {$counts['corr_overdue']}";
        }

        $message = "<b>დღის შეჯამება</b>\n\n"
            .$actsSection
            .$teamLines
            .$corrSection;

        $telegram->sendToMe($message);

        $this->info('Daily summary sent.');
    }
}
