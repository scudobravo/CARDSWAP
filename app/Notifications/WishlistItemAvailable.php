<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\CardListing;

class WishlistItemAvailable extends Notification implements ShouldQueue
{
    use Queueable;

    protected $listing;

    /**
     * Create a new notification instance.
     */
    public function __construct(CardListing $listing)
    {
        $this->listing = $listing;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⭐ Una carta dalla tua wishlist è disponibile!')
            ->view('emails.wishlist-item-available', [
                'user' => $notifiable,
                'listing' => $this->listing
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'listing_id' => $this->listing->id,
            'title' => $this->listing->title,
            'price' => $this->listing->price,
            'seller_name' => $this->listing->seller->name,
            'published_at' => $this->listing->published_at,
        ];
    }
}