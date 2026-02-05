<?php

namespace App\Listeners;

use App\Events\OrderReleased;
use App\Services\NotificationService;

class NotifySellerOrderReleased
{
    public function handle(OrderReleased $event): void
    {
        $order = $event->order;
        $seller = $order->seller;
        if (!$seller) {
            return;
        }

        app(NotificationService::class)->send($seller, 'seller_order_released', [
            'title' => 'Pagamento rilasciato',
            'message' => 'Pagamento rilasciato con successo.',
            'action_url' => config('app.frontend_url', config('app.url')) . '/seller/orders/' . $order->id,
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
