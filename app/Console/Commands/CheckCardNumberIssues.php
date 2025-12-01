<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardModel;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CheckCardNumberIssues extends Command
{
    protected $signature = 'check:card-number-issues 
                            {--category= : Check only specific category (slug)}
                            {--search= : Search for specific card number value}
                            {--limit=10 : Limit results}';

    protected $description = 'Verifica problemi con card_number_in_set nel database';

    public function handle()
    {
        $categorySlug = $this->option('category');
        $searchValue = $this->option('search');
        $limit = (int) $this->option('limit');

        $this->info('🔍 Verifica problemi con card_number_in_set...');
        $this->newLine();

        // Query base
        $query = CardModel::query()
            ->join('categories', 'card_models.category_id', '=', 'categories.id')
            ->select([
                'card_models.id',
                'card_models.name as card_name',
                'card_models.card_number',
                'card_models.card_number_in_set',
                'categories.name as category_name',
                'categories.slug as category_slug'
            ]);

        // Filtra per categoria se specificata
        if ($categorySlug) {
            $query->where('categories.slug', $categorySlug);
        }

        // Cerca un valore specifico
        if ($searchValue) {
            $query->where(function($q) use ($searchValue) {
                $q->where('card_models.card_number', 'LIKE', "%{$searchValue}%")
                  ->orWhere('card_models.card_number_in_set', 'LIKE', "%{$searchValue}%")
                  ->orWhere('card_models.name', 'LIKE', "%{$searchValue}%");
            });
        } else {
            // Altrimenti mostra carte con possibili problemi
            $query->where(function($q) {
                $q->whereColumn('card_models.card_number_in_set', 'card_models.card_number')
                  ->orWhere(function($q2) {
                      $q2->whereNull('card_models.card_number_in_set')
                         ->whereNotNull('card_models.card_number')
                         ->where('card_models.card_number', '!=', '');
                  });
            });
        }

        $cards = $query->limit($limit)->get();

        if ($cards->isEmpty()) {
            $this->info('✅ Nessun problema trovato!');
            return 0;
        }

        $this->info("📊 Trovate {$cards->count()} carte:");
        $this->newLine();

        $tableData = [];
        foreach ($cards as $card) {
            $status = 'OK';
            if ($card->card_number_in_set === $card->card_number) {
                $status = '⚠️  PROBLEMA: card_number_in_set = card_number';
            } elseif ($card->card_number_in_set === null && !empty($card->card_number)) {
                $status = '✅ OK: card_number_in_set è NULL';
            } elseif ($card->card_number_in_set !== null && $card->card_number_in_set !== $card->card_number) {
                $status = '✅ OK: card_number_in_set diverso da card_number';
            }

            $tableData[] = [
                'ID' => $card->id,
                'Categoria' => $card->category_name,
                'Nome Carta' => substr($card->card_name, 0, 40) . '...',
                'card_number' => $card->card_number ?: '(vuoto)',
                'card_number_in_set' => $card->card_number_in_set ?: '(NULL)',
                'Status' => $status
            ];
        }

        $this->table(
            ['ID', 'Categoria', 'Nome Carta', 'card_number', 'card_number_in_set', 'Status'],
            $tableData
        );

        // Statistiche per categoria
        $this->newLine();
        $this->info('📈 Statistiche per categoria:');
        
        $stats = DB::table('card_models')
            ->join('categories', 'card_models.category_id', '=', 'categories.id')
            ->select([
                'categories.name as category_name',
                'categories.slug',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN card_models.card_number_in_set = card_models.card_number THEN 1 ELSE 0 END) as same_value'),
                DB::raw('SUM(CASE WHEN card_models.card_number_in_set IS NULL AND card_models.card_number IS NOT NULL AND card_models.card_number != "" THEN 1 ELSE 0 END) as null_when_has_number'),
                DB::raw('SUM(CASE WHEN card_models.card_number_in_set IS NOT NULL AND card_models.card_number_in_set != card_models.card_number THEN 1 ELSE 0 END) as different_value')
            ])
            ->groupBy('categories.id', 'categories.name', 'categories.slug')
            ->orderBy('same_value', 'desc')
            ->get();

        $statsTable = [];
        foreach ($stats as $stat) {
            $statsTable[] = [
                'Categoria' => $stat->category_name,
                'Totale' => $stat->total,
                'card_number_in_set = card_number' => $stat->same_value,
                'card_number_in_set = NULL (ha card_number)' => $stat->null_when_has_number,
                'card_number_in_set diverso' => $stat->different_value
            ];
        }

        $this->table(
            ['Categoria', 'Totale', 'card_number_in_set = card_number', 'card_number_in_set = NULL (ha card_number)', 'card_number_in_set diverso'],
            $statsTable
        );

        return 0;
    }
}

