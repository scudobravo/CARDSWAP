<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StripeService;
use App\Models\User;

class ForceKycCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kyc:force-check {--user-id= : ID dell\'utente da controllare} {--all : Controlla tutti gli utenti con KYC pending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Forza il controllo dello stato KYC su Stripe per utenti con verifica pending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Controllo forzato stato KYC su Stripe...');
        
        $stripeService = new StripeService();
        
        if ($this->option('all')) {
            $this->checkAllUsers($stripeService);
        } else {
            $userId = $this->option('user-id') ?: 1;
            $this->checkUser($userId, $stripeService);
        }
        
        return 0;
    }
    
    private function checkAllUsers(StripeService $stripeService)
    {
        $users = User::where('kyc_status', 'pending')
                    ->whereNotNull('stripe_verification_session_id')
                    ->get();
        
        $this->info("👥 Trovati {$users->count()} utenti con KYC pending");
        
        foreach ($users as $user) {
            $this->checkUser($user->id, $stripeService);
        }
    }
    
    private function checkUser($userId, StripeService $stripeService)
    {
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Utente con ID {$userId} non trovato");
            return;
        }
        
        $this->info("👤 Controllando utente: {$user->name} ({$user->email})");
        
        if (!$user->stripe_verification_session_id) {
            $this->warn("⚠️  Nessuna sessione di verifica attiva per questo utente");
            return;
        }
        
        $result = $stripeService->getVerificationSessionStatus($user->stripe_verification_session_id);
        
        if (!$result['success']) {
            $this->error("❌ Errore nel controllo stato: {$result['error']}");
            return;
        }
        
        $this->info("📊 Stato Stripe: {$result['status']}");
        
        if ($result['status'] === 'verified' && $user->kyc_status !== 'approved') {
            $this->info("🔄 Aggiornando stato utente...");
            
            $user->update([
                'kyc_status' => 'approved',
                'stripe_identity_verified' => true,
                'stripe_identity_verified_at' => now()
            ]);
            
            // Crea notifica
            $user->notifications()->create([
                'type' => 'kyc_update',
                'title' => 'Verifica identità completata',
                'message' => 'La tua verifica identità è stata completata con successo. Ora puoi vendere sulla piattaforma.',
                'data' => [
                    'verification_session_id' => $user->stripe_verification_session_id,
                    'status' => 'approved'
                ]
            ]);
            
            $this->info("✅ Stato aggiornato! KYC approvato per {$user->name}");
        } else {
            $this->info("ℹ️  Nessun aggiornamento necessario");
        }
    }
}