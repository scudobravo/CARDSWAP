<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderTrackingEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Webhook AfterShip Tracking.
 * CardSwap V1 usa AfterShip come unica fonte per aggiornamenti di tracking (vedi doc cap. 10).
 *
 * Verifica firma: header aftership-hmac-sha256 = base64(HMAC-SHA256(webhook_secret, body)).
 * @see https://aftership.com/docs/tracking/webhook/webhook-signature
 */
class AfterShipWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();
        $secret = config('services.aftership.webhook_secret', '');

        if ($secret !== '' && !$this->verifySignature($request, $rawBody, $secret)) {
            Log::warning('AfterShip webhook: firma non valida');
            return response()->json(['message' => 'Invalid signature'], 401);
        }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            Log::warning('AfterShip webhook: body JSON non valido');
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        // Formato webhook 2026-01: event, msg, ts. I dati tracking sono in "msg".
        $eventType = $payload['event'] ?? $payload['meta']['type'] ?? $payload['type'] ?? null;
        $trackingData = $payload['msg'] ?? $payload['data']['tracking'] ?? $payload['data'] ?? $payload['tracking'] ?? $payload;

        Log::info('AfterShip webhook received', ['event' => $eventType]);

        $trackingNumber = $trackingData['tracking_number'] ?? ($trackingData['tracking']['tracking_number'] ?? null);
        if (!$trackingNumber) {
            Log::warning('AfterShip webhook: tracking_number non trovato nel payload', ['keys' => is_array($trackingData) ? array_keys($trackingData) : []]);
            return response()->json(['message' => 'OK']); // 200 per evitare retry
        }

        $order = Order::where('tracking_number', $trackingNumber)->first();
        if (!$order) {
            Log::info('AfterShip webhook: ordine non trovato per tracking_number', ['tracking_number' => $trackingNumber]);
            return response()->json(['message' => 'OK']);
        }

        // Stato: nel formato 2026-01 è in msg.tag (es. InTransit, Delivered, InfoReceived)
        $deliveryStatus = $trackingData['tag'] ?? $trackingData['delivery_status'] ?? null;

        $mappedStatus = $this->mapDeliveryStatusToOrderStatus($deliveryStatus);
        $description = $deliveryStatus ? "AfterShip: {$deliveryStatus}" : 'AfterShip webhook update';

        OrderTrackingEvent::create([
            'order_id' => $order->id,
            'status' => $mappedStatus ?? $deliveryStatus ?? 'update',
            'carrier_code' => $trackingData['slug'] ?? $order->carrier_code,
            'tracking_number' => $trackingNumber,
            'tracking_url' => $trackingData['courier_tracking_link'] ?? $trackingData['aftership_tracking_url'] ?? $trackingData['tracking_url'] ?? $order->tracking_url,
            'description' => $description,
            'occurred_at' => now(),
        ]);

        if ($mappedStatus !== null) {
            $updateOrder = ['status' => $mappedStatus];
            if ($mappedStatus === 'shipped' && !$order->shipped_at) {
                $updateOrder['shipped_at'] = now();
            }
            if ($mappedStatus === 'delivered') {
                $updateOrder['delivered_at'] = now();
            }
            $order->update($updateOrder);
            Log::info('AfterShip webhook: ordine aggiornato', [
                'order_id' => $order->id,
                'tracking_number' => $trackingNumber,
                'delivery_status' => $deliveryStatus,
                'mapped_status' => $mappedStatus,
            ]);
        }

        return response()->json(['message' => 'OK']);
    }

    private function verifySignature(Request $request, string $rawBody, string $secret): bool
    {
        $signature = $request->header('aftership-hmac-sha256');
        if (empty($signature)) {
            return false;
        }
        $expected = base64_encode(hash_hmac('sha256', $rawBody, $secret, true));
        return hash_equals($expected, $signature);
    }

    /**
     * Mappa delivery_status/tag AfterShip allo status ordine CardSwap.
     * @see https://aftership.com/docs/tracking/enum/delivery-statuses
     */
    private function mapDeliveryStatusToOrderStatus(?string $deliveryStatus): ?string
    {
        if (empty($deliveryStatus)) {
            return null;
        }
        $status = strtolower($deliveryStatus);
        if (in_array($status, ['delivered'], true)) {
            return 'delivered';
        }
        if (in_array($status, ['intransit', 'in_transit', 'outfordelivery', 'out_for_delivery', 'pending', 'inforeceived'], true)) {
            return 'shipped';
        }
        if (in_array($status, ['exception', 'failure'], true)) {
            return 'shipped'; // lasciamo shipped, l’evento resta in OrderTrackingEvent
        }
        return null;
    }
}
