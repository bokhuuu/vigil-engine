<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

class HorizonHealthCheckCommand extends Command
{
    protected $signature = 'vigil:horizon-check';

    protected $description = 'Check Horizon status and alert via Telegram if it is down';

    private const CACHE_KEY = 'horizon_health_down';

    public function handle(TelegramService $telegram): void
    {
        $supervisors = app(MasterSupervisorRepository::class)->all();
        $isRunning = collect($supervisors)->contains(
            fn ($s) => $s->status === 'running'
        );

        $wasDown = Cache::get(self::CACHE_KEY, false);

        if (! $isRunning) {
            if (! $wasDown) {
                // First detection — alert and mark as down
                Cache::put(self::CACHE_KEY, true, now()->addHours(24));
                $telegram->sendToMe(
                    "🔴 Horizon გაჩერებულია\n\n".
                        'გასაშვებად: <code>php artisan horizon</code>'
                );
                $this->error('Horizon is DOWN — alert sent');
            } else {
                $this->warn('Horizon is DOWN — already alerted, skipping');
            }

            return;
        }

        if ($wasDown) {
            // Horizon recovered — clear flag and notify
            Cache::forget(self::CACHE_KEY);
            $telegram->sendToMe('✅ Horizon აღდგა');
            $this->info('Horizon recovered — alert sent');

            return;
        }

        $this->info('Horizon is running');
    }
}
