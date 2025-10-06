<?php

namespace App\Listeners;

use App\Events\ListingSold;
use App\Notifications\ListingSold as ListingSoldNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendListingSoldNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ListingSold $event): void
    {
        // Notifica il venditore
        $event->listing->seller->notify(new ListingSoldNotification($event->listing, $event->order));
        
        // Notifica l'acquirente
        $event->order->buyer->notify(new ListingSoldNotification($event->listing, $event->order));
    }
}