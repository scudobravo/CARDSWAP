<?php

namespace App\Listeners;

use App\Events\ListingStatusChanged;
use App\Notifications\ListingPublished;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendListingPublishedNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ListingStatusChanged $event): void
    {
        // Invia notifica solo quando l'inserzione viene pubblicata
        if ($event->newStatus === 'active' && $event->oldStatus !== 'active') {
            $event->listing->seller->notify(new ListingPublished($event->listing));
        }
    }
}