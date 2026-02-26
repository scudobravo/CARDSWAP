<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UpdateSellerTotalSales extends Command
{
    protected $signature = 'sellers:update-total-sales {--seller-id= : ID specifico venditore}';
    protected $description = 'Ricalcola il numero di vendite (total_sales) per i venditori in base agli ordini completed';

    public function handle(): int
    {
        $sellerId = $this->option('seller-id');

        if ($sellerId) {
            $this->updateSingleSeller((int) $sellerId);
        } else {
            $this->updateAllSellers();
        }

        return self::SUCCESS;
    }

    private function updateSingleSeller(int $sellerId): void
    {
        $seller = User::find($sellerId);
        if (!$seller) {
            $this->error("Venditore con ID {$sellerId} non trovato");
            return;
        }

        $count = $seller->sellerOrders()->where('status', 'completed')->count();
        $seller->update(['total_sales' => $count]);
        $this->info("Venditore {$seller->name} (ID {$sellerId}): total_sales = {$count}");
    }

    private function updateAllSellers(): void
    {
        $sellers = User::whereIn('role', ['seller', 'admin'])->get();
        $bar = $this->output->createProgressBar($sellers->count());

        foreach ($sellers as $seller) {
            $count = $seller->sellerOrders()->where('status', 'completed')->count();
            $seller->update(['total_sales' => $count]);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("total_sales aggiornati per {$sellers->count()} venditori.");
    }
}
