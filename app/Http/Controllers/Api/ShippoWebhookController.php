<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTrackingEvent;
use App\Services\ShippoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ShippoWebhookController extends Controller
{
    private ShippoService $shippoService;

    public function __construct(ShippoService $shippoService)
    {
        $this->shippoService = $shippoService;
    }

    /**
     * Gestisce i webhook di Shippo per il tracking
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        try {
            // Log del payload per audit
            Log::info('Shippo Webhook ricevuto', [
                'payload' => $request->all(),
                'headers' => $request->headers->all()
            ]);

            $data = $request->all();
            
            // Verifica che sia un webhook valido
            if (!isset($data['event']) || !isset($data['data'])) {
                Log::warning('Webhook Shippo non valido', ['payload' => $data]);
                return response()->json(['success' => false, 'message' => 'Webhook non valido'], 400);
            }

            $event = $data['event'];
            $trackingData = $data['data'];

            // Processa l'evento in base al tipo
            switch ($event) {
                case 'transaction.updated':
                    $this->handleTransactionUpdated($trackingData);
                    break;
                    
                case 'track.updated':
                    $this->handleTrackUpdated($trackingData);
                    break;
                    
                // Eventi legacy per compatibilità
                case 'shipment.picked_up':
                    $this->handlePickedUp($trackingData);
                    break;
                    
                case 'shipment.in_transit':
                    $this->handleInTransit($trackingData);
                    break;
                    
                case 'shipment.delivered':
                    $this->handleDelivered($trackingData);
                    break;
                    
                case 'shipment.failure':
                    $this->handleFailure($trackingData);
                    break;
                    
                default:
                    Log::info('Evento Shippo non gestito', ['event' => $event]);
            }

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            Log::error('Errore gestione webhook Shippo', [
                'error' => $e->getMessage(),
                'payload' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore interno del server'
            ], 500);
        }
    }

    /**
     * Gestisce l'evento "transaction.updated"
     */
    private function handleTransactionUpdated(array $transactionData): void
    {
        $trackingNumber = $transactionData['tracking_number'] ?? null;
        $status = $transactionData['status'] ?? null;
        
        if (!$trackingNumber || !$status) {
            Log::warning('Dati mancanti in transaction.updated event', ['data' => $transactionData]);
            return;
        }

        // Trova l'ordine con questo tracking number
        $order = $this->findOrderByTrackingNumber($trackingNumber);
        
        if (!$order) {
            Log::warning('Ordine non trovato per tracking number', ['tracking_number' => $trackingNumber]);
            return;
        }

        // Mappa lo status di Shippo ai nostri stati
        $mappedStatus = $this->mapShippoStatus($status);
        
        if ($mappedStatus) {
            $order->update([
                'tracking_status' => $mappedStatus,
                'status' => $this->getOrderStatusFromTracking($mappedStatus)
            ]);

            // Crea evento di tracking
            $this->createTrackingEvent($order, $mappedStatus, "Status aggiornato: {$status}", $transactionData);

            // Invia notifica se necessario
            $this->sendShippingNotification($order, $mappedStatus);

            Log::info('Ordine aggiornato da transaction.updated', [
                'order_id' => $order->id,
                'tracking_number' => $trackingNumber,
                'shippo_status' => $status,
                'mapped_status' => $mappedStatus
            ]);
        }
    }

    /**
     * Gestisce l'evento "track.updated"
     */
    private function handleTrackUpdated(array $trackData): void
    {
        $trackingNumber = $trackData['tracking_number'] ?? null;
        $status = $trackData['status'] ?? null;
        
        if (!$trackingNumber || !$status) {
            Log::warning('Dati mancanti in track.updated event', ['data' => $trackData]);
            return;
        }

        // Trova l'ordine con questo tracking number
        $order = $this->findOrderByTrackingNumber($trackingNumber);
        
        if (!$order) {
            Log::warning('Ordine non trovato per tracking number', ['tracking_number' => $trackingNumber]);
            return;
        }

        // Mappa lo status di tracking ai nostri stati
        $mappedStatus = $this->mapTrackingStatus($status);
        
        if ($mappedStatus) {
            $order->update([
                'tracking_status' => $mappedStatus,
                'status' => $this->getOrderStatusFromTracking($mappedStatus)
            ]);

            // Crea evento di tracking
            $this->createTrackingEvent($order, $mappedStatus, "Tracking aggiornato: {$status}", $trackData);

            // Invia notifica se necessario
            $this->sendShippingNotification($order, $mappedStatus);

            // Se consegnato, avvia timer rilascio fondi
            if ($mappedStatus === 'delivered') {
                $this->startFundsReleaseTimer($order);
            }

            Log::info('Ordine aggiornato da track.updated', [
                'order_id' => $order->id,
                'tracking_number' => $trackingNumber,
                'track_status' => $status,
                'mapped_status' => $mappedStatus
            ]);
        }
    }

    /**
     * Mappa lo status di Shippo ai nostri stati
     */
    private function mapShippoStatus(string $shippoStatus): ?string
    {
        $statusMap = [
            'SUCCESS' => 'shipped',
            'PENDING' => 'processing',
            'ERROR' => 'failed',
            'REFUNDED' => 'refunded',
        ];

        return $statusMap[$shippoStatus] ?? null;
    }

    /**
     * Mappa lo status di tracking ai nostri stati
     */
    private function mapTrackingStatus(string $trackStatus): ?string
    {
        $statusMap = [
            'PICKED_UP' => 'picked_up',
            'IN_TRANSIT' => 'in_transit',
            'OUT_FOR_DELIVERY' => 'out_for_delivery',
            'DELIVERED' => 'delivered',
            'FAILURE' => 'failed',
            'RETURNED' => 'returned',
        ];

        return $statusMap[$trackStatus] ?? null;
    }

    /**
     * Ottiene lo status dell'ordine dal tracking status
     */
    private function getOrderStatusFromTracking(string $trackingStatus): string
    {
        $statusMap = [
            'picked_up' => 'shipped',
            'in_transit' => 'shipped',
            'out_for_delivery' => 'shipped',
            'delivered' => 'delivered',
            'failed' => 'shipping_failed',
            'returned' => 'returned',
        ];

        return $statusMap[$trackingStatus] ?? 'processing';
    }

    /**
     * Gestisce l'evento "picked_up"
     */
    private function handlePickedUp(array $trackingData): void
    {
        $trackingNumber = $trackingData['tracking_number'] ?? null;
        
        if (!$trackingNumber) {
            Log::warning('Tracking number mancante in picked_up event', ['data' => $trackingData]);
            return;
        }

        // Trova l'ordine con questo tracking number
        $order = $this->findOrderByTrackingNumber($trackingNumber);
        
        if (!$order) {
            Log::warning('Ordine non trovato per tracking number', ['tracking_number' => $trackingNumber]);
            return;
        }

        // Aggiorna stato ordine
        $order->update([
            'status' => 'shipped',
            'tracking_status' => 'picked_up',
            'shipped_at' => now()
        ]);

        // Crea evento di tracking
        $this->createTrackingEvent($order, 'picked_up', 'Pacco ritirato dal corriere', $trackingData);

        // Invia notifica al cliente
        $this->sendShippingNotification($order, 'picked_up');

        Log::info('Ordine aggiornato: picked_up', [
            'order_id' => $order->id,
            'tracking_number' => $trackingNumber
        ]);
    }

    /**
     * Gestisce l'evento "in_transit"
     */
    private function handleInTransit(array $trackingData): void
    {
        $trackingNumber = $trackingData['tracking_number'] ?? null;
        
        if (!$trackingNumber) {
            Log::warning('Tracking number mancante in in_transit event', ['data' => $trackingData]);
            return;
        }

        $order = $this->findOrderByTrackingNumber($trackingNumber);
        
        if (!$order) {
            Log::warning('Ordine non trovato per tracking number', ['tracking_number' => $trackingNumber]);
            return;
        }

        // Aggiorna stato ordine
        $order->update([
            'tracking_status' => 'in_transit'
        ]);

        // Crea evento di tracking
        $this->createTrackingEvent($order, 'in_transit', 'Pacco in transito', $trackingData);

        // Invia notifica al cliente
        $this->sendShippingNotification($order, 'in_transit');

        Log::info('Ordine aggiornato: in_transit', [
            'order_id' => $order->id,
            'tracking_number' => $trackingNumber
        ]);
    }

    /**
     * Gestisce l'evento "delivered"
     */
    private function handleDelivered(array $trackingData): void
    {
        $trackingNumber = $trackingData['tracking_number'] ?? null;
        
        if (!$trackingNumber) {
            Log::warning('Tracking number mancante in delivered event', ['data' => $trackingData]);
            return;
        }

        $order = $this->findOrderByTrackingNumber($trackingNumber);
        
        if (!$order) {
            Log::warning('Ordine non trovato per tracking number', ['tracking_number' => $trackingNumber]);
            return;
        }

        // Aggiorna stato ordine
        $order->update([
            'status' => 'delivered',
            'tracking_status' => 'delivered',
            'delivered_at' => now()
        ]);

        // Crea evento di tracking
        $this->createTrackingEvent($order, 'delivered', 'Pacco consegnato', $trackingData);

        // Invia notifica al cliente
        $this->sendShippingNotification($order, 'delivered');

        // Avvia timer 72h per rilascio fondi
        $this->startFundsReleaseTimer($order);

        Log::info('Ordine aggiornato: delivered', [
            'order_id' => $order->id,
            'tracking_number' => $trackingNumber
        ]);
    }

    /**
     * Gestisce l'evento "failure"
     */
    private function handleFailure(array $trackingData): void
    {
        $trackingNumber = $trackingData['tracking_number'] ?? null;
        
        if (!$trackingNumber) {
            Log::warning('Tracking number mancante in failure event', ['data' => $trackingData]);
            return;
        }

        $order = $this->findOrderByTrackingNumber($trackingNumber);
        
        if (!$order) {
            Log::warning('Ordine non trovato per tracking number', ['tracking_number' => $trackingNumber]);
            return;
        }

        // Aggiorna stato ordine
        $order->update([
            'status' => 'shipping_failed',
            'tracking_status' => 'failed'
        ]);

        // Crea evento di tracking
        $this->createTrackingEvent($order, 'failed', 'Problema con la spedizione', $trackingData);

        // Invia notifica al cliente
        $this->sendShippingNotification($order, 'failed');

        Log::info('Ordine aggiornato: failed', [
            'order_id' => $order->id,
            'tracking_number' => $trackingNumber
        ]);
    }

    /**
     * Trova un ordine per tracking number
     */
    private function findOrderByTrackingNumber(string $trackingNumber): ?Order
    {
        return Order::where('tracking_number', $trackingNumber)->first();
    }

    /**
     * Crea un evento di tracking
     */
    private function createTrackingEvent(Order $order, string $status, string $description, array $trackingData): void
    {
        OrderTrackingEvent::create([
            'order_id' => $order->id,
            'status' => $status,
            'description' => $description,
            'location' => $trackingData['location'] ?? null,
            'timestamp' => $trackingData['timestamp'] ?? now(),
            'carrier' => $trackingData['carrier'] ?? null,
            'raw_data' => $trackingData
        ]);
    }

    /**
     * Invia notifica di spedizione
     */
    private function sendShippingNotification(Order $order, string $status): void
    {
        // TODO: Implementare invio notifiche email/push
        // Per ora solo log
        Log::info('Notifica spedizione da inviare', [
            'order_id' => $order->id,
            'status' => $status,
            'customer_email' => $order->user->email ?? 'N/A'
        ]);
    }

    /**
     * Avvia timer per rilascio fondi (72h dopo consegna)
     */
    private function startFundsReleaseTimer(Order $order): void
    {
        // TODO: Implementare job per rilascio fondi dopo 72h
        // Per ora solo log
        Log::info('Timer rilascio fondi avviato', [
            'order_id' => $order->id,
            'release_at' => now()->addHours(72)
        ]);
    }
}
