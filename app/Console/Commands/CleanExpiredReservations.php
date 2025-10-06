<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Availability;
use Carbon\Carbon;

class CleanExpiredReservations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'availability:clean-expired {--dry-run : Mostra solo cosa verrebbe pulito senza eseguire}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pulisce le prenotazioni e i lock scaduti';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $now = now();
        
        $this->info('Pulizia prenotazioni scadute...');
        
        // Trova lock scaduti
        $expiredLocks = Availability::expiredLocks()->get();
        $this->info("Trovati {$expiredLocks->count()} lock scaduti");
        
        // Trova prenotazioni scadute
        $expiredReservations = Availability::expiredReservations()->get();
        $this->info("Trovate {$expiredReservations->count()} prenotazioni scadute");
        
        $totalCleaned = 0;
        
        if ($isDryRun) {
            $this->warn('DRY RUN - Nessuna modifica verrà effettuata');
            
            foreach ($expiredLocks as $availability) {
                $this->line("Lock scaduto per inserzione {$availability->card_listing_id} (utente: {$availability->user_id})");
            }
            
            foreach ($expiredReservations as $availability) {
                $this->line("Prenotazione scaduta per inserzione {$availability->card_listing_id} (utente: {$availability->user_id})");
            }
            
            return;
        }
        
        // Pulisci lock scaduti
        foreach ($expiredLocks as $availability) {
            if ($availability->releaseLock()) {
                $totalCleaned++;
                $this->line("Rilasciato lock per inserzione {$availability->card_listing_id}");
            }
        }
        
        // Pulisci prenotazioni scadute
        foreach ($expiredReservations as $availability) {
            if ($availability->releaseReservation()) {
                $totalCleaned++;
                $this->line("Rilasciata prenotazione per inserzione {$availability->card_listing_id}");
            }
        }
        
        $this->info("Pulizia completata: {$totalCleaned} prenotazioni/lock rilasciati");
        
        // Mostra statistiche
        $this->showStatistics();
    }
    
    /**
     * Mostra statistiche sulla disponibilità
     */
    private function showStatistics()
    {
        $this->info("\nStatistiche disponibilità:");
        
        $stats = [
            'Disponibili' => Availability::where('status', 'available')->count(),
            'Bloccate' => Availability::where('status', 'locked')->count(),
            'Prenotate' => Availability::where('status', 'reserved')->count(),
            'Vendute' => Availability::where('status', 'sold')->count(),
        ];
        
        foreach ($stats as $status => $count) {
            $this->line("  {$status}: {$count}");
        }
        
        // Mostra prenotazioni in scadenza (nei prossimi 5 minuti)
        $soon = now()->addMinutes(5);
        $expiringSoon = Availability::where('status', 'locked')
            ->where('locked_until', '<=', $soon)
            ->where('locked_until', '>', now())
            ->count();
            
        $expiringSoonReservations = Availability::where('status', 'reserved')
            ->where('reserved_until', '<=', $soon)
            ->where('reserved_until', '>', now())
            ->count();
        
        if ($expiringSoon > 0 || $expiringSoonReservations > 0) {
            $this->warn("\nPrenotazioni in scadenza nei prossimi 5 minuti:");
            $this->line("  Lock: {$expiringSoon}");
            $this->line("  Prenotazioni: {$expiringSoonReservations}");
        }
    }
}