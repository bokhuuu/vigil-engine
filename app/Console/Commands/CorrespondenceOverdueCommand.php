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

        $fields = config('vigil.airtable.fields.correspondence');
        $updated = 0;

        foreach ($overdue as $record) {
            $airtable->markCorrespondenceOverdue($record['id']);
            $updated++;

            $subject = $record['fields'][$fields['subject']] ?? 'უცნობი';
            $deadline = $record['fields'][$fields['deadline']] ?? '—';

            $this->warn("Overdue: {$subject} (deadline: {$deadline})");
        }

        $date = Carbon::now()->format('d.m.Y');
        $message = "🔴 <b>ვადაგადაცილებული კორესპონდენცია</b>\n\n"
            ."სულ: {$updated}\n\n";

        foreach ($overdue as $record) {
            $subject = $record['fields'][$fields['subject']] ?? 'უცნობი';
            $deadline = $record['fields'][$fields['deadline']] ?? '—';
            $message .= "❗ {$subject}\n   ვადა: {$deadline}\n\n";
        }

        $message .= 'გთხოვთ დაუყოვნებლივ უპასუხოთ.';

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
