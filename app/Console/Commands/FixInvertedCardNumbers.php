<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardModel;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class FixInvertedCardNumbers extends Command
{
    protected $signature = 'fix:inverted-card-numbers 
                            {--category= : Fix only specific category (slug)}
                            {--dry-run : Show what would be changed without making changes}
                            {--limit=100 : Limit number of fixes per run}';

    protected $description = 'Corregge carte dove card_number e card_number_in_set sono invertiti (card_number contiene NUMBERED / e card_number_in_set contiene Numero)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $categorySlug = $this->option('category');
        $limit = (int) $this->option('limit');

        $this->info('🔧 Correzione valori invertiti tra card_number e card_number_in_set...');
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  MODALITÀ DRY-RUN: nessuna modifica verrà applicata');
            $this->newLine();
        }

        // Query per trovare carte dove card_number sembra essere un NUMBERED / (contiene lettere o pattern tipici)
        // e card_number_in_set sembra essere un Numero (solo numeri o pattern semplici)
        $query = CardModel::query()
            ->join('categories', 'card_models.category_id', '=', 'categories.id')
            ->whereNotNull('card_models.card_number')
            ->whereNotNull('card_models.card_number_in_set')
            ->where('card_models.card_number', '!=', '')
            ->where('card_models.card_number_in_set', '!=', '')
            ->whereColumn('card_models.card_number', '!=', 'card_models.card_number_in_set')
            ->where(function($q) {
                // card_number contiene lettere o pattern complessi (probabilmente NUMBERED /)
                $q->where('card_models.card_number', 'REGEXP', '[A-Za-z-]')
                  // E card_number_in_set è solo numeri o pattern semplici (probabilmente Numero)
                  ->where(function($q2) {
                      $q2->where('card_models.card_number_in_set', 'REGEXP', '^[0-9]+$')
                         ->orWhere('card_models.card_number_in_set', 'REGEXP', '^[0-9]+[a-z]+$');
                  });
            })
            ->select([
                'card_models.id',
                'card_models.name',
                'card_models.card_number',
                'card_models.card_number_in_set',
                'categories.name as category_name',
                'categories.slug as category_slug'
            ]);

        if ($categorySlug) {
            $query->where('categories.slug', $categorySlug);
        }

        $cards = $query->limit($limit)->get();

        if ($cards->isEmpty()) {
            $this->info('✅ Nessuna carta con valori invertiti trovata!');
            return 0;
        }

        $this->info("📊 Trovate {$cards->count()} carte con possibili valori invertiti:");
        $this->newLine();

        $fixed = 0;
        $examples = [];

        foreach ($cards as $card) {
            $examples[] = [
                'ID' => $card->id,
                'Categoria' => $card->category_name,
                'Nome' => substr($card->name, 0, 40) . '...',
                'card_number (attuale)' => $card->card_number,
                'card_number_in_set (attuale)' => $card->card_number_in_set,
                'card_number (dopo fix)' => $card->card_number_in_set,
                'card_number_in_set (dopo fix)' => $card->card_number,
            ];

            if (!$dryRun) {
                CardModel::where('id', $card->id)->update([
                    'card_number' => $card->card_number_in_set,
                    'card_number_in_set' => $card->card_number,
                ]);
                $fixed++;
            }
        }

        $this->table(
            ['ID', 'Categoria', 'Nome', 'card_number (attuale)', 'card_number_in_set (attuale)', 'card_number (dopo fix)', 'card_number_in_set (dopo fix)'],
            $examples
        );

        if ($dryRun) {
            $this->newLine();
            $this->warn("⚠️  Questo era un DRY-RUN. {$cards->count()} carte verrebbero corrette.");
            $this->info("Esegui senza --dry-run per applicare le modifiche.");
        } else {
            $this->newLine();
            $this->info("✅ Corrette {$fixed} carte!");
        }

        return 0;
    }
}

