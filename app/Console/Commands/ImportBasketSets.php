<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\CardSet;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ImportBasketSets extends Command
{
    protected $signature = 'import:basket-sets {--file= : Path al file CSV}';
    protected $description = 'Importa i set di basket (BRAND, SET, YEAR) dal file CSV';

    public function handle()
    {
        $filePath = $this->option('file') ?: base_path('TOIMPORT/Elenco per Box_Pacchetti Basket - Foglio1.csv');

        if (!file_exists($filePath)) {
            $this->error("File non trovato: {$filePath}");
            return 1;
        }

        $this->info("🚀 Inizio importazione set di basket...");

        $category = Category::where('slug', 'basket')->first();
        if (!$category) {
            $category = Category::create([
                'name' => 'Basket',
                'slug' => 'basket',
                'description' => 'Carte da collezione di basket',
                'is_active' => true,
                'sort_order' => 2,
            ]);
        }

        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);
        
        $created = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 3) continue;
            
            $brand = strtoupper(trim($row[0]));
            $setName = trim($row[1]);
            $year = trim($row[2]);
            
            $fullSetName = "{$brand} {$setName}";
            $slug = Str::slug($fullSetName);
            
            $exists = CardSet::where('slug', $slug)->exists();
            
            if (!$exists) {
                CardSet::create([
                    'category_id' => $category->id,
                    'name' => $fullSetName,
                    'slug' => $slug,
                    'brand' => $brand,
                    'year' => $this->extractYear($year),
                    'season' => $year,
                    'is_official' => true,
                    'is_active' => true,
                    'sort_order' => 1,
                ]);
                $created++;
            } else {
                $skipped++;
            }
        }

        fclose($handle);

        $this->info("✅ Importazione completata: {$created} set creati, {$skipped} già esistenti");
        return 0;
    }

    private function extractYear($year)
    {
        if (preg_match('/(\d{4})/', $year, $matches)) {
            return (int) $matches[1];
        }
        return 2024;
    }
}

