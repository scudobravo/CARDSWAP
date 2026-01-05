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
        $stripeSecret = config('services.stripe.secret');
        
        if (empty($stripeSecret)) {
            Log::error('Stripe secret key is not configured');
            throw new \Exception('Stripe secret key is not configured');
        }
        
        if (!str_starts_with($stripeSecret, 'sk_')) {
            Log::error('Invalid Stripe secret key format');
            throw new \Exception('Invalid Stripe secret key format');
        }
        
        // Verifica che in locale si usino chiavi di test
        if (config('app.env') === 'local' && str_starts_with($stripeSecret, 'sk_live_')) {
            Log::warning('ATTENZIONE: Stai usando chiavi LIVE in ambiente locale. Usa chiavi TEST (sk_test_...)');
        }
        
        Stripe::setApiKey($stripeSecret);
        $this->stripe = new StripeClient($stripeSecret);
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
            Log::info('Stripe Identity Enabled: ' . (config('services.stripe.identity_enabled') ? 'true' : 'false'));
            
            // Verifica che Stripe Identity sia abilitato
            if (!config('services.stripe.identity_enabled')) {
                Log::error('Stripe Identity is not enabled in configuration');
                return [
                    'success' => false,
                    'error' => 'Stripe Identity is not enabled',
                ];
            }
            
            // IMPORTANTE: Stripe determina il paese dai dati utente (indirizzo)
            // Assicuriamoci che l'utente abbia un indirizzo italiano configurato
            $userCountry = $user->country ?? null;
            
            // Verifica se l'utente ha un indirizzo predefinito
            $defaultAddress = $user->defaultAddress;
            if ($defaultAddress && $defaultAddress->country) {
                $userCountry = $defaultAddress->country;
            }
            
            // Se non abbiamo un paese, usa IT come default
            if (!$userCountry) {
                $userCountry = 'IT';
                Log::warning('Utente ' . $user->id . ' non ha un paese configurato, usando IT come default');
            }
            
            // Verifica che il paese sia IT (Italia) - Stripe Identity funziona meglio con dati completi
            if ($userCountry !== 'IT') {
                Log::warning('Utente ' . $user->id . ' ha paese ' . $userCountry . ' invece di IT. Stripe potrebbe richiedere documenti diversi.');
            }
            
            // IMPORTANTE: Stripe Identity NON supporta 'provided_details' nella creazione della VerificationSession
            // Stripe determina il paese dall'account Stripe principale o dai dati che l'utente inserisce nel form
            // Il paese viene determinato quando l'utente compila il form di verifica su Stripe
            
            Log::info('Creating verification session for user ' . $user->id . ' (User country: ' . ($userCountry ?? 'IT') . ')');
            
            // Prepara le opzioni per la sessione di verifica
            // Stripe determina il paese dall'account principale o dai dati inseriti dall'utente nel form
            $sessionOptions = [
                'type' => 'document',
                'metadata' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_country' => $userCountry, // Aggiungiamo nel metadata (non influisce sul paese richiesto)
                ],
                'options' => [
                    'document' => [
                        // Tipi di documento compatibili con l'Italia: carta d'identità, patente, passaporto
                        'allowed_types' => ['id_card', 'driving_license', 'passport'],
                        // PROVA: disabilitiamo require_id_number e lasciamo che Stripe lo determini dal documento
                        // Se l'utente carica un documento italiano, Stripe dovrebbe richiedere codice fiscale, non SSN
                        'require_id_number' => false, // CAMBIATO: false per evitare richiesta SSN forzata
                        'require_live_capture' => true,
                        'require_matching_selfie' => true,
                    ],
                ],
                'return_url' => config('app.url') . '/dashboard/kyc',
                // Nota: Stripe Identity non supporta il parametro 'locale' nella VerificationSession
                // La localizzazione viene gestita automaticamente da Stripe in base al paese dell'utente
            ];

            // Applica opzioni aggiuntive se fornite
            $sessionOptions = array_merge_recursive($sessionOptions, $options);

            $session = VerificationSession::create($sessionOptions);

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
     * Resetta una sessione di verifica (pulisce i dati locali)
     * Nota: Stripe non permette di cancellare le sessioni, ma possiamo resettare i dati locali
     * per permettere all'utente di creare una nuova sessione
     */
    public function resetVerificationSession(User $user): array
    {
        try {
            $sessionId = $user->stripe_verification_session_id;
            
            if ($sessionId) {
                // Verifica lo stato della sessione prima di resettare
                try {
                    $sessionStatus = $this->getVerificationSessionStatus($sessionId);
                    Log::info('Resetting verification session ' . $sessionId . ' with status: ' . ($sessionStatus['status'] ?? 'unknown'));
                } catch (\Exception $e) {
                    Log::warning('Could not retrieve session status before reset: ' . $e->getMessage());
                }
            }
            
            // Resetta i dati locali dell'utente
            $user->update([
                'stripe_verification_session_id' => null,
                'kyc_status' => 'not_submitted',
                'kyc_submitted_at' => null,
                'kyc_verified_at' => null,
                'kyc_rejection_reason' => null,
                // Non resettiamo stripe_identity_verified se era già verificato
                // perché potrebbe essere ancora valido
            ]);
            
            Log::info('Verification session reset for user: ' . $user->id);
            
            return [
                'success' => true,
                'message' => 'Sessione di verifica resettata con successo'
            ];
        } catch (\Exception $e) {
            Log::error('Error resetting verification session: ' . $e->getMessage());
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
            
            // Messaggi di errore più user-friendly
            $errorMessage = $e->getMessage();
            
            if (str_contains($errorMessage, 'signed up for Connect')) {
                $errorMessage = 'Stripe Connect Marketplace non è abilitato nel tuo account Stripe principale. ' .
                    'Poiché CardSwap gestisce più venditori, devi configurare Stripe Connect come Marketplace. ' .
                    'Vai su https://dashboard.stripe.com/connect/overview e segui la guida: https://docs.stripe.com/connect/marketplace';
            } elseif (str_contains($errorMessage, 'Invalid API Key')) {
                $errorMessage = 'Chiave API Stripe non valida. Verifica le credenziali nel file .env';
            } elseif (str_contains($errorMessage, 'rate limit')) {
                $errorMessage = 'Troppe richieste a Stripe. Riprova tra qualche minuto.';
            }
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'stripe_error_code' => $e->getStripeCode(),
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
            
            $errorMessage = $e->getMessage();
            
            // Messaggio più chiaro per errore HTTPS in live mode
            if (str_contains($errorMessage, 'Livemode requests must always be redirected via HTTPS')) {
                $errorMessage = 'Stai usando chiavi API di PRODUZIONE (live mode) in locale. ' .
                    'In locale devi usare chiavi di TEST (sk_test_... e pk_test_...). ' .
                    'Vai su https://dashboard.stripe.com/test/apikeys e copia le chiavi di test nel file .env';
            } elseif (str_contains($errorMessage, 'HTTPS')) {
                $errorMessage = 'Stripe richiede HTTPS per le URL di ritorno in live mode. ' .
                    'In locale, usa chiavi di TEST (sk_test_...) invece di chiavi LIVE (sk_live_...).';
            }
            
            return [
                'success' => false,
                'error' => $errorMessage,
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
     * Rimborsa un pagamento
     */
    public function refundPayment(string $paymentIntentId, float $amount, string $reason = 'requested_by_customer'): array
    {
        try {
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $paymentIntentId,
                'amount' => (int) round($amount * 100), // Converti in centesimi
                'reason' => $reason,
            ]);

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
            
            // Per pagamenti multi-venditore, NON possiamo usare application_fee_amount sul PaymentIntent principale
            // perché Stripe richiede transfer_data[destination] quando si usa application_fee_amount
            // Invece, creiamo il PaymentIntent senza application_fee e gestiamo i trasferimenti dopo la conferma
            // La commissione viene calcolata e trattenuta nei trasferimenti (venditore riceve 94%, piattaforma trattiene 6%)
            
            // Crea il PaymentIntent principale SENZA application_fee_amount
            // I trasferimenti verranno creati dopo che il pagamento è confermato (via webhook)
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $totalAmount,
                'currency' => $orderData['currency'] ?? 'eur',
                'metadata' => [
                    'order_id' => $orderData['order_id'],
                    'buyer_id' => $orderData['buyer_id'],
                    'type' => 'multi_vendor',
                    'application_fee' => $orderData['application_fee'] * 100, // Salva la commissione nei metadata
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // NOTA: I trasferimenti NON vengono creati qui perché il pagamento non è ancora confermato
            // Verranno creati automaticamente quando il webhook riceve payment_intent.succeeded
            // Questo evita il problema "Can only apply an application_fee_amount when using transfer_data[destination]"

            return [
                'success' => true,
                'payment_intent' => $paymentIntent,
                'client_secret' => $paymentIntent->client_secret,
                'transfers' => [], // I trasferimenti verranno creati via webhook
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
    public function calculateApplicationFee(float $amount, float $commissionRate = 0.06): float
    {
        return $amount * $commissionRate; // 6% Commissione venditore CardSwap
    }

    /**
     * Gestisce pagamento riuscito
     */
    private function handlePaymentSucceeded(object $paymentIntent): void
    {
        $orderId = $paymentIntent->metadata->order_id ?? null;
        if (!$orderId) {
            Log::warning('Payment succeeded but no order_id in metadata', [
                'payment_intent_id' => $paymentIntent->id,
                'metadata' => $paymentIntent->metadata->toArray()
            ]);
            return;
        }

        $order = \App\Models\Order::find($orderId);
        if (!$order) {
            Log::error('Order not found for payment succeeded webhook', [
                'order_id' => $orderId,
                'payment_intent_id' => $paymentIntent->id
            ]);
            return;
        }

        // Verifica che l'ordine sia in uno stato valido per essere aggiornato
        $validStatuses = ['pending', 'pending_payment'];
        if (!in_array($order->status, $validStatuses)) {
            Log::warning('Order status not valid for payment succeeded update', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'current_status' => $order->status,
                'payment_intent_id' => $paymentIntent->id,
                'valid_statuses' => $validStatuses
            ]);
            // Se è già paid_funds_held, potrebbe essere un webhook duplicato - log e continua
            if ($order->status === 'paid_funds_held') {
                Log::info('Payment succeeded webhook received but order already in paid_funds_held (duplicate webhook?)', [
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntent->id
                ]);
            }
            return;
        }

        Log::info('Updating order to paid_funds_held from payment succeeded webhook', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'previous_status' => $order->status,
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100
        ]);

        // Aggiorna stato ordine - FONDI TRATTENUTI, NON TRASFERITI
        // I fondi vengono trattenuti da CardSwap fino a 72h dopo la consegna
        $order->update([
            'status' => 'paid_funds_held', // Nuovo stato: fondi pagati ma trattenuti
            'paid_at' => now()
        ]);

        Log::info('Order updated to paid_funds_held successfully', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'seller_payout_amount' => $order->seller_payout_amount,
            'payout_status' => $order->payout_status
        ]);

        // NON creare trasferimenti immediati - i fondi devono essere trattenuti
        // I trasferimenti verranno creati solo dopo 72h dalla consegna (via webhook Shippo DELIVERED)
        // Questo previene truffe: il venditore riceve i fondi solo dopo che il pacco è stato consegnato

        // Conferma prenotazione quantità
        $reservationId = $paymentIntent->metadata->reservation_id ?? null;
        if ($reservationId) {
            app(\App\Services\AvailabilityService::class)->confirmReservation($reservationId);
        }

        // Invia notifiche
        $this->notifyPaymentSuccess($order, $paymentIntent);
    }

    /**
     * Crea trasferimenti per ordine multi-venditore
     */
    private function createTransfersForMultiVendorOrder(\App\Models\Order $order, object $paymentIntent): void
    {
        try {
            // Carica orderItems con le relazioni necessarie
            $order->load(['orderItems.cardListing.seller']);
            
            // Raggruppa gli orderItems per venditore
            $sellersData = [];
            foreach ($order->orderItems as $item) {
                $listing = $item->cardListing;
                if (!$listing || !$listing->seller) {
                    Log::warning("OrderItem {$item->id} non ha listing o seller valido", [
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                    ]);
                    continue;
                }
                
                $sellerId = $listing->seller_id;
                if (!isset($sellersData[$sellerId])) {
                    $seller = $listing->seller;
                    if (!$seller->stripe_account_id) {
                        Log::warning("Venditore {$sellerId} non ha Stripe Connect configurato, salto trasferimento", [
                            'order_id' => $order->id,
                            'seller_id' => $sellerId,
                        ]);
                        continue;
                    }
                    
                    $sellersData[$sellerId] = [
                        'seller' => $seller,
                        'amount' => 0,
                    ];
                }
                
                // Aggiungi il totale dell'item (94% va al venditore, 6% è commissione)
                $sellersData[$sellerId]['amount'] += $item->total_price;
            }

            if (empty($sellersData)) {
                Log::warning("Nessun venditore valido trovato per ordine {$order->id}");
                return;
            }

            // Crea trasferimenti per ogni venditore
            foreach ($sellersData as $sellerData) {
                $seller = $sellerData['seller'];
                $sellerSubtotal = $sellerData['amount'];
                $sellerAmount = (int) round($sellerSubtotal * 0.94 * 100); // 94% del subtotale in centesimi
                
                if ($sellerAmount <= 0) {
                    Log::warning("Importo trasferimento zero per venditore {$seller->id}, salto", [
                        'order_id' => $order->id,
                        'seller_id' => $seller->id,
                        'subtotal' => $sellerSubtotal,
                    ]);
                    continue;
                }
                
                $transfer = $this->stripe->transfers->create([
                    'amount' => $sellerAmount,
                    'currency' => 'eur',
                    'destination' => $seller->stripe_account_id,
                    'metadata' => [
                        'order_id' => $order->id,
                        'seller_id' => $seller->id,
                        'payment_intent_id' => $paymentIntent->id,
                    ],
                ]);
                
                Log::info("Trasferimento creato per venditore", [
                    'seller_id' => $seller->id,
                    'transfer_id' => $transfer->id,
                    'amount' => $sellerAmount / 100,
                    'order_id' => $order->id,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Errore nella creazione trasferimenti multi-venditore: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'payment_intent_id' => $paymentIntent->id,
                'trace' => $e->getTraceAsString(),
            ]);
            // Non blocchiamo il processo, l'ordine è già confermato
        }
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
            'message' => 'Hai ricevuto €' . number_format($transfer->amount / 100, 2, ',', '.') . ' per la vendita', // Formato italiano: punto per migliaia, virgola per decimali
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
            'message' => 'Si è verificato un errore nel trasferimento di €' . number_format($transfer->amount / 100, 2, ',', '.'), // Formato italiano: punto per migliaia, virgola per decimali
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
