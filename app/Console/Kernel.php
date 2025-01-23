<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:execute-functions-c-s-v')
            ->dailyAt('06:01')
            ->timezone('America/Mexico_City')
            ->appendOutputTo(storage_path('logs/scheduler.log'));

        $schedule->command('app:check-files-s3')
            ->hourlyAt(10)
            ->timezone('America/Mexico_City')
            ->appendOutputTo(storage_path('logs/scheduler.log'));
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
