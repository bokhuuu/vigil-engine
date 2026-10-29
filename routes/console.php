<?php

use App\Services\DriveWatcherService;
use Illuminate\Support\Facades\Schedule;

// Morning run - Georgia, Monday to Friday
Schedule::command('vigil:correspondence-overdue')->dailyAt(config('vigil.schedule.morning_time'))->days([1, 2, 3, 4, 5]);
Schedule::command('vigil:deadline-warning')->dailyAt(config('vigil.schedule.morning_time'))->days([1, 2, 3, 4, 5]);
Schedule::command('vigil:sector-deadline')->dailyAt(config('vigil.schedule.morning_time'))->days([1, 2, 3, 4, 5]);
// Schedule::command('vigil:sector-progress')->dailyAt(config('vigil.schedule.morning_time'))->days([1, 2, 3, 4, 5]);

// Business module — overdue correspondence check
Schedule::command('business:check-overdue')->dailyAt(config('vigil.schedule.morning_time'))->days([1, 2, 3, 4, 5]);

// Evening run - Georgia, Monday to Friday

// Schedule::command('vigil:sector-progress')->dailyAt(config('vigil.schedule.evening_time'))->days([1, 2, 3, 4, 5]);
Schedule::command('vigil:act-alert')->dailyAt(config('vigil.schedule.evening_time'))->days([1, 2, 3, 4, 5]);

// Update reminders - every min
Schedule::command('vigil:send-update-reminders')->everyMinute();

// Drive watcher — every minute, live
Schedule::command('vigil:drive-watch')
    ->everyMinute()
    ->withoutOverlapping(2)
    ->runInBackground();

// Rebuild Drive watcher folder index hourly (picks up new ACT folders)
Schedule::command('vigil:drive-watch --seed')->hourly();

// Nightly rescan — corrects any state drift, 23:00 Georgia (19:00 UTC)
Schedule::call(fn () => app(DriveWatcherService::class)->rescan())
    ->dailyAt('23:00');

// Weekly backup - Sunday 11:00 Georgia
Schedule::command('vigil:backup')->weeklyOn(config('vigil.schedule.backup_day'), config('vigil.schedule.backup_time'));

// Business module — Gmail watcher
Schedule::command('business:watch-gmail')->everyMinute()->withoutOverlapping(2)->runInBackground();

// Drive folders — every 15 min, all days: new ACTs (limited per run) + sync recently changed
Schedule::command('vigil:create-drive-folders', [
    '--limit' => config('vigil.drive.create_limit_per_run'),
])->everyFifteenMinutes()->withoutOverlapping(30);
