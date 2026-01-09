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
                            {--order-id= : Fissa solo un ordine specifico}';

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

        if ($dryRun) {
            $this->info('🔍 Modalità DRY-RUN: nessuna modifica verrà applicata');
        }

        $this->info('Inizio aggiornamento card listings vendute...');

        // Query per trovare ordini con orderItems
        $query = Order::with(['orderItems.cardListing'])
            ->whereHas('orderItems');

        if ($orderId) {
            $query->where('id', $orderId);
        }

        $orders = $query->get();
        $this->info("Trovati {$orders->count()} ordini da verificare");

        $fixedCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        foreach ($orders as $order) {
            $this->line("Verificando ordine #{$order->order_number} (ID: {$order->id})");

            foreach ($order->orderItems as $orderItem) {
                if (!$orderItem->cardListing) {
                    $this->warn("  ⚠️  OrderItem #{$orderItem->id} non ha cardListing associata");
                    $skippedCount++;
                    continue;
                }

                $listing = $orderItem->cardListing;
                $quantitySold = $orderItem->quantity;

                // Calcola la quantità totale venduta per questa listing da tutti gli ordini
                $totalSold = OrderItem::where('card_listing_id', $listing->id)
                    ->whereHas('order', function ($q) {
                        // Solo ordini confermati/pagati (non cancellati o rimborsati)
                        $q->whereNotIn('status', ['cancelled', 'refunded']);
                    })
                    ->sum('quantity');

                // Calcola la quantità che dovrebbe rimanere
                // Assumiamo che la quantity originale fosse quantity + totalSold
                $expectedRemaining = max(0, $listing->quantity - ($totalSold - $quantitySold));

                // Se la listing è ancora active ma dovrebbe essere sold
                if ($listing->status === 'active' && $expectedRemaining <= 0) {
                    $this->info("  ✅ Listing #{$listing->id} dovrebbe essere venduta (quantity: {$listing->quantity}, sold: {$totalSold})");

                    if (!$dryRun) {
                        try {
                            DB::beginTransaction();

                            // Aggiorna quantity a 0 e status a sold
                            $listing->update([
                                'quantity' => 0,
                                'status' => 'sold'
                            ]);

                            DB::commit();

                            $this->info("  ✅ Listing #{$listing->id} aggiornata: status=sold, quantity=0");
                            $fixedCount++;

                            Log::info('Card listing fixed by FixSoldCardListings command', [
                                'listing_id' => $listing->id,
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'total_sold' => $totalSold,
                                'previous_quantity' => $listing->getOriginal('quantity'),
                                'previous_status' => $listing->getOriginal('status')
                            ]);
                        } catch (\Exception $e) {
                            DB::rollBack();
                            $this->error("  ❌ Errore aggiornando listing #{$listing->id}: {$e->getMessage()}");
                            $errorCount++;

                            Log::error('Error fixing card listing', [
                                'listing_id' => $listing->id,
                                'order_id' => $order->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                    } else {
                        $fixedCount++;
                    }
                } elseif ($listing->status === 'active' && $listing->quantity > 0) {
                    // Aggiorna la quantity se non è corretta
                    $currentQuantity = $listing->quantity;
                    $originalQuantity = $currentQuantity + $totalSold; // Stima quantity originale

                    if ($currentQuantity != $expectedRemaining) {
                        $this->info("  📊 Listing #{$listing->id} quantity da aggiornare: {$currentQuantity} -> {$expectedRemaining}");

                        if (!$dryRun) {
                            try {
                                DB::beginTransaction();

                                $listing->update([
                                    'quantity' => $expectedRemaining
                                ]);

                                // Se dopo l'aggiornamento quantity è 0, marca come sold
                                if ($expectedRemaining <= 0) {
                                    $listing->update(['status' => 'sold']);
                                    $this->info("  ✅ Listing #{$listing->id} aggiornata: status=sold, quantity=0");
                                } else {
                                    $this->info("  ✅ Listing #{$listing->id} quantity aggiornata: {$expectedRemaining}");
                                }

                                DB::commit();
                                $fixedCount++;

                                Log::info('Card listing quantity fixed by FixSoldCardListings command', [
                                    'listing_id' => $listing->id,
                                    'order_id' => $order->id,
                                    'previous_quantity' => $currentQuantity,
                                    'new_quantity' => $expectedRemaining
                                ]);
                            } catch (\Exception $e) {
                                DB::rollBack();
                                $this->error("  ❌ Errore aggiornando listing #{$listing->id}: {$e->getMessage()}");
                                $errorCount++;
                            }
                        } else {
                            $fixedCount++;
                        }
                    }
                }
            }
        }

        $this->newLine();
        $this->info("✅ Completato!");
        $this->table(
            ['Risultato', 'Conteggio'],
            [
                ['Listings aggiornate', $fixedCount],
                ['Errori', $errorCount],
                ['Saltate', $skippedCount],
            ]
        );

        if ($dryRun) {
            $this->warn('⚠️  Modalità DRY-RUN: nessuna modifica è stata applicata');
            $this->info('Esegui senza --dry-run per applicare le modifiche');
        }

        return 0;
    }
}
