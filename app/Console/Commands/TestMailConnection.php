<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Test SMTP connection and credentials by sending one email.
 * Uses config from .env (MAIL_*). No credentials in code.
 */
class TestMailConnection extends Command
{
    protected $signature = 'mail:test {to? : Email destinatario (default: MAIL_FROM_ADDRESS)}';
    protected $description = 'Invia un\'email di test per verificare connessione SMTP e credenziali';

    public function handle(): int
    {
        $to = $this->argument('to') ?: config('mail.from.address');
        $driver = config('mail.default');
        $host = config('mail.mailers.smtp.host');
        $port = config('mail.mailers.smtp.port');
        $user = config('mail.mailers.smtp.username');
        $hasPassword = !empty(config('mail.mailers.smtp.password'));

        $this->info("Driver: {$driver}");
        $this->info("Host: {$host}:{$port}");
        $this->info("Username: {$user}");
        $this->info("Password impostata: " . ($hasPassword ? 'sì (' . strlen(config('mail.mailers.smtp.password')) . ' caratteri)' : 'no'));
        $this->info("Destinatario test: {$to}");
        $this->newLine();

        try {
            Mail::raw(
                "Questo è un messaggio di test CardSwap.\nData/Ora: " . now()->toIso8601String(),
                function ($message) use ($to) {
                    $message->to($to)
                        ->subject('CardSwap - Test connessione email');
                }
            );
            $this->info('OK: Email inviata. Controlla la casella (e spam) di ' . $to);
            return 0;
        } catch (\Throwable $e) {
            $this->error('ERRORE: ' . $e->getMessage());
            return 1;
        }
    }
}
