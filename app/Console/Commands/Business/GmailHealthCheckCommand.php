<?php

declare(strict_types=1);

namespace App\Console\Commands\Business;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class GmailHealthCheckCommand extends Command
{
    protected $signature = 'business:gmail-check';

    protected $description = 'Alert via Telegram if business:watch-gmail has not run in the last 5 minutes';

    private const CACHE_KEY = 'business_gmail_last_run';

    private const ALERT_KEY = 'business_gmail_health_down';

    public function handle(TelegramService $telegram): int
    {
        $lastRun = Cache::get(self::CACHE_KEY);
        $isStale = $lastRun === null || now()->diffInMinutes($lastRun) >= 5;
        $wasDown = Cache::get(self::ALERT_KEY, false);

        if ($isStale) {
            if (! $wasDown) {
                Cache::put(self::ALERT_KEY, true, now()->addHours(24));
                $telegram->sendToMe(
                    "🔴 <b>business:watch-gmail</b> გაჩერდა\n\n".
                        'ბოლო გაშვება: '.($lastRun ? $lastRun->diffForHumans() : 'უცნობია')."\n".
                        'შეამოწმე: <code>php artisan horizon:status</code>'
                );
                $this->error('Gmail watcher is STALE — alert sent');
            } else {
                $this->warn('Gmail watcher is STALE — already alerted, skipping');
            }

            return self::FAILURE;
        }

        if ($wasDown) {
            Cache::forget(self::ALERT_KEY);
            $telegram->sendToMe('✅ <b>business:watch-gmail</b> აღდგა');
            $this->info('Gmail watcher recovered — alert sent');

            return self::SUCCESS;
        }

        $this->info('Gmail watcher is healthy');

        return self::SUCCESS;
    }
}
