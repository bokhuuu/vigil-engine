<?php

use Illuminate\Support\Facades\Schedule;

// Morning run - 11:00 Georgia, Monday to Friday
Schedule::command('vigil:correspondence-overdue')->dailyAt('11:00')->days([1, 2, 3, 4, 5]);
Schedule::command('vigil:deadline-warning')->dailyAt('11:00')->days([1, 2, 3, 4, 5]);
Schedule::command('vigil:sector-deadline')->dailyAt('11:00')->days([1, 2, 3, 4, 5]);
// Schedule::command('vigil:sector-progress')->dailyAt('11:00')->days([1, 2, 3, 4, 5]);

// Evening run - 20:00 Georgia, Monday to Friday
// Schedule::command('vigil:sector-progress')->dailyAt('20:00')->days([1, 2, 3, 4, 5]);
Schedule::command('vigil:act-alert')->dailyAt('20:00')->days([1, 2, 3, 4, 5]);

// Update reminders - every 15 min
Schedule::command('vigil:send-update-reminders')->everyFifteenMinutes();

// Weekly backup - Sunday 11:00 Georgia
Schedule::command('vigil:backup')->weeklyOn(0, '11:00');
