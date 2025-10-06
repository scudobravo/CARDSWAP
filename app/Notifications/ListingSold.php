<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\CardListing;
use App\Models\Order;

class ListingSold extends Notification implements ShouldQueue
{
    use Queueable;

    protected $listing;
    protected $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(CardListing $listing, Order $order)
    {
        $this->listing = $listing;
        $this->order = $order;
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
            ->subject('💰 La tua carta è stata venduta!')
            ->view('emails.listing-sold', [
                'user' => $notifiable,
                'listing' => $this->listing,
                'order' => $this->order
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'listing_id' => $this->listing->id,
            'order_id' => $this->order->id,
            'title' => $this->listing->title,
            'price' => $this->order->total_amount,
            'buyer_name' => $this->order->buyer->name,
            'sold_at' => $this->order->created_at,
        ];
    }
}