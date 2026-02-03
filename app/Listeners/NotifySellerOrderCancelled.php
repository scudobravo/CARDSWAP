<?php

namespace App\Listeners;

use App\Events\OrderCancelled;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifySellerOrderCancelled implements ShouldQueue
{
    public function handle(OrderCancelled $event): void
    {
        $order = $event->order;
        $seller = $order->seller;
        if (!$seller) {
            return;
        }

        app(NotificationService::class)->send($seller, 'seller_order_cancelled', [
            'title' => 'Ordine annullato',
            'message' => 'Ordine annullato per mancato rispetto delle tempistiche di spedizione.',
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
