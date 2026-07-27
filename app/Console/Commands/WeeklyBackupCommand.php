<?php

namespace App\Console\Commands;

use App\Services\AirtableClient;
use App\Services\AirtableService;
use App\Services\TelegramService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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

        $counts = [
            'acts' => count($data['acts']),
            'correspondence' => count($data['correspondence']),
            'teams' => count($data['teams']),
            'notifications' => count($data['notifications']),
            'businesses' => count($data['businesses']),
        ];

        $message = "💾 <b>Vigil Weekly Backup — {$date}</b>\n\n"
            ."✅ შენახულია: {$filename}\n\n"
            ."აქტები: {$counts['acts']}\n"
            ."კორესპონდენცია: {$counts['correspondence']}\n"
            ."გუნდები: {$counts['teams']}\n"
            ."შეტყობინებები: {$counts['notifications']}\n"
            ."ბიზნესები: {$counts['businesses']}";

        $telegram->sendToMe($message);

        $this->info("Backup saved to {$path}");
    }
}
