<?php

use Illuminate\Support\Facades\Schedule;

// Morning run - 11:00 Monday to Saturday (not Sunday)
Schedule::command('vigil:daily-summary')->dailyAt('11:00')->days([1, 2, 3, 4, 5, 6]);
Schedule::command('vigil:correspondence-overdue')->dailyAt('11:00')->days([1, 2, 3, 4, 5, 6]);

// Evening run - 20:00 Monday to Saturday (not Sunday)
Schedule::command('vigil:act-alert')->dailyAt('20:00')->days([1, 2, 3, 4, 5, 6]);

// Weekly backup - Sunday 11:00 only
Schedule::command('vigil:backup')->weeklyOn(0, '11:00');
