<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportFiltriCalcio extends Command
{
    protected $signature = 'import:filtri-calcio {--force : Forza l\'importazione anche se i dati esistono già}';
    protected $description = 'Importa i dati aggiuntivi dal file Filtri Calcio.csv';

    public function handle()
    {
        $this->info('🚀 Inizio importazione dati Filtri Calcio...');
        
        try {
            $csvPath = base_path('IMPORT/Filtri Calcio.csv');
            $data = $this->readCsv($csvPath);
            
            $this->info("📊 Trovate " . count($data) . " righe nel file Filtri Calcio.csv");
            
            // Analizza le prime righe per capire la struttura
            if (count($data) > 0) {
                $this->info("📋 Intestazioni: " . implode(', ', $data[0]));
            }
            
            // Per ora mostriamo solo le prime 10 righe per analisi
            $this->info("📄 Prime 10 righe di dati:");
            for ($i = 0; $i < min(10, count($data)); $i++) {
                $this->line("  Riga {$i}: " . implode(' | ', array_slice($data[$i], 0, 5)));
            }
            
            $this->info('✅ Analisi completata. Implementare logica specifica se necessario.');
            
        } catch (\Exception $e) {
            $this->error('❌ Errore durante l\'importazione: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }

    private function readCsv($filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File non trovato: {$filePath}");
        }
        
        $data = [];
        $handle = fopen($filePath, 'r');
        
        if ($handle !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $data[] = $row;
            }
            fclose($handle);
        }
        
        return $data;
    }
}
