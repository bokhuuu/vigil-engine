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

        $message = "🛡️ <b>Vigil Daily Summary</b>\n\n"
            ."სულ მიღებული: {$counts['total']}\n"
            ."დასრულებული ✅: {$counts['done']}\n"
            ."არასრული ❌: {$counts['incomplete']}\n\n"
            ."ინსპექტორების მლოდინი: {$counts['by_team']['ინსპექტორები']}\n"
            ."ამზომველების მლოდინი: {$counts['by_team']['ამზომველები']}\n"
            ."ხარჯთაღმრიცხველების მლოდინი: {$counts['by_team']['ხარჯთაღმრიცხველი']}\n"
            ."აგრონომების მლოდინი: {$counts['by_team']['აგრონომები']}\n\n"
            ."კორესპონდენცია ვადაგადაცილებული: {$counts['corr_overdue']}";

        $telegram->sendToMe($message);

        $this->info('Daily summary sent.');
    }
}
