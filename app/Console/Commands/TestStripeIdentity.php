<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StripeService;
use App\Models\User;

class TestStripeIdentity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:test-identity {--user-id=1 : ID dell\'utente da testare}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa la configurazione Stripe Identity creando una sessione di verifica';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Testando configurazione Stripe Identity...');
        
        // Verifica configurazione
        $this->checkConfiguration();
        
        // Trova utente
        $userId = $this->option('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Utente con ID {$userId} non trovato");
            return 1;
        }
        
        $this->info("👤 Testando con utente: {$user->name} ({$user->email})");
        
        // Testa creazione sessione
        $this->testSessionCreation($user);
        
        return 0;
    }
    
    private function checkConfiguration()
    {
        $this->info('📋 Verificando configurazione...');
        
        $stripeKey = config('services.stripe.key');
        $stripeSecret = config('services.stripe.secret');
        $identityEnabled = config('services.stripe.identity_enabled');
        $appUrl = config('app.url');
        
        if (!$stripeKey) {
            $this->error('❌ STRIPE_KEY non configurata');
            return;
        }
        
        if (!$stripeSecret) {
            $this->error('❌ STRIPE_SECRET non configurata');
            return;
        }
        
        if (!$identityEnabled) {
            $this->warn('⚠️  STRIPE_IDENTITY_ENABLED è false');
        }
        
        if (!$appUrl) {
            $this->warn('⚠️  APP_URL non configurata');
        }
        
        $this->info('✅ Configurazione base OK');
        $this->line("   - Stripe Key: " . substr($stripeKey, 0, 20) . '...');
        $this->line("   - Identity Enabled: " . ($identityEnabled ? 'Yes' : 'No'));
        $this->line("   - App URL: {$appUrl}");
    }
    
    private function testSessionCreation(User $user)
    {
        $this->info('🚀 Creando sessione di verifica...');
        
        try {
            $stripeService = new StripeService();
            $result = $stripeService->createIdentityVerificationSession($user);
            
            if ($result['success']) {
                $this->info('✅ Sessione creata con successo!');
                $this->line("   - Session ID: {$result['session_id']}");
                $this->line("   - URL: {$result['url']}");
                $this->line("   - Client Secret: " . substr($result['client_secret'], 0, 20) . '...');
                
                // Salva session ID per test
                $user->update(['stripe_verification_session_id' => $result['session_id']]);
                
                $this->info('💡 Per testare:');
                $this->line("   1. Vai su: {$result['url']}");
                $this->line("   2. Usa documenti di test (es: 4000000000000002)");
                $this->line("   3. Controlla lo stato con: php artisan stripe:check-status {$user->id}");
                
            } else {
                $this->error('❌ Errore nella creazione della sessione:');
                $this->error("   {$result['error']}");
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Errore: ' . $e->getMessage());
        }
    }
}