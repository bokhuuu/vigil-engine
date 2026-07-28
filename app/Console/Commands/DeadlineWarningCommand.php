<?php

namespace App\Console\Commands;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DeadlineWarningCommand extends Command
{
    protected $signature = 'vigil:deadline-warning';

    protected $description = 'Send deadline warnings for ACT commitments and correspondence';

    public function handle(): void
    {
        $airtable = new AirtableService(new AirtableClient);
        $telegram = new TelegramService;
        $today = Carbon::now()->startOfDay();
        $date = $today->format('d.m.Y');

        $this->info('Checking deadlines...');

        // ── ACT commitment deadlines ──────────────────────────────────────
        $deadlines = $airtable->getUpcomingDeadlines();
        $warnings = [];

        foreach ($deadlines as $record) {
            $fields = $record['fields'];
            $promisedRaw = $fields['📅 დაპირებული თარიღი'] ?? null;

            if (! $promisedRaw) {
                continue;
            }

            $promised = Carbon::parse($promisedRaw)->startOfDay();
            $daysLeft = $today->diffInDays($promised, false);
            $title = $fields['🔖 სათაური'] ?? '—';
            $notes = $fields['📝 შენიშვნა'] ?? '';

            if ($daysLeft < 0) {
                // Overdue - mark in Airtable
                $airtable->markDeadlineOverdue($record['id']);
                $warnings[] = "❌ <b>ვადა გავიდა:</b> {$title}\n   ვადა იყო: {$promised->format('d.m.Y')}\n   {$notes}";
            } elseif ($daysLeft === 0) {
                $warnings[] = "🚨 <b>დღეს ვადა:</b> {$title}\n   {$notes}";
            } elseif ($daysLeft === 1) {
                $warnings[] = "🔴 <b>ხვალ ვადა:</b> {$title}\n   {$notes}";
            } elseif ($daysLeft === 2) {
                $warnings[] = "⚠️ <b>2 დღე რჩება:</b> {$title}\n   {$notes}";
            }
        }

        // ── Correspondence promised dates ─────────────────────────────────
        $corrList = $airtable->getCorrespondenceWithPromisedDates();

        foreach ($corrList as $record) {
            $fields = $record['fields'];
            $promisedRaw = $fields['📅 დაპირებული თარიღი'] ?? null;

            if (! $promisedRaw) {
                continue;
            }

            $promised = Carbon::parse($promisedRaw)->startOfDay();
            $daysLeft = $today->diffInDays($promised, false);
            $subject = $fields['📋 საკითხი'] ?? '—';

            if ($daysLeft < 0) {
                $warnings[] = "❌ <b>კორ. ვადა გავიდა:</b> {$subject}\n   ვადა იყო: {$promised->format('d.m.Y')}";
            } elseif ($daysLeft === 0) {
                $warnings[] = "🚨 <b>კორ. დღეს ვადა:</b> {$subject}";
            } elseif ($daysLeft === 1) {
                $warnings[] = "🔴 <b>კორ. ხვალ ვადა:</b> {$subject}";
            } elseif ($daysLeft === 2) {
                $warnings[] = "⚠️ <b>კორ. 2 დღე რჩება:</b> {$subject}";
            }
        }

        // ── Send to Zura ──────────────────────────────────────────────────
        if (empty($warnings)) {
            $telegram->sendToMe("✅ ვადები — {$date} — პრობლემა არ არის.");
            $this->info('No deadline warnings.');

            return;
        }

        $message = "⏰ <b>ვადების შეხსენება — {$date}</b>\n\n"
            .implode("\n\n", $warnings);

        $telegram->sendToMe($message);
        $this->info('Deadline warnings sent: '.count($warnings));
    }
}
