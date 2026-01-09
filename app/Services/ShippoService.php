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
     * Ottieni corrieri disponibili per un paese specifico
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
     * Calcola tariffe usando Shippo per Poste Italiane e altri corrieri
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
            
            $shipmentPayload = [
                'address_from' => [
                    'object_id' => $from['object_id'],
                    'country' => $fromAddress['country'] ?? $from['country'] ?? null
                ],
                'address_to' => [
                    'object_id' => $to['object_id'],
                    'country' => $toAddress['country'] ?? $to['country'] ?? null
                ],
                'parcels' => [$parcelPayload],
            ];
            
            // Rimuovi campi null
            if ($shipmentPayload['address_from']['country'] === null) {
                unset($shipmentPayload['address_from']['country']);
            }
            if ($shipmentPayload['address_to']['country'] === null) {
                unset($shipmentPayload['address_to']['country']);
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

    /**
     * Calcola tariffe per un ordine multi-venditore
     */
    public function calculateRatesForOrder(array $sellers, array $shippingAddress): array
    {
        $results = [];
        
        // Gestisce sia array associativo (sellerId => sellerData) che array numerico ([seller])
        foreach ($sellers as $sellerId => $sellerData) {
            // Se è un array numerico, usa l'ID dal sellerData
            if (is_numeric($sellerId) && isset($sellerData['id'])) {
                $sellerId = $sellerData['id'];
            }
            
            try {
                Log::info('Preparing addresses for Shippo', [
                    'seller_id' => $sellerId,
                    'seller_address' => $sellerData['address'] ?? [],
                    'shipping_address' => $shippingAddress
                ]);

                // Crea indirizzo mittente (venditore)
                $fromAddressData = [
                    'name' => $sellerData['name'] ?? 'Venditore',
                    'street1' => $sellerData['address']['street1'] ?? '',
                    'city' => $sellerData['address']['city'] ?? '',
                    'state' => $sellerData['address']['state'] ?? '',
                    'zip' => $sellerData['address']['zip'] ?? '',
                    'country' => $sellerData['address']['country'] ?? 'IT',
                ];
                
                // Aggiungi campi opzionali solo se presenti
                if (!empty($sellerData['company'])) {
                    $fromAddressData['company'] = $sellerData['company'];
                }
                if (!empty($sellerData['phone'])) {
                    $fromAddressData['phone'] = $sellerData['phone'];
                }
                if (!empty($sellerData['email'])) {
                    $fromAddressData['email'] = $sellerData['email'];
                }
                
                $fromAddress = $this->createAddress($fromAddressData, true);
                
                Log::info('From address created', [
                    'seller_id' => $sellerId,
                    'address_object_id' => $fromAddress['object_id'] ?? 'N/A',
                    'validation' => $fromAddress['validation_results'] ?? []
                ]);

                // Crea indirizzo destinatario
                $toAddressData = [
                    'name' => $shippingAddress['name'] ?? 'Destinatario',
                    'street1' => $shippingAddress['street1'] ?? '',
                    'city' => $shippingAddress['city'] ?? '',
                    'state' => $shippingAddress['state'] ?? '',
                    'zip' => $shippingAddress['zip'] ?? '',
                    'country' => $shippingAddress['country'] ?? 'IT',
                ];
                
                if (!empty($shippingAddress['phone'])) {
                    $toAddressData['phone'] = $shippingAddress['phone'];
                }
                
                $toAddress = $this->createAddress($toAddressData, true);
                
                Log::info('To address created', [
                    'seller_id' => $sellerId,
                    'address_object_id' => $toAddress['object_id'] ?? 'N/A',
                    'validation' => $toAddress['validation_results'] ?? []
                ]);

                // Usa configurazione pacco dalla config
                $parcelConfig = config('services.shippo.pricing.default_parcel');
                $parcel = $this->createParcel([
                    'length' => (string) $parcelConfig['length'],
                    'width' => (string) $parcelConfig['width'],
                    'height' => (string) $parcelConfig['height'],
                    'distance_unit' => $parcelConfig['distance_unit'],
                    'weight' => (string) $parcelConfig['weight'],
                    'mass_unit' => $parcelConfig['mass_unit'],
                ]);

                // Crea shipment e calcola tariffe
                // Shippo richiede il paese anche quando si usa object_id
                // Shippo richiede anche mass_unit, weight e dimensioni quando si usa object_id del pacco
                $parcelConfig = config('services.shippo.pricing.default_parcel');
                $parcelPayload = [
                    'object_id' => $parcel['object_id'],
                    'mass_unit' => $parcelConfig['mass_unit'] ?? $parcel['mass_unit'] ?? 'kg',
                    'weight' => $parcelConfig['weight'] ?? $parcel['weight'] ?? null,
                    'length' => $parcelConfig['length'] ?? $parcel['length'] ?? null,
                    'width' => $parcelConfig['width'] ?? $parcel['width'] ?? null,
                    'height' => $parcelConfig['height'] ?? $parcel['height'] ?? null,
                    'distance_unit' => $parcelConfig['distance_unit'] ?? $parcel['distance_unit'] ?? 'cm'
                ];
                
                // Rimuovi campi null
                foreach (['weight', 'length', 'width', 'height'] as $field) {
                    if ($parcelPayload[$field] === null) {
                        unset($parcelPayload[$field]);
                    }
                }
                
                // Determina i carrier accounts disponibili per questa rotta
                $fromCountry = $sellerData['address']['country'] ?? $fromAddress['country'] ?? 'IT';
                $toCountry = $shippingAddress['country'] ?? $toAddress['country'] ?? 'IT';
                $availableCarriers = $this->getAvailableCarriers($toCountry);
                
                // Estrai gli account IDs dei carrier disponibili
                $carrierAccountIds = [];
                foreach ($availableCarriers as $carrierConfig) {
                    if (isset($carrierConfig['account_id']) && ($carrierConfig['available'] ?? true)) {
                        // Per carrier domestici, verifica che sia una rotta domestica
                        if (($carrierConfig['domestic_only'] ?? false) && $fromCountry === 'IT' && $toCountry === 'IT') {
                            $carrierAccountIds[] = $carrierConfig['account_id'];
                        } elseif (!($carrierConfig['domestic_only'] ?? false)) {
                            // Carrier internazionali
                            $carrierAccountIds[] = $carrierConfig['account_id'];
                        }
                    }
                }
                
                Log::info('Preparing Shippo shipment with carrier accounts', [
                    'seller_id' => $sellerId,
                    'from_country' => $fromCountry,
                    'to_country' => $toCountry,
                    'available_carriers' => array_keys($availableCarriers),
                    'carrier_account_ids' => $carrierAccountIds
                ]);
                
                $shipmentPayload = [
                    'address_from' => [
                        'object_id' => $fromAddress['object_id'],
                        'country' => $fromCountry
                    ],
                    'address_to' => [
                        'object_id' => $toAddress['object_id'],
                        'country' => $toCountry
                    ],
                    'parcels' => [$parcelPayload],
                ];
                
                // Aggiungi carrier accounts solo se disponibili
                if (!empty($carrierAccountIds)) {
                    $shipmentPayload['carrier_accounts'] = $carrierAccountIds;
                }
                
                Log::info('Creating Shippo shipment for rate calculation', [
                    'seller_id' => $sellerId,
                    'from_country' => $fromCountry,
                    'to_country' => $toCountry,
                    'from_city' => $sellerData['address']['city'] ?? 'N/A',
                    'to_city' => $shippingAddress['city'] ?? 'N/A',
                    'from_zip' => $sellerData['address']['zip'] ?? 'N/A',
                    'to_zip' => $shippingAddress['zip'] ?? 'N/A',
                    'parcel_weight' => $parcelConfig['weight'] ?? 'N/A',
                    'carrier_accounts_count' => count($carrierAccountIds)
                ]);

                $shipment = $this->createShipment($shipmentPayload, false);

                Log::info('Shippo shipment created', [
                    'seller_id' => $sellerId,
                    'shipment_id' => $shipment['object_id'] ?? 'N/A',
                    'rates_count' => count($shipment['rates'] ?? []),
                    'rates' => $shipment['rates'] ?? []
                ]);

                // Applica markup e categorizza tariffe
                $rates = $this->processRates($shipment['rates'] ?? []);

                Log::info('Rates processed', [
                    'seller_id' => $sellerId,
                    'processed_rates_count' => count($rates),
                    'rates' => $rates
                ]);

                if (empty($rates)) {
                    $errorMessage = 'Nessuna tariffa disponibile per questa destinazione';
                    $shipmentMessages = $shipment['messages'] ?? [];
                    
                    // Filtra e formatta i messaggi di errore
                    $filteredMessages = [];
                    foreach ($shipmentMessages as $message) {
                        $text = $message['text'] ?? '';
                        // Ignora errori di carrier non disponibili (es. DHL Express da fuori USA)
                        if (stripos($text, "doesn't support shipments from outside") === false && 
                            stripos($text, "master account doesn't support") === false) {
                            $filteredMessages[] = $text;
                        }
                    }
                    
                    // Se ci sono messaggi rilevanti, aggiungili
                    if (!empty($filteredMessages)) {
                        $errorMessage .= ': ' . implode(', ', array_unique($filteredMessages));
                    } else {
                        // Messaggio generico se non ci sono messaggi rilevanti
                        $errorMessage .= '. Verifica che il codice postale sia corretto e che ci siano corrieri disponibili per questa destinazione.';
                    }
                    
                    Log::warning('No rates available for shipment', [
                        'seller_id' => $sellerId,
                        'shipment_id' => $shipment['object_id'] ?? 'N/A',
                        'from_country' => $fromCountry ?? 'N/A',
                        'to_country' => $toCountry ?? 'N/A',
                        'from_city' => $sellerData['address']['city'] ?? 'N/A',
                        'to_city' => $shippingAddress['city'] ?? 'N/A',
                        'from_zip' => $sellerData['address']['zip'] ?? 'N/A',
                        'to_zip' => $shippingAddress['zip'] ?? 'N/A',
                        'shipment_status' => $shipment['status'] ?? 'N/A',
                        'shipment_messages' => $shipmentMessages,
                        'filtered_messages' => $filteredMessages,
                        'shipment_rates' => $shipment['rates'] ?? [],
                        'carrier_accounts_used' => $carrierAccountIds ?? []
                    ]);
                    
                    $results[$sellerId] = [
                        'error' => $errorMessage,
                        'seller' => $sellerData,
                        'shipment_id' => $shipment['object_id'] ?? null,
                    ];
                } else {
                    $results[$sellerId] = [
                        'seller' => $sellerData,
                        'shipment_id' => $shipment['object_id'],
                        'rates' => $rates,
                        'from_address' => $fromAddress,
                        'to_address' => $toAddress,
                        'parcel' => $parcel,
                    ];
                }

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
