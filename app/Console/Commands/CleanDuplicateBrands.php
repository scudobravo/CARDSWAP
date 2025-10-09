<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanDuplicateBrands extends Command
{
    protected $signature = 'clean:duplicate-brands {--dry-run : Esegue in modalità dry-run senza modificare i dati}';
    protected $description = 'Pulisce i brand duplicati e le grading companies invalide (punti)';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️  MODALITÀ DRY-RUN: Nessuna modifica verrà salvata');
        }
        
        $this->info('🧹 Inizio pulizia dati...');
        $this->newLine();
        
        // 1. Pulisci grading companies invalide
        $this->cleanGradingCompanies($dryRun);
        
        // 2. Unifica brand duplicati nei card_sets
        $this->cleanBrandsInCardSets($dryRun);
        
        $this->newLine();
        $this->info('✅ Pulizia completata!');
        
        return 0;
    }

    /**
     * Rimuove le grading companies che sono solo punti
     */
    private function cleanGradingCompanies($dryRun = false)
    {
        $this->info('📊 Pulizia grading companies...');
        
        // Trova le grading companies invalide (punti)
        $invalidCompanies = DB::table('grading_companies')
            ->whereIn('name', ['.', '..'])
            ->orWhere('name', 'LIKE', '.%')
            ->get();
        
        if ($invalidCompanies->isEmpty()) {
            $this->info('  ✓ Nessuna grading company invalida trovata');
            return;
        }
        
        $this->info("  Trovate {$invalidCompanies->count()} grading companies invalide:");
        foreach ($invalidCompanies as $company) {
            $this->line("    - ID: {$company->id}, Name: '{$company->name}'");
        }
        
        if (!$dryRun) {
            // Elimina le grading companies invalide
            DB::table('grading_companies')
                ->whereIn('name', ['.', '..'])
                ->orWhere('name', 'LIKE', '.%')
                ->delete();
            
            $this->info("  ✅ Eliminate {$invalidCompanies->count()} grading companies invalide");
        } else {
            $this->warn("  ⚠️  DRY-RUN: {$invalidCompanies->count()} grading companies verrebbero eliminate");
        }
    }

    /**
     * Unifica i brand duplicati nei card_sets
     */
    private function cleanBrandsInCardSets($dryRun = false)
    {
        $this->info('📦 Pulizia brand nei card_sets...');
        
        // Trova tutti i brand unici
        $brands = DB::table('card_sets')
            ->select('brand')
            ->distinct()
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->get()
            ->pluck('brand')
            ->toArray();
        
        // Raggruppa i brand per versione uppercase
        $brandGroups = [];
        foreach ($brands as $brand) {
            $upperBrand = strtoupper($brand);
            if (!isset($brandGroups[$upperBrand])) {
                $brandGroups[$upperBrand] = [];
            }
            $brandGroups[$upperBrand][] = $brand;
        }
        
        // Trova i brand che hanno duplicati
        $duplicates = array_filter($brandGroups, function($group) {
            return count($group) > 1;
        });
        
        if (empty($duplicates)) {
            $this->info('  ✓ Nessun brand duplicato trovato');
            return;
        }
        
        $this->info("  Trovati " . count($duplicates) . " gruppi di brand duplicati:");
        
        foreach ($duplicates as $upperBrand => $variants) {
            $this->line("    - {$upperBrand}: " . implode(', ', $variants));
            
            if (!$dryRun) {
                // Aggiorna tutti i variant al formato uppercase
                foreach ($variants as $variant) {
                    if ($variant !== $upperBrand) {
                        DB::table('card_sets')
                            ->where('brand', $variant)
                            ->update(['brand' => $upperBrand]);
                        
                        $this->line("      ✓ Aggiornato '{$variant}' -> '{$upperBrand}'");
                    }
                }
            } else {
                $this->warn("      ⚠️  DRY-RUN: " . implode(', ', $variants) . " -> {$upperBrand}");
            }
        }
        
        if (!$dryRun) {
            $this->info("  ✅ Brand unificati con successo");
        }
    }
}

