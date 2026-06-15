<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteEligibleSellers extends Command
{
    protected $signature = 'users:promote-sellers {--dry-run : Mostra i candidati senza aggiornare}';

    protected $description = 'Promuove a seller gli utenti buyer con KYC approvato e Stripe Connect attivo';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $candidates = User::query()
            ->where('role', 'buyer')
            ->where('kyc_status', 'approved')
            ->whereNotNull('stripe_account_id')
            ->where('stripe_charges_enabled', true)
            ->where('stripe_payouts_enabled', true)
            ->get();

        if ($candidates->isEmpty()) {
            $this->info('Nessun utente da promuovere.');
            return self::SUCCESS;
        }

        $promoted = 0;

        foreach ($candidates as $user) {
            if (!$user->isEligibleForSellerPromotion()) {
                continue;
            }

            if ($dryRun) {
                $this->line("DRY-RUN: {$user->email} (ID {$user->id})");
                $promoted++;
                continue;
            }

            if ($user->promoteToSellerIfEligible()) {
                $this->info("Promosso: {$user->email} (ID {$user->id})");
                $promoted++;
            }
        }

        $this->info($dryRun
            ? "Candidati trovati: {$promoted}"
            : "Utenti promossi: {$promoted}");

        return self::SUCCESS;
    }
}
