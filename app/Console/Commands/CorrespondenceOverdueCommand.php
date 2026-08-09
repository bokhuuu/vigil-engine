<?php

namespace App\Console\Commands;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CorrespondenceOverdueCommand extends Command
{
    protected $signature = 'vigil:correspondence-overdue';

    protected $description = 'Mark overdue correspondence and alert Zura';

    public function handle(): void
    {
        $airtable = new AirtableService(new AirtableClient);
        $telegram = new TelegramService;

        $this->info('Checking correspondence deadlines...');
        $overdue = $airtable->getOverdueCorrespondence();

        if (empty($overdue)) {
            $this->info('No overdue correspondence. All good.');
            $telegram->sendToMe('✅ კორესპონდენცია — ვადაგადაცილებული არ არის.');

            return;
        }

        $updated = 0;

        foreach ($overdue as $record) {
            $airtable->markCorrespondenceOverdue($record['id']);
            $updated++;
            $subject = $record['fields']['📋 საკითხი'] ?? 'უცნობი';
            $this->warn("Overdue: {$subject}");
        }

        $total = count($overdue);
        $message = "<b>ვადაგადაცილებული კორესპონდენცია</b>\n"
            ."სულ: {$total}\n\n";

        $today = Carbon::now()->startOfDay();

        foreach ($overdue as $record) {
            $fields = $record['fields'];
            $subject = $fields['📋 საკითხი'] ?? 'უცნობი';
            $lot = $fields['🗂️ ლოტი'] ?? null;
            $deadline = $fields['⏰ ვადა'] ?? null;
            $corrUrl = $airtable->recordUrl(config('vigil.airtable.tables.correspondence'), $record['id']);

            $message .= "📋 <a href=\"{$corrUrl}\">{$subject}</a>\n";
            if ($lot) {
                $message .= "🗂️ {$lot}\n";
            }
            if ($deadline) {
                $deadlineDate = Carbon::parse($deadline)->startOfDay();
                $overdueDays = (int) $deadlineDate->diffInDays($today, false);
                $message .= "🔴 გადაცილება: {$overdueDays} დღე\n";
            }
            $message .= "—————————————\n";
        }

        $telegram->sendToMe($message);

        // Send to all managers
        $managers = $airtable->getManagers();
        foreach ($managers as $manager) {
            $telegramId = $manager['fields']['✈️ Telegram'] ?? null;
            if ($telegramId) {
                $telegram->send($telegramId, $message);
            }
        }

        $this->info("Done. {$updated} records marked overdue.");
    }
}
