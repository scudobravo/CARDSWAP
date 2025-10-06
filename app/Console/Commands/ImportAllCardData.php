<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ImportAllCardData extends Command
{
    protected $signature = 'import:all-card-data {--force : Forza l\'importazione anche se i dati esistono già}';
    protected $description = 'Importa tutti i dati delle carte da calcio in sequenza';

    public function handle()
    {
        $this->info('🚀 Inizio importazione completa dati carte da calcio...');
        $this->newLine();
        
        $force = $this->option('force');
        
        try {
            // 1. Importa grading companies e scores
            $this->info('📊 Fase 1: Importazione grading companies e scores...');
            $exitCode = Artisan::call('import:card-data', ['--force' => $force]);
            
            if ($exitCode !== 0) {
                $this->error('❌ Errore durante l\'importazione dei dati principali');
                return 1;
            }
            
            $this->info('✅ Fase 1 completata');
            $this->newLine();
            
            // 2. Analizza il file Filtri Calcio
            $this->info('📊 Fase 2: Analisi file Filtri Calcio...');
            $exitCode = Artisan::call('import:filtri-calcio', ['--force' => $force]);
            
            if ($exitCode !== 0) {
                $this->warn('⚠️  Analisi Filtri Calcio completata con warning');
            } else {
                $this->info('✅ Fase 2 completata');
            }
            
            $this->newLine();
            
            // 3. Mostra statistiche finali
            $this->showFinalStats();
            
            $this->info('🎉 Importazione completa terminata con successo!');
            
        } catch (\Exception $e) {
            $this->error('❌ Errore durante l\'importazione: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }

    private function showFinalStats()
    {
        $this->info('📊 Statistiche finali:');
        
        $stats = [
            'Grading Companies' => DB::table('grading_companies')->count(),
            'Grading Scores' => DB::table('grading_scores')->count(),
            'Card Sets' => DB::table('card_sets')->count(),
            'Teams' => DB::table('teams')->count(),
            'Players' => DB::table('players')->count(),
            'Card Models' => DB::table('card_models')->count(),
        ];
        
        foreach ($stats as $table => $count) {
            $this->line("  📈 {$table}: {$count}");
        }
        
        $this->newLine();
    }
}
