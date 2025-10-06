<?php

namespace App\Services;

use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

class StripeErrorService
{
    /**
     * Gestisce errori Stripe e restituisce messaggi user-friendly
     */
    public function handleStripeError(ApiErrorException $e): array
    {
        $errorCode = $e->getStripeCode();
        $errorType = $e->getStripeParam();
        
        Log::error('Stripe Error: ' . $e->getMessage(), [
            'code' => $errorCode,
            'type' => $errorType,
            'decline_code' => $e->getDeclineCode() ?? null
        ]);

        return match($errorCode) {
            'card_declined' => $this->handleCardDeclined($e),
            'expired_card' => $this->handleExpiredCard(),
            'incorrect_cvc' => $this->handleIncorrectCvc(),
            'processing_error' => $this->handleProcessingError(),
            'authentication_required' => $this->handleAuthenticationRequired(),
            'insufficient_funds' => $this->handleInsufficientFunds(),
            'withdraw_count_limit_exceeded' => $this->handleWithdrawLimitExceeded(),
            'duplicate_transaction' => $this->handleDuplicateTransaction(),
            'invalid_request_error' => $this->handleInvalidRequest($e),
            'api_error' => $this->handleApiError($e),
            'rate_limit_error' => $this->handleRateLimitError(),
            default => $this->handleGenericError($e)
        };
    }

    /**
     * Gestisce carte rifiutate
     */
    private function handleCardDeclined(ApiErrorException $e): array
    {
        $declineCode = $e->getDeclineCode();
        
        return match($declineCode) {
            'generic_decline' => [
                'user_message' => 'La tua carta è stata rifiutata. Contatta la tua banca per maggiori informazioni.',
                'action' => 'contact_bank',
                'retry' => false
            ],
            'insufficient_funds' => [
                'user_message' => 'Fondi insufficienti sulla carta. Verifica il saldo disponibile.',
                'action' => 'check_balance',
                'retry' => true
            ],
            'lost_card' => [
                'user_message' => 'La carta è stata segnalata come smarrita. Contatta la tua banca.',
                'action' => 'contact_bank',
                'retry' => false
            ],
            'stolen_card' => [
                'user_message' => 'La carta è stata segnalata come rubata. Contatta la tua banca.',
                'action' => 'contact_bank',
                'retry' => false
            ],
            'expired_card' => [
                'user_message' => 'La carta è scaduta. Usa una carta diversa.',
                'action' => 'use_different_card',
                'retry' => false
            ],
            'incorrect_cvc' => [
                'user_message' => 'Il codice CVC non è corretto. Verifica i dati della carta.',
                'action' => 'check_cvc',
                'retry' => true
            ],
            'incorrect_number' => [
                'user_message' => 'Il numero della carta non è corretto. Verifica i dati inseriti.',
                'action' => 'check_card_number',
                'retry' => true
            ],
            'invalid_expiry_month' => [
                'user_message' => 'Il mese di scadenza non è valido. Verifica la data di scadenza.',
                'action' => 'check_expiry',
                'retry' => true
            ],
            'invalid_expiry_year' => [
                'user_message' => 'L\'anno di scadenza non è valido. Verifica la data di scadenza.',
                'action' => 'check_expiry',
                'retry' => true
            ],
            'card_velocity_exceeded' => [
                'user_message' => 'Troppi tentativi di pagamento. Riprova tra qualche minuto.',
                'action' => 'wait_and_retry',
                'retry' => true
            ],
            'currency_not_supported' => [
                'user_message' => 'La carta non supporta questa valuta. Usa una carta diversa.',
                'action' => 'use_different_card',
                'retry' => false
            ],
            'duplicate_transaction' => [
                'user_message' => 'Transazione duplicata rilevata. Verifica se il pagamento è già stato elaborato.',
                'action' => 'check_payment_status',
                'retry' => false
            ],
            default => [
                'user_message' => 'La carta è stata rifiutata. Contatta la tua banca per maggiori informazioni.',
                'action' => 'contact_bank',
                'retry' => false
            ]
        };
    }

    /**
     * Gestisce carte scadute
     */
    private function handleExpiredCard(): array
    {
        return [
            'user_message' => 'La carta è scaduta. Usa una carta diversa o aggiorna i dati.',
            'action' => 'use_different_card',
            'retry' => false
        ];
    }

    /**
     * Gestisce CVC incorretto
     */
    private function handleIncorrectCvc(): array
    {
        return [
            'user_message' => 'Il codice CVC non è corretto. Verifica i 3 numeri sul retro della carta.',
            'action' => 'check_cvc',
            'retry' => true
        ];
    }

    /**
     * Gestisce errori di elaborazione
     */
    private function handleProcessingError(): array
    {
        return [
            'user_message' => 'Errore temporaneo durante l\'elaborazione. Riprova tra qualche minuto.',
            'action' => 'retry_later',
            'retry' => true
        ];
    }

    /**
     * Gestisce autenticazione richiesta
     */
    private function handleAuthenticationRequired(): array
    {
        return [
            'user_message' => 'È richiesta l\'autenticazione aggiuntiva. Completa la verifica con la tua banca.',
            'action' => 'complete_authentication',
            'retry' => true
        ];
    }

    /**
     * Gestisce fondi insufficienti
     */
    private function handleInsufficientFunds(): array
    {
        return [
            'user_message' => 'Fondi insufficienti. Verifica il saldo disponibile sulla tua carta.',
            'action' => 'check_balance',
            'retry' => true
        ];
    }

    /**
     * Gestisce limite prelievi superato
     */
    private function handleWithdrawLimitExceeded(): array
    {
        return [
            'user_message' => 'Hai superato il limite di prelievi giornalieri. Riprova domani.',
            'action' => 'try_tomorrow',
            'retry' => false
        ];
    }

    /**
     * Gestisce transazione duplicata
     */
    private function handleDuplicateTransaction(): array
    {
        return [
            'user_message' => 'Transazione duplicata rilevata. Verifica se il pagamento è già stato elaborato.',
            'action' => 'check_payment_status',
            'retry' => false
        ];
    }

    /**
     * Gestisce richieste non valide
     */
    private function handleInvalidRequest(ApiErrorException $e): array
    {
        return [
            'user_message' => 'Dati di pagamento non validi. Verifica le informazioni inserite.',
            'action' => 'check_payment_data',
            'retry' => true,
            'technical_message' => $e->getMessage()
        ];
    }

    /**
     * Gestisce errori API
     */
    private function handleApiError(ApiErrorException $e): array
    {
        return [
            'user_message' => 'Errore temporaneo del sistema di pagamento. Riprova tra qualche minuto.',
            'action' => 'retry_later',
            'retry' => true,
            'technical_message' => $e->getMessage()
        ];
    }

    /**
     * Gestisce errori di rate limiting
     */
    private function handleRateLimitError(): array
    {
        return [
            'user_message' => 'Troppi tentativi di pagamento. Riprova tra qualche minuto.',
            'action' => 'wait_and_retry',
            'retry' => true
        ];
    }

    /**
     * Gestisce errori generici
     */
    private function handleGenericError(ApiErrorException $e): array
    {
        return [
            'user_message' => 'Errore durante il pagamento. Riprova o contatta l\'assistenza.',
            'action' => 'contact_support',
            'retry' => true,
            'technical_message' => $e->getMessage()
        ];
    }

    /**
     * Ottieni suggerimenti per l'utente basati sull'azione
     */
    public function getActionSuggestions(string $action): array
    {
        return match($action) {
            'contact_bank' => [
                'Verifica con la tua banca se la carta è attiva',
                'Controlla se ci sono limitazioni sul tuo conto',
                'Prova con una carta diversa'
            ],
            'check_balance' => [
                'Verifica il saldo disponibile',
                'Controlla se ci sono prelievi in sospeso',
                'Prova con un importo inferiore'
            ],
            'use_different_card' => [
                'Usa una carta diversa',
                'Verifica che la carta sia valida',
                'Controlla la data di scadenza'
            ],
            'check_cvc' => [
                'Verifica i 3 numeri sul retro della carta',
                'Assicurati di inserire solo numeri',
                'Controlla che non ci siano spazi'
            ],
            'check_card_number' => [
                'Verifica il numero della carta',
                'Controlla che non ci siano spazi o caratteri speciali',
                'Assicurati di aver inserito tutti i numeri'
            ],
            'check_expiry' => [
                'Verifica la data di scadenza',
                'Formato: MM/AA (es. 12/25)',
                'Controlla che la carta non sia scaduta'
            ],
            'wait_and_retry' => [
                'Aspetta 5-10 minuti prima di riprovare',
                'Chiudi e riapri la pagina',
                'Prova con una carta diversa'
            ],
            'retry_later' => [
                'Riprova tra 10-15 minuti',
                'Verifica la connessione internet',
                'Contatta l\'assistenza se il problema persiste'
            ],
            'complete_authentication' => [
                'Completa la verifica con la tua banca',
                'Usa l\'app della banca se disponibile',
                'Contatta la banca per sbloccare la carta'
            ],
            'check_payment_status' => [
                'Verifica lo stato del pagamento',
                'Controlla l\'email di conferma',
                'Contatta l\'assistenza se necessario'
            ],
            'try_tomorrow' => [
                'Riprova domani',
                'Usa una carta diversa',
                'Contatta la banca per aumentare i limiti'
            ],
            'check_payment_data' => [
                'Verifica tutti i dati inseriti',
                'Controlla che i campi siano compilati correttamente',
                'Prova a ricaricare la pagina'
            ],
            'contact_support' => [
                'Contatta l\'assistenza clienti',
                'Fornisci il codice errore se disponibile',
                'Prova con una carta diversa'
            ],
            default => [
                'Riprova il pagamento',
                'Verifica i dati inseriti',
                'Contatta l\'assistenza se il problema persiste'
            ]
        };
    }
}
