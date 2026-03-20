<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

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
        RateLimiter::for('auth', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip()),
                Limit::perMinute(5)->by((string) $request->input('email')),
            ];
        });

        RateLimiter::for('public', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        Gate::define('manage-user-file', function (User $user, string $path): bool {
            $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
            $userNamespace = sprintf('public/users/%d/', $user->id);

            return str_starts_with($normalizedPath, $userNamespace);
        });

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
