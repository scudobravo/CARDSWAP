<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardListing;
use Illuminate\Support\Facades\DB;

class PublishPendingListings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'listings:publish-pending';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pubblica tutte le inserzioni in pending_review o draft che dovrebbero essere attive';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Pubblicazione inserzioni in pending_review o draft...');

        // Pubblica tutte le inserzioni in pending_review o draft
        $pendingListings = CardListing::whereIn('status', ['pending_review', 'draft', 'approved'])
            ->get();

        $publishedCount = 0;

        foreach ($pendingListings as $listing) {
            $oldStatus = $listing->status;
            $listing->publish();
            $publishedCount++;
            $this->line("✅ Inserzione #{$listing->id} pubblicata (status: {$oldStatus} -> active)");
        }

        $this->info("✅ Pubblicate {$publishedCount} inserzioni");

        return Command::SUCCESS;
    }
}
