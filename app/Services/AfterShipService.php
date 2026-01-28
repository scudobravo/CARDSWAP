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
        $this->baseUrl = config('services.aftership.base_url', 'https://api.aftership.com');
        $this->apiKey = config('services.aftership.api_key', '');
        $this->apiVersion = config('services.aftership.api_version', '2026-01');
    }

    /**
     * Crea un tracking su AfterShip quando il venditore inserisce il numero di tracking.
     * POST /tracking/{version}/trackings
     */
    public function createTracking(Order $order, string $trackingNumber, ?string $slug = null): array
    {
        if (empty($this->apiKey)) {
            Log::warning('AfterShip API key non configurata, skip create tracking', [
                'order_id' => $order->id,
                'tracking_number' => $trackingNumber,
            ]);
            return ['success' => false, 'reason' => 'api_key_missing'];
        }

        $tracking = [
            'tracking_number' => $trackingNumber,
            'title' => 'Order ' . $order->order_number,
        ];
        if ($slug !== null && $slug !== '') {
            $tracking['slug'] = $slug;
        }

        $path = "/tracking/{$this->apiVersion}/trackings";
        $payload = ['tracking' => $tracking];

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

            Log::warning('AfterShip create tracking non 2xx', [
                'order_id' => $order->id,
                'status' => $response->status(),
                'body' => $body,
            ]);
            return [
                'success' => false,
                'status' => $response->status(),
                'body' => $body,
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

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'as-api-key' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->acceptJson()->asJson();
    }
}
