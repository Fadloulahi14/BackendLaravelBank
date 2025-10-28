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
        // Gérer les blocages et archivages des comptes toutes les 2 minutes
        $schedule->job(new \App\Jobs\ArchiveExpiredBlockedAccounts)->everyTwoMinutes();

        // Vérifier et débloquer automatiquement les comptes dont la période de blocage est expirée toutes les 2 minutes
        $schedule->job(new \App\Jobs\UnarchiveExpiredBlockedAccounts)->everyTwoMinutes();
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
