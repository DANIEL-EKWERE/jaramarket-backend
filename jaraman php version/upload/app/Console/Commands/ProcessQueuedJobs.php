<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class ProcessQueuedJobs extends Command
{
    /**
     * Run all queued jobs (notifications, Firebase pushes, emails).
     * Designed for cron: runs the queue worker for a fixed time window,
     * then exits cleanly — safe to call every minute via cron.
     *
     * Cron entry (runs every minute):
     *   * * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
     */
    protected $signature = 'queue:process-all
                            {--timeout=55 : Seconds the worker runs before stopping (default 55s for 1-min cron)}
                            {--queue=default,notifications,firebase : Comma-separated queue names to process}
                            {--tries=3 : Max attempts per job before marking failed}';

    protected $description = 'Process all queued jobs including notifications and Firebase pushes. Safe for cron.';

    public function handle(): int
    {
        $timeout = (int) $this->option('timeout');
        $queues  = $this->option('queue');
        $tries   = (int) $this->option('tries');

        $this->info('['.now()->toDateTimeString().'] Processing queued jobs...');

        // Log pending jobs count
        $pending = DB::table('jobs')->count();
        $failed  = DB::table('failed_jobs')->count();

        $this->info("  Pending jobs : {$pending}");
        $this->info("  Failed jobs  : {$failed}");

        if ($pending === 0) {
            $this->info('  No pending jobs — exiting.');
            return 0;
        }

        // Run queue worker with time limit (stops before cron fires again)
        $exitCode = Artisan::call('queue:work', [
            '--queue'            => $queues,
            '--timeout'          => $timeout - 5,   // inner job timeout
            '--max-time'         => $timeout,        // total worker lifetime
            '--tries'            => $tries,
            '--stop-when-empty'  => true,
            '--no-interaction'   => true,
        ]);

        $remaining = DB::table('jobs')->count();
        $this->info("  Jobs remaining after run: {$remaining}");
        $this->info('['.now()->toDateTimeString().'] Done.');

        Log::info('ProcessQueuedJobs completed', [
            'processed' => $pending - $remaining,
            'remaining' => $remaining,
        ]);

        return $exitCode;
    }
}
