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
        // Archiver les comptes bloqués expirés quotidiennement à minuit
        $schedule->job(new \App\Jobs\ArchiveExpiredBlockedAccounts)->daily();

        // Désarchiver les comptes bloqués dont la période est expirée toutes les heures
        $schedule->job(new \App\Jobs\UnarchiveExpiredBlockedAccounts)->hourly();
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
