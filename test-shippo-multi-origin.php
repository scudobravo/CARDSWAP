<?php

/**
 * Script per testare la disponibilità di corrieri Shippo
 * per spedizioni da diversi paesi di origine
 * 
 * Questo script verifica il problema menzionato da Shippo:
 * gli account built-in supportano principalmente spedizioni dagli USA
 */

require_once 'vendor/autoload.php';

use App\Services\ShippoService;

// Carica configurazione Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test Corrieri Shippo: Multi-Paese di Origine ===\n\n";
echo "Questo script verifica quali corrieri sono disponibili\n";
echo "per spedizioni che partono da diversi paesi.\n\n";

$shippoService = new ShippoService();

// Verifica configurazione
$apiKey = config('services.shippo.key');
if (!$apiKey) {
    echo "❌ Chiave API SHIPPO non configurata\n";
    echo "Aggiungi SHIPPO_API_KEY al file .env\n";
    exit(1);
}

echo "✅ Chiave API configurata\n";

// Verifica se è chiave di test o produzione
if (strpos($apiKey, 'shippo_test_') === 0) {
    echo "⚠️  ATTENZIONE: Stai usando una chiave API di TEST\n";
    echo "   Le chiavi di test potrebbero avere limitazioni geografiche\n";
    echo "   Considera di testare anche con la chiave di PRODUZIONE\n\n";
} else if (strpos($apiKey, 'shippo_live_') === 0) {
    echo "✅ Chiave API di PRODUZIONE rilevata\n\n";
} else {
    echo "⚠️  Tipo di chiave API non riconosciuto\n\n";
}

// Paesi di origine da testare
$originCountries = [
    'IT' => 'Italia',
    'US' => 'Stati Uniti',
    'FR' => 'Francia',
    'DE' => 'Germania',
    'GB' => 'Regno Unito',
    'ES' => 'Spagna',
];

// Paese di destinazione di test
$testDestinations = [
    'IT' => 'Italia',
    'US' => 'Stati Uniti',
    'DE' => 'Germania',
];

echo "=== Test Preliminare: Creazione Indirizzi ===\n";
echo "Verifica se la creazione degli indirizzi funziona correttamente...\n\n";

// Test creazione indirizzo semplice
try {
    $testAddress = [
        'name' => 'Test',
        'street1' => 'Via Roma 1',
        'city' => 'Milano',
        'state' => 'MI',
        'zip' => '20100',
        'country' => 'IT',
    ];
    
    $testAddr = $shippoService->createAddress($testAddress, false);
    echo "✅ Creazione indirizzo IT funziona (ID: " . substr($testAddr['object_id'] ?? 'N/A', 0, 20) . ")\n\n";
} catch (Exception $e) {
    echo "❌ ERRORE nella creazione indirizzo base: " . substr($e->getMessage(), 0, 200) . "\n";
    echo "   Questo potrebbe essere il problema principale!\n\n";
}

echo "=== Test Disponibilità Corrieri per Paese di Origine ===\n\n";

foreach ($originCountries as $originCode => $originName) {
    echo "📍 Origine: {$originName} ({$originCode})\n";
    echo str_repeat('-', 50) . "\n";
    
    // Indirizzo mittente di test per questo paese
    $fromAddresses = [
        'IT' => [
            'name' => 'Test Sender',
            'street1' => 'Via Roma 1',
            'city' => 'Milano',
            'state' => 'MI',
            'zip' => '20100',
            'country' => 'IT',
        ],
        'US' => [
            'name' => 'Test Sender',
            'street1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10001',
            'country' => 'US',
        ],
        'FR' => [
            'name' => 'Test Sender',
            'street1' => '1 Rue de la Paix',
            'city' => 'Paris',
            'state' => 'IDF',
            'zip' => '75001',
            'country' => 'FR',
        ],
        'DE' => [
            'name' => 'Test Sender',
            'street1' => 'Hauptstraße 1',
            'city' => 'Berlin',
            'state' => 'BE',
            'zip' => '10115',
            'country' => 'DE',
        ],
        'GB' => [
            'name' => 'Test Sender',
            'street1' => '1 High Street',
            'city' => 'London',
            'state' => 'ENG',
            'zip' => 'SW1A 1AA',
            'country' => 'GB',
        ],
        'ES' => [
            'name' => 'Test Sender',
            'street1' => 'Calle Mayor 1',
            'city' => 'Madrid',
            'state' => 'MD',
            'zip' => '28001',
            'country' => 'ES',
        ],
    ];
    
    $fromAddress = $fromAddresses[$originCode] ?? $fromAddresses['IT'];
    
    // Test per ogni destinazione
    foreach ($testDestinations as $destCode => $destName) {
        if ($originCode === $destCode) {
            continue; // Salta stesso paese
        }
        
        echo "  → Destinazione: {$destName} ({$destCode})\n";
        
        // Indirizzo destinatario di test
        $toAddresses = [
            'IT' => [
                'name' => 'Test Recipient',
                'street1' => 'Via Napoli 2',
                'city' => 'Roma',
                'state' => 'RM',
                'zip' => '00100',
                'country' => 'IT',
            ],
            'US' => [
                'name' => 'Test Recipient',
                'street1' => '456 Oak Ave',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'zip' => '90001',
                'country' => 'US',
            ],
            'DE' => [
                'name' => 'Test Recipient',
                'street1' => 'Musterstraße 2',
                'city' => 'Munich',
                'state' => 'BY',
                'zip' => '80331',
                'country' => 'DE',
            ],
        ];
        
        $toAddress = $toAddresses[$destCode];
        
        // Pacco di test
        $parcel = [
            'length' => '22',
            'width' => '15',
            'height' => '3',
            'distance_unit' => 'cm',
            'weight' => '0.1',
            'mass_unit' => 'kg',
        ];
        
        try {
            // Prova a creare uno shipment e vedere quali tariffe sono disponibili
            // Prima prova senza validazione degli indirizzi
            try {
                // Crea indirizzo mittente senza validazione
                $from = $shippoService->createAddress($fromAddress, false);
                
                // Crea indirizzo destinatario senza validazione
                $to = $shippoService->createAddress($toAddress, false);
                
                // Crea pacco
                $parcelObj = $shippoService->createParcel($parcel);
                
                // Crea shipment
                // Shippo richiede il paese anche quando si usa object_id
                // Shippo richiede anche mass_unit e weight quando si usa object_id del pacco
                $parcelPayload = [
                    'object_id' => $parcelObj['object_id'],
                    'mass_unit' => $parcel['mass_unit'],
                    'weight' => $parcel['weight']
                ];
                
                $shipmentPayload = [
                    'address_from' => [
                        'object_id' => $from['object_id'],
                        'country' => $fromAddress['country']
                    ],
                    'address_to' => [
                        'object_id' => $to['object_id'],
                        'country' => $toAddress['country']
                    ],
                    'parcels' => [$parcelPayload],
                ];
                
                $shipment = $shippoService->createShipment($shipmentPayload, false);
                
                $rates = $shipment['rates'] ?? [];
                
                if (empty($rates)) {
                    echo "    ⚠️  Nessuna tariffa disponibile\n";
                } else {
                    echo "    ✅ " . count($rates) . " tariffe disponibili:\n";
                    foreach (array_slice($rates, 0, 3) as $rate) { // Mostra solo le prime 3
                        $carrier = $rate['provider'] ?? $rate['carrier'] ?? 'N/A';
                        $amount = $rate['amount'] ?? 'N/A';
                        $service = $rate['servicelevel']['name'] ?? $rate['service_name'] ?? 'N/A';
                        $currency = $rate['currency'] ?? 'EUR';
                        echo "       - {$carrier} ({$service}): {$currency} {$amount}\n";
                    }
                    if (count($rates) > 3) {
                        echo "       ... e altre " . (count($rates) - 3) . " tariffe\n";
                    }
                }
            } catch (Exception $e) {
                // Mostra errore completo per debug
                $errorMsg = $e->getMessage();
                $errorDetails = '';
                
                // Cerca di estrarre dettagli dall'errore
                if (strpos($errorMsg, 'HTTP request returned status code') !== false) {
                    // Estrai il JSON dall'errore se presente
                    if (preg_match('/\{.*\}/s', $errorMsg, $matches)) {
                        $errorJson = json_decode($matches[0], true);
                        if ($errorJson) {
                            $errorDetails = json_encode($errorJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                        }
                    }
                }
                
                // Mostra sempre l'errore completo per debug
                if (strpos($errorMsg, 'No rates found') !== false || 
                    strpos($errorMsg, 'no available rates') !== false ||
                    strpos($errorMsg, 'No carrier accounts') !== false) {
                    echo "    ❌ Nessun corriere disponibile per questa rotta\n";
                    if ($errorDetails) {
                        echo "       " . substr($errorDetails, 0, 300) . "\n";
                    }
                } else if (strpos($errorMsg, 'country') !== false || strpos($errorMsg, 'Address') !== false) {
                    echo "    ❌ Errore validazione indirizzo\n";
                    echo "       Errore completo: " . substr($errorMsg, 0, 200) . "\n";
                    if ($errorDetails) {
                        echo "       Dettagli JSON:\n";
                        echo "       " . substr($errorDetails, 0, 500) . "\n";
                    }
                } else {
                    echo "    ❌ Errore: " . substr($errorMsg, 0, 150) . "\n";
                    if ($errorDetails) {
                        echo "       " . substr($errorDetails, 0, 300) . "\n";
                    }
                }
            }
        } catch (Exception $e) {
            echo "    ❌ Errore generico: " . substr($e->getMessage(), 0, 80) . "\n";
        }
    }
    
    echo "\n";
}

echo "\n=== Riepilogo ===\n";
echo "Questo test verifica se Shippo supporta spedizioni da paesi diversi dagli USA.\n";
echo "Se vedi molti errori per paesi non-USA, significa che gli account built-in\n";
echo "di Shippo hanno limitazioni geografiche come menzionato nel messaggio.\n\n";

echo "⚠️  NOTA IMPORTANTE:\n";
echo "Se tutti i test hanno fallito, possibili cause:\n";
echo "1. Chiave API di TEST con limitazioni geografiche\n";
echo "   → Soluzione: Testa in PRODUZIONE con chiave LIVE\n";
echo "2. Account Shippo non configurato correttamente\n";
echo "   → Verifica: https://goshippo.com/dashboard/settings/carrier-accounts\n";
echo "3. Problemi di validazione indirizzi\n";
echo "   → Alcuni formati di indirizzo potrebbero non essere accettati\n";
echo "4. Limitazioni geografiche reali degli account built-in\n";
echo "   → Come menzionato nel messaggio di Shippo\n\n";

echo "🔧 PROSSIMI PASSI:\n";
echo "1. Esegui questo script in PRODUZIONE:\n";
echo "   ssh user@server 'cd /path/to/app && php test-shippo-multi-origin.php'\n\n";
echo "2. Se anche in produzione fallisce, contatta Shippo support:\n";
echo "   - Email: support@shippo.com\n";
echo "   - Chiedi: Quali corrieri sono disponibili per spedizioni dall'Italia?\n";
echo "   - Chiedi: Come funziona l'integrazione 'Gray label'?\n\n";

echo "=== Raccomandazioni ===\n";
echo "1. Se molti paesi non-USA falliscono, considera:\n";
echo "   - Usare account corrieri locali per ogni paese\n";
echo "   - Implementare integrazione 'Gray label' di Shippo\n";
echo "   - Usare un mix: account built-in per USA, account locali per altri paesi\n\n";

echo "2. Se solo alcuni paesi falliscono:\n";
echo "   - Documenta quali rotte funzionano\n";
echo "   - Implementa fallback per rotte non supportate\n";
echo "   - Comunica limitazioni ai venditori\n\n";

echo "=== Test Completato ===\n";
