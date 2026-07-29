<?php

namespace App\Console\Commands;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WeeklyBackupCommand extends Command
{
    protected $signature = 'vigil:backup';

    protected $description = 'Weekly backup of all Airtable data to JSON';

    public function handle(): void
    {
        $airtable = new AirtableService(new AirtableClient);
        $telegram = new TelegramService;

        $this->info('Starting weekly backup...');

        $data = $airtable->getAllForBackup();
        $date = Carbon::now()->format('Y-m-d');
        $filename = "backup-{$date}.json";
        $path = storage_path("backups/{$filename}");

        File::ensureDirectoryExists(storage_path('backups'));
        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Upload to Backblaze B2
        try {
            Storage::disk('b2_backups')->put($filename, File::get($path));
            $this->info('Backup uploaded to B2.');
        } catch (\Throwable $e) {
            Log::error('B2 backup upload failed', ['error' => $e->getMessage()]);
            $telegram->sendToMe('⚠️ B2 სარეზერვო ასლის ატვირთვა ვერ მოხდა: '.$e->getMessage());
        }

        $counts = [
            'acts' => count($data['acts']),
            'deadlines' => count($data['deadlines']),
            'sectors' => count($data['sectors']),
            'correspondence' => count($data['correspondence']),
            'teams' => count($data['teams']),
            'notifications' => count($data['notifications']),
            'businesses' => count($data['businesses']),
        ];

        $message = "💾 <b>Vigil — კვირის სარეზერვო ასლი</b>\n\n"
            ."✅ შენახულია და B2-ზე ატვირთულია\n\n"
            ."აქტები: {$counts['acts']}\n"
            ."ვადები: {$counts['deadlines']}\n"
            ."სექტორები: {$counts['sectors']}\n"
            ."კორესპონდენცია: {$counts['correspondence']}\n"
            ."გუნდები: {$counts['teams']}\n"
            ."შეტყობინებები: {$counts['notifications']}\n"
            ."ბიზნესები: {$counts['businesses']}";

        $telegram->sendToMe($message);

        $this->info("Backup saved to {$path}");
    }
}
