<?php

namespace App\Services;

use App\Models\ShippingZone;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * @deprecated ShippoService è DEPRECATO e NON fa parte di CardSwap Shipping V1.
 * 
 * Shippo NON viene più utilizzato per:
 * - Pricing (usa CardSwap Shipping V1: shipping_price_tables)
 * - Checkout (usa POST /api/shipping/v1/calculate-rates)
 * - Tracking (usa AfterShip - vedi TrackingController)
 * - Post-ordine (usa AfterShip webhook)
 * 
 * Questo servizio è mantenuto solo per compatibilità legacy e sarà rimosso in futuro.
 * 
 * Messaggio standard: "Shippo is deprecated and not used by CardSwap Shipping V1"
 */
class ShippoService
{
    private string $baseUrl = 'https://api.goshippo.com/';
    private string $apiKey;

    public function __construct()
    {
        Log::warning('ShippoService is deprecated and not used by CardSwap Shipping V1', [
            'service' => 'ShippoService',
            'note' => 'Shippo is no longer part of CardSwap V1. Use CardSwap Shipping V1 for pricing and AfterShip for tracking.'
        ]);
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
            $response = $this->client()->timeout(30)->post($this->baseUrl . $path, $payload);
            $response->throw();
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Shippo API Error', [
                'path' => $path,
                'payload' => $payload,
                'error' => $e->getMessage(),
                'response_status' => $response->status() ?? null,
                'response_body' => $response->body() ?? null
            ]);
            throw $e;
        }
    }

    private function get(string $path, array $query = []): array
    {
        try {
            $response = $this->client()->timeout(120)->get($this->baseUrl . $path, $query);
            $response->throw();
            return $response->json();
        } catch (\Exception $e) {
            Log::error('Shippo API Error', [
                'path' => $path,
                'query' => $query,
                'error' => $e->getMessage(),
                'response_status' => $response->status() ?? null,
                'response_body' => $response->body() ?? null
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
     * @deprecated Shippo NON fa parte di CardSwap Shipping V1
     * 
     * Crea e valida un indirizzo
     */
    public function createAddress(array $address, bool $validate = false): array
    {
        $payload = array_merge($address, ['validate' => $validate]);
        return $this->post('addresses', $payload);
    }

    /**
     * @deprecated Shippo NON fa parte di CardSwap Shipping V1
     * 
     * Valida un indirizzo senza salvarlo
     */
    public function validateAddress(array $address): array
    {
        return $this->post('addresses/validate', $address);
    }

    /**
     * @deprecated Shippo NON fa parte di CardSwap Shipping V1
     * 
     * Crea un pacco con dimensioni e peso
     */
    public function createParcel(array $parcel): array
    {
        return $this->post('parcels', $parcel);
    }

    /**
     * @deprecated Shippo NON fa parte di CardSwap Shipping V1
     * 
     * Crea uno shipment e calcola le tariffe
     */
    public function createShipment(array $payload, bool $async = false): array
    {
        $payload['async'] = $async;
        return $this->post('shipments', $payload);
    }

    /**
     * @deprecated Shippo NON fa parte di CardSwap Shipping V1
     * 
     * Acquista un'etichetta di spedizione
     * Per Poste Italiane, usa PNG invece di PDF (PDF non supportato)
     * 
     * NOTA: CardSwap V1 NON richiede acquisto automatico di etichette Shippo.
     * Le etichette vengono gestite manualmente dal venditore o tramite AfterShip.
     */
    public function buyLabel(string $rateObjectId, string $labelFileType = null): array
    {
        // Se non specificato, usa PNG come default (supportato da tutti i carrier incluso Poste Italiane)
        // PDF non è supportato da Poste Italiane
        if ($labelFileType === null) {
            $labelFileType = 'PNG';
        }
        
        return $this->post('transactions', [
            'rate' => $rateObjectId,
            'label_file_type' => $labelFileType,
            'async' => false
        ]);
    }

    /**
     * @deprecated Shippo NON fa parte di CardSwap Shipping V1
     * 
     * Ottieni tracking di una spedizione
     * 
     * NOTA: CardSwap V1 usa ESCLUSIVAMENTE AfterShip per il tracking.
     * Vedi TrackingController e AfterShip webhook.
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
     * @deprecated Shippo NON fa parte di CardSwap Shipping V1
     * 
     * Lista carrier accounts
     */
    public function listCarrierAccounts(): array
    {
        return $this->get('carrier_accounts');
    }

    /**
     * Recupera una transazione Shippo per ID
     */
    public function getTransaction(string $transactionId): array
    {
        return $this->get("transactions/{$transactionId}");
    }

    /**
     * Lista tutte le transazioni Shippo (con filtri opzionali)
     */
    public function listTransactions(array $query = []): array
    {
        return $this->get('transactions', $query);
    }

    /**
     * @deprecated Shippo NON fa parte di CardSwap Shipping V1
     * 
     * Ottieni corrieri disponibili per un paese specifico
     * 
     * NOTA: CardSwap V1 usa shipping_price_tables per determinare i metodi disponibili.
     */
    public function getAvailableCarriers(string $destinationCountry): array
    {
        $carriers = config('services.shippo.carriers');
        $availableCarriers = [];

        // Aggiungi corrieri domestici se la destinazione è Italia
        if ($destinationCountry === 'IT') {
            $availableCarriers = array_merge($availableCarriers, $carriers['domestic']);
        }

        // Aggiungi corrieri internazionali
        foreach ($carriers['international'] as $carrierCode => $carrierConfig) {
            if ($carrierConfig['countries'] === ['*'] || in_array($destinationCountry, $carrierConfig['countries'])) {
                $availableCarriers[$carrierCode] = $carrierConfig;
            }
        }

        // Ordina per priorità
        uasort($availableCarriers, function($a, $b) {
            return ($a['priority'] ?? 999) <=> ($b['priority'] ?? 999);
        });

        return $availableCarriers;
    }

    /**
     * Calcola prezzo per corriere con prezzo fisso (deprecato - ora usa Shippo)
     */
    public function calculateFixedPrice(string $carrierCode, string $destinationCountry, float $weight, float $orderValue): float
    {
        $carriers = config('services.shippo.carriers');
        $pricingConfig = config('services.shippo.pricing');
        
        // Cerca il corriere nella configurazione
        foreach ($carriers['domestic'] as $carrier) {
            if ($carrier['code'] === $carrierCode && isset($carrier['fixed_price'])) {
                $basePrice = $carrier['fixed_price'];
                $markup = $pricingConfig['markup'] ?? 1.60;
                $managementFee = $pricingConfig['management_fee'] ?? 0.90;
                
                return $basePrice + $markup + $managementFee;
            }
        }
        
        // Fallback al calcolo tradizionale
        return $this->calculateTraditionalPrice($weight, $orderValue);
    }

    /**
     * @deprecated Shippo NON fa parte di CardSwap Shipping V1
     * 
     * Calcola tariffe usando Shippo per Poste Italiane e altri corrieri
     * 
     * NOTA: CardSwap V1 usa ESCLUSIVAMENTE shipping_price_tables per il pricing.
     * Vedi CardSwapShippingController::calculateRates()
     */
    public function calculateShippoRates(array $fromAddress, array $toAddress, array $parcel, array $carrierAccounts = []): array
    {
        try {
            // Crea indirizzo mittente
            $from = $this->createAddress($fromAddress, true);
            
            // Crea indirizzo destinatario
            $to = $this->createAddress($toAddress, true);
            
            // Crea pacco
            $parcelObj = $this->createParcel($parcel);
            
            // Prepara payload per shipment
            // Shippo richiede il paese anche quando si usa object_id
            // Shippo richiede anche mass_unit, weight e dimensioni quando si usa object_id del pacco
            $parcelPayload = [
                'object_id' => $parcelObj['object_id'],
                'mass_unit' => $parcel['mass_unit'] ?? $parcelObj['mass_unit'] ?? 'kg',
                'weight' => $parcel['weight'] ?? $parcelObj['weight'] ?? null,
                'length' => $parcel['length'] ?? $parcelObj['length'] ?? null,
                'width' => $parcel['width'] ?? $parcelObj['width'] ?? null,
                'height' => $parcel['height'] ?? $parcelObj['height'] ?? null,
                'distance_unit' => $parcel['distance_unit'] ?? $parcelObj['distance_unit'] ?? 'cm'
            ];
            
            // Rimuovi campi null
            foreach (['weight', 'length', 'width', 'height'] as $field) {
                if ($parcelPayload[$field] === null) {
                    unset($parcelPayload[$field]);
                }
            }
            
            // Prepara payload shipment con tutti i campi obbligatori degli indirizzi
            // Shippo richiede: street1, city, zip, country (state opzionale per alcuni paesi)
            // street2 opzionale (solo se c'è apartment/suite/unit number)
            // Anche se abbiamo object_id, includiamo i dati completi per sicurezza
            $shipmentPayload = [
                'address_from' => [
                    'object_id' => $from['object_id'],
                    'name' => $fromAddress['name'] ?? $from['name'] ?? '',
                    'street1' => $fromAddress['street1'] ?? $from['street1'] ?? '',
                    'city' => $fromAddress['city'] ?? $from['city'] ?? '',
                    'state' => $fromAddress['state'] ?? $from['state'] ?? '',
                    'zip' => $fromAddress['zip'] ?? $from['zip'] ?? '',
                    'country' => $fromAddress['country'] ?? $from['country'] ?? null
                ],
                'address_to' => [
                    'object_id' => $to['object_id'],
                    'name' => $toAddress['name'] ?? $to['name'] ?? '',
                    'street1' => $toAddress['street1'] ?? $to['street1'] ?? '',
                    'city' => $toAddress['city'] ?? $to['city'] ?? '',
                    'state' => $toAddress['state'] ?? $to['state'] ?? '',
                    'zip' => $toAddress['zip'] ?? $to['zip'] ?? '',
                    'country' => $toAddress['country'] ?? $to['country'] ?? null
                ],
                'parcels' => [$parcelPayload],
            ];
            
            // Aggiungi street2 solo se presente (opzionale)
            if (!empty($fromAddress['street2'] ?? $from['street2'] ?? null)) {
                $shipmentPayload['address_from']['street2'] = $fromAddress['street2'] ?? $from['street2'];
            }
            if (!empty($toAddress['street2'] ?? $to['street2'] ?? null)) {
                $shipmentPayload['address_to']['street2'] = $toAddress['street2'] ?? $to['street2'];
            }
            
            // Rimuovi solo campi opzionali vuoti
            // NON rimuovere mai: street1, city, zip, country (obbligatori per Shippo)
            // Rimuovi solo state se vuoto (opzionale per alcuni paesi)
            // Rimuovi name se vuoto (opzionale)
            foreach (['address_from', 'address_to'] as $addrKey) {
                if (empty($shipmentPayload[$addrKey]['state']) && $shipmentPayload[$addrKey]['state'] !== '0') {
                    unset($shipmentPayload[$addrKey]['state']);
                }
                // Rimuovi name solo se vuoto (opzionale)
                if (empty($shipmentPayload[$addrKey]['name'])) {
                    unset($shipmentPayload[$addrKey]['name']);
                }
                // Rimuovi country solo se null (non dovrebbe mai essere null, ma per sicurezza)
                if ($shipmentPayload[$addrKey]['country'] === null) {
                    unset($shipmentPayload[$addrKey]['country']);
                }
                // I campi obbligatori (street1, city, zip) devono sempre essere presenti
                // Anche se vuoti, li lasciamo così Shippo può validarli e restituire errori chiari
            }
            
            // Aggiungi carrier accounts specifici se forniti
            if (!empty($carrierAccounts)) {
                $shipmentPayload['carrier_accounts'] = $carrierAccounts;
            }
            
            // Crea shipment e calcola tariffe
            $shipment = $this->createShipment($shipmentPayload, false);
            
            // Processa le tariffe con markup
            return $this->processRates($shipment['rates'] ?? []);
            
        } catch (\Exception $e) {
            Log::error('Errore calcolo tariffe Shippo', [
                'from' => $fromAddress,
                'to' => $toAddress,
                'parcel' => $parcel,
                'error' => $e->getMessage()
            ]);
            
            // Rilancia l'eccezione invece di restituire errore generico
            throw new \Exception('Servizio di calcolo tariffe temporaneamente non disponibile. Riprova più tardi.');
        }
    }

    /**
     * Calcola prezzo tradizionale (fallback)
     */
    private function calculateTraditionalPrice(float $weight, float $orderValue): float
    {
        $pricingConfig = config('services.shippo.pricing');
        $basePrice = 5.00; // Prezzo base
        $weightPrice = $weight * 0.50; // €0.50 per kg
        $valuePrice = $orderValue * 0.02; // 2% del valore
        $markup = $pricingConfig['markup'] ?? 1.60;
        $managementFee = $pricingConfig['management_fee'] ?? 0.90;
        
        return $basePrice + $weightPrice + $valuePrice + $markup + $managementFee;
    }

    /**
     * Verifica se un corriere è disponibile per una destinazione
     */
    public function isCarrierAvailable(string $carrierCode, string $destinationCountry): bool
    {
        $carriers = config('services.shippo.carriers');
        
        // Controlla corrieri domestici
        if (isset($carriers['domestic'][$carrierCode])) {
            return in_array($destinationCountry, $carriers['domestic'][$carrierCode]['countries']);
        }
        
        // Controlla corrieri internazionali
        if (isset($carriers['international'][$carrierCode])) {
            $carrierConfig = $carriers['international'][$carrierCode];
            return $carrierConfig['countries'] === ['*'] || in_array($destinationCountry, $carrierConfig['countries']);
        }
        
        return false;
    }

    // ============================================
    // METODO LEGACY PRICING RIMOSSO
    // ============================================
    // calculateRatesForOrder() - RIMOSSO definitivamente
    // 
    // Usa invece POST /api/shipping/v1/calculate-rates per CardSwap Shipping V1.
    // 
    // NOTA: Shippo viene ancora usato per la creazione di etichette (purchaseLabel),
    // ma NON più per il calcolo dei prezzi durante il checkout.
    // ============================================
    // 
    // Il metodo originale (circa 800 righe) è stato rimosso.
    // Se necessario per recovery/legacy, consultare git history.
    // ============================================

    /**
     * Processa e categorizza le tariffe con markup
     */
    private function processRates(array $rates): array
    {
        $processedRates = [];
        $pricingConfig = config('services.shippo.pricing');
        $markup = $pricingConfig['markup'] ?? 1.60; // €1,60 markup
        $managementFee = $pricingConfig['management_fee'] ?? 0.90; // €0,90 spese gestione

        foreach ($rates as $rate) {
            $originalAmount = floatval($rate['amount']);
            $amountWithMarkup = $originalAmount + $markup + $managementFee;

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
                'breakdown' => [
                    'shippo_rate' => $originalAmount,
                    'markup' => $markup,
                    'management_fee' => $managementFee,
                    'total' => $amountWithMarkup
                ]
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
     * @deprecated Shippo NON fa parte di CardSwap Shipping V1
     * 
     * Acquista etichetta per un ordine
     * 
     * NOTA: CardSwap V1 NON richiede acquisto automatico di etichette Shippo.
     * Le etichette vengono gestite manualmente dal venditore o tramite AfterShip.
     */
    public function purchaseLabelForOrder(string $rateObjectId, array $orderData): array
    {
        try {
            $transaction = $this->buyLabel($rateObjectId);
            
            Log::info('Etichetta acquistata', [
                'transaction_id' => $transaction['object_id'] ?? 'N/A',
                'tracking_number' => $transaction['tracking_number'] ?? 'N/A',
                'label_url' => $transaction['label_url'] ?? 'N/A',
                'order_id' => $orderData['order_id'] ?? null,
                'transaction_keys' => array_keys($transaction), // Log per debug
            ]);

            // Estrai il carrier dalla rate o dalla transaction
            // Shippo può restituire tracking_provider, carrier, o provider
            $carrier = $transaction['tracking_provider'] 
                ?? $transaction['carrier'] 
                ?? $transaction['rate']['provider'] 
                ?? $transaction['rate']['carrier']
                ?? 'poste_italiane'; // Fallback per Poste Italiane

            // Estrai tracking_url da vari possibili campi
            $trackingUrl = $transaction['tracking_url_provider'] 
                ?? $transaction['tracking_url'] 
                ?? $transaction['rate']['tracking_url']
                ?? null;

            return [
                'success' => true,
                'transaction_id' => $transaction['object_id'] ?? null,
                'tracking_number' => $transaction['tracking_number'] ?? null,
                'tracking_url' => $trackingUrl,
                'label_url' => $transaction['label_url'] ?? null,
                'carrier' => $carrier,
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
