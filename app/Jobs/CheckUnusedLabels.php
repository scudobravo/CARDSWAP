<?php

namespace App\Jobs;

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
                // Etichetta mai usata - timeout anti-frode
                Log::warning('Timeout anti-frode: etichetta non usata dopo 5 giorni', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'label_created_at' => $order->label_created_at
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

                    // Strike al venditore (TODO: implementare sistema di strike)
                    $seller = $order->seller;
                    if ($seller) {
                        Log::info('Strike applicato al venditore per etichetta non usata', [
                            'seller_id' => $seller->id,
                            'order_id' => $order->id
                        ]);
                        // TODO: Implementare sistema di strike (es. campo strikes nella tabella users)
                    }

                    // Il costo Shippo resta a carico del venditore (già addebitato quando è stata creata l'etichetta)
                    Log::info('Ordine annullato per timeout anti-frode', [
                        'order_id' => $order->id,
                        'buyer_refunded' => isset($refund) && $refund['success']
                    ]);

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

