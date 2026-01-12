<?php

namespace App\Console\Commands;

use App\Models\Order;
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
                $transaction = $shippoService->getTransaction($transactionId);
                $this->line("  ✅ Risposta ricevuta da Shippo");
                
                // Log dettagliato per debug
                $this->line("  Status transazione: " . ($transaction['status'] ?? 'N/A'));
                $this->line("  Object state: " . ($transaction['object_state'] ?? 'N/A'));
                
                // Cerca label_url in vari possibili campi
                $labelUrl = $transaction['label_url'] ?? null;
                
                // Se è vuoto, potrebbe essere una stringa vuota
                if (empty($labelUrl) && isset($transaction['label_url'])) {
                    $this->warn("  ⚠️  label_url presente ma vuoto nella risposta Shippo");
                }
                
                // Verifica se la transazione è ancora in elaborazione
                if (($transaction['status'] ?? '') === 'QUEUED' || ($transaction['status'] ?? '') === 'WAITING') {
                    $this->warn("  ⚠️  Transazione ancora in elaborazione (status: {$transaction['status']})");
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
                    $this->line("  💡 Verifica lo status della transazione su Shippo dashboard");
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
