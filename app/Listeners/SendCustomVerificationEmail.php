<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendCustomVerificationEmail
{
    /**
     * Invia l'email di verifica con il token custom (non Laravel signed URL).
     */
    public function handle(Registered $event): void
    {
        $user = $event->user;

        if (empty($user->email_verification_token)) {
            Log::warning('SendCustomVerificationEmail: utente senza email_verification_token', ['user_id' => $user->id]);
            return;
        }

        $frontendUrl = rtrim(config('app.frontend_url', config('app.url')), '/');
        $verifyUrl = $frontendUrl . '/verify-email?token=' . urlencode($user->email_verification_token);

        try {
            Mail::send('emails.verify-email', [
                'user' => $user,
                'verifyUrl' => $verifyUrl,
            ], function ($m) use ($user) {
                $m->to($user->email, $user->name)
                    ->subject('Conferma il tuo account - CardSwap');
            });
        } catch (\Throwable $e) {
            Log::warning('SendCustomVerificationEmail: invio fallito', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
