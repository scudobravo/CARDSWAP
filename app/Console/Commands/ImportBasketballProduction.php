<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportBasketballProduction extends Command
{
    protected $signature = 'import:basketball-production 
                            {--auto : Esegui automaticamente senza conferma}
                            {--background : Esegui in background con nohup}';
    
    protected $description = 'Importa tutti i file CSV di basket in produzione';

    public function handle()
    {
        $this->info('🏀 Importazione dati Basketball in PRODUZIONE');
        $this->info('════════════════════════════════════════════');
        $this->newLine();

        // Verifica ambiente
        $env = config('app.env');
        if ($env !== 'production' && !$this->option('auto')) {
            if (!$this->confirm("⚠️  L'ambiente non è 'production' (attuale: $env). Continuare?")) {
                $this->error('❌ Operazione annullata.');
                return 1;
            }
        }

        // Verifica stato attuale
        $this->info('📊 Verifica stato attuale del database...');
        $basketCards = \App\Models\CardModel::whereHas('category', function($q) {
            $q->where('slug', 'basketball');
        })->count();
        
        $basketSets = \App\Models\CardSet::whereHas('category', function($q) {
            $q->where('slug', 'basketball');
        })->count();

        $this->info("✅ Carte Basketball attuali: $basketCards");
        $this->info("✅ Set Basketball attuali: $basketSets");
        $this->newLine();

        // Verifica file CSV
        $this->info('📁 Verifica file CSV...');
        $files = [
            'TOIMPORT/Elenco Set Basket 1 - Foglio1.csv',
            'TOIMPORT/Elenco Set Basket 2 - Foglio1.csv',
            'TOIMPORT/Elenco Set Basket 3 - Foglio1.csv',
        ];

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
            $this->error('❌ Nessun file CSV trovato!');
            $this->warn('   Assicurati che i file CSV siano nella directory TOIMPORT/');
            return 1;
        }

        $this->newLine();

        // Conferma
        if (!$this->option('auto')) {
            $this->warn('⚠️  ATTENZIONE: Stai per importare i dati di basket in PRODUZIONE');
            $this->warn('   Questo processo potrebbe richiedere molto tempo.');
            if (!$this->confirm('   Vuoi procedere?')) {
                $this->error('❌ Operazione annullata.');
                return 1;
            }
        }

        $this->newLine();
        $this->info('🚀 Inizio importazione...');
        $this->newLine();

        $startTime = microtime(true);

        // Importazione file
        foreach ($filesFound as $index => $file) {
            $this->info("📄 Importazione file " . ($index + 1) . ": $file");
            $this->call('import:basket-cards', [
                '--file' => $file
            ]);
            $this->info("✅ File " . ($index + 1) . " importato");
            $this->newLine();
        }

        // Verifica finale
        $this->info('📊 Verifica finale...');
        $finalCards = \App\Models\CardModel::whereHas('category', function($q) {
            $q->where('slug', 'basketball');
        })->count();
        
        $finalSets = \App\Models\CardSet::whereHas('category', function($q) {
            $q->where('slug', 'basketball');
        })->count();

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) / 60, 2);

        $this->newLine();
        $this->info('✅ Importazione completata!');
        $this->info("   Carte Basketball: $finalCards (prima: $basketCards)");
        $this->info("   Set Basketball: $finalSets (prima: $basketSets)");
        $this->info("   Tempo impiegato: $duration minuti");
        $this->info('════════════════════════════════════════════');

        return 0;
    }
}

