<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;

class DiskAlertCommand extends Command
{
    protected $signature = 'vigil:disk-alert {--threshold=80 : Alert if disk usage percentage exceeds this value}';

    protected $description = 'Send a Telegram alert if disk usage exceeds the threshold (default 80%).';

    public function __construct(
        private TelegramService $telegram,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path = storage_path();
        $total = disk_total_space($path);
        $free = disk_free_space($path);

        if ($total === false || $free === false || $total === 0) {
            $this->error('Could not read disk space.');

            return self::FAILURE;
        }

        $usedPercent = (int) round((($total - $free) / $total) * 100);
        $threshold = (int) $this->option('threshold');

        $totalGb = round($total / 1024 / 1024 / 1024, 1);
        $freeGb = round($free / 1024 / 1024 / 1024, 1);
        $usedGb = round(($total - $free) / 1024 / 1024 / 1024, 1);

        if ($usedPercent >= $threshold) {
            $this->telegram->sendToGroup(
                "⚠️ სერვერის დისკი გადაჭედილია\n\n".
                    "📊 გამოყენებულია: {$usedPercent}%\n".
                    "💾 სულ: {$totalGb}GB\n".
                    "✅ თავისუფალია: {$freeGb}GB\n".
                    "❌ გამოყენებულია: {$usedGb}GB"
            );

            $this->warn("⚠️ Disk usage {$usedPercent}% >= {$threshold}% — alert sent.");

            return self::SUCCESS;
        }

        $this->info("✅ Disk usage {$usedPercent}% — OK (free: {$freeGb}GB)");

        return self::SUCCESS;
    }
}
