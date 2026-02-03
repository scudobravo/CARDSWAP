<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyBuyerOrderConfirmed implements ShouldQueue
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        $buyer = $order->buyer;
        if (!$buyer) {
            return;
        }

        app(NotificationService::class)->send($buyer, 'buyer_order_confirmed', [
            'title' => 'Ordine confermato',
            'message' => 'Il tuo ordine è stato confermato.',
            'action_url' => config('app.frontend_url', config('app.url')) . '/purchases/orders/' . $order->id,
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
