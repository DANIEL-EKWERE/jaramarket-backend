<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * ─────────────────────────────────────────────────────────────────────
     * CRON SETUP — Add ONE line to your server crontab:
     *
     *   * * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
     *
     * This runs every minute. Laravel's scheduler then decides what to run.
     * ─────────────────────────────────────────────────────────────────────
     */
   protected function schedule(Schedule $schedule): void
    {
        // Queue worker
        $schedule->command('queue:work database --queue=payments,notifications,ai,default --stop-when-empty --tries=3 --timeout=60 --max-jobs=50')
        ->everyMinute()
        ->withoutOverlapping(300)
        ->runInBackground()
        ->appendOutputTo(storage_path('logs/queue-worker.log'));
    
        // Retry failed jobs carefully
        $schedule->command('queue:retry --queue=default')
            ->hourly()
            ->withoutOverlapping();
    
        // Bank sync
        $schedule->command('banks:fetch')
            ->daily()
            ->at('01:00')
            ->withoutOverlapping();
    
        // Prune failed jobs
        $schedule->command('queue:prune-failed --hours=168')
            ->weekly();
    
        // Prune notifications (safe version)
        $schedule->call(function () {
            \App\Models\User::chunk(100, function ($users) {
                foreach ($users as $user) {
                    $user->notifications()
                        ->where('created_at', '<', now()->subDays(30))
                        ->delete();
                }
            });
        })->monthly()->name('prune-old-notifications')->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
