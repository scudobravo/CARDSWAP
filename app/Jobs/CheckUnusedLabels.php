<?php

namespace App\Jobs;

use App\Helpers\ShippingAuditLog;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Services\StripeService;

class CheckUnusedLabels implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(StripeService $stripeService): void
    {
        // Trova ordini con etichetta creata da più di 5 giorni
        // ma senza evento CARRIER_ACCEPTED (etichetta mai usata)
        $cutoffDate = now()->subDays(5);
        
        Log::info('Checking for unused labels (timeout anti-frode)', [
            'cutoff_date' => $cutoffDate,
            'checking_status' => 'label_created'
        ]);
        
        // Nota: NON usiamo whereNull('shipped_at') perché quando viene creata l'etichetta
        // viene impostato shipped_at. Verificheremo invece se ci sono eventi di tracking
        $orders = Order::where('status', 'label_created')
            ->where('label_created_at', '<=', $cutoffDate)
            ->get();
        
        Log::info('Found orders with label_created status older than 5 days', [
            'count' => $orders->count()
        ]);

        foreach ($orders as $order) {
            // AUDIT-FIX: refresh prima di agire per evitare race (stato può essere cambiato da webhook/altro job)
            $order->refresh();
            // Verifica se c'è un evento di tracking che indica che il corriere ha accettato il pacco
            // Usa whereIn per evitare problemi con orWhere
            $hasCarrierAccepted = $order->trackingEvents()
                ->whereIn('status', ['picked_up', 'in_transit', 'out_for_delivery'])
                ->exists();
            
            Log::debug('Checking order for unused label', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'label_created_at' => $order->label_created_at,
                'has_carrier_accepted' => $hasCarrierAccepted,
                'tracking_events_count' => $order->trackingEvents()->count()
            ]);

            if (!$hasCarrierAccepted) {
                // D5 / AUDIT-FIX: skip se ordine non più modificabile (cancelled, refunded, dispute, completed)
                if (in_array($order->status, ['cancelled', 'refunded', 'dispute_hold', 'completed'], true) || $order->has_dispute) {
                    Log::info('D5-JOB: CheckUnusedLabels skip – ordine non modificabile', ['order_id' => $order->id, 'status' => $order->status, 'has_dispute' => $order->has_dispute]);
                    continue;
                }

                // Etichetta mai usata - timeout anti-frode
                Log::warning('D5-JOB: Timeout anti-frode – etichetta non usata dopo 5 giorni', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'label_created_at' => $order->label_created_at,
                ]);

                try {
                    // Annulla ordine
                    $order->update([
                        'status' => 'cancelled',
                        'payout_status' => 'cancelled',
                        'notes' => ($order->notes ?? '') . "\n[Auto-cancellato] Etichetta non usata dopo 5 giorni - timeout anti-frode"
                    ]);

                    // Rimborso buyer
                    if ($order->stripe_payment_intent_id) {
                        $refundAmountInCents = (int) round($order->total_amount * 100);
                        $refund = $stripeService->createRefund(
                            $order->stripe_payment_intent_id,
                            $refundAmountInCents,
                            [
                                'order_id' => $order->id,
                                'order_number' => $order->order_number,
                                'reason' => 'timeout_anti_frode'
                            ]
                        );
                        
                        if ($refund['success']) {
                            $order->update([
                                'refunded_at' => now(),
                                'refund_reason' => 'Etichetta non usata dopo 5 giorni - timeout anti-frode'
                            ]);
                        }
                    }

                    $order->load(['seller', 'buyer']);

                    // D5: audit log ordine cancellato (source=job)
                    ShippingAuditLog::log(
                        ShippingAuditLog::ACTION_ORDER_CANCELLED,
                        ShippingAuditLog::SOURCE_JOB,
                        (int) $order->id,
                        (int) $order->seller_id,
                        (int) $order->buyer_id,
                        ['reason' => 'unused_label_timeout', 'buyer_refunded' => isset($refund) && ($refund['success'] ?? false)]
                    );

                    // D5: anti-abuse seller – log evento negativo (monitoraggio, nessun blocco in V1)
                    if ($order->seller_id) {
                        ShippingAuditLog::logSellerNegativeEvent('unused_label_cancelled', (int) $order->id, (int) $order->seller_id, [
                            'label_created_at' => $order->label_created_at?->toIso8601String(),
                        ]);
                    }

                    Log::info('Ordine annullato per timeout anti-frode', [
                        'order_id' => $order->id,
                        'buyer_refunded' => isset($refund) && $refund['success'],
                    ]);

                    event(new \App\Events\OrderCancelled($order));
                    if ($order->refunded_at) {
                        event(new \App\Events\OrderRefunded($order));
                    }

                } catch (\Exception $e) {
                    Log::error('Errore durante timeout anti-frode', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }
    }
}

