<?php

use App\Jobs\CloseStaleVisits;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduler — cPanel cron runs `php artisan schedule:run` every minute
|--------------------------------------------------------------------------
|
| No Supervisor on shared hosting: the queue is drained from cron.
| CloseStaleVisits runs inline (not queued) and first, so visit summaries
| go out within ~1–2 minutes of the tab closing.
|
*/

Schedule::job(new CloseStaleVisits)->everyMinute()->name('close-stale-visits')->withoutOverlapping();

Schedule::command('queue:work --stop-when-empty --max-time=50')->everyMinute()->withoutOverlapping();
