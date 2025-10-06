<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\StripeService;
use App\Models\User;

class CheckStripeIdentityStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stripe:check-status {user-id : ID dell\'utente da controllare}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Controlla lo stato della verifica Stripe Identity per un utente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('user-id');
        $user = User::find($userId);
        
        if (!$user) {
            $this->error("❌ Utente con ID {$userId} non trovato");
            return 1;
        }
        
        $this->info("👤 Controllando stato per: {$user->name} ({$user->email})");
        
        // Mostra stato attuale nel database
        $this->showCurrentStatus($user);
        
        // Controlla stato su Stripe se c'è una sessione
        if ($user->stripe_verification_session_id) {
            $this->checkStripeStatus($user);
        } else {
            $this->warn('⚠️  Nessuna sessione di verifica attiva');
        }
        
        return 0;
    }
    
    private function showCurrentStatus(User $user)
    {
        $this->info('📊 Stato attuale nel database:');
        $this->line("   - KYC Status: {$user->kyc_status}");
        $this->line("   - Stripe Identity Verified: " . ($user->stripe_identity_verified ? 'Yes' : 'No'));
        $this->line("   - Verification Session ID: " . ($user->stripe_verification_session_id ?: 'None'));
        $this->line("   - Verified At: " . ($user->stripe_identity_verified_at ?: 'Never'));
    }
    
    private function checkStripeStatus(User $user)
    {
        $this->info('🔍 Controllando stato su Stripe...');
        
        try {
            $stripeService = new StripeService();
            $result = $stripeService->getVerificationSessionStatus($user->stripe_verification_session_id);
            
            if ($result['success']) {
                $this->info('✅ Stato Stripe recuperato:');
                $this->line("   - Status: {$result['status']}");
                
                if (isset($result['verified_outputs'])) {
                    $this->line("   - Verified Outputs: " . json_encode($result['verified_outputs'], JSON_PRETTY_PRINT));
                }
                
                // Aggiorna stato se necessario
                if ($result['status'] === 'verified' && !$user->stripe_identity_verified) {
                    $this->info('🔄 Aggiornando stato utente...');
                    $user->update([
                        'kyc_status' => 'approved',
                        'stripe_identity_verified' => true,
                        'stripe_identity_verified_at' => now()
                    ]);
                    $this->info('✅ Stato aggiornato!');
                }
                
            } else {
                $this->error('❌ Errore nel recupero dello stato:');
                $this->error("   {$result['error']}");
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Errore: ' . $e->getMessage());
        }
    }
}