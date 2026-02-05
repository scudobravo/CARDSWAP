<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Services\NotificationService;

class NotifySellerOrderPaid
{
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;
        $seller = $order->seller;
        if (!$seller) {
            return;
        }

        app(NotificationService::class)->send($seller, 'seller_order_paid', [
            'title' => 'Nuovo ordine ricevuto',
            'message' => "Hai 5 giorni per spedire l'ordine.\nSe tracciato, inserisci il tracking entro 7 giorni.",
            'action_url' => config('app.frontend_url', config('app.url')) . '/seller/orders/' . $order->id,
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
