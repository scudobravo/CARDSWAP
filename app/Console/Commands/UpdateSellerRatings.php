<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\OrderFeedback;

class UpdateSellerRatings extends Command
{
    protected $signature = 'feedback:update-seller-ratings {--seller-id= : ID specifico venditore}';
    protected $description = 'Aggiorna le medie rating di tutti i venditori o di uno specifico';

    public function handle(): int
    {
        $sellerId = $this->option('seller-id');
        
        if ($sellerId) {
            $this->updateSingleSeller($sellerId);
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

        $this->updateSellerRating($seller);
        $this->info("Rating aggiornato per venditore: {$seller->name}");
    }

    private function updateAllSellers(): void
    {
        $sellers = User::whereIn('role', ['seller', 'admin'])->get();
        $bar = $this->output->createProgressBar($sellers->count());

        foreach ($sellers as $seller) {
            $this->updateSellerRating($seller);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Rating aggiornati per {$sellers->count()} venditori");
    }

    private function updateSellerRating(User $seller): void
    {
        $feedbacks = OrderFeedback::where('seller_id', $seller->id)
            ->where('is_public', true)
            ->where('is_hidden', false);

        $totalFeedbacks = $feedbacks->count();
        $averageRating = $totalFeedbacks > 0 ? $feedbacks->avg('rating') : 0;

        $seller->update(['rating' => round($averageRating, 2)]);
    }
}
