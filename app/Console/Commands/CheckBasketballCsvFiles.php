<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckBasketballCsvFiles extends Command
{
    protected $signature = 'basketball:check-csv-files';

    protected $description = 'Verifica se i file CSV di Basketball esistono sul server';

    public function handle()
    {
        $this->info('🔍 Verifica file CSV di Basketball sul server...');
        $this->newLine();

        $paths = [
            'Persistente (storage/app/TOIMPORT)' => storage_path('app/TOIMPORT'),
            'Current (current/TOIMPORT)' => '/home/forge/www.cardswaptcg.com/current/TOIMPORT',
            'Base (base_path/TOIMPORT)' => base_path('TOIMPORT'),
        ];

        $foundFiles = [];
        $totalSize = 0;

        foreach ($paths as $label => $path) {
            $this->info("📁 {$label}: {$path}");
            
            if (!is_dir($path)) {
                $this->warn("   ❌ Directory non esiste");
                $this->newLine();
                continue;
            }

            $this->info("   ✅ Directory esiste");
            
            $files = glob($path . '/*Basket*.csv');
            if (empty($files)) {
                $this->warn("   ❌ Nessun file Basket*.csv trovato");
            } else {
                $this->info("   📄 File trovati:");
                foreach ($files as $file) {
                    $size = filesize($file);
                    $totalSize += $size;
                    $sizeFormatted = number_format($size / 1024 / 1024, 2) . ' MB';
                    $this->info("      ✅ " . basename($file) . " ({$sizeFormatted})");
                    $foundFiles[] = $file;
                }
            }
            $this->newLine();
        }

        // Cerca anche nelle release
        $this->info("🔍 Cerca file nelle release...");
        $releaseFiles = glob('/home/forge/www.cardswaptcg.com/releases/*/TOIMPORT/*Basket*.csv');
        if ($releaseFiles) {
            $this->info("   📄 File trovati nelle release:");
            foreach (array_slice($releaseFiles, 0, 10) as $file) {
                $size = filesize($file);
                $sizeFormatted = number_format($size / 1024 / 1024, 2) . ' MB';
                $this->info("      ✅ " . $file . " ({$sizeFormatted})");
                $foundFiles[] = $file;
            }
            if (count($releaseFiles) > 10) {
                $this->info("      ... e altri " . (count($releaseFiles) - 10) . " file");
            }
        } else {
            $this->warn("   ❌ Nessun file trovato nelle release");
        }
        $this->newLine();

        // Riepilogo
        $this->info("📊 Riepilogo:");
        $this->info("   ✅ File trovati: " . count($foundFiles));
        if ($totalSize > 0) {
            $this->info("   📦 Dimensione totale: " . number_format($totalSize / 1024 / 1024, 2) . " MB");
        }

        if (empty($foundFiles)) {
            $this->newLine();
            $this->error("❌ Nessun file CSV trovato!");
            $this->warn("💡 Suggerimenti:");
            $this->warn("   1. Copia i file in storage/app/TOIMPORT (directory persistente)");
            $this->warn("   2. Oppure usa: ./copy_basketball_csvs.sh");
            return 1;
        } else {
            $this->newLine();
            $this->info("✅ File CSV trovati! Puoi eseguire:");
            $this->info("   php artisan basketball:update-rarity-variation --dry-run");
            return 0;
        }
    }
}

