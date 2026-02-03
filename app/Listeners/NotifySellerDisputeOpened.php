<?php

namespace App\Listeners;

use App\Events\DisputeOpened;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifySellerDisputeOpened implements ShouldQueue
{
    public function handle(DisputeOpened $event): void
    {
        $order = $event->order;
        $seller = $order->seller;
        if (!$seller) {
            return;
        }

        app(NotificationService::class)->send($seller, 'seller_dispute_opened', [
            'title' => 'Disputa aperta',
            'message' => "È stata aperta una disputa su un ordine.\nAccedi per fornire la documentazione richiesta.",
            'action_url' => config('app.frontend_url', config('app.url')) . '/seller/orders/' . $order->id,
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
