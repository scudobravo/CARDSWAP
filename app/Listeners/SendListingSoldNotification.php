<?php

namespace App\Listeners;

use App\Events\ListingSold;
use App\Models\UserNotification;
use App\Notifications\ListingSold as ListingSoldNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendListingSoldNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /** Finestra (minuti) entro cui considerare già inviata un'email "venduta" per stesso ordine/venditore. */
    private const DEDUP_WINDOW_MINUTES = 60;

    /**
     * Handle the event.
     * Inviamo una sola email "La tua carta è stata venduta" al venditore per ordine (dedup),
     * per evitare 2+ email quando ci sono più listing o più eventi ListingSold per lo stesso ordine.
     */
    public function handle(ListingSold $event): void
    {
        $order = $event->order;
        $listing = $event->listing;
        $seller = $listing->seller;

        // Dedup: una sola email al venditore per ordine (evita 3 email per stessa vendita)
        $alreadySent = UserNotification::where('user_id', $seller->id)
            ->where('type', 'seller_listing_sold_sent')
            ->where('data->order_id', $order->id)
            ->where('created_at', '>=', now()->subMinutes(self::DEDUP_WINDOW_MINUTES))
            ->exists();

        if (!$alreadySent) {
            UserNotification::create([
                'user_id' => $seller->id,
                'type' => 'seller_listing_sold_sent',
                'title' => 'Listing sold email sent',
                'message' => '',
                'data' => ['order_id' => $order->id],
            ]);
            $seller->notify(new ListingSoldNotification($listing, $order));
        }

        // Notifica l'acquirente (una per listing, ok per coerenza con contenuto)
        $event->order->buyer->notify(new ListingSoldNotification($listing, $order));
    }
}