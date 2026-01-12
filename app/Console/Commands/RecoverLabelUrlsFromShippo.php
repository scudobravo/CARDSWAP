<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ShippingZone;
use App\Services\ShippoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class RecoverLabelUrlsFromShippo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:recover-label-urls 
                            {--order-id= : Recupera label_url per un ordine specifico}
                            {--transaction-id= : Recupera label_url usando un transaction_id specifico}
                            {--recreate : Se la transazione è in errore, ricrea una nuova con PNG}
                            {--dry-run : Mostra solo cosa verrebbe recuperato senza aggiornare}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recupera label_url da Shippo per ordini che hanno etichette create ma non hanno label_url salvato';

    /**
     * Execute the console command.
     */
    public function handle(ShippoService $shippoService)
    {
        $dryRun = $this->option('dry-run');
        $orderId = $this->option('order-id');
        $transactionId = $this->option('transaction-id');

        if ($dryRun) {
            $this->warn('🔍 Modalità DRY-RUN: nessuna modifica verrà applicata');
        }

        $this->info('Inizio recupero label_url da Shippo...');

        // Se specificato transaction_id, recupera direttamente da Shippo
        if ($transactionId) {
            $this->info("Recupero label_url per transaction_id: {$transactionId}");
            
            try {
                $this->line("  Chiamata API Shippo per recuperare transazione...");
                
                // Verifica che il metodo esista
                if (!method_exists($shippoService, 'getTransaction')) {
                    $this->error("  ❌ Metodo getTransaction non trovato in ShippoService");
                    return 1;
                }
                
                // Log payload chiamata getTransaction
                Log::info('RecoverLabelUrls: getTransaction payload', [
                    'order_id' => $orderId,
                    'transaction_id' => $transactionId
                ]);
                
                $transaction = $shippoService->getTransaction($transactionId);
                
                // Log risposta completa getTransaction
                Log::info('RecoverLabelUrls: getTransaction response', [
                    'order_id' => $orderId,
                    'transaction_id' => $transactionId,
                    'status' => $transaction['status'] ?? 'N/A',
                    'label_url' => $transaction['label_url'] ?? null,
                    'rate' => $transaction['rate'] ?? null,
                    'full_response' => $transaction,
                    'response_keys' => array_keys($transaction ?? [])
                ]);
                
                if (empty($transaction)) {
                    $this->error("  ❌ Risposta vuota da Shippo");
                    Log::error('RecoverLabelUrls: getTransaction response vuota', [
                        'order_id' => $orderId,
                        'transaction_id' => $transactionId
                    ]);
                    return 1;
                }
                
                $this->line("  ✅ Risposta ricevata da Shippo");
                
                // Log dettagliato per debug
                $status = $transaction['status'] ?? 'N/A';
                $this->line("  Status transazione: {$status}");
                $this->line("  Object state: " . ($transaction['object_state'] ?? 'N/A'));
                
                // Se la transazione ha errori, mostra i messaggi
                if ($status === 'ERROR' && !empty($transaction['messages'])) {
                    $this->error("  ❌ Transazione in errore! Messaggi:");
                    foreach ($transaction['messages'] as $message) {
                        $source = $message['source'] ?? 'N/A';
                        $text = $message['text'] ?? 'N/A';
                        $code = $message['code'] ?? '';
                        $this->error("    - [{$source}] {$text}" . ($code ? " (code: {$code})" : ""));
                    }
                }
                
                // Cerca label_url in vari possibili campi
                $labelUrl = $transaction['label_url'] ?? null;
                
                // Se è vuoto, potrebbe essere una stringa vuota
                if (empty($labelUrl) && isset($transaction['label_url'])) {
                    $this->warn("  ⚠️  label_url presente ma vuoto nella risposta Shippo");
                    if ($status === 'ERROR') {
                        $this->error("  ❌ La transazione è in errore, quindi l'etichetta non è stata generata");
                    }
                }
                
                // Verifica se la transazione è ancora in elaborazione
                if ($status === 'QUEUED' || $status === 'WAITING') {
                    $this->warn("  ⚠️  Transazione ancora in elaborazione (status: {$status})");
                    $this->info("  💡 Riprova tra qualche minuto");
                }
                
                // Mostra tutte le chiavi disponibili per debug
                $this->line("  Chiavi disponibili nella transazione: " . implode(', ', array_keys($transaction)));
                
                if ($labelUrl) {
                    $this->info("✅ Label URL trovato: {$labelUrl}");
                    
                    // Se c'è un order_id nei log, prova ad associarlo
                    if ($orderId) {
                        $order = Order::find($orderId);
                        if ($order) {
                            if (!$dryRun) {
                                $order->update(['label_url' => $labelUrl]);
                                $this->info("✅ Aggiornato ordine #{$order->order_number} con label_url");
                            } else {
                                $this->info("  [DRY-RUN] Aggiornerebbe ordine #{$order->order_number} con label_url: {$labelUrl}");
                            }
                        }
                    }
                } else {
                    $this->warn("⚠️  Label URL non trovato nella transazione");
                    
                    // Se la transazione è in errore e --recreate è specificato, prova a ricreare con PNG
                    if ($status === 'ERROR' && $this->option('recreate') && $orderId) {
                        $this->info("  🔄 Tentativo di ricreare etichetta con formato PNG...");
                        
                        try {
                            $order = Order::find($orderId);
                            if (!$order) {
                                $this->error("  ❌ Ordine non trovato");
                                return 1;
                            }
                            
                            // Recupera il rate_object_id dalla transazione
                            // Il rate può essere un oggetto completo o solo un object_id (stringa)
                            $rateObjectId = null;
                            if (is_array($transaction['rate'] ?? null)) {
                                $rateObjectId = $transaction['rate']['object_id'] ?? null;
                            } elseif (is_string($transaction['rate'] ?? null)) {
                                // Se è una stringa, è direttamente l'object_id
                                $rateObjectId = $transaction['rate'];
                            }
                            
                            if (!$rateObjectId) {
                                $this->error("  ❌ Rate object_id non trovato nella transazione");
                                $this->line("  💡 Struttura rate: " . json_encode($transaction['rate'] ?? 'N/A'));
                                $this->line("  💡 Dovrai ricalcolare le tariffe manualmente");
                                return 1;
                            }
                            
                            // Prova prima con il rate esistente
                            $this->line("  Rate object_id: {$rateObjectId}");
                            $this->line("  Tentativo con rate esistente...");
                            
                            // Prova prima con PNG, poi con ZPL se PNG fallisce
                            $formatsToTry = ['PNG', 'ZPL'];
                            $labelCreated = false;
                            
                            foreach ($formatsToTry as $format) {
                                try {
                                    $this->line("  Tentativo con formato {$format}...");
                                    
                                    // Log payload chiamata buyLabel
                                    $buyLabelPayload = [
                                        'rate' => $rateObjectId,
                                        'label_file_type' => $format,
                                        'async' => false
                                    ];
                                    Log::info('RecoverLabelUrls: buyLabel payload', [
                                        'order_id' => $orderId,
                                        'transaction_id' => $transactionId,
                                        'rate_object_id' => $rateObjectId,
                                        'format' => $format,
                                        'payload' => $buyLabelPayload
                                    ]);
                                    
                                    $newTransaction = $shippoService->buyLabel($rateObjectId, $format);
                                    
                                    // Log risposta completa buyLabel
                                    Log::info('RecoverLabelUrls: buyLabel response', [
                                        'order_id' => $orderId,
                                        'transaction_id' => $transactionId,
                                        'rate_object_id' => $rateObjectId,
                                        'format' => $format,
                                        'status' => $newTransaction['status'] ?? 'N/A',
                                        'label_url' => $newTransaction['label_url'] ?? null,
                                        'tracking_number' => $newTransaction['tracking_number'] ?? null,
                                        'full_response' => $newTransaction,
                                        'response_keys' => array_keys($newTransaction ?? [])
                                    ]);
                                    
                                    $newStatus = $newTransaction['status'] ?? 'N/A';
                                    $newLabelUrl = $newTransaction['label_url'] ?? null;
                                    
                                    if ($newStatus === 'SUCCESS' && $newLabelUrl) {
                                        $this->info("  ✅ Etichetta creata con successo usando rate esistente e formato {$format}!");
                                        $this->info("  ✅ Label URL: {$newLabelUrl}");
                                        
                                        if (!$dryRun) {
                                            $carrier = (is_array($newTransaction['rate'] ?? null) ? ($newTransaction['rate']['provider'] ?? null) : null) ?? $order->carrier_code;
                                            $order->update([
                                                'label_url' => $newLabelUrl,
                                                'tracking_number' => $newTransaction['tracking_number'] ?? $order->tracking_number,
                                                'carrier_code' => $carrier,
                                                'status' => 'label_created',
                                                'label_created_at' => now()
                                            ]);
                                            $this->info("  ✅ Ordine #{$order->order_number} aggiornato con nuovo label_url");
                                        } else {
                                            $this->info("  [DRY-RUN] Aggiornerebbe ordine #{$order->order_number} con label_url: {$newLabelUrl}");
                                        }
                                        $labelCreated = true;
                                        break; // Esci dal loop se ha successo
                                    } else {
                                        $this->warn("  ⚠️  Formato {$format} fallito (status: {$newStatus})");
                                        if (!empty($newTransaction['messages'])) {
                                            foreach ($newTransaction['messages'] as $msg) {
                                                $this->warn("    - " . ($msg['text'] ?? 'N/A'));
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    $this->warn("  ⚠️  Errore con formato {$format}: {$e->getMessage()}");
                                }
                            }
                            
                            if ($labelCreated) {
                                return 0;
                            }
                            
                            $this->line("  🔄 Tutti i formati falliti, ricalcolo tariffe per ottenere nuovo rate...");
                            
                            // Se il rate esistente non funziona, ricalcola le tariffe
                            $this->line("  📦 Ricalcolo tariffe per ordine #{$order->order_number}...");
                            
                            // Carica relazioni necessarie
                            $order->load(['orderItems.cardListing.seller', 'buyer']);
                            
                            // Prepara dati per ricalcolo tariffe
                            $sellers = $order->getSellers();
                            if ($sellers->isEmpty()) {
                                $this->error("  ❌ Nessun venditore trovato per l'ordine");
                                return 1;
                            }
                            
                            $shippingAddress = $order->shipping_address;
                            if (empty($shippingAddress)) {
                                $this->error("  ❌ Indirizzo di spedizione non trovato nell'ordine");
                                return 1;
                            }
                            
                            // Formatta shipping_address come si aspetta ShippoService
                            // Deve avere: name, street1, city, state, zip, country
                            // IMPORTANTE: Verifica tutti i possibili formati di salvataggio
                            $formattedShippingAddress = [
                                'name' => $shippingAddress['name'] 
                                    ?? (($shippingAddress['first_name'] ?? '') . ' ' . ($shippingAddress['last_name'] ?? ''))
                                    ?? $order->buyer->name 
                                    ?? 'Destinatario',
                                'street1' => $shippingAddress['street1'] 
                                    ?? $shippingAddress['address_line_1'] 
                                    ?? $shippingAddress['address']
                                    ?? '',
                                'street2' => $shippingAddress['street2'] 
                                    ?? $shippingAddress['address_line_2'] 
                                    ?? null,
                                'city' => $shippingAddress['city'] ?? '',
                                'state' => $shippingAddress['state'] 
                                    ?? $shippingAddress['state_province'] 
                                    ?? $shippingAddress['province']
                                    ?? '',
                                'zip' => $shippingAddress['zip'] 
                                    ?? $shippingAddress['postal_code'] 
                                    ?? $shippingAddress['zip_code']
                                    ?? '',
                                'country' => $shippingAddress['country'] ?? 'IT',
                            ];
                            
                            // Valida che i campi obbligatori non siano vuoti
                            if (empty($formattedShippingAddress['street1']) || 
                                empty($formattedShippingAddress['city']) || 
                                empty($formattedShippingAddress['zip'])) {
                                $this->error("  ❌ Indirizzo di spedizione incompleto:");
                                $this->error("    street1: " . ($formattedShippingAddress['street1'] ?: 'VUOTO'));
                                $this->error("    city: " . ($formattedShippingAddress['city'] ?: 'VUOTO'));
                                $this->error("    zip: " . ($formattedShippingAddress['zip'] ?: 'VUOTO'));
                                $this->line("  Indirizzo originale: " . json_encode($shippingAddress));
                                Log::error('RecoverLabelUrls: Indirizzo di spedizione incompleto', [
                                    'order_id' => $orderId,
                                    'order_number' => $order->order_number,
                                    'original_shipping_address' => $shippingAddress,
                                    'formatted_shipping_address' => $formattedShippingAddress
                                ]);
                                return 1;
                            }
                            
                            $this->line("  Shipping address formattato: " . json_encode($formattedShippingAddress));
                            
                            // Prepara sellers per ShippoService
                            $sellersData = [];
                            foreach ($sellers as $seller) {
                                // Cerca indirizzo del venditore
                                $sellerAddress = $seller->addresses()->where('is_shipping', true)->first() 
                                    ?? $seller->addresses()->where('is_default', true)->first()
                                    ?? $seller->addresses()->first();
                                
                                if (!$sellerAddress) {
                                    $this->error("  ❌ Indirizzo venditore non trovato per {$seller->name}");
                                    return 1;
                                }
                                
                                $sellersData[$seller->id] = [
                                    'id' => $seller->id,
                                    'name' => $seller->name,
                                    'address' => [
                                        'street1' => $sellerAddress->address_line_1 ?? '',
                                        'street2' => $sellerAddress->address_line_2 ?? null,
                                        'city' => $sellerAddress->city ?? '',
                                        'state' => $sellerAddress->state_province ?? '',
                                        'zip' => $sellerAddress->postal_code ?? '',
                                        'country' => $sellerAddress->country ?? 'IT',
                                        'phone' => $sellerAddress->phone ?? null,
                                    ]
                                ];
                                
                                $this->line("  Seller {$seller->id} address: " . json_encode($sellersData[$seller->id]['address']));
                            }
                            
                            // Ricalcola tariffe
                            $this->line("  Chiamata calculateRatesForOrder...");
                            $ratesResult = $shippoService->calculateRatesForOrder($sellersData, $formattedShippingAddress);
                            
                            // Log per debug
                            $this->line("  Struttura ratesResult: " . json_encode(array_keys($ratesResult ?? [])));
                            
                            if (empty($ratesResult)) {
                                $this->error("  ❌ ratesResult vuoto");
                                Log::error('Ricalcolo tariffe fallito - ratesResult vuoto', [
                                    'order_id' => $order->id,
                                    'sellers_data' => array_keys($sellersData),
                                    'shipping_address' => $shippingAddress
                                ]);
                                return 1;
                            }
                            
                            // Prendi la prima tariffa disponibile
                            $firstSellerId = array_key_first($ratesResult);
                            $this->line("  First seller ID: {$firstSellerId}");
                            
                            $sellerResult = $ratesResult[$firstSellerId] ?? null;
                            if (!$sellerResult) {
                                $this->error("  ❌ Risultato venditore non trovato");
                                Log::error('Ricalcolo tariffe fallito - seller result non trovato', [
                                    'order_id' => $order->id,
                                    'first_seller_id' => $firstSellerId,
                                    'available_keys' => array_keys($ratesResult)
                                ]);
                                return 1;
                            }
                            
                            // Verifica se c'è un errore nel risultato
                            if (isset($sellerResult['error'])) {
                                $this->error("  ❌ Errore nel calcolo tariffe: " . $sellerResult['error']);
                                Log::error('Ricalcolo tariffe fallito - errore nel risultato', [
                                    'order_id' => $order->id,
                                    'seller_id' => $firstSellerId,
                                    'error' => $sellerResult['error'],
                                    'seller_result' => $sellerResult
                                ]);
                                return 1;
                            }
                            
                            $rates = $sellerResult['rates'] ?? [];
                            $this->line("  Rates count: " . count($rates));
                            $this->line("  Seller result keys: " . json_encode(array_keys($sellerResult)));
                            
                            if (empty($rates)) {
                                $this->error("  ❌ Nessuna tariffa disponibile per il venditore");
                                Log::error('Ricalcolo tariffe fallito - nessuna tariffa', [
                                    'order_id' => $order->id,
                                    'seller_result_keys' => array_keys($sellerResult),
                                    'seller_result' => $sellerResult,
                                    'ratesResult' => $ratesResult
                                ]);
                                return 1;
                            }
                            
                            $newRate = $rates[0]; // Prendi la prima tariffa
                            $newRateObjectId = $newRate['object_id'] ?? null;
                            
                            if (!$newRateObjectId) {
                                $this->error("  ❌ Rate object_id non trovato nelle nuove tariffe");
                                return 1;
                            }
                            
                            $this->line("  ✅ Nuovo rate object_id: {$newRateObjectId}");
                            $this->line("  Creazione nuova transazione con formato PNG...");
                            
                            // Crea nuova transazione con PNG
                            $buyLabelPayload = [
                                'rate' => $newRateObjectId,
                                'label_file_type' => 'PNG',
                                'async' => false
                            ];
                            
                            Log::info('RecoverLabelUrls: buyLabel con nuovo rate payload', [
                                'order_id' => $orderId,
                                'order_number' => $order->order_number,
                                'new_rate_object_id' => $newRateObjectId,
                                'payload' => $buyLabelPayload
                            ]);
                            
                            $newTransaction = $shippoService->buyLabel($newRateObjectId, 'PNG');
                            
                            // Log risposta completa buyLabel con nuovo rate
                            Log::info('RecoverLabelUrls: buyLabel con nuovo rate response', [
                                'order_id' => $orderId,
                                'order_number' => $order->order_number,
                                'new_rate_object_id' => $newRateObjectId,
                                'status' => $newTransaction['status'] ?? 'N/A',
                                'label_url' => $newTransaction['label_url'] ?? null,
                                'tracking_number' => $newTransaction['tracking_number'] ?? null,
                                'transaction_id' => $newTransaction['object_id'] ?? null,
                                'full_response' => $newTransaction,
                                'response_keys' => array_keys($newTransaction ?? [])
                            ]);
                            
                            $newStatus = $newTransaction['status'] ?? 'N/A';
                            $newLabelUrl = $newTransaction['label_url'] ?? null;
                            
                            $this->line("  Status nuova transazione: {$newStatus}");
                            
                            if ($newStatus === 'SUCCESS' && $newLabelUrl) {
                                $this->info("  ✅ Nuova etichetta creata con successo!");
                                $this->info("  ✅ Label URL: {$newLabelUrl}");
                                
                                if (!$dryRun) {
                                    $carrier = (is_array($newTransaction['rate'] ?? null) ? ($newTransaction['rate']['provider'] ?? null) : null) ?? $order->carrier_code;
                                    $order->update([
                                        'label_url' => $newLabelUrl,
                                        'tracking_number' => $newTransaction['tracking_number'] ?? $order->tracking_number,
                                        'carrier_code' => $carrier,
                                        'status' => 'label_created',
                                        'label_created_at' => now()
                                    ]);
                                    $this->info("  ✅ Ordine #{$order->order_number} aggiornato con nuovo label_url");
                                } else {
                                    $this->info("  [DRY-RUN] Aggiornerebbe ordine #{$order->order_number} con label_url: {$newLabelUrl}");
                                }
                            } else {
                                $this->error("  ❌ Nuova transazione fallita o label_url vuoto");
                                if (!empty($newTransaction['messages'])) {
                                    foreach ($newTransaction['messages'] as $msg) {
                                        $this->error("    - " . ($msg['text'] ?? 'N/A'));
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            $this->error("  ❌ Errore creazione nuova transazione: {$e->getMessage()}");
                        }
                    } else {
                        $this->line("  💡 Verifica lo status della transazione su Shippo dashboard");
                        if ($status === 'ERROR') {
                            $this->line("  💡 Usa --recreate per tentare di ricreare l'etichetta con PNG");
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->error("❌ Errore recupero transazione: {$e->getMessage()}");
                $this->error("  File: {$e->getFile()}:{$e->getLine()}");
                $this->error("  Trace: " . substr($e->getTraceAsString(), 0, 500));
                
                Log::error('Errore recupero transazione Shippo nel comando', [
                    'transaction_id' => $transactionId,
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
            
            return 0;
        }

        // Altrimenti, cerca ordini con label_created ma senza label_url
        $query = Order::where('status', 'label_created')
            ->whereNull('label_url');

        if ($orderId) {
            $query->where('id', $orderId);
        }

        $orders = $query->get();
        $this->info("Trovati {$orders->count()} ordini con etichetta creata ma senza label_url");

        if ($orders->isEmpty()) {
            $this->info('✅ Nessun ordine da recuperare');
            return 0;
        }

        $recovered = 0;
        $errors = 0;
        $notFound = 0;

        foreach ($orders as $order) {
            $this->line("📦 Ordine #{$order->order_number} (ID: {$order->id})");

            // Cerca transaction_id nei log per questo ordine
            // Cerca nei log recenti (ultimi 7 giorni)
            $logFile = storage_path('logs/laravel.log');
            $transactionId = null;
            
            if (file_exists($logFile)) {
                // Cerca nei log per questo order_id
                $logContent = shell_exec("tail -n 5000 {$logFile} | grep -E 'Etichetta acquistata.*order_id.*{$order->id}|transaction_id.*order_id.*{$order->id}' | tail -n 1");
                
                if ($logContent && preg_match('/"transaction_id":"([^"]+)"/', $logContent, $matches)) {
                    $transactionId = $matches[1];
                    $this->line("  Transaction ID trovato nei log: {$transactionId}");
                }
            }

            if (!$transactionId) {
                $this->warn("  ⚠️  Transaction ID non trovato nei log per questo ordine");
                $notFound++;
                continue;
            }

            try {
                // Recupera la transazione da Shippo
                $transaction = $shippoService->getTransaction($transactionId);
                $labelUrl = $transaction['label_url'] ?? null;

                if ($labelUrl) {
                    $this->info("  ✅ Label URL recuperato: {$labelUrl}");

                    if (!$dryRun) {
                        $order->update(['label_url' => $labelUrl]);
                        $this->info("  ✅ Ordine aggiornato");
                        $recovered++;
                    } else {
                        $this->info("  [DRY-RUN] Aggiornerebbe con: {$labelUrl}");
                        $recovered++;
                    }
                } else {
                    $this->warn("  ⚠️  Label URL non presente nella transazione Shippo");
                    $notFound++;
                }
            } catch (\Exception $e) {
                $this->error("  ❌ Errore: {$e->getMessage()}");
                $errors++;

                Log::error('Errore recupero label_url da Shippo', [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'transaction_id' => $transactionId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->warn("⚠️  Modalità DRY-RUN: nessuna modifica è stata applicata");
        }
        $this->info('✅ Completato!');
        
        $this->table(
            ['Risultato', 'Conteggio'],
            [
                ['Label URL recuperati', $recovered],
                ['Non trovati', $notFound],
                ['Errori', $errors],
            ]
        );

        if ($dryRun) {
            $this->warn("Esegui senza --dry-run per applicare le modifiche");
        }

        return 0;
    }
}
