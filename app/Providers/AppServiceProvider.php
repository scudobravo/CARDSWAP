<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Link reset password verso la SPA (frontend)
        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        ResetPassword::toMailUsing(function (object $notifiable, string $token) use ($frontendUrl) {
            $url = $frontendUrl . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
            return (new MailMessage)
                ->subject('Reimposta la tua password - CardSwap')
                ->line('Hai richiesto il reset della password. Clicca il pulsante qui sotto per reimpostarla.')
                ->action('Reimposta password', $url)
                ->line('Se non hai richiesto tu il reset, ignora questa email.');
        });
    }
}
