<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardModel;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class ReimportBasketballCards extends Command
{
    protected $signature = 'basketball:reimport 
                            {--dry-run : Run without making changes}
                            {--keep-listings : Keep existing listings when deleting cards}';

    protected $description = 'Cancella tutte le carte di Basketball e le reimporta con rarity_variation';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $keepListings = $this->option('keep-listings');
        
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - Nessuna modifica verrà applicata');
        }

        // Trova la categoria Basketball
        $category = Category::where('slug', 'basketball')->first();
        if (!$category) {
            $this->error('❌ Categoria basketball non trovata!');
            return 1;
        }

        // Conta le carte esistenti
        $totalCards = CardModel::where('category_id', $category->id)->count();
        $this->info("📊 Carte di Basketball esistenti: {$totalCards}");

        if ($totalCards === 0) {
            $this->info("✅ Nessuna carta da cancellare, procedi direttamente con l'importazione");
        } else {
            if (!$dryRun) {
                if (!$this->confirm("⚠️  Vuoi davvero cancellare {$totalCards} carte di Basketball? (yes/no)", false)) {
                    $this->info("❌ Operazione annullata");
                    return 0;
                }
            }

            $this->info("🗑️  Cancellazione carte di Basketball...");
            
            if (!$dryRun) {
                DB::beginTransaction();
                try {
                    if ($keepListings) {
                        // Se manteniamo i listings, cancelliamo solo i card_models
                        // I listings verranno orfani ma non verranno cancellati
                        $deleted = CardModel::where('category_id', $category->id)->delete();
                    } else {
                        // Cancella prima i listings associati, poi i card_models
                        $cardIds = CardModel::where('category_id', $category->id)->pluck('id');
                        $listingsDeleted = DB::table('card_listings')
                            ->whereIn('card_model_id', $cardIds)
                            ->delete();
                        $this->info("   🗑️  Cancellati {$listingsDeleted} listings");
                        
                        $deleted = CardModel::where('category_id', $category->id)->delete();
                    }
                    
                    DB::commit();
                    $this->info("   ✅ Cancellate {$deleted} carte");
                } catch (\Exception $e) {
                    DB::rollback();
                    $this->error("❌ Errore durante la cancellazione: " . $e->getMessage());
                    return 1;
                }
            } else {
                $this->info("   ⚠️  DRY RUN - {$totalCards} carte verrebbero cancellate");
            }
        }

        $this->newLine();
        $this->info("📥 Reimportazione carte di Basketball...");
        $this->info("💡 Il comando import:basket-cards ora salva correttamente rarity_variation");
        $this->newLine();

        if (!$dryRun) {
            $this->info("🚀 Esegui manualmente:");
            $this->info("   php artisan import:basket-cards --file=/path/to/file1.csv");
            $this->info("   php artisan import:basket-cards --file=/path/to/file2.csv");
            $this->info("   php artisan import:basket-cards --file=/path/to/file3.csv");
            $this->newLine();
            $this->info("💡 Oppure trova i file automaticamente:");
            $this->info("   php artisan import:basket-cards");
        } else {
            $this->info("⚠️  DRY RUN - L'importazione non verrà eseguita");
        }

        return 0;
    }
}

