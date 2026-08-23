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

        // Mark newly overdue
        $newOverdue = $airtable->getOverdueCorrespondence();
        foreach ($newOverdue as $record) {
            $airtable->markCorrespondenceOverdue($record['id']);
            $subject = $record['fields']['📋 საკითხი'] ?? 'უცნობი';
            $this->warn("Newly overdue: {$subject}");
        }

        // Get all overdue (new + existing)
        $allOverdue = $airtable->getAlreadyOverdueCorrespondence();

        if (empty($allOverdue)) {
            $this->info('No overdue correspondence. All good.');
            $telegram->sendToMe('✅ კორესპონდენცია — ვადაგადაცილებული არ არის.');

            return;
        }

        $total = count($allOverdue);
        $message = "<b>ვადაგადაცილებული კორესპონდენცია</b>\n"
            ."სულ: {$total}\n\n";

        $today = Carbon::now()->startOfDay();

        // Build items with overdue days for sorting
        $items = [];
        foreach ($allOverdue as $record) {
            $fields = $record['fields'];
            $deadline = $fields['⏰ ვადა'] ?? null;
            $overdueDays = 0;
            if ($deadline) {
                $deadlineDate = Carbon::parse($deadline)->startOfDay();
                $overdueDays = (int) $deadlineDate->diffInDays($today, false);
            }
            $items[] = [
                'record' => $record,
                'overdueDays' => $overdueDays,
            ];
        }

        // Sort by most overdue first
        usort($items, fn ($a, $b) => $b['overdueDays'] - $a['overdueDays']);

        foreach ($items as $item) {
            $fields = $item['record']['fields'];
            $subject = $fields['📋 საკითხი'] ?? 'უცნობი';
            $corrUrl = $airtable->recordUrl(config('vigil.airtable.tables.correspondence'), $item['record']['id']);
            $message .= "📋 <a href=\"{$corrUrl}\">{$subject}</a>\n";
            $message .= "🔴 გადაცილება: {$item['overdueDays']} დღე\n";
            $message .= "—————————————\n";
        }

        // Send with line-based chunking
        $sendChunked = function (callable $sendFn) use ($message) {
            $lines = explode("\n", $message);
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

        $sendChunked(fn ($chunk) => $telegram->sendToMe($chunk));

        // Send to all managers
        $managers = $airtable->getManagers();
        foreach ($managers as $manager) {
            $telegramId = $manager['fields']['✈️ Telegram'] ?? null;
            if ($telegramId) {
                $sendChunked(fn ($chunk) => $telegram->send($telegramId, $chunk));
            }
        }

        $this->info("Done. {$total} overdue records alerted.");
    }
}
