<?php

namespace App\Console\Commands;

use App\Services\ShippoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestShippoHardcoded extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shippo:test-hardcoded';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Shippo API con dati hardcoded per IT-IT (Calderara di Reno → Reggio Calabria)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Aumenta timeout per chiamate API
        set_time_limit(120);
        ini_set('max_execution_time', '120');
        
        $this->info('🧪 Test Shippo API con dati hardcoded per IT-IT');
        $this->newLine();

        $shippoService = new ShippoService();

        // Dati hardcoded come richiesto
        $fromAddressData = [
            'name' => 'Test Sender',
            'street1' => 'Via Test 1',
            'city' => 'Calderara di Reno',
            'state' => 'BO',
            'zip' => '40012',
            'country' => 'IT',
            'phone' => '+393339999999',
            'email' => 'test@cardswap.com'
        ];

        $toAddressData = [
            'name' => 'Test Receiver',
            'street1' => 'Via Test 2',
            'city' => 'Reggio Calabria',
            'state' => 'RC',
            'zip' => '89100',
            'country' => 'IT',
            'phone' => '+393339999998'
        ];

        $parcelData = [
            'length' => '20',
            'width' => '15',
            'height' => '2',
            'distance_unit' => 'cm',
            'weight' => '0.2',
            'mass_unit' => 'kg'
        ];

        $this->info('📦 Dati test:');
        $this->line('  From: ' . $fromAddressData['city'] . ' (' . $fromAddressData['state'] . '), ' . $fromAddressData['zip'] . ', ' . $fromAddressData['country']);
        $this->line('  To: ' . $toAddressData['city'] . ' (' . $toAddressData['state'] . '), ' . $toAddressData['zip'] . ', ' . $toAddressData['country']);
        $this->line('  Parcel: ' . $parcelData['weight'] . ' ' . $parcelData['mass_unit'] . ', ' . $parcelData['length'] . 'x' . $parcelData['width'] . 'x' . $parcelData['height'] . ' ' . $parcelData['distance_unit']);
        $this->newLine();

        try {
            $this->info('🔄 Creazione indirizzi...');
            
            // Crea indirizzi con gestione errori dettagliata
            try {
                $this->line('  Creando indirizzo FROM...');
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                $fromAddress = $shippoService->createAddress($fromAddressData, true);
                $this->line('  ✅ Indirizzo FROM creato: ' . ($fromAddress['object_id'] ?? 'N/A'));
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            } catch (\Exception $e) {
                $this->error('  ❌ Errore creazione indirizzo FROM:');
                $this->error('    ' . $e->getMessage());
                $this->error('    File: ' . $e->getFile() . ':' . $e->getLine());
                Log::error('Errore creazione indirizzo FROM nel test', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
            
            try {
                $this->line('  Creando indirizzo TO...');
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
                $toAddress = $shippoService->createAddress($toAddressData, true);
                $this->line('  ✅ Indirizzo TO creato: ' . ($toAddress['object_id'] ?? 'N/A'));
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            } catch (\Exception $e) {
                $this->error('  ❌ Errore creazione indirizzo TO:');
                $this->error('    ' . $e->getMessage());
                $this->error('    File: ' . $e->getFile() . ':' . $e->getLine());
                Log::error('Errore creazione indirizzo TO nel test', [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            $this->info('✅ Indirizzi creati:');
            $this->line('  From address ID: ' . ($fromAddress['object_id'] ?? 'N/A'));
            $this->line('  To address ID: ' . ($toAddress['object_id'] ?? 'N/A'));
            $this->newLine();

            // Verifica validazione indirizzi
            if (isset($fromAddress['validation_results'])) {
                $fromValid = $fromAddress['validation_results']['is_valid'] ?? false;
                $this->line('  From address valid: ' . ($fromValid ? '✅ Sì' : '❌ No'));
            }
            if (isset($toAddress['validation_results'])) {
                $toValid = $toAddress['validation_results']['is_valid'] ?? false;
                $this->line('  To address valid: ' . ($toValid ? '✅ Sì' : '❌ No'));
            }
            $this->newLine();

            $this->info('🔄 Creazione pacco...');
            try {
                $parcel = $shippoService->createParcel($parcelData);
                $this->info('✅ Pacco creato: ' . ($parcel['object_id'] ?? 'N/A'));
            } catch (\Exception $e) {
                $this->error('❌ Errore creazione pacco:');
                $this->error('  ' . $e->getMessage());
                $this->error('  File: ' . $e->getFile() . ':' . $e->getLine());
                throw $e;
            }
            $this->newLine();

            $this->info('🔄 Creazione shipment (SENZA carrier_accounts specificati)...');
            
            // Shipment SENZA carrier_accounts - Shippo userà i suoi default
            // Usa solo object_id del pacco, non l'intero oggetto (evita problemi con extra: [])
            $shipmentPayload = [
                'address_from' => [
                    'object_id' => $fromAddress['object_id'],
                    'country' => 'IT'
                ],
                'address_to' => [
                    'object_id' => $toAddress['object_id'],
                    'country' => 'IT'
                ],
                'parcels' => [
                    [
                        'object_id' => $parcel['object_id'],
                        'mass_unit' => $parcelData['mass_unit'],
                        'weight' => $parcelData['weight'],
                        'length' => $parcelData['length'],
                        'width' => $parcelData['width'],
                        'height' => $parcelData['height'],
                        'distance_unit' => $parcelData['distance_unit']
                    ]
                ],
                'async' => false
            ];

            $this->line('📋 Payload shipment:');
            $this->line(json_encode($shipmentPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->newLine();

            $shipment = $shippoService->createShipment($shipmentPayload, false);

            $this->info('✅ Shipment creato: ' . ($shipment['object_id'] ?? 'N/A'));
            $this->line('  Status: ' . ($shipment['status'] ?? 'N/A'));
            $this->newLine();

            // Rates grezze
            $rawRates = $shipment['rates'] ?? [];
            $this->info('📊 Rates grezze da Shippo (prima di qualsiasi filtro):');
            $this->line('  Count: ' . count($rawRates));
            
            if (count($rawRates) > 0) {
                $this->line('  Rates:');
                foreach ($rawRates as $index => $rate) {
                    $this->line('    ' . ($index + 1) . '. ' . ($rate['provider'] ?? 'N/A') . ' - ' . ($rate['servicelevel']['name'] ?? 'N/A') . ' - €' . ($rate['amount'] ?? 'N/A'));
                }
            } else {
                $this->warn('  ⚠️  Nessuna rate restituita da Shippo');
            }
            $this->newLine();

            // Messages da Shippo
            if (!empty($shipment['messages'])) {
                $this->warn('⚠️  Messages da Shippo:');
                foreach ($shipment['messages'] as $msg) {
                    $this->line('  - [' . ($msg['source'] ?? 'N/A') . '] ' . ($msg['text'] ?? 'N/A'));
                }
                $this->newLine();
            }

            // Logging completo per analisi
            Log::info('Test Shippo hardcoded IT-IT completato', [
                'from' => $fromAddressData,
                'to' => $toAddressData,
                'parcel' => $parcelData,
                'shipment_id' => $shipment['object_id'] ?? 'N/A',
                'shipment_status' => $shipment['status'] ?? 'N/A',
                'raw_rates_count' => count($rawRates),
                'raw_rates' => $rawRates,
                'shipment_messages' => $shipment['messages'] ?? []
            ]);

            // Test con carrier accounts specifici (Chronopost e Colissimo)
            $this->info('🔄 Test con carrier accounts specifici (Chronopost e Colissimo)...');
            
            // Recupera carrier accounts disponibili
            $carrierAccounts = $shippoService->listCarrierAccounts();
            $chronopostId = null;
            $colissimoId = null;
            
            if (isset($carrierAccounts['results'])) {
                foreach ($carrierAccounts['results'] as $account) {
                    $carrier = strtolower($account['carrier'] ?? '');
                    if ($carrier === 'chronopost' && ($account['active'] ?? false)) {
                        $chronopostId = $account['object_id'];
                    }
                    if ($carrier === 'colissimo' && ($account['active'] ?? false)) {
                        $colissimoId = $account['object_id'];
                    }
                }
            }

            if ($chronopostId || $colissimoId) {
                $carrierAccountIds = array_filter([$chronopostId, $colissimoId]);
                
                $shipmentPayloadWithCarriers = $shipmentPayload;
                $shipmentPayloadWithCarriers['carrier_accounts'] = array_values($carrierAccountIds);
                
                $this->line('  Carrier accounts: ' . implode(', ', $carrierAccountIds));
                $this->newLine();
                
                $shipment2 = $shippoService->createShipment($shipmentPayloadWithCarriers, false);
                
                $rawRates2 = $shipment2['rates'] ?? [];
                $this->info('📊 Rates con carrier accounts specifici:');
                $this->line('  Count: ' . count($rawRates2));
                
                if (count($rawRates2) > 0) {
                    foreach ($rawRates2 as $index => $rate) {
                        $this->line('    ' . ($index + 1) . '. ' . ($rate['provider'] ?? 'N/A') . ' - ' . ($rate['servicelevel']['name'] ?? 'N/A') . ' - €' . ($rate['amount'] ?? 'N/A'));
                    }
                } else {
                    $this->warn('  ⚠️  Nessuna rate restituita');
                }
                $this->newLine();
                
                if (!empty($shipment2['messages'])) {
                    $this->warn('⚠️  Messages:');
                    foreach ($shipment2['messages'] as $msg) {
                        $this->line('  - [' . ($msg['source'] ?? 'N/A') . '] ' . ($msg['text'] ?? 'N/A'));
                    }
                }
            } else {
                $this->warn('⚠️  Chronopost o Colissimo non trovati nei carrier accounts');
            }

            $this->newLine();
            $this->info('✅ Test completato! Controlla i log per dettagli completi.');

        } catch (\Exception $e) {
            $this->error('❌ Errore durante il test:');
            $this->error($e->getMessage());
            $this->error('File: ' . $e->getFile() . ':' . $e->getLine());
            
            Log::error('Errore test Shippo hardcoded', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }

        return 0;
    }
}
