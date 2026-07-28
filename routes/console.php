<?php

use Illuminate\Support\Facades\Schedule;

// Morning run - 07:00 UTC = 11:00 Georgia, Monday to Saturday
Schedule::command('vigil:daily-summary')->dailyAt('07:00')->days([1, 2, 3, 4, 5, 6]);
Schedule::command('vigil:correspondence-overdue')->dailyAt('07:00')->days([1, 2, 3, 4, 5, 6]);
Schedule::command('vigil:deadline-warning')->dailyAt('07:00')->days([1, 2, 3, 4, 5, 6]);

// Evening run - 16:00 UTC = 20:00 Georgia, Monday to Saturday
Schedule::command('vigil:act-alert')->dailyAt('16:00')->days([1, 2, 3, 4, 5, 6]);

// Weekly backup - Sunday 07:00 UTC = 11:00 Georgia
Schedule::command('vigil:backup')->weeklyOn(0, '07:00');
