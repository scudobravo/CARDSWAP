<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\CardListing;
use App\Models\Order;

class ListingSold
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $listing;
    public $order;

    /**
     * Create a new event instance.
     */
    public function __construct(CardListing $listing, Order $order)
    {
        $this->listing = $listing;
        $this->order = $order;
    }
}