<?php

use Illuminate\Support\Facades\Schedule;

// Morning run - 11:00 Georgia, Monday to Friday
Schedule::command('vigil:correspondence-overdue')->dailyAt('11:00')->days([1, 2, 3, 4, 5]);
Schedule::command('vigil:deadline-warning')->dailyAt('11:00')->days([1, 2, 3, 4, 5]);
Schedule::command('vigil:sector-deadline')->dailyAt('11:00')->days([1, 2, 3, 4, 5]);
// Schedule::command('vigil:sector-progress')->dailyAt('11:00')->days([1, 2, 3, 4, 5]);

// Business module — overdue correspondence check
Schedule::command('business:check-overdue')->dailyAt('11:00')->days([1, 2, 3, 4, 5]);

// Evening run - 20:00 Georgia, Monday to Friday
// Schedule::command('vigil:sector-progress')->dailyAt('20:00')->days([1, 2, 3, 4, 5]);
Schedule::command('vigil:act-alert')->dailyAt('20:00')->days([1, 2, 3, 4, 5]);

// Update reminders - every min
Schedule::command('vigil:send-update-reminders')->everyMinute();

// Drive watcher — every min, dry-run alongside Apps Script
Schedule::command('vigil:drive-watch --dry-run')
    ->everyMinute()
    ->withoutOverlapping(2)
    ->runInBackground();

// Rebuild Drive watcher folder index hourly (picks up new ACT folders)
Schedule::command('vigil:drive-watch --seed')->hourly();

// Weekly backup - Sunday 11:00 Georgia
Schedule::command('vigil:backup')->weeklyOn(0, '11:00');

// Business module — Gmail watcher
Schedule::command('business:watch-gmail')->everyFiveMinutes()->withoutOverlapping(2)->runInBackground();

// Drive folders — every 15 min, all days: new ACTs (limited per run) + sync recently changed
// Schedule::command('vigil:create-drive-folders', [
//     '--limit' => config('vigil.drive.create_limit_per_run'),
// ])->everyFifteenMinutes()->withoutOverlapping(30);
