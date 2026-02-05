<?php

namespace App\Listeners;

use App\Events\OrderShippedUntracked;
use App\Services\NotificationService;

class NotifyBuyerOrderShippedUntracked
{
    public function handle(OrderShippedUntracked $event): void
    {
        $order = $event->order;
        $buyer = $order->buyer;
        if (!$buyer) {
            return;
        }

        app(NotificationService::class)->send($buyer, 'buyer_order_shipped_untracked', [
            'title' => 'Ordine spedito (non tracciato)',
            'message' => "Il venditore ha spedito l'ordine.\nLa consegna non è tracciata.\n\nNessun tracking. CardSwap non può verificare la consegna.",
            'action_url' => config('app.frontend_url', config('app.url')) . '/purchases/orders/' . $order->id,
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
