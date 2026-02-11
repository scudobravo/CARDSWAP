<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

        // (1) Ordini tracciati: delivered_pending_72h con payout_scheduled_at <= now
        // (2) Ordini non tracciati: shipped senza tracking, payout_scheduled_at <= now (72h da mark-shipped)
        $orders = Order::where(function ($q) {
            $q->where('status', 'delivered_pending_72h')
                ->orWhere(function ($q2) {
                    $q2->where('status', 'shipped')
                        ->where(function ($q3) {
                            $q3->whereNull('tracking_number')->orWhere('tracking_number', '');
                        });
                });
        })
            ->where(function ($q) {
                $q->where('payout_status', 'pending_payout')->orWhereNull('payout_status');
            })
            ->whereNotNull('payout_scheduled_at')
            ->where('payout_scheduled_at', '<=', now())
            ->where('has_dispute', false)
            ->get();

        Log::info('Ordini trovati per payout', ['count' => $orders->count()]);

        foreach ($orders as $order) {
            // D5: verifica stato prima di agire – skip se ordine non più modificabile
            $order->refresh();
            $alreadyPaidOrHold = in_array($order->payout_status, ['paid', 'dispute_hold', 'cancelled'], true);
            $validForPayout = $order->status === 'delivered_pending_72h'
                || ($order->status === 'shipped' && empty($order->tracking_number));
            if (!$validForPayout || $order->has_dispute || $alreadyPaidOrHold) {
                Log::info('D5-JOB: ProcessScheduledPayouts skip ordine', [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'has_dispute' => $order->has_dispute,
                    'payout_status' => $order->payout_status,
                ]);
                continue;
            }
            Log::info('D5-JOB: ProcessScheduledPayouts dispatch ReleaseSellerFunds', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);
            ReleaseSellerFunds::dispatch($order);
        }

        Log::info('ProcessScheduledPayouts job completed', ['processed' => $orders->count()]);
    }
}
