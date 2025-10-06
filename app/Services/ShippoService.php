<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShippoService
{
    private string $baseUrl = 'https://api.goshippo.com/';
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.shippo.key');
    }

    private function client()
    {
        return Http::withToken($this->apiKey, 'ShippoToken')
                   ->acceptJson()
                   ->asJson();
    }

    private function post(string $path, array $payload): array
    {
        try {
            $response = $this->client()->post($this->baseUrl . $path, $payload);
            $response->throw();
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Shippo API Error', [
                'path' => $path,
                'payload' => $payload,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function get(string $path, array $query = []): array
    {
        try {
            $response = $this->client()->get($this->baseUrl . $path, $query);
            $response->throw();
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Shippo API Error', [
                'path' => $path,
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function put(string $path, array $payload = []): array
    {
        try {
            $response = $this->client()->put($this->baseUrl . $path, $payload);
            $response->throw();
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Shippo API Error', [
                'path' => $path,
                'payload' => $payload,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Crea e valida un indirizzo
     */
    public function createAddress(array $address, bool $validate = false): array
    {
        $payload = array_merge($address, ['validate' => $validate]);
        return $this->post('addresses', $payload);
    }

    /**
     * Valida un indirizzo senza salvarlo
     */
    public function validateAddress(array $address): array
    {
        return $this->post('addresses/validate', $address);
    }

    /**
     * Crea un pacco con dimensioni e peso
     */
    public function createParcel(array $parcel): array
    {
        return $this->post('parcels', $parcel);
    }

    /**
     * Crea uno shipment e calcola le tariffe
     */
    public function createShipment(array $payload, bool $async = false): array
    {
        $payload['async'] = $async;
        return $this->post('shipments', $payload);
    }

    /**
     * Acquista un'etichetta di spedizione
     */
    public function buyLabel(string $rateObjectId, string $labelFileType = 'PDF'): array
    {
        return $this->post('transactions', [
            'rate' => $rateObjectId,
            'label_file_type' => $labelFileType,
            'async' => false
        ]);
    }

    /**
     * Ottieni tracking di una spedizione
     */
    public function getTracking(string $carrier, string $trackingNumber): array
    {
        return $this->get("tracks/{$carrier}/{$trackingNumber}");
    }

    /**
     * Crea un item per dogana
     */
    public function createCustomsItem(array $item): array
    {
        return $this->post('customs/items', $item);
    }

    /**
     * Crea una dichiarazione doganale
     */
    public function createCustomsDeclaration(array $declaration): array
    {
        return $this->post('customs/declarations', $declaration);
    }

    /**
     * Programma un ritiro
     */
    public function schedulePickup(array $payload): array
    {
        return $this->post('pickups', $payload);
    }

    /**
     * Crea un manifest
     */
    public function createManifest(array $payload): array
    {
        return $this->post('manifests', $payload);
    }

    /**
     * Crea un batch per etichette multiple
     */
    public function createBatch(array $payload): array
    {
        return $this->post('batches', $payload);
    }

    /**
     * Aggiungi spedizioni a un batch
     */
    public function addToBatch(string $batchId, array $shipments): array
    {
        return $this->post("batches/{$batchId}/add_shipments", ['shipments' => $shipments]);
    }

    /**
     * Acquista un batch
     */
    public function purchaseBatch(string $batchId): array
    {
        return $this->post("batches/{$batchId}/purchase", []);
    }

    /**
     * Rimborsa un'etichetta non usata
     */
    public function refundLabel(string $transactionId): array
    {
        return $this->post('refunds', ['transaction' => $transactionId]);
    }

    /**
     * Lista carrier accounts
     */
    public function listCarrierAccounts(): array
    {
        return $this->get('carrier_accounts');
    }

    /**
     * Calcola tariffe per un ordine multi-venditore
     */
    public function calculateRatesForOrder(array $sellers, array $shippingAddress): array
    {
        $results = [];
        
        foreach ($sellers as $sellerId => $sellerData) {
            try {
                // Crea indirizzo mittente (venditore)
                $fromAddress = $this->createAddress([
                    'name' => $sellerData['name'],
                    'company' => $sellerData['company'] ?? '',
                    'street1' => $sellerData['address']['street1'],
                    'city' => $sellerData['address']['city'],
                    'state' => $sellerData['address']['state'],
                    'zip' => $sellerData['address']['zip'],
                    'country' => $sellerData['address']['country'],
                    'phone' => $sellerData['phone'] ?? '',
                    'email' => $sellerData['email'] ?? '',
                ], true);

                // Crea indirizzo destinatario
                $toAddress = $this->createAddress([
                    'name' => $shippingAddress['name'],
                    'street1' => $shippingAddress['street1'],
                    'city' => $shippingAddress['city'],
                    'state' => $shippingAddress['state'],
                    'zip' => $shippingAddress['zip'],
                    'country' => $shippingAddress['country'],
                    'phone' => $shippingAddress['phone'] ?? '',
                ], true);

                // Crea pacco standard per carte
                $parcel = $this->createParcel([
                    'length' => '22',
                    'width' => '15',
                    'height' => '3',
                    'distance_unit' => 'cm',
                    'weight' => '0.25',
                    'mass_unit' => 'kg',
                ]);

                // Crea shipment e calcola tariffe
                $shipment = $this->createShipment([
                    'address_from' => ['object_id' => $fromAddress['object_id']],
                    'address_to' => ['object_id' => $toAddress['object_id']],
                    'parcels' => [['object_id' => $parcel['object_id']]],
                ], false);

                // Applica markup +€1,60 e categorizza tariffe
                $rates = $this->processRates($shipment['rates']);

                $results[$sellerId] = [
                    'seller' => $sellerData,
                    'shipment_id' => $shipment['object_id'],
                    'rates' => $rates,
                    'from_address' => $fromAddress,
                    'to_address' => $toAddress,
                    'parcel' => $parcel,
                ];

            } catch (\Exception $e) {
                Log::error('Errore calcolo tariffe venditore', [
                    'seller_id' => $sellerId,
                    'error' => $e->getMessage()
                ]);
                
                $results[$sellerId] = [
                    'error' => 'Impossibile calcolare tariffe per questo venditore',
                    'seller' => $sellerData,
                ];
            }
        }

        return $results;
    }

    /**
     * Processa e categorizza le tariffe con markup
     */
    private function processRates(array $rates): array
    {
        $processedRates = [];
        $markup = 1.60; // €1,60 markup

        foreach ($rates as $rate) {
            $originalAmount = floatval($rate['amount']);
            $amountWithMarkup = $originalAmount + $markup;

            // Categorizza per tipo di servizio
            $serviceType = $this->categorizeService($rate['servicelevel']['name']);
            
            $processedRates[] = [
                'object_id' => $rate['object_id'],
                'carrier' => $rate['provider'],
                'service_name' => $rate['servicelevel']['name'],
                'service_type' => $serviceType,
                'original_amount' => $originalAmount,
                'amount' => $amountWithMarkup,
                'currency' => $rate['currency'],
                'estimated_days' => $rate['estimated_days'] ?? null,
                'tracking' => $rate['tracking'] ?? false,
                'insurance' => $rate['insurance'] ?? false,
            ];
        }

        // Ordina per prezzo
        usort($processedRates, function($a, $b) {
            return $a['amount'] <=> $b['amount'];
        });

        return $processedRates;
    }

    /**
     * Categorizza il servizio in Standard/Express/Assicurata
     */
    private function categorizeService(string $serviceName): string
    {
        $serviceName = strtolower($serviceName);
        
        if (strpos($serviceName, 'express') !== false || 
            strpos($serviceName, 'priority') !== false ||
            strpos($serviceName, 'overnight') !== false) {
            return 'express';
        }
        
        if (strpos($serviceName, 'insured') !== false ||
            strpos($serviceName, 'signature') !== false) {
            return 'insured';
        }
        
        return 'standard';
    }

    /**
     * Acquista etichetta per un ordine
     */
    public function purchaseLabelForOrder(string $rateObjectId, array $orderData): array
    {
        try {
            $transaction = $this->buyLabel($rateObjectId);
            
            Log::info('Etichetta acquistata', [
                'transaction_id' => $transaction['object_id'],
                'tracking_number' => $transaction['tracking_number'],
                'label_url' => $transaction['label_url'],
                'order_id' => $orderData['order_id'] ?? null,
            ]);

            return [
                'success' => true,
                'transaction_id' => $transaction['object_id'],
                'tracking_number' => $transaction['tracking_number'],
                'tracking_url' => $transaction['tracking_url_provider'],
                'label_url' => $transaction['label_url'],
                'carrier' => $transaction['tracking_provider'],
                'estimated_delivery' => $transaction['eta'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Errore acquisto etichetta', [
                'rate_object_id' => $rateObjectId,
                'order_data' => $orderData,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Impossibile acquistare l\'etichetta: ' . $e->getMessage()
            ];
        }
    }
}
