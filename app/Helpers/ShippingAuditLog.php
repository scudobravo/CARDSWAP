<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

/**
 * D5 – Hardening & Anti-abuse.
 * Log strutturato per eventi critici: order_id, seller_id, buyer_id, action, source, timestamp.
 */
class ShippingAuditLog
{
    public const SOURCE_API = 'api';
    public const SOURCE_JOB = 'job';
    public const SOURCE_WEBHOOK = 'webhook';

    public const ACTION_TRACKING_INSERTED = 'tracking_inserted';
    public const ACTION_SHIPPED_UNTRACKED = 'shipped_untracked';
    public const ACTION_ORDER_CANCELLED = 'order_cancelled';
    public const ACTION_ORDER_DELIVERED = 'order_delivered';
    public const ACTION_DISPUTE_OPENED = 'dispute_opened';
    public const ACTION_DISPUTE_RESOLVED = 'dispute_resolved';
    public const ACTION_RELEASE_FUNDS = 'release_funds';

    public static function log(
        string $action,
        string $source,
        ?int $orderId = null,
        ?int $sellerId = null,
        ?int $buyerId = null,
        array $extra = []
    ): void {
        $payload = array_filter([
            'order_id' => $orderId,
            'seller_id' => $sellerId,
            'buyer_id' => $buyerId,
            'action' => $action,
            'source' => $source,
            'timestamp' => now()->toIso8601String(),
        ] + $extra);

        Log::info('D5-AUDIT', $payload);
    }

    /** D5 – Anti-abuse seller: log evento negativo (monitoraggio, nessun blocco in V1). */
    public static function logSellerNegativeEvent(
        string $eventType,
        int $orderId,
        int $sellerId,
        array $context = []
    ): void {
        Log::warning('D5-ANTI-ABUSE-SELLER', [
            'event' => $eventType,
            'order_id' => $orderId,
            'seller_id' => $sellerId,
            'timestamp' => now()->toIso8601String(),
        ] + $context);
    }
}
