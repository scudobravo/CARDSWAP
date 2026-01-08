<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\StripeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ReleaseSellerFunds implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $order;

    /**
     * Create a new job instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     */
    public function handle(StripeService $stripeService): void
    {
        // Usa lock pessimistico per evitare race conditions con openDispute
        $order = \Illuminate\Support\Facades\DB::transaction(function () {
            return Order::where('id', $this->order->id)
                ->lockForUpdate()
                ->first();
        });
        
        if (!$order) {
            Log::warning('Ordine non trovato per rilascio fondi', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number ?? 'N/A'
            ]);
            return;
        }

        Log::info('ReleaseSellerFunds job started', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'current_status' => $order->status,
            'payout_status' => $order->payout_status,
            'has_dispute' => $order->has_dispute,
            'payout_scheduled_at' => $order->payout_scheduled_at,
            'delivered_at' => $order->delivered_at,
            'payout_scheduled_hours_ago' => $order->payout_scheduled_at ? now()->diffInHours($order->payout_scheduled_at) : null
        ]);

        // Verifica che l'ordine sia nello stato corretto
        if ($order->status !== 'delivered_pending_72h') {
            Log::warning('Ordine non in stato delivered_pending_72h, salto rilascio fondi', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'current_status' => $order->status,
                'expected_status' => 'delivered_pending_72h',
                'payout_status' => $order->payout_status,
                'has_dispute' => $order->has_dispute
            ]);
            return;
        }

        // Verifica se il payout è già stato completato (evita duplicati)
        if ($order->payout_status === 'paid') {
            Log::warning('Payout already completed for order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payout_completed_at' => $order->payout_completed_at,
                'stripe_transfer_id' => $order->stripe_transfer_id,
                'current_status' => $order->status
            ]);
            return;
        }

        // Verifica se c'è una dispute aperta (con lock per evitare race condition)
        if ($order->has_dispute) {
            Log::info('Dispute aperta per ordine, blocco payout', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'dispute_opened_at' => $order->dispute_opened_at,
                'payout_status' => $order->payout_status,
                'current_status' => $order->status
            ]);
            
            // Aggiorna solo se non è già in dispute_hold
            if ($order->status !== 'dispute_hold' || $order->payout_status !== 'dispute_hold') {
                $order->update([
                    'status' => 'dispute_hold',
                    'payout_status' => 'dispute_hold'
                ]);
                
                Log::info('Stato ordine aggiornato a dispute_hold da ReleaseSellerFunds', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number
                ]);
            }
            return;
        }

        // Verifica che ci sia un importo da pagare
        if (!$order->seller_payout_amount || $order->seller_payout_amount <= 0) {
            Log::warning('Importo payout zero o mancante', ['order_id' => $order->id]);
            return;
        }

        // Verifica che il venditore abbia Stripe Connect configurato
        $seller = $order->seller;
        if (!$seller || !$seller->stripe_account_id) {
            Log::error('Venditore senza Stripe Connect configurato', [
                'order_id' => $order->id,
                'seller_id' => $order->seller_id
            ]);
            return;
        }

        try {
            // Verifica nuovamente dispute dopo il lock (doppio controllo per sicurezza)
            $order->refresh();
            if ($order->has_dispute) {
                Log::warning('Dispute rilevata dopo lock in ReleaseSellerFunds, blocco payout', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'dispute_opened_at' => $order->dispute_opened_at
                ]);
                $order->update([
                    'status' => 'dispute_hold',
                    'payout_status' => 'dispute_hold'
                ]);
                return;
            }

            // Crea il trasferimento Stripe (94% del subtotale)
            $amountInCents = (int) round($order->seller_payout_amount * 100);
            
            Log::info('Iniziando trasferimento Stripe per venditore', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'seller_id' => $seller->id,
                'seller_email' => $seller->email,
                'seller_stripe_account_id' => $seller->stripe_account_id,
                'amount_euros' => $order->seller_payout_amount,
                'amount_cents' => $amountInCents,
                'payment_intent_id' => $order->stripe_payment_intent_id
            ]);
            
            $transfer = $stripeService->createTransfer(
                $seller->stripe_account_id,
                $amountInCents,
                'eur',
                [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'seller_id' => $seller->id,
                    'payment_intent_id' => $order->stripe_payment_intent_id
                ]
            );

            if ($transfer['success']) {
                // Aggiorna lo stato dell'ordine
                $order->update([
                    'status' => 'completed',
                    'payout_status' => 'paid',
                    'payout_completed_at' => now(),
                    'stripe_transfer_id' => $transfer['transfer']->id
                ]);

                Log::info('Fondi rilasciati al venditore con successo', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'seller_id' => $seller->id,
                    'seller_email' => $seller->email,
                    'amount_euros' => $order->seller_payout_amount,
                    'amount_cents' => $amountInCents,
                    'transfer_id' => $transfer['transfer']->id,
                    'payout_completed_at' => $order->payout_completed_at,
                    'delivered_at' => $order->delivered_at,
                    'hours_since_delivery' => $order->delivered_at ? now()->diffInHours($order->delivered_at) : null,
                    'payout_scheduled_at' => $order->payout_scheduled_at,
                    'hours_since_scheduled' => $order->payout_scheduled_at ? now()->diffInHours($order->payout_scheduled_at) : null
                ]);
            } else {
                Log::error('Errore nel trasferimento fondi Stripe', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'seller_id' => $seller->id,
                    'seller_email' => $seller->email,
                    'amount_euros' => $order->seller_payout_amount,
                    'amount_cents' => $amountInCents,
                    'error' => $transfer['error'] ?? 'Unknown error',
                    'stripe_error' => $transfer['stripe_error'] ?? null
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Eccezione durante rilascio fondi', [
                'order_id' => $order->id,
                'order_number' => $order->order_number ?? 'N/A',
                'seller_id' => $seller->id ?? null,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

