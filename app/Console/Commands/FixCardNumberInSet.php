<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardModel;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class FixCardNumberInSet extends Command
{
    protected $signature = 'fix:card-number-in-set 
                            {--category= : Fix only specific category (slug)}
                            {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Corregge card_number_in_set impostando NULL quando è uguale a card_number (probabilmente NUMBERED / era vuoto)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $categorySlug = $this->option('category');

        $this->info('🔧 Correzione card_number_in_set...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  MODALITÀ DRY-RUN: nessuna modifica verrà applicata');
            $this->newLine();
        }

        // Ottieni le categorie da correggere
        $categories = [];
        if ($categorySlug) {
            $category = Category::where('slug', $categorySlug)->first();
            if (!$category) {
                $this->error("❌ Categoria '{$categorySlug}' non trovata!");
                return 1;
            }
            $categories[] = $category;
        } else {
            // Tutte le categorie
            $categories = Category::where('is_active', true)->get();
        }

        $totalFixed = 0;
        $totalByCategory = [];

        foreach ($categories as $category) {
            $this->info("📦 Categoria: {$category->name} (slug: {$category->slug})");
            
            // Trova le carte dove card_number_in_set = card_number e non è NULL
            $query = CardModel::where('category_id', $category->id)
                ->whereNotNull('card_number_in_set')
                ->whereColumn('card_number_in_set', 'card_number');

            $count = $query->count();
            
            if ($count > 0) {
                $this->line("   Trovate {$count} carte da correggere");
                
                if (!$dryRun) {
                    $fixed = $query->update(['card_number_in_set' => null]);
                    $this->info("   ✅ Corrette {$fixed} carte");
                    $totalFixed += $fixed;
                    $totalByCategory[$category->name] = $fixed;
                } else {
                    // In dry-run, mostra alcuni esempi
                    $examples = $query->limit(5)->get(['id', 'name', 'card_number', 'card_number_in_set']);
                    if ($examples->count() > 0) {
                        $this->line("   Esempi di carte che verrebbero corrette:");
                        foreach ($examples as $card) {
                            $this->line("      - ID {$card->id}: {$card->name} (card_number: '{$card->card_number}', card_number_in_set: '{$card->card_number_in_set}')");
                        }
                        if ($count > 5) {
                            $this->line("      ... e altre " . ($count - 5) . " carte");
                        }
                    }
                    $totalByCategory[$category->name] = $count;
                }
            } else {
                $this->line("   ✅ Nessuna carta da correggere");
            }
            
            $this->newLine();
        }

        // Riepilogo finale
        $this->info('📊 Riepilogo:');
        foreach ($totalByCategory as $catName => $count) {
            $this->line("   - {$catName}: {$count} carte");
        }
        
        if ($totalFixed > 0) {
            $this->newLine();
            $this->info("✅ Totale carte corrette: {$totalFixed}");
        } elseif (!$dryRun) {
            $this->newLine();
            $this->info("✅ Nessuna carta da correggere!");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('⚠️  Questo era un DRY-RUN. Esegui senza --dry-run per applicare le modifiche.');
        }

        return 0;
    }
}

