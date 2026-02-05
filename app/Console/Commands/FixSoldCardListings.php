<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CardListing;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class FixSoldCardListings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:fix-sold-listings 
                            {--dry-run : Esegue solo un controllo senza modificare i dati}
                            {--order-id= : Fissa solo un ordine specifico}
                            {--listing-id= : Fissa solo una listing specifica}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggiorna lo stato delle card listings vendute basandosi sugli ordini esistenti';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $orderId = $this->option('order-id');
        $listingId = $this->option('listing-id');

        if ($dryRun) {
            $this->info('Modalità DRY-RUN: nessuna modifica verrà applicata');
        }

        $this->info('Inizio aggiornamento card listings vendute...');

        // Se specificato listing-id, lavora solo su quella
        if ($listingId) {
            $listing = CardListing::with('cardModel')->find($listingId);
            if (!$listing) {
                $this->error("Listing #{$listingId} non trovata");
                return 1;
            }
            $this->fixListing($listing, $dryRun);
            return 0;
        }

        // Query per trovare card listings attive che potrebbero essere vendute
        $query = CardListing::with('cardModel')
            ->where('status', 'active');

        $listings = $query->get();
        $this->info("Trovate {$listings->count()} card listings attive da verificare");

        $fixedCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($listings as $listing) {
            $result = $this->fixListing($listing, $dryRun);
            if ($result === 'fixed') {
                $fixedCount++;
            } elseif ($result === 'error') {
                $errorCount++;
            } else {
                $skippedCount++;
            }
        }

        $this->newLine();
        $this->info("Completato!");
        $this->table(
            ['Risultato', 'Conteggio'],
            [
                ['Listings aggiornate', $fixedCount],
                ['Errori', $errorCount],
                ['Saltate/OK', $skippedCount],
            ]
        );

        if ($dryRun) {
            $this->warn('Modalità DRY-RUN: nessuna modifica è stata applicata');
            $this->info('Esegui senza --dry-run per applicare le modifiche');
        }

        return 0;
    }

    /**
     * Verifica e fixa una singola listing
     */
    private function fixListing(CardListing $listing, bool $dryRun): string
    {
        $cardName = $listing->cardModel?->name ?? 'N/A';
        
        // Calcola la quantità totale venduta per questa listing da tutti gli ordini
        $totalSold = OrderItem::where('card_listing_id', $listing->id)
            ->whereHas('order', function ($q) {
                // Solo ordini confermati/pagati (non cancellati o rimborsati)
                $q->whereNotIn('status', ['cancelled', 'refunded']);
            })
            ->sum('quantity');

        $this->line("Listing #{$listing->id} ({$cardName}): quantity={$listing->quantity}, status={$listing->status}, totalSold={$totalSold}");

        // Logica: se quantity = 0 ma status è ancora 'active', marcare come sold
        if ($listing->status === 'active' && $listing->quantity <= 0) {
            $this->info("  Dovrebbe essere venduta: quantity=0 ma status=active");

            if (!$dryRun) {
                try {
                    DB::beginTransaction();

                    $listing->update([
                        'quantity' => 0,
                        'status' => 'sold'
                    ]);

                    DB::commit();

                    $this->info("  Listing #{$listing->id} aggiornata: status=sold, quantity=0");
                    
                    Log::info('Card listing fixed by FixSoldCardListings command', [
                        'listing_id' => $listing->id,
                        'card_name' => $cardName,
                        'total_sold' => $totalSold,
                        'previous_quantity' => $listing->getOriginal('quantity'),
                        'previous_status' => $listing->getOriginal('status')
                    ]);

                    return 'fixed';
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("  Errore aggiornando listing #{$listing->id}: {$e->getMessage()}");
                    $errorCount++;

                    Log::error('Error fixing card listing', [
                        'listing_id' => $listing->id,
                        'error' => $e->getMessage()
                    ]);

                    return 'error';
                }
            } else {
                return 'fixed';
            }
        } elseif ($listing->status === 'active' && $listing->quantity > 0 && $totalSold > 0) {
            // Se c'è almeno una vendita ma quantity è ancora positiva, verifica se è corretta
            // Se totalSold >= quantity attuale, significa che è stata venduta completamente
            // (quantity attuale + totalSold = quantity originale, quindi se totalSold >= quantity attuale, è venduta)
            if ($totalSold >= $listing->quantity) {
                $this->info("  Dovrebbe essere venduta: totalSold ({$totalSold}) >= quantity attuale ({$listing->quantity})");

                if (!$dryRun) {
                    try {
                        DB::beginTransaction();

                        $listing->update([
                            'quantity' => 0,
                            'status' => 'sold'
                        ]);

                        DB::commit();

                        $this->info("  Listing #{$listing->id} aggiornata: status=sold, quantity=0");
                        
                        Log::info('Card listing fixed by FixSoldCardListings command (totalSold >= quantity)', [
                            'listing_id' => $listing->id,
                            'card_name' => $cardName,
                            'total_sold' => $totalSold,
                            'previous_quantity' => $listing->getOriginal('quantity'),
                            'previous_status' => $listing->getOriginal('status')
                        ]);

                        return 'fixed';
                    } catch (\Exception $e) {
                        DB::rollBack();
                        $this->error("  Errore aggiornando listing #{$listing->id}: {$e->getMessage()}");
                        
                        Log::error('Error fixing card listing', [
                            'listing_id' => $listing->id,
                            'error' => $e->getMessage()
                        ]);

                        return 'error';
                    }
                } else {
                    return 'fixed';
                }
            } else {
                $this->line("   OK: quantity={$listing->quantity}, totalSold={$totalSold} (non completamente venduta)");
                return 'skipped';
            }
        } else {
            $this->line("   OK: status={$listing->status}, quantity={$listing->quantity}, totalSold={$totalSold}");
            return 'skipped';
        }
    }
}
