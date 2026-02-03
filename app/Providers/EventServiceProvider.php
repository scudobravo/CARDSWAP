<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\ListingStatusChanged;
use App\Events\ListingSold;
use App\Events\OrderPaid;
use App\Events\OrderShippedUntracked;
use App\Events\TrackingAdded;
use App\Events\OrderDelivered;
use App\Events\OrderCancelled;
use App\Events\OrderReleased;
use App\Events\DisputeOpened;
use App\Events\OrderRefunded;
use App\Events\DisputeUpdated;
use App\Listeners\SendListingPublishedNotification;
use App\Listeners\SendListingSoldNotification;
use App\Listeners\SendWishlistNotifications;
use App\Listeners\NotifySellerOrderPaid;
use App\Listeners\NotifyBuyerOrderConfirmed;
use App\Listeners\NotifyBuyerOrderShippedUntracked;
use App\Listeners\NotifyBuyerTrackingAdded;
use App\Listeners\NotifySellerOrderDelivered;
use App\Listeners\NotifyBuyerOrderDelivered;
use App\Listeners\NotifySellerOrderCancelled;
use App\Listeners\NotifySellerOrderReleased;
use App\Listeners\NotifyBuyerOrderReleased;
use App\Listeners\NotifySellerDisputeOpened;
use App\Listeners\NotifyBuyerOrderRefunded;
use App\Listeners\NotifyBuyerDisputeUpdated;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Eventi per le inserzioni
        ListingStatusChanged::class => [
            SendListingPublishedNotification::class,
            SendWishlistNotifications::class,
        ],

        ListingSold::class => [
            SendListingSoldNotification::class,
        ],

        // CardSwap Shipping V1 – FASE D3 notifiche
        OrderPaid::class => [
            NotifySellerOrderPaid::class,
            NotifyBuyerOrderConfirmed::class,
        ],
        OrderShippedUntracked::class => [
            NotifyBuyerOrderShippedUntracked::class,
        ],
        TrackingAdded::class => [
            NotifyBuyerTrackingAdded::class,
        ],
        OrderDelivered::class => [
            NotifySellerOrderDelivered::class,
            NotifyBuyerOrderDelivered::class,
        ],
        OrderCancelled::class => [
            NotifySellerOrderCancelled::class,
        ],
        OrderReleased::class => [
            NotifySellerOrderReleased::class,
            NotifyBuyerOrderReleased::class,
        ],
        DisputeOpened::class => [
            NotifySellerDisputeOpened::class,
        ],
        OrderRefunded::class => [
            NotifyBuyerOrderRefunded::class,
        ],
        DisputeUpdated::class => [
            NotifyBuyerDisputeUpdated::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}