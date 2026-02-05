<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Services\StripeService;
use App\Services\ShippoService;

class CheckProductionConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'production:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica la configurazione per la produzione (Stripe, Shippo, Email, ecc.)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verifica Configurazione Produzione');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $allOk = true;

        // 1. Verifica Ambiente
        $allOk = $this->checkEnvironment() && $allOk;

        // 2. Verifica Stripe
        $allOk = $this->checkStripe() && $allOk;

        // 3. Verifica Shippo
        $allOk = $this->checkShippo() && $allOk;

        // 4. Verifica Email
        $allOk = $this->checkEmail() && $allOk;

        // 5. Verifica Database
        $allOk = $this->checkDatabase() && $allOk;

        // 6. Verifica URL e SSL
        $allOk = $this->checkUrl() && $allOk;

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');
        
        if ($allOk) {
            $this->info('Tutte le verifiche sono passate! Pronto per la produzione.');
            return 0;
        } else {
            $this->error('❌ Alcune verifiche sono fallite. Controlla la configurazione.');
            return 1;
        }
    }

    private function checkEnvironment(): bool
    {
        $this->info('1. Verifica Ambiente');
        
        $env = config('app.env');
        $debug = config('app.debug');
        $url = config('app.url');

        $ok = true;

        if ($env === 'production') {
            $this->line("   APP_ENV: $env");
        } else {
            $this->error("   APP_ENV: $env (dovrebbe essere 'production')");
            $ok = false;
        }

        if (!$debug) {
            $this->line("   APP_DEBUG: false");
        } else {
            $this->error("   APP_DEBUG: true (dovrebbe essere false in produzione)");
            $ok = false;
        }

        if ($url && str_starts_with($url, 'https://')) {
            $this->line("   APP_URL: $url (HTTPS attivo)");
        } elseif ($url) {
            $this->warn("    APP_URL: $url (non usa HTTPS)");
        } else {
            $this->error("   APP_URL: non configurato");
            $ok = false;
        }

        $this->newLine();
        return $ok;
    }

    private function checkStripe(): bool
    {
        $this->info('2. Verifica Stripe');
        
        $stripeKey = config('services.stripe.key');
        $stripeSecret = config('services.stripe.secret');
        $webhookSecret = config('services.stripe.webhook_secret');
        
        $ok = true;

        // Verifica chiave pubblica
        if ($stripeKey) {
            if (str_starts_with($stripeKey, 'pk_live_')) {
                $this->line("   STRIPE_KEY: configurata (produzione)");
            } elseif (str_starts_with($stripeKey, 'pk_test_')) {
                $this->error("   STRIPE_KEY: è una chiave di TEST (usa pk_live_)");
                $ok = false;
            } else {
                $this->error("   STRIPE_KEY: formato non valido");
                $ok = false;
            }
        } else {
            $this->error("   STRIPE_KEY: non configurata");
            $ok = false;
        }

        // Verifica chiave segreta
        if ($stripeSecret) {
            if (str_starts_with($stripeSecret, 'sk_live_')) {
                $this->line("   STRIPE_SECRET: configurata (produzione)");
            } elseif (str_starts_with($stripeSecret, 'sk_test_')) {
                $this->error("   STRIPE_SECRET: è una chiave di TEST (usa sk_live_)");
                $ok = false;
            } else {
                $this->error("   STRIPE_SECRET: formato non valido");
                $ok = false;
            }
        } else {
            $this->error("   STRIPE_SECRET: non configurata");
            $ok = false;
        }

        // Verifica webhook secret
        if ($webhookSecret) {
            if (str_starts_with($webhookSecret, 'whsec_')) {
                $this->line("   STRIPE_WEBHOOK_SECRET: configurato");
            } else {
                $this->warn("    STRIPE_WEBHOOK_SECRET: formato sospetto");
            }
        } else {
            $this->warn("    STRIPE_WEBHOOK_SECRET: non configurato (webhook non funzioneranno)");
        }

        // Test connessione Stripe
        if ($stripeSecret && str_starts_with($stripeSecret, 'sk_live_')) {
            try {
                $stripeService = new StripeService();
                $this->line("   Connessione Stripe: OK");
            } catch (\Exception $e) {
                $this->error("   Connessione Stripe fallita: " . $e->getMessage());
                $ok = false;
            }
        }

        $this->newLine();
        return $ok;
    }

    private function checkShippo(): bool
    {
        $this->info('3. Verifica Shippo');
        
        $apiKey = config('services.shippo.key');
        $sender = config('services.shippo.sender');
        
        $ok = true;

        if ($apiKey) {
            if (str_starts_with($apiKey, 'shippo_live_') || str_starts_with($apiKey, 'shippo_test_')) {
                if (str_starts_with($apiKey, 'shippo_live_')) {
                    $this->line("   SHIPPO_API_KEY: configurata (produzione)");
                } else {
                    $this->warn("    SHIPPO_API_KEY: è una chiave di TEST");
                }
            } else {
                $this->warn("    SHIPPO_API_KEY: formato non riconosciuto");
            }
        } else {
            $this->error("   SHIPPO_API_KEY: non configurata");
            $ok = false;
        }

        // Verifica indirizzo mittente
        $requiredFields = ['name', 'street1', 'city', 'zip', 'country'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (empty($sender[$field])) {
                $missingFields[] = "SHIPPO_SENDER_" . strtoupper($field);
            }
        }

        if (empty($missingFields)) {
            $this->line("   Indirizzo mittente: completo");
            $this->line("      " . ($sender['name'] ?? '') . ", " . ($sender['city'] ?? '') . ", " . ($sender['country'] ?? ''));
        } else {
            $this->error("   Indirizzo mittente incompleto. Campi mancanti: " . implode(', ', $missingFields));
            $ok = false;
        }

        $this->newLine();
        return $ok;
    }

    private function checkEmail(): bool
    {
        $this->info('4. Verifica Email');
        
        $mailer = config('mail.default');
        $fromAddress = config('mail.from.address');
        $fromName = config('mail.from.name');
        
        $ok = true;

        // Verifica mailer
        $validMailers = ['smtp', 'mailgun', 'postmark', 'resend', 'ses'];
        if (in_array($mailer, $validMailers)) {
            $this->line("   MAIL_MAILER: $mailer");
        } elseif ($mailer === 'log') {
            $this->warn("    MAIL_MAILER: log (le email non verranno inviate realmente)");
        } else {
            $this->error("   MAIL_MAILER: $mailer (non valido per produzione)");
            $ok = false;
        }

        // Verifica indirizzo mittente
        if ($fromAddress && $fromAddress !== 'hello@example.com') {
            if (filter_var($fromAddress, FILTER_VALIDATE_EMAIL)) {
                $this->line("   MAIL_FROM_ADDRESS: $fromAddress");
            } else {
                $this->error("   MAIL_FROM_ADDRESS: formato email non valido");
                $ok = false;
            }
        } else {
            $this->error("   MAIL_FROM_ADDRESS: non configurato o usa valore di default");
            $ok = false;
        }

        if ($fromName && $fromName !== 'Example') {
            $this->line("   MAIL_FROM_NAME: $fromName");
        } else {
            $this->warn("    MAIL_FROM_NAME: non configurato o usa valore di default");
        }

        // Verifica configurazione SMTP se usato
        if ($mailer === 'smtp') {
            $host = config('mail.mailers.smtp.host');
            $port = config('mail.mailers.smtp.port');
            $username = config('mail.mailers.smtp.username');
            
            if ($host && $host !== '127.0.0.1') {
                $this->line("   SMTP Host: $host");
            } else {
                $this->error("   SMTP Host: non configurato correttamente");
                $ok = false;
            }

            if ($username) {
                $this->line("   SMTP Username: configurato");
            } else {
                $this->warn("    SMTP Username: non configurato");
            }
        }

        $this->newLine();
        return $ok;
    }

    private function checkDatabase(): bool
    {
        $this->info('5. Verifica Database');
        
        try {
            \DB::connection()->getPdo();
            $this->line("   Connessione database: OK");
            
            // Verifica che non sia il database di test
            $database = config('database.connections.mysql.database');
            if (str_contains(strtolower($database), 'test')) {
                $this->warn("    Database sembra essere di test: $database");
            } else {
                $this->line("   Database: $database");
            }
            
            $this->newLine();
            return true;
        } catch (\Exception $e) {
            $this->error("   Connessione database fallita: " . $e->getMessage());
            $this->newLine();
            return false;
        }
    }

    private function checkUrl(): bool
    {
        $this->info('6. Verifica URL e SSL');
        
        $url = config('app.url');
        $ok = true;

        if (!$url) {
            $this->error("   APP_URL: non configurato");
            $ok = false;
        } elseif (!str_starts_with($url, 'https://')) {
            $this->warn("    APP_URL: non usa HTTPS ($url)");
            $this->warn("      In produzione è fortemente consigliato usare HTTPS");
        } else {
            $this->line("   APP_URL: $url (HTTPS attivo)");
            
            // Verifica che l'URL sia raggiungibile
            try {
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_NOBODY, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode >= 200 && $httpCode < 400) {
                    $this->line("   URL raggiungibile: HTTP $httpCode");
                } else {
                    $this->warn("    URL risponde con codice: HTTP $httpCode");
                }
            } catch (\Exception $e) {
                $this->warn("    Impossibile verificare raggiungibilità URL: " . $e->getMessage());
            }
        }

        $this->newLine();
        return $ok;
    }
}



