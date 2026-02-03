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
        // Promemoria spedizione ogni ora
        $schedule->command('orders:send-shipment-reminders --hours=24')->hourly();

        // FASE D3: notifiche reminder venditore (untracked 5° giorno, tracked 6° giorno)
        $schedule->job(new \App\Jobs\SendShipmentReminderNotifications())->dailyAt('09:00');

        // Controllo etichette non usate (timeout anti-frode) ogni giorno
        $schedule->job(new \App\Jobs\CheckUnusedLabels())->daily();

        // Controllo payout schedulati ogni ora (fallback per job dispatchati con delay)
        $schedule->job(new \App\Jobs\ProcessScheduledPayouts())->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}


