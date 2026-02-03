<?php

namespace App\Listeners;

use App\Events\OrderDelivered;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyBuyerOrderDelivered implements ShouldQueue
{
    public function handle(OrderDelivered $event): void
    {
        $order = $event->order;
        $buyer = $order->buyer;
        if (!$buyer) {
            return;
        }

        app(NotificationService::class)->send($buyer, 'buyer_order_delivered', [
            'title' => 'Ordine consegnato',
            'message' => "L'ordine risulta consegnato.\nHai 72 ore per segnalare eventuali problemi.",
            'action_url' => config('app.frontend_url', config('app.url')) . '/purchases/orders/' . $order->id,
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
