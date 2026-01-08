<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job schedulato che controlla periodicamente gli ordini pronti per il payout
 * Fallback per assicurarsi che i fondi vengano rilasciati anche se il job dispatchato con delay non viene processato
 */
class ProcessScheduledPayouts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('ProcessScheduledPayouts job started');

        // Trova ordini in delivered_pending_72h con payout_scheduled_at <= now() e payout_status = pending_payout
        $orders = Order::where('status', 'delivered_pending_72h')
            ->where('payout_status', 'pending_payout')
            ->whereNotNull('payout_scheduled_at')
            ->where('payout_scheduled_at', '<=', now())
            ->where('has_dispute', false)
            ->get();

        Log::info('Ordini trovati per payout', ['count' => $orders->count()]);

        foreach ($orders as $order) {
            Log::info('Processing scheduled payout for order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payout_scheduled_at' => $order->payout_scheduled_at
            ]);

            // Dispatcha il job ReleaseSellerFunds per questo ordine
            ReleaseSellerFunds::dispatch($order);
        }

        Log::info('ProcessScheduledPayouts job completed', ['processed' => $orders->count()]);
    }
}
