<?php

namespace App\Listeners;

use App\Events\DisputeUpdated;
use App\Services\NotificationService;

class NotifyBuyerDisputeUpdated
{
    public function handle(DisputeUpdated $event): void
    {
        $order = $event->order;
        $buyer = $order->buyer;
        if (!$buyer) {
            return;
        }

        app(NotificationService::class)->send($buyer, 'buyer_dispute_updated', [
            'title' => 'Disputa aggiornata',
            'message' => 'La disputa sul tuo ordine è stata aggiornata.',
            'action_url' => config('app.frontend_url', config('app.url')) . '/purchases/orders/' . $order->id,
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
