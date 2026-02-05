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
            $this->warn('DRY RUN MODE - Nessuna modifica verrà applicata');
        }

        // Trova la categoria Basketball
        $category = Category::where('slug', 'basketball')->first();
        if (!$category) {
            $this->error('Categoria basketball non trovata!');
            return 1;
        }

        // Usa una directory persistente che non viene cancellata durante i deploy
        // storage/app è persistente tra i deploy in Forge
        $persistentPath = storage_path('app/TOIMPORT');
        $toimportPath = '/home/forge/www.cardswaptcg.com/current/TOIMPORT';
        
        // Crea la directory persistente se non esiste
        if (!is_dir($persistentPath)) {
            $this->info(" Creazione directory persistente: {$persistentPath}");
            if (!mkdir($persistentPath, 0755, true)) {
                $this->error(" Impossibile creare la directory {$persistentPath}");
            } else {
                $this->info(" Directory persistente creata");
            }
        }
        
        // Verifica anche la directory current (per retrocompatibilità)
        if (!is_dir($toimportPath)) {
            $this->warn("Directory {$toimportPath} non esiste, tentativo di creazione...");
            if (!mkdir($toimportPath, 0755, true)) {
                $this->warn("Impossibile creare la directory {$toimportPath} (non critico)");
            } else {
                $this->info(" Directory {$toimportPath} creata");
            }
        }

        // Trova i file CSV
        $csvFiles = $this->findCsvFiles();
        if (empty($csvFiles)) {
            $this->error('Nessun file CSV trovato!');
            $this->warn('Suggerimenti:');
            $this->warn('   1. Verifica che i file CSV siano presenti in /home/forge/www.cardswaptcg.com/current/TOIMPORT/');
            $this->warn('   2. Lista file nella directory: ls -la /home/forge/www.cardswaptcg.com/current/TOIMPORT/');
            $this->warn('   3. Esegui lo script direttamente sul server di produzione via SSH');
            $this->warn('   4. Oppure usa --file=/path/to/file.csv per specificare un file specifico');
            
            // Mostra cosa c'è nella directory se esiste
            if (is_dir($toimportPath)) {
                $this->info("\n📁 Contenuto di {$toimportPath}:");
                $files = scandir($toimportPath);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $fullPath = $toimportPath . '/' . $file;
                        $size = is_file($fullPath) ? filesize($fullPath) : 0;
                        $this->info("   - {$file} (" . number_format($size / 1024 / 1024, 2) . " MB)");
                    }
                }
            }
            
            return 1;
        }

        $this->info('File CSV trovati: ' . count($csvFiles));
        foreach ($csvFiles as $file) {
            $this->info("   - {$file}");
        }

        // Rimuovi duplicati dai file CSV (usa solo quelli in /current/)
        $uniqueCsvFiles = [];
        foreach ($csvFiles as $file) {
            if (strpos($file, '/current/TOIMPORT/') !== false) {
                $uniqueCsvFiles[] = $file;
            }
        }
        // Se non ci sono file in /current/, usa quelli trovati
        if (empty($uniqueCsvFiles)) {
            $uniqueCsvFiles = array_unique($csvFiles);
        } else {
            $uniqueCsvFiles = array_unique($uniqueCsvFiles);
        }
        
        $this->info(" File CSV unici da processare: " . count($uniqueCsvFiles));

        // Crea un file di cache con tutte le corrispondenze per evitare di leggere i CSV ogni volta
        $cacheFile = storage_path('app/cache_basketball_rarity_variation.json');
        $useCache = file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 3600; // Cache valida per 1 ora
        
        if ($useCache) {
            $this->info(" Caricamento cache esistente...");
            $csvData = json_decode(file_get_contents($cacheFile), true);
            $this->info(" Chiavi caricate dalla cache: " . count($csvData));
        } else {
            // NON caricare tutto il CSV in memoria - invece crea un indice leggero
            // che mappa solo le chiavi necessarie ai file e posizioni
            $this->info(" Creazione indice CSV e cache (senza caricare tutto in memoria)...");
            $csvData = $this->createCsvCache($uniqueCsvFiles, $cacheFile);
            $this->info(" Chiavi uniche nella cache: " . count($csvData));
        }

        // Conta le carte totali
        $totalCards = CardModel::where('category_id', $category->id)
            ->whereHas('player')
            ->whereHas('cardSet')
            ->count();

        $this->info("🎴 Carte di Basketball totali: {$totalCards}");

        $updated = 0;
        $skipped = 0;
        $notFound = 0;

        // Processa le carte in chunk per risparmiare memoria
        $chunkSize = 500;
        $bar = $this->output->createProgressBar($totalCards);
        $bar->start();

        CardModel::where('category_id', $category->id)
            ->whereHas('player')
            ->whereHas('cardSet')
            ->chunk($chunkSize, function ($cards) use ($csvData, $dryRun, &$updated, &$skipped, &$notFound, &$bar) {
                foreach ($cards as $card) {
                    try {
                        // Carica le relazioni solo quando necessario
                        $card->load(['player', 'cardSet', 'team']);
                        
                        $rarityVariation = $this->findRarityVariationFromCache($card, $csvData);
                        
                        if ($rarityVariation !== null) {
                            // Aggiorna solo se la rarity_variation è diversa
                            if ($card->rarity_variation !== $rarityVariation) {
                                if (!$dryRun) {
                                    $card->rarity_variation = $rarityVariation;
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
                
                // Libera memoria dopo ogni chunk
                gc_collect_cycles();
            });

        $bar->finish();
        $this->newLine(2);

        $this->info("Statistiche:");
        $this->info("   Carte aggiornate: {$updated}");
        $this->info("   Carte saltate (già aggiornate): {$skipped}");
        $this->info("   Carte non trovate nel CSV: {$notFound}");

        if ($dryRun) {
            $this->warn("\nDRY RUN - Nessuna modifica è stata applicata");
        } else {
            $this->info("\nAggiornamento completato!");
        }

        return 0;
    }

    private function findCsvFiles()
    {
        $file = $this->option('file');
        
        if ($file && file_exists($file)) {
            $this->info(" Usando file specificato: {$file}");
            return [$file];
        }

        $csvFiles = [];
        
        // Prima cerca i file nella directory persistente (storage/app/TOIMPORT)
        $persistentPath = storage_path('app/TOIMPORT');
        $persistentFiles = [
            $persistentPath . '/Elenco Set Basket 1 - Foglio1.csv',
            $persistentPath . '/Elenco Set Basket 2 - Foglio1.csv',
            $persistentPath . '/Elenco Set Basket 3 - Foglio1.csv',
        ];
        
        $this->info(" Cerca file nella directory persistente (storage/app/TOIMPORT)...");
        foreach ($persistentFiles as $file) {
            if (file_exists($file) && is_readable($file)) {
                $csvFiles[] = $file;
                $this->info("   File trovato: {$file}");
            }
        }
        
        // Poi cerca i file specifici nel percorso del server Forge (per retrocompatibilità)
        $specificFiles = [
            '/home/forge/www.cardswaptcg.com/current/TOIMPORT/Elenco Set Basket 1 - Foglio1.csv',
            '/home/forge/www.cardswaptcg.com/current/TOIMPORT/Elenco Set Basket 2 - Foglio1.csv',
            '/home/forge/www.cardswaptcg.com/current/TOIMPORT/Elenco Set Basket 3 - Foglio1.csv',
        ];
        
        $this->info(" Cerca file specifici nel server Forge...");
        foreach ($specificFiles as $specificFile) {
            $exists = file_exists($specificFile);
            $readable = $exists ? is_readable($specificFile) : false;
            $this->info("   {$specificFile}: " . ($exists ? ($readable ? "trovato e leggibile" : "trovato ma non leggibile") : "non trovato"));
            
            if ($exists && $readable) {
                $csvFiles[] = $specificFile;
            }
        }
        
        // Se non ha trovato file specifici, cerca nelle directory
        if (empty($csvFiles)) {
            $this->info("Cerca file nelle directory...");
            $searchPaths = [
                storage_path('app/TOIMPORT'), // Directory persistente (priorità)
                base_path('TOIMPORT'),
                storage_path('app'),
                storage_path(),
                '/home/forge/www.cardswaptcg.com/current/TOIMPORT',
            ];

            foreach ($searchPaths as $path) {
                $isDir = is_dir($path);
                $this->info("   {$path}: " . ($isDir ? "directory esiste" : "non esiste o non è una directory"));
                
                if ($isDir) {
                    $files = glob($path . '/*Basket*.csv');
                    if ($files) {
                        $csvFiles = array_merge($csvFiles, $files);
                        foreach ($files as $f) {
                            $this->info("   File trovato: {$f}");
                        }
                    } else {
                        $this->info("   Nessun file Basket*.csv trovato in questa directory");
                    }
                }
            }
            
            // Cerca anche nei releases con wildcard
            $this->info(" Cerca file nei releases...");
            $releaseFiles = glob('/home/forge/www.cardswaptcg.com/releases/*/TOIMPORT/*Basket*.csv');
            if ($releaseFiles) {
                $csvFiles = array_merge($csvFiles, $releaseFiles);
                foreach ($releaseFiles as $f) {
                    $this->info("   File trovato: {$f}");
                }
            } else {
                $this->info("    Nessun file trovato nei releases");
            }
        }

        // Rimuovi duplicati
        $csvFiles = array_unique($csvFiles);
        
        // Ordina per data di modifica (più recenti prima) e preferisci /current/
        usort($csvFiles, function($a, $b) {
            // Preferisci file in /current/ rispetto a /releases/
            $aIsCurrent = strpos($a, '/current/') !== false;
            $bIsCurrent = strpos($b, '/current/') !== false;
            
            if ($aIsCurrent && !$bIsCurrent) return -1;
            if (!$aIsCurrent && $bIsCurrent) return 1;
            
            if (file_exists($a) && file_exists($b)) {
                return filemtime($b) - filemtime($a);
            }
            return 0;
        });

        return $csvFiles;
    }

    private function createCsvCache($files, $cacheFile)
    {
        // Crea un file di cache con tutte le corrispondenze chiave -> rarity_variation
        $cache = [];
        $processedRows = 0;

        foreach ($files as $file) {
            if (!file_exists($file) || !is_readable($file)) {
                $this->warn("File non leggibile: {$file}");
                continue;
            }

            $this->info("📖 Creazione cache da: " . basename($file));

            $handle = fopen($file, 'r');
            if (!$handle) {
                $this->warn("Impossibile aprire file: {$file}");
                continue;
            }

            // Leggi l'header
            $headers = fgetcsv($handle);
            if (!$headers) {
                fclose($handle);
                continue;
            }

            // Normalizza gli header
            $headers = array_map(function($h) {
                return trim(str_replace("\xEF\xBB\xBF", '', $h));
            }, $headers);

            $rowNum = 0;
            while (($row = fgetcsv($handle)) !== false) {
                $rowNum++;
                
                if (count($row) < count($headers) || empty(array_filter($row))) {
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

                if (empty($playerName) || empty($cardNumber)) {
                    continue;
                }

                // Crea chiavi di matching e salva direttamente la rarity_variation
                $keys = [
                    strtolower("{$playerName}|{$cardNumber}|{$rarity}"),
                    strtolower("{$playerName}|{$cardNumber}|{$brand}|{$setName}|{$rarity}"),
                ];

                if (!empty($teamName)) {
                    $keys[] = strtolower("{$playerName}|{$cardNumber}|{$teamName}|{$rarity}");
                }

                // Salva la chiave -> rarity_variation direttamente nella cache
                foreach ($keys as $key) {
                    if (!isset($cache[$key])) {
                        $cache[$key] = !empty($rarityVariation) ? $rarityVariation : null;
                        $processedRows++;
                        break;
                    }
                }

                // Salva la cache periodicamente per non perdere dati in caso di crash
                if ($rowNum % 100000 === 0) {
                    file_put_contents($cacheFile, json_encode($cache, JSON_UNESCAPED_UNICODE));
                    gc_collect_cycles();
                }
            }

            fclose($handle);
            $this->info("   Processate {$rowNum} righe da " . basename($file));
        }

        // Salva la cache finale
        file_put_contents($cacheFile, json_encode($cache, JSON_UNESCAPED_UNICODE));
        $this->info(" Cache salvata in: {$cacheFile}");

        return $cache;
    }

    private function findRarityVariationFromCache($card, $csvData)
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

        // Cerca direttamente nella cache
        foreach ($matchKeys as $key) {
            if (isset($csvData[$key])) {
                return $csvData[$key];
            }
        }

        return null;
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

