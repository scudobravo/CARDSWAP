<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportDisneySpongebobProduction extends Command
{
    protected $signature = 'import:disney-spongebob-production 
                            {--auto : Esegui automaticamente senza conferma}
                            {--category= : Importa solo una categoria (disney|spongebob)}';
    
    protected $description = 'Importa i file CSV di Disney e Spongebob in produzione';

    public function handle()
    {
        $this->info('🎨 Importazione dati Disney e Spongebob in PRODUZIONE');
        $this->info('═══════════════════════════════════════════════════════');
        $this->newLine();

        // Verifica ambiente
        $env = config('app.env');
        if ($env !== 'production' && !$this->option('auto')) {
            if (!$this->confirm("⚠️  L'ambiente non è 'production' (attuale: $env). Continuare?")) {
                $this->error('❌ Operazione annullata.');
                return 1;
            }
        }

        $categoryFilter = $this->option('category');
        $categories = [];
        
        if (!$categoryFilter || $categoryFilter === 'disney') {
            $categories[] = 'disney';
        }
        if (!$categoryFilter || $categoryFilter === 'spongebob') {
            $categories[] = 'spongebob';
        }

        $startTime = microtime(true);

        foreach ($categories as $categorySlug) {
            $this->importCategory($categorySlug);
        }

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) / 60, 2);

        $this->newLine();
        $this->info("✅ Importazione completata! Tempo totale: $duration minuti");
        $this->info('═══════════════════════════════════════════════════════');

        return 0;
    }

    private function importCategory($categorySlug)
    {
        $categoryName = ucfirst($categorySlug);
        $this->info("📦 Importazione categoria: $categoryName");
        $this->newLine();

        // Verifica stato attuale
        $currentCards = \App\Models\CardModel::whereHas('category', function($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        })->count();
        
        $currentSets = \App\Models\CardSet::whereHas('category', function($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        })->count();

        $this->info("📊 Carte $categoryName attuali: $currentCards");
        $this->info("📊 Set $categoryName attuali: $currentSets");
        $this->newLine();

        // File da importare
        $files = [];
        if ($categorySlug === 'disney') {
            $files = [
                'TOIMPORT/Lista Carte Disney - Foglio1.csv',
            ];
        } elseif ($categorySlug === 'spongebob') {
            $files = [
                'TOIMPORT/Lista Carte Spongebob - Foglio1.csv',
            ];
        }

        // Verifica file
        $this->info("📁 Verifica file CSV...");
        $filesFound = [];
        foreach ($files as $file) {
            if (file_exists(base_path($file))) {
                $this->info("✅ Trovato: $file");
                $filesFound[] = $file;
            } else {
                $this->error("❌ File non trovato: $file");
            }
        }

        if (empty($filesFound)) {
            $this->error("❌ Nessun file CSV trovato per $categoryName!");
            return;
        }

        $this->newLine();

        // Importazione
        foreach ($filesFound as $index => $file) {
            $this->info("📄 Importazione file " . ($index + 1) . ": $file");
            
            if ($categorySlug === 'disney') {
                $this->call('import:disney-cards', [
                    '--file' => $file
                ]);
            } elseif ($categorySlug === 'spongebob') {
                $this->call('import:spongebob-cards', [
                    '--file' => $file
                ]);
            }
            
            $this->info("✅ File " . ($index + 1) . " importato");
            $this->newLine();
        }

        // Verifica finale
        $finalCards = \App\Models\CardModel::whereHas('category', function($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        })->count();
        
        $finalSets = \App\Models\CardSet::whereHas('category', function($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        })->count();

        $this->info("✅ $categoryName completato!");
        $this->info("   Carte: $finalCards (prima: $currentCards)");
        $this->info("   Set: $finalSets (prima: $currentSets)");
        $this->newLine();
    }
}

