<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardModel;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateBasketballRarityVariation extends Command
{
    protected $signature = 'basketball:update-rarity-variation 
                            {--file= : Path to CSV file}
                            {--dry-run : Run without making changes}';

    protected $description = 'Aggiorna il campo rarity_variation per le carte di Basketball dal CSV';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - Nessuna modifica verrà applicata');
        }

        // Trova la categoria Basketball
        $category = Category::where('slug', 'basketball')->first();
        if (!$category) {
            $this->error('❌ Categoria basketball non trovata!');
            return 1;
        }

        // Trova i file CSV
        $csvFiles = $this->findCsvFiles();
        if (empty($csvFiles)) {
            $this->error('❌ Nessun file CSV trovato!');
            $this->warn('💡 Suggerimenti:');
            $this->warn('   1. Verifica che i file CSV siano presenti in /home/forge/www.cardswaptcg.com/current/TOIMPORT/');
            $this->warn('   2. Esegui lo script direttamente sul server di produzione via SSH');
            $this->warn('   3. Oppure usa --file=/path/to/file.csv per specificare un file specifico');
            return 1;
        }

        $this->info('📁 File CSV trovati: ' . count($csvFiles));
        foreach ($csvFiles as $file) {
            $this->info("   - {$file}");
        }

        // Carica tutti i dati CSV
        $csvData = $this->loadCsvData($csvFiles);
        $this->info("📊 Righe CSV caricate: " . count($csvData));

        // Carica tutte le carte di Basketball
        $cards = CardModel::where('category_id', $category->id)
            ->whereHas('player')
            ->whereHas('cardSet')
            ->with(['player', 'cardSet', 'team'])
            ->get();

        $this->info("🎴 Carte di Basketball trovate: " . $cards->count());

        $updated = 0;
        $skipped = 0;
        $notFound = 0;

        $bar = $this->output->createProgressBar($cards->count());
        $bar->start();

        foreach ($cards as $card) {
            try {
                $csvRow = $this->findMatchingCsvRow($card, $csvData);
                
                if ($csvRow) {
                    $rarityVariation = trim($csvRow['Rarity Variation'] ?? '');
                    
                    // Aggiorna solo se la rarity_variation è diversa
                    if (!empty($rarityVariation) && $card->rarity_variation !== $rarityVariation) {
                        if (!$dryRun) {
                            $card->rarity_variation = $rarityVariation;
                            $card->save();
                        }
                        $updated++;
                    } elseif (empty($rarityVariation) && $card->rarity_variation !== null) {
                        // Se nel CSV è vuoto ma nel DB c'è un valore, resetta
                        if (!$dryRun) {
                            $card->rarity_variation = null;
                            $card->save();
                        }
                        $updated++;
                    } else {
                        $skipped++;
                    }
                } else {
                    $notFound++;
                }
            } catch (\Exception $e) {
                $this->error("\n❌ Errore aggiornando carta ID {$card->id}: " . $e->getMessage());
                $skipped++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("📈 Statistiche:");
        $this->info("   ✅ Carte aggiornate: {$updated}");
        $this->info("   ⏭️  Carte saltate (già aggiornate): {$skipped}");
        $this->info("   ❌ Carte non trovate nel CSV: {$notFound}");

        if ($dryRun) {
            $this->warn("\n⚠️  DRY RUN - Nessuna modifica è stata applicata");
        } else {
            $this->info("\n✅ Aggiornamento completato!");
        }

        return 0;
    }

    private function findCsvFiles()
    {
        $file = $this->option('file');
        
        if ($file && file_exists($file)) {
            return [$file];
        }

        // Cerca i file CSV in diverse directory
        $searchPaths = [
            base_path('TOIMPORT'),
            storage_path('app'),
            storage_path(),
            '/home/forge/www.cardswaptcg.com/current/TOIMPORT',
            '/home/forge/www.cardswaptcg.com/current/TOIMPORT/Elenco Set Basket 1 - Foglio1.csv',
            '/home/forge/www.cardswaptcg.com/current/TOIMPORT/Elenco Set Basket 2 - Foglio1.csv',
            '/home/forge/www.cardswaptcg.com/current/TOIMPORT/Elenco Set Basket 3 - Foglio1.csv',
            '/home/forge/www.cardswaptcg.com/releases/*/TOIMPORT',
        ];

        $csvFiles = [];
        
        foreach ($searchPaths as $path) {
            // Se è un file specifico, verifica se esiste
            if (strpos($path, '.csv') !== false) {
                if (file_exists($path) && is_readable($path)) {
                    $csvFiles[] = $path;
                    $this->info("✅ File trovato: {$path}");
                }
                continue;
            }
            
            if (strpos($path, '*') !== false) {
                // Pattern con wildcard
                $files = glob($path . '/*Basket*.csv');
                if ($files) {
                    $csvFiles = array_merge($csvFiles, $files);
                    foreach ($files as $f) {
                        $this->info("✅ File trovato: {$f}");
                    }
                }
            } elseif (is_dir($path)) {
                $files = glob($path . '/*Basket*.csv');
                if ($files) {
                    $csvFiles = array_merge($csvFiles, $files);
                    foreach ($files as $f) {
                        $this->info("✅ File trovato: {$f}");
                    }
                }
            }
        }

        // Rimuovi duplicati
        $csvFiles = array_unique($csvFiles);
        
        // Ordina per data di modifica (più recenti prima)
        usort($csvFiles, function($a, $b) {
            if (file_exists($a) && file_exists($b)) {
                return filemtime($b) - filemtime($a);
            }
            return 0;
        });

        return $csvFiles;
    }

    private function loadCsvData($files)
    {
        $csvData = [];

        foreach ($files as $file) {
            if (!file_exists($file) || !is_readable($file)) {
                $this->warn("⚠️  File non leggibile: {$file}");
                continue;
            }

            $handle = fopen($file, 'r');
            if (!$handle) {
                $this->warn("⚠️  Impossibile aprire file: {$file}");
                continue;
            }

            // Leggi l'header
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                continue;
            }

            // Normalizza gli header (rimuovi BOM e spazi)
            $headers = array_map(function($h) {
                return trim(str_replace("\xEF\xBB\xBF", '', $h));
            }, $headers);

            $rowNum = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                
                if (count($row) < count($headers)) {
                    continue;
                }

                $rowData = array_combine($headers, $row);
                
                $playerName = trim($rowData['Player'] ?? '');
                $cardNumber = trim($rowData['Numero'] ?? '');
                $rarity = trim($rowData['Rarity'] ?? 'Base');
                $rarityVariation = trim($rowData['Rarity Variation'] ?? '');
                $brand = strtoupper(trim($rowData['BRAND'] ?? ''));
                $setName = trim($rowData['SET'] ?? '');
                $teamName = trim($rowData['Team'] ?? '');
                $numbered = trim($rowData['NUMBERED /'] ?? '');

                if (empty($playerName) || empty($cardNumber)) {
                    continue;
                }

                // Crea chiavi di matching multiple
                $keys = [
                    strtolower("{$playerName}|{$cardNumber}|{$rarity}"),
                    strtolower("{$playerName}|{$cardNumber}|{$brand}|{$setName}|{$rarity}"),
                    strtolower("{$playerName}|{$cardNumber}|{$teamName}|{$rarity}"),
                ];

                foreach ($keys as $key) {
                    if (!isset($csvData[$key])) {
                        $csvData[$key] = $rowData;
                    }
                }
            }

            fclose($handle);
        }

        return $csvData;
    }

    private function findMatchingCsvRow($card, $csvData)
    {
        if (!$card->player || !$card->cardSet) {
            return null;
        }

        $playerName = strtolower(trim($card->player->name));
        $cardNumber = strtolower(trim($card->card_number ?? ''));
        $rarity = strtolower(trim($card->rarity ?? 'Base'));
        $brand = strtolower(trim($card->cardSet->brand ?? ''));
        $setName = strtolower(trim($card->cardSet->name ?? ''));
        $teamName = $card->team ? strtolower(trim($card->team->name ?? '')) : '';

        // Prova diverse chiavi di matching
        $matchKeys = [
            "{$playerName}|{$cardNumber}|{$rarity}",
            "{$playerName}|{$cardNumber}|{$brand}|{$setName}|{$rarity}",
        ];

        if (!empty($teamName)) {
            $matchKeys[] = "{$playerName}|{$cardNumber}|{$teamName}|{$rarity}";
        }

        foreach ($matchKeys as $key) {
            if (isset($csvData[$key])) {
                return $csvData[$key];
            }
        }

        return null;
    }
}

