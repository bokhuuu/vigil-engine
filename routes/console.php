<?php

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

// Nightly rescan — corrects any state drift, 03:00 Georgia (23:00 UTC)
Schedule::command('vigil:drive-rescan')
    ->dailyAt('23:00');

// Disk alert — daily at 09:00 Georgia (05:00 UTC), all days
Schedule::command('vigil:disk-alert')->dailyAt('05:00');

// Horizon health check — every 5 minutes, all days
Schedule::command('vigil:horizon-check')->everyFiveMinutes();

// Weekly backup - Sunday 11:00 Georgia
Schedule::command('vigil:backup')->weeklyOn(config('vigil.schedule.backup_day'), config('vigil.schedule.backup_time'));

// Storage cleanup — delete stale download ZIPs older than 2 hours
Schedule::command('storage:cleanup')->hourly();

// Business module — Gmail watcher
Schedule::command('business:watch-gmail')->everyMinute()->withoutOverlapping(2)->runInBackground();

// Business module — Drive subfolder state sync
Schedule::command('business:sync-drive-states')->everyFifteenMinutes()->withoutOverlapping(10);

// Drive folders — every 15 min, all days: new ACTs (limited per run) + sync recently changed
Schedule::command('vigil:create-drive-folders', [
    '--limit' => config('vigil.drive.create_limit_per_run'),
])->everyFifteenMinutes()->withoutOverlapping(30);
