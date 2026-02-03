<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifySellerOrderDelivered implements ShouldQueue
{
    public function handle(OrderDelivered $event): void
    {
        $order = $event->order;
        $seller = $order->seller;
        if (!$seller) {
            return;
        }

        app(NotificationService::class)->send($seller, 'seller_order_delivered', [
            'title' => 'Ordine consegnato',
            'message' => "L'ordine risulta consegnato.\nIl pagamento verrà rilasciato dopo 72 ore se non ci sono dispute.",
            'action_url' => config('app.frontend_url', config('app.url')) . '/seller/orders/' . $order->id,
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
