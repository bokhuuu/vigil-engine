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

        $corrStatus = $counts['corr_overdue'] > 0
            ? "⚠️ ვადაგადაცილებული: {$counts['corr_overdue']}"
            : '✅ ყველა დროულია';

        $message = "🛡️ <b>Vigil — დღის შეჯამება</b>\n\n"
            ."აქტები: {$counts['total']} სულ | ✅ {$counts['done']} | ❌ {$counts['incomplete']}\n\n"
            ."<b>გუნდების მოლოდინი:</b>\n"
            ."ინსპექტორები: {$counts['by_team']['ინსპექტორები']}\n"
            ."ამზომველები: {$counts['by_team']['ამზომველები']}\n"
            ."ხარჯთაღმრიცხველები: {$counts['by_team']['ხარჯთაღმრიცხველი']}\n"
            ."აგრონომები: {$counts['by_team']['აგრონომები']}\n\n"
            ."📨 კორესპონდენცია: {$corrStatus}";

        $telegram->sendToMe($message);

        $this->info('Daily summary sent.');
    }
}
