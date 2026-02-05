<?php

namespace App\Listeners;

use App\Events\OrderRefunded;
use App\Services\NotificationService;

class NotifyBuyerOrderRefunded
{
    public function handle(OrderRefunded $event): void
    {
        $order = $event->order;
        $buyer = $order->buyer;
        if (!$buyer) {
            return;
        }

        app(NotificationService::class)->send($buyer, 'buyer_order_refunded', [
            'title' => 'Ordine annullato e rimborsato',
            'message' => 'Ordine annullato e rimborsato.',
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
