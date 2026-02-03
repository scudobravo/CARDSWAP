<?php

namespace App\Listeners;

use App\Events\TrackingAdded;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyBuyerTrackingAdded implements ShouldQueue
{
    public function handle(TrackingAdded $event): void
    {
        $order = $event->order;
        $buyer = $order->buyer;
        if (!$buyer) {
            return;
        }

        app(NotificationService::class)->send($buyer, 'buyer_tracking_added', [
            'title' => 'Tracking inserito',
            'message' => 'Il tuo ordine è stato spedito con tracking.',
            'action_url' => config('app.frontend_url', config('app.url')) . '/purchases/orders/' . $order->id,
            'data' => ['order_id' => $order->id, 'order_number' => $order->order_number],
            'order_number' => $order->order_number,
        ]);
    }
}
