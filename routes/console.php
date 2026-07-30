<?php

use Illuminate\Support\Facades\Schedule;

// Morning run - 11:00 Georgia, Monday to Saturday
Schedule::command('vigil:daily-summary')->dailyAt('11:00')->days([1, 2, 3, 4, 5, 6]);
Schedule::command('vigil:correspondence-overdue')->dailyAt('11:00')->days([1, 2, 3, 4, 5, 6]);
Schedule::command('vigil:deadline-warning')->dailyAt('11:00')->days([1, 2, 3, 4, 5, 6]);

// Evening run - 20:00 Georgia, Monday to Saturday
Schedule::command('vigil:sector-progress')->dailyAt('20:00')->days([1, 2, 3, 4, 5, 6]);
Schedule::command('vigil:act-alert')->dailyAt('20:00')->days([1, 2, 3, 4, 5, 6]);

// Weekly backup - Sunday 11:00 Georgia
Schedule::command('vigil:backup')->weeklyOn(0, '11:00');
