<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ResetKycForProduction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kyc:reset-for-production 
                            {--user-id= : Reset KYC per un utente specifico}
                            {--all : Reset KYC per tutti gli utenti}
                            {--dry-run : Mostra cosa verrebbe fatto senza applicare le modifiche}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resetta lo stato KYC degli utenti che hanno completato il KYC con Stripe Test, per permettere di rifarlo con Stripe Live';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Reset KYC per Produzione');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $userId = $this->option('user-id');
        $all = $this->option('all');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - Nessuna modifica verrà applicata');
            $this->newLine();
        }

        // Verifica ambiente
        $env = config('app.env');
        if ($env !== 'production' && !$dryRun) {
            if (!$this->confirm("L'ambiente non è 'production' (attuale: $env). Continuare?")) {
                $this->error('Operazione annullata.');
                return 1;
            }
        }

        // Determina quali utenti resettare
        $query = User::query();

        if ($userId) {
            $query->where('id', $userId);
            $this->info("Reset KYC per utente ID: $userId");
        } elseif ($all) {
            $this->info("🌍 Reset KYC per TUTTI gli utenti");
        } else {
            // Default: solo utenti con KYC approvato (probabilmente fatto in test)
            $query->where('kyc_status', 'approved')
                  ->orWhere('stripe_identity_verified', true);
            $this->info("Reset KYC per utenti con KYC approvato o verifica Stripe Identity");
        }

        $users = $query->get();

        if ($users->isEmpty()) {
            $this->info('Nessun utente trovato da resettare.');
            return 0;
        }

        $this->info("Trovati {$users->count()} utente/i da resettare:");
        $this->newLine();

        // Mostra tabella utenti
        $tableData = [];
        foreach ($users as $user) {
            $tableData[] = [
                'ID' => $user->id,
                'Email' => $user->email,
                'Nome' => $user->name,
                'KYC Status' => $user->kyc_status,
                'Stripe Identity' => $user->stripe_identity_verified ? 'Si' : 'No',
                'Session ID' => $user->stripe_verification_session_id ? substr($user->stripe_verification_session_id, 0, 20) . '...' : 'N/A',
            ];
        }

        $this->table(
            ['ID', 'Email', 'Nome', 'KYC Status', 'Stripe Identity', 'Session ID'],
            $tableData
        );

        $this->newLine();

        if (!$this->confirm('Vuoi procedere con il reset?' . ($dryRun ? ' (DRY RUN)' : ''))) {
            $this->error('Operazione annullata.');
            return 1;
        }

        $this->newLine();

        // Reset KYC
        $resetCount = 0;
        foreach ($users as $user) {
            if ($dryRun) {
                $this->line("[DRY RUN] Reset KYC per utente: {$user->email} (ID: {$user->id})");
            } else {
                try {
                    DB::beginTransaction();

                    $user->update([
                        'kyc_status' => 'not_submitted',
                        'stripe_identity_verified' => false,
                        'stripe_identity_verified_at' => null,
                        'stripe_verification_session_id' => null,
                        'kyc_submitted_at' => null,
                        'kyc_verified_at' => null,
                        'kyc_rejection_reason' => null,
                    ]);

                    // Crea notifica per l'utente
                    $user->notifications()->create([
                        'type' => 'kyc_update',
                        'title' => 'Verifica identità richiesta',
                        'message' => 'È necessario completare nuovamente la verifica identità per utilizzare i servizi di pagamento. Vai su Dashboard > KYC per iniziare.',
                        'data' => [
                            'reason' => 'reset_for_production',
                            'requires_action' => true
                        ]
                    ]);

                    DB::commit();
                    $this->info("Reset KYC completato per: {$user->email} (ID: {$user->id})");
                    $resetCount++;
                } catch (\Exception $e) {
                    DB::rollBack();
                    $this->error("Errore nel reset KYC per {$user->email}: " . $e->getMessage());
                }
            }
        }

        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════');

        if ($dryRun) {
            $this->warn("DRY RUN completato. {$users->count()} utente/i verrebbero resettati.");
            $this->info('Esegui senza --dry-run per applicare le modifiche.');
        } else {
            $this->info("Reset completato! {$resetCount} utente/i resettati.");
            $this->newLine();
            $this->info('Prossimi passi:');
            $this->line('   1. Gli utenti dovranno completare nuovamente il KYC');
            $this->line('   2. Vai su Dashboard > KYC per iniziare');
            $this->line('   3. Il KYC verrà fatto con le chiavi Stripe di produzione');
        }

        return 0;
    }
}

