<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\ListingStatusChanged;
use App\Events\ListingSold;
use App\Listeners\SendListingPublishedNotification;
use App\Listeners\SendListingSoldNotification;
use App\Listeners\SendWishlistNotifications;

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