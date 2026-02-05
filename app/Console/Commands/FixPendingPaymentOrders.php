<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FixPendingPaymentOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:fix-pending-payment {--dry-run : Mostra solo gli ordini che verrebbero aggiornati senza modificarli}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggiorna gli ordini con status pending_payment che hanno un Payment Intent (pagati ma non confermati)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        // Trova ordini con pending_payment che hanno un Payment Intent
        $orders = Order::where('status', 'pending_payment')
            ->whereNotNull('stripe_payment_intent_id')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            $this->info(' Nessun ordine trovato con status pending_payment e Payment Intent.');
            return 0;
        }

        $this->info("Trovati {$orders->count()} ordini da aggiornare:");
        $this->newLine();

        $updated = 0;
        foreach ($orders as $order) {
            $this->line("Ordine #{$order->order_number} (ID: {$order->id})");
            $this->line("  - Stato attuale: {$order->status}");
            $this->line("  - Payment Intent: {$order->stripe_payment_intent_id}");
            $this->line("  - Totale: €{$order->total_amount}");
            $this->line("  - Data: {$order->created_at}");

            if (!$dryRun) {
                $order->update([
                    'status' => 'confirmed',
                    'paid_at' => $order->paid_at ?? now()
                ]);

                $this->info("  Aggiornato a 'confirmed'");
                $updated++;

                Log::info('Ordine aggiornato da pending_payment a confirmed', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'payment_intent_id' => $order->stripe_payment_intent_id
                ]);
            } else {
                $this->comment("   Verrebbe aggiornato a 'confirmed' (dry-run)");
            }

            $this->newLine();
        }

        if ($dryRun) {
            $this->warn("  DRY RUN: Nessun ordine è stato modificato. Rimuovi --dry-run per applicare le modifiche.");
        } else {
            $this->info("{$updated} ordini aggiornati con successo!");
        }

        return 0;
    }
}

