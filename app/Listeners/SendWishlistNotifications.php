<?php

namespace App\Listeners;

use App\Events\ListingStatusChanged;
use App\Notifications\WishlistItemAvailable;
use App\Models\Wishlist;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWishlistNotifications implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(ListingStatusChanged $event): void
    {
        // Invia notifiche solo quando l'inserzione viene pubblicata
        if ($event->newStatus === 'active' && $event->oldStatus !== 'active') {
            $this->notifyWishlistUsers($event->listing);
        }
    }

    /**
     * Notifica gli utenti che hanno questa carta nella wishlist
     */
    private function notifyWishlistUsers($listing): void
    {
        // Trova tutti gli utenti che hanno questa carta nella wishlist
        $wishlistUsers = Wishlist::where('card_model_id', $listing->card_model_id)
            ->with('user')
            ->get();

        foreach ($wishlistUsers as $wishlistItem) {
            // Evita di notificare il venditore stesso
            if ($wishlistItem->user_id !== $listing->seller_id) {
                $wishlistItem->user->notify(new WishlistItemAvailable($listing));
            }
        }
    }
}