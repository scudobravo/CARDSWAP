<?php

namespace App\Services;

use App\Models\User;
use Stripe\Stripe;
use Stripe\StripeClient;
use Stripe\Identity\VerificationSession;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StripeService
{
    private StripeClient $stripe;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Crea una sessione di verifica Stripe Identity per KYC
     */
    public function createIdentityVerificationSession(User $user, array $options = []): array
    {
        try {
            Log::info('Creating Stripe Identity session for user: ' . $user->id);
            Log::info('Stripe API Key: ' . substr(config('services.stripe.secret'), 0, 10) . '...');
            Log::info('App URL: ' . config('app.url'));
            Log::info('Environment: ' . config('app.env'));
            
            $session = VerificationSession::create([
                'type' => 'document',
                'metadata' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                ],
                'options' => [
                    'document' => [
                        'allowed_types' => ['driving_license', 'id_card', 'passport'],
                        'require_id_number' => true,
                        'require_live_capture' => true,
                        'require_matching_selfie' => true,
                    ],
                ],
                'return_url' => config('app.url') . '/dashboard/kyc',
                ...$options
            ]);

            Log::info('Stripe Identity session created successfully: ' . $session->id);
            
            return [
                'success' => true,
                'session_id' => $session->id,
                'client_secret' => $session->client_secret,
                'url' => $session->url,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Identity Error: ' . $e->getMessage());
            Log::error('Stripe Error Code: ' . $e->getStripeCode());
            Log::error('Stripe Error Type: ' . $e->getError()->type ?? 'unknown');
            Log::error('Stripe Error Param: ' . $e->getError()->param ?? 'unknown');
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stripe_code' => $e->getStripeCode(),
                'stripe_type' => $e->getError()->type ?? 'unknown',
            ];
        } catch (\Exception $e) {
            Log::error('General Error in Stripe Identity: ' . $e->getMessage());
            Log::error('Error Trace: ' . $e->getTraceAsString());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Recupera lo stato di una sessione di verifica
     */
    public function getVerificationSessionStatus(string $sessionId): array
    {
        try {
            $session = VerificationSession::retrieve($sessionId);
            
            return [
                'success' => true,
                'status' => $session->status,
                'url' => $session->url ?? null,
                'verified_outputs' => $session->verified_outputs ?? null,
                'last_verification_report' => $session->last_verification_report ?? null,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Identity Status Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Crea un account Stripe Connect per venditori
     */
    public function createConnectAccount(User $user, array $accountData = []): array
    {
        try {
            $account = Account::create([
                'type' => 'express',
                'country' => 'IT', // Italia
                'email' => $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                ],
                'business_type' => 'individual',
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
                ...$accountData
            ]);

            return [
                'success' => true,
                'account_id' => $account->id,
                'account' => $account,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect Account Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Crea un link per onboarding Stripe Connect
     */
    public function createAccountLink(string $accountId, string $returnUrl, string $refreshUrl): array
    {
        try {
            $accountLink = AccountLink::create([
                'account' => $accountId,
                'return_url' => $returnUrl,
                'refresh_url' => $refreshUrl,
                'type' => 'account_onboarding',
            ]);

            return [
                'success' => true,
                'url' => $accountLink->url,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect Link Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Recupera informazioni su un account Connect
     */
    public function getConnectAccount(string $accountId): array
    {
        try {
            $account = Account::retrieve($accountId);
            
            return [
                'success' => true,
                'account' => $account,
                'charges_enabled' => $account->charges_enabled,
                'payouts_enabled' => $account->payouts_enabled,
                'details_submitted' => $account->details_submitted,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect Account Retrieve Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Crea un link di login per account Connect
     */
    public function createLoginLink(string $accountId): array
    {
        try {
            $loginLink = Account::createLoginLink($accountId);
            
            return [
                'success' => true,
                'url' => $loginLink->url,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect Login Link Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Crea un pagamento con split automatico
     */
    public function createPaymentWithSplit(array $paymentData): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $paymentData['amount'], // in centesimi
                'currency' => $paymentData['currency'] ?? 'eur',
                'application_fee_amount' => $paymentData['application_fee'], // commissione piattaforma
                'transfer_data' => [
                    'destination' => $paymentData['seller_account_id'], // account venditore
                ],
                'metadata' => $paymentData['metadata'] ?? [],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent->client_secret,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Recupera lo stato di un pagamento
     */
    public function getPaymentStatus(string $paymentIntentId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->retrieve($paymentIntentId);
            
            return [
                'success' => true,
                'status' => $paymentIntent->status,
                'payment_intent' => $paymentIntent,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Payment Status Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Crea un trasferimento manuale
     */
    public function createTransfer(string $accountId, int $amount, string $currency = 'eur', array $metadata = []): array
    {
        try {
            $transfer = $this->stripe->transfers->create([
                'amount' => $amount,
                'currency' => $currency,
                'destination' => $accountId,
                'metadata' => $metadata,
            ]);

            return [
                'success' => true,
                'transfer' => $transfer,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Transfer Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifica webhook Stripe
     */
    public function verifyWebhook(string $payload, string $signature): bool
    {
        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
            
            return true;
        } catch (\Exception $e) {
            Log::error('Stripe Webhook Verification Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Gestisce eventi webhook
     */
    public function handleWebhookEvent(object $event): void
    {
        switch ($event->type) {
            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;
            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
            case 'payment_intent.canceled':
                $this->handlePaymentCanceled($event->data->object);
                break;
            case 'transfer.created':
                $this->handleTransferCreated($event->data->object);
                break;
            case 'transfer.failed':
                $this->handleTransferFailed($event->data->object);
                break;
            case 'refund.created':
                $this->handleRefundCreated($event->data->object);
                break;
            case 'identity.verification_session.verified':
                $this->handleIdentityVerified($event->data->object);
                break;
                
            case 'identity.verification_session.requires_input':
                $this->handleIdentityRequiresInput($event->data->object);
                break;
                
            case 'account.updated':
                $this->handleAccountUpdated($event->data->object);
                break;
                
            // duplicate cases removed below
                
            default:
                Log::info('Unhandled Stripe webhook event: ' . $event->type);
        }
    }

    /**
     * Gestisce verifica identità completata
     */
    private function handleIdentityVerified(object $session): void
    {
        $userId = $session->metadata->user_id ?? null;
        if (!$userId) return;

        $user = User::find($userId);
        if (!$user) return;

        // Aggiorna stato KYC dell'utente
        $user->updateKycStatus('approved');
        
        // Crea notifica
        $user->notifications()->create([
            'type' => 'kyc_update',
            'title' => 'Verifica identità completata',
            'message' => 'La tua verifica identità è stata completata con successo. Ora puoi vendere sulla piattaforma.',
            'data' => [
                'verification_session_id' => $session->id,
                'status' => 'approved'
            ]
        ]);
    }

    /**
     * Gestisce verifica identità che richiede input
     */
    private function handleIdentityRequiresInput(object $session): void
    {
        $userId = $session->metadata->user_id ?? null;
        if (!$userId) return;

        $user = User::find($userId);
        if (!$user) return;

        // Crea notifica
        $user->notifications()->create([
            'type' => 'kyc_update',
            'title' => 'Verifica identità richiede attenzione',
            'message' => 'La tua verifica identità richiede ulteriori informazioni. Controlla il tuo profilo.',
            'data' => [
                'verification_session_id' => $session->id,
                'status' => 'requires_input'
            ]
        ]);
    }

    /**
     * Gestisce aggiornamento account Connect
     */
    private function handleAccountUpdated(object $account): void
    {
        // Trova utente per account ID
        $user = User::where('stripe_account_id', $account->id)->first();
        if (!$user) return;

        // Aggiorna stato account
        $user->update([
            'stripe_charges_enabled' => $account->charges_enabled,
            'stripe_payouts_enabled' => $account->payouts_enabled,
            'stripe_details_submitted' => $account->details_submitted,
        ]);
    }

    // removed duplicate placeholder handlers; real implementations are below

    /**
     * Aggiorna account Stripe Connect
     */
    public function updateConnectAccount(string $accountId, array $data): array
    {
        try {
            $account = $this->stripe->accounts->update($accountId, $data);
            
            return [
                'success' => true,
                'account' => $account,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect Account Update Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Elimina account Stripe Connect
     */
    public function deleteConnectAccount(string $accountId): array
    {
        try {
            $this->stripe->accounts->delete($accountId);
            
            return [
                'success' => true,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Connect Account Delete Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Crea un pagamento multi-venditore con split automatico
     */
    public function createMultiVendorPayment(array $orderData): array
    {
        try {
            $totalAmount = $orderData['total_amount'] * 100; // Converti in centesimi
            $applicationFee = $orderData['application_fee'] * 100; // Commissione piattaforma
            
            // Crea il PaymentIntent principale
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $totalAmount,
                'currency' => $orderData['currency'] ?? 'eur',
                'application_fee_amount' => $applicationFee,
                'metadata' => [
                    'order_id' => $orderData['order_id'],
                    'buyer_id' => $orderData['buyer_id'],
                    'type' => 'multi_vendor'
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // Crea i trasferimenti per ogni venditore
            $transfers = [];
            foreach ($orderData['sellers'] as $seller) {
                $sellerAmount = $seller['amount'] * 100; // Converti in centesimi
                
                $transfer = $this->stripe->transfers->create([
                    'amount' => $sellerAmount,
                    'currency' => $orderData['currency'] ?? 'eur',
                    'destination' => $seller['stripe_account_id'],
                    'metadata' => [
                        'order_id' => $orderData['order_id'],
                        'seller_id' => $seller['seller_id'],
                        'payment_intent_id' => $paymentIntent->id,
                    ],
                ]);
                
                $transfers[] = $transfer;
            }

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent->client_secret,
                'transfers' => $transfers,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Multi-Vendor Payment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'stripe_error' => $e
            ];
        }
    }

    /**
     * Crea un pagamento con split automatico per un singolo venditore
     */
    public function createSingleVendorPayment(array $paymentData): array
    {
        try {
            $amount = $paymentData['amount'] * 100; // Converti in centesimi
            $applicationFee = $paymentData['application_fee'] * 100; // Commissione piattaforma
            
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $amount,
                'currency' => $paymentData['currency'] ?? 'eur',
                'application_fee_amount' => $applicationFee,
                'transfer_data' => [
                    'destination' => $paymentData['seller_account_id'],
                ],
                'metadata' => $paymentData['metadata'] ?? [],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent->client_secret,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Single Vendor Payment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calcola la commissione piattaforma
     */
    public function calculateApplicationFee(float $amount, float $commissionRate = 0.05): float
    {
        return $amount * $commissionRate; // 5% di default
    }

    /**
     * Gestisce pagamento riuscito
     */
    private function handlePaymentSucceeded(object $paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;
        if (!$orderId) return;

        $order = \App\Models\Order::find($orderId);
        if (!$order) return;

        // Aggiorna stato ordine
        $order->update([
            'status' => 'confirmed',
            'paid_at' => now()
        ]);

        // Conferma prenotazione quantità
        $reservationId = $paymentIntent->metadata->reservation_id ?? null;
        if ($reservationId) {
            app(\App\Services\AvailabilityService::class)->confirmReservation($reservationId);
        }

        // Invia notifiche
        $this->notifyPaymentSuccess($order, $paymentIntent);
    }

    /**
     * Gestisce pagamento fallito
     */
    private function handlePaymentFailed(object $paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;
        if (!$orderId) return;

        $order = \App\Models\Order::find($orderId);
        if (!$order) return;

        // Aggiorna stato ordine
        $order->update([
            'status' => 'payment_failed'
        ]);

        // Rilascia prenotazione quantità
        $reservationId = $paymentIntent->metadata->reservation_id ?? null;
        if ($reservationId) {
            app(\App\Services\AvailabilityService::class)->releaseReservation($reservationId);
        }

        // Invia notifiche
        $this->notifyPaymentFailed($order, $paymentIntent);
    }

    /**
     * Gestisce pagamento cancellato
     */
    private function handlePaymentCanceled(object $paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;
        if (!$orderId) return;

        $order = \App\Models\Order::find($orderId);
        if (!$order) return;

        // Aggiorna stato ordine
        $order->update([
            'status' => 'cancelled'
        ]);

        // Rilascia prenotazione quantità
        $reservationId = $paymentIntent->metadata->reservation_id ?? null;
        if ($reservationId) {
            app(\App\Services\AvailabilityService::class)->releaseReservation($reservationId);
        }

        // Invia notifiche
        $this->notifyPaymentCanceled($order, $paymentIntent);
    }

    /**
     * Gestisce trasferimento creato
     */
    private function handleTransferCreated(object $transfer): void
    {
        $sellerId = $transfer->metadata->seller_id ?? null;
        if (!$sellerId) return;

        $seller = \App\Models\User::find($sellerId);
        if (!$seller) return;

        // Invia notifica al venditore
        $seller->notifications()->create([
            'type' => 'payment_received',
            'title' => 'Pagamento ricevuto',
            'message' => 'Hai ricevuto €' . number_format($transfer->amount / 100, 2) . ' per la vendita',
            'data' => [
                'transfer_id' => $transfer->id,
                'amount' => $transfer->amount / 100,
                'currency' => $transfer->currency
            ]
        ]);
    }

    /**
     * Gestisce trasferimento fallito
     */
    private function handleTransferFailed(object $transfer): void
    {
        $sellerId = $transfer->metadata->seller_id ?? null;
        if (!$sellerId) return;

        $seller = \App\Models\User::find($sellerId);
        if (!$seller) return;

        // Invia notifica al venditore
        $seller->notifications()->create([
            'type' => 'payment_failed',
            'title' => 'Errore trasferimento',
            'message' => 'Si è verificato un errore nel trasferimento di €' . number_format($transfer->amount / 100, 2),
            'data' => [
                'transfer_id' => $transfer->id,
                'amount' => $transfer->amount / 100,
                'currency' => $transfer->currency
            ]
        ]);
    }

    /**
     * Gestisce rimborso creato
     */
    private function handleRefundCreated(object $refund): void
    {
        $orderId = $refund->metadata->order_id ?? null;
        if (!$orderId) return;

        $order = \App\Models\Order::find($orderId);
        if (!$order) return;

        // Aggiorna stato ordine
        $order->update([
            'status' => 'refunded',
            'refunded_at' => now(),
            'refund_reason' => $refund->reason ?? 'Rimborso richiesto'
        ]);

        // Invia notifiche
        $this->notifyRefundCreated($order, $refund);
    }

    /**
     * Notifica pagamento riuscito
     */
    private function notifyPaymentSuccess(\App\Models\Order $order, object $paymentIntent): void
    {
        // Notifica acquirente
        $order->buyer->notifications()->create([
            'type' => 'payment_received',
            'title' => 'Pagamento confermato',
            'message' => 'Il pagamento per l\'ordine #' . $order->order_number . ' è stato confermato',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'amount' => $order->total_amount,
                'payment_intent_id' => $paymentIntent->id
            ]
        ]);

        // Notifica venditori
        $sellers = $order->getSellers();
        foreach ($sellers as $seller) {
            $seller->notifications()->create([
                'type' => 'order_confirmed',
                'title' => 'Ordine confermato',
                'message' => 'L\'ordine #' . $order->order_number . ' è stato confermato e pagato',
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'buyer_name' => $order->buyer->name
                ]
            ]);
        }

        // Email di conferma ordine all'acquirente
        try {
            $emailData = $this->buildOrderEmailData($order);
            Mail::send('emails.order-confirmation', $emailData, function ($message) use ($order) {
                $message->to($order->buyer->email, (string) $order->buyer->name)
                        ->subject('Conferma ordine #' . $order->order_number);
            });
        } catch (\Throwable $e) {
            Log::error('Order confirmation email failed: ' . $e->getMessage());
        }
    }

    /**
     * Notifica pagamento fallito
     */
    private function notifyPaymentFailed(\App\Models\Order $order, object $paymentIntent): void
    {
        $order->buyer->notifications()->create([
            'type' => 'payment_failed',
            'title' => 'Pagamento fallito',
            'message' => 'Il pagamento per l\'ordine #' . $order->order_number . ' non è riuscito',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_intent_id' => $paymentIntent->id
            ]
        ]);
    }

    /**
     * Notifica pagamento cancellato
     */
    private function notifyPaymentCanceled(\App\Models\Order $order, object $paymentIntent): void
    {
        $order->buyer->notifications()->create([
            'type' => 'payment_canceled',
            'title' => 'Pagamento cancellato',
            'message' => 'Il pagamento per l\'ordine #' . $order->order_number . ' è stato cancellato',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'payment_intent_id' => $paymentIntent->id
            ]
        ]);
    }

    /**
     * Notifica rimborso creato
     */
    private function notifyRefundCreated(\App\Models\Order $order, object $refund): void
    {
        $order->buyer->notifications()->create([
            'type' => 'refund_created',
            'title' => 'Rimborso elaborato',
            'message' => 'Il rimborso per l\'ordine #' . $order->order_number . ' è stato elaborato',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100
            ]
        ]);
    }

    /**
     * Prepara i dati per le email d'ordine
     */
    private function buildOrderEmailData(\App\Models\Order $order): array
    {
        $order->load(['buyer', 'orderItems.cardListing.cardModel']);

        $items = [];
        foreach ($order->orderItems as $orderItem) {
            $items[] = [
                'title' => $orderItem->cardListing->cardModel->name ?? 'Carta',
                'quantity' => $orderItem->quantity,
                'total_price' => (float) $orderItem->total_price,
                'condition' => $orderItem->condition,
            ];
        }

        $totals = [
            'subtotal' => (float) $order->subtotal,
            'shipping' => (float) $order->shipping_cost,
            'tax' => (float) $order->tax_amount,
            'total' => (float) $order->total_amount,
        ];

        $shipping = is_array($order->shipping_address) ? $order->shipping_address : [];

        $buyer = [
            'name' => (string) $order->buyer->name,
            'email' => (string) $order->buyer->email,
        ];

        $orderData = [
            'id' => $order->id,
            'order_number' => (string) $order->order_number,
            'total_amount' => (float) $order->total_amount,
        ];

        return [
            'order' => $orderData,
            'buyer' => $buyer,
            'items' => $items,
            'totals' => $totals,
            'shipping' => $shipping,
        ];
    }

    /**
     * Crea un refund parziale o completo
     */
    public function createRefund(string $paymentIntentId, ?int $amount = null, array $metadata = []): array
    {
        try {
            $refundData = [
                'payment_intent' => $paymentIntentId,
                'metadata' => $metadata,
            ];
            
            if ($amount !== null) {
                $refundData['amount'] = $amount;
            }
            
            $refund = $this->stripe->refunds->create($refundData);
            
            return [
                'success' => true,
                'refund' => $refund,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Refund Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Recupera i dettagli di un refund
     */
    public function getRefund(string $refundId): array
    {
        try {
            $refund = $this->stripe->refunds->retrieve($refundId);
            
            return [
                'success' => true,
                'refund' => $refund,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Refund Retrieve Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Lista tutti i refund per un PaymentIntent
     */
    public function listRefunds(string $paymentIntentId): array
    {
        try {
            $refunds = $this->stripe->refunds->all([
                'payment_intent' => $paymentIntentId,
            ]);
            
            return [
                'success' => true,
                'refunds' => $refunds->data,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe Refunds List Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Crea un PaymentMethod
     */
    public function createPaymentMethod(array $cardData): array
    {
        try {
            $paymentMethod = $this->stripe->paymentMethods->create([
                'type' => 'card',
                'card' => [
                    'number' => $cardData['number'],
                    'exp_month' => $cardData['exp_month'],
                    'exp_year' => $cardData['exp_year'],
                    'cvc' => $cardData['cvc'],
                ],
                'billing_details' => $cardData['billing_details'] ?? [],
            ]);
            
            return [
                'success' => true,
                'payment_method' => $paymentMethod,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentMethod Create Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Conferma un PaymentIntent
     */
    public function confirmPaymentIntent(string $paymentIntentId, string $paymentMethodId): array
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->confirm($paymentIntentId, [
                'payment_method' => $paymentMethodId,
            ]);
            
            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Stripe PaymentIntent Confirm Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
