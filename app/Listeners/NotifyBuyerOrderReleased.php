<?php

namespace App\Listeners;

use App\Events\OrderReleased;
use App\Services\NotificationService;

class NotifyBuyerOrderReleased
{
    public function handle(OrderReleased $event): void
    {
        $order = $event->order;
        $buyer = $order->buyer;
        if (!$buyer) {
            return;
        }

        app(NotificationService::class)->send($buyer, 'buyer_order_released', [
            'title' => 'Ordine completato',
            'message' => 'Ordine completato. Il pagamento al venditore è stato rilasciato.',
            'action_url' => config('app.frontend_url', config('app.url')) . '/purchases/orders/' . $order->id,
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
