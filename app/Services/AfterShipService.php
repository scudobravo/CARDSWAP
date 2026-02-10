<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Servizio per l'integrazione con AfterShip Tracking API.
 * CardSwap V1 usa AfterShip come unica fonte per il tracking (vedi doc cap. 10).
 *
 * @see https://www.aftership.com/docs/tracking/quickstart/api-quick-start
 */
class AfterShipService
{
    private string $baseUrl;
    private string $apiKey;
    private string $apiVersion;

    public function __construct()
    {
        $this->baseUrl = (string) (config('services.aftership.base_url') ?? 'https://api.aftership.com');
        $this->apiKey = (string) (config('services.aftership.api_key') ?? '');
        $this->apiVersion = (string) (config('services.aftership.api_version') ?? '2026-01');
    }

    /**
     * Crea un tracking su AfterShip quando il venditore inserisce il numero di tracking.
     * POST /tracking/{version}/trackings
     */
    public function createTracking(Order $order, string $trackingNumber, ?string $slug = null): array
    {
        $trackingNumber = is_string($trackingNumber) ? trim($trackingNumber) : '';
        if ($trackingNumber === '') {
            return [
                'success' => false,
                'message' => 'Inserisci il numero di tracking.',
            ];
        }

        if (empty($this->apiKey)) {
            Log::warning('AfterShip API key non configurata, skip create tracking', [
                'order_id' => $order->id,
                'tracking_number' => $trackingNumber,
            ]);
            return [
                'success' => false,
                'reason' => 'api_key_missing',
                'message' => 'Servizio di verifica tracking non disponibile. Contatta l\'assistenza.',
            ];
        }

        $tracking = [
            'tracking_number' => $trackingNumber,
            'title' => 'Order ' . $order->order_number,
        ];
        if ($slug !== null && $slug !== '') {
            // AfterShip richiede slug tipo "poste-italiane" (minuscolo, trattini)
            $tracking['slug'] = $this->normalizeCarrierSlug($slug);
        }

        $path = "/tracking/{$this->apiVersion}/trackings";
        // AfterShip 2026-01: il body è l'oggetto tracking in root, non sotto chiave "tracking"
        $payload = $tracking;

        Log::info('AfterShip createTracking: payload inviato', [
            'order_id' => $order->id,
            'url' => $this->baseUrl . $path,
            'payload' => $payload,
            'tracking_number_in_payload' => $tracking['tracking_number'] ?? null,
            'tracking_number_length' => strlen((string) ($tracking['tracking_number'] ?? '')),
            'slug_in_payload' => $tracking['slug'] ?? null,
        ]);

        try {
            $response = $this->client()->timeout(30)->post($this->baseUrl . $path, $payload);
            $body = $response->json();

            if ($response->successful()) {
                Log::info('AfterShip tracking creato', [
                    'order_id' => $order->id,
                    'tracking_number' => $trackingNumber,
                    'aftership_id' => $body['data']['tracking']['id'] ?? null,
                ]);
                return [
                    'success' => true,
                    'data' => $body['data'] ?? $body,
                ];
            }

            $userMessage = $this->userMessageFromAfterShipResponse($body, $response->status());
            Log::warning('AfterShip create tracking non 2xx', [
                'order_id' => $order->id,
                'tracking_number_sent' => $trackingNumber,
                'http_status' => $response->status(),
                'meta_code' => $body['meta']['code'] ?? null,
                'meta_type' => $body['meta']['type'] ?? null,
                'meta_message' => $body['meta']['message'] ?? null,
                'body_full' => $body,
                'user_message' => $userMessage,
            ]);
            return [
                'success' => false,
                'status' => $response->status(),
                'body' => $body,
                'message' => $userMessage,
            ];
        } catch (\Exception $e) {
            Log::error('AfterShip create tracking exception', [
                'order_id' => $order->id,
                'tracking_number' => $trackingNumber,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Impossibile verificare il numero di tracking. Riprova più tardi o controlla il codice e il corriere.',
            ];
        }
    }

    /**
     * Aggiorna un tracking esistente su AfterShip.
     * PUT /tracking/{version}/trackings/{id}
     */
    public function updateTracking(string $trackingId, array $trackingData): array
    {
        if (empty($this->apiKey)) {
            Log::warning('AfterShip API key non configurata, skip update tracking', ['tracking_id' => $trackingId]);
            return ['success' => false, 'reason' => 'api_key_missing'];
        }

        $path = "/tracking/{$this->apiVersion}/trackings/{$trackingId}";
        $payload = ['tracking' => $trackingData];

        try {
            $response = $this->client()->timeout(30)->put($this->baseUrl . $path, $payload);
            $body = $response->json();

            if ($response->successful()) {
                return ['success' => true, 'data' => $body['data'] ?? $body];
            }
            return ['success' => false, 'status' => $response->status(), 'body' => $body];
        } catch (\Exception $e) {
            Log::error('AfterShip update tracking exception', [
                'tracking_id' => $trackingId,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Normalizza lo slug corriere per AfterShip: minuscolo, spazi → trattini.
     * Es. "Poste italiane" → "poste-italiane"
     */
    private function normalizeCarrierSlug(string $slug): string
    {
        $s = trim($slug);
        $s = preg_replace('/\s+/', '-', $s);
        $s = strtolower($s);
        $s = preg_replace('/[^a-z0-9\-]/', '', $s);
        return $s;
    }

    /**
     * Restituisce un messaggio in italiano per l'utente a partire dalla risposta di errore AfterShip.
     * @see https://aftership.com/docs/tracking/quickstart/request-errors
     */
    private function userMessageFromAfterShipResponse(?array $body, int $httpStatus): string
    {
        if (!is_array($body)) {
            return 'Il numero di tracking non è stato accettato. Controlla il codice e il corriere e riprova.';
        }

        $code = $body['meta']['code'] ?? null;

        $messagesByCode = [
            4003 => 'Questo numero di tracking è già registrato per un altro ordine.',
            4005 => 'Il numero di tracking non è valido. Controlla il codice e riprova.',
            4007 => 'Il numero di tracking è obbligatorio.',
            4008 => 'Uno o più campi non sono validi. Controlla i dati inseriti.',
            4010 => 'Il corriere selezionato non è valido. Scegli il corriere corretto dalla lista.',
            4011 => 'Per questo corriere sono richiesti altri campi. Controlla i dati inseriti.',
            4012 => 'Il numero di tracking non è riconosciuto da questo corriere, ha formato non valido o il corriere non è supportato. Verifica codice e corriere (es. Poste Italiane).',
            4017 => 'Il formato del numero di tracking non è valido. Controlla il codice e riprova.',
        ];

        if ($code !== null && isset($messagesByCode[$code])) {
            return $messagesByCode[$code];
        }

        $apiMessage = $body['meta']['message'] ?? null;
        if (is_string($apiMessage) && $apiMessage !== '') {
            return $apiMessage;
        }

        if ($httpStatus >= 400 && $httpStatus < 500) {
            return 'Il numero di tracking non è stato accettato. Controlla il codice e il corriere (es. Poste Italiane) e riprova.';
        }

        return 'Impossibile verificare il numero di tracking in questo momento. Riprova più tardi.';
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'as-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->acceptJson()->asJson();
    }
}
