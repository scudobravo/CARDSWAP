<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\CardSet;
use App\Models\CardModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ImportDisneyCards extends Command
{
    protected $signature = 'import:disney-cards 
                            {--file= : Path al file CSV}
                            {--limit= : Limite di righe da processare}
                            {--chunk=1000 : Dimensione del chunk}';

    protected $description = 'Importa le carte Disney dal file CSV';

    private $chunkSize = 1000;
    private $cache = ['card_sets' => []];

    public function handle()
    {
        $filePath = $this->option('file');
        
        // Se non specificato, cerca in percorsi comuni e poi in modo ricorsivo
        if (!$filePath) {
            $fileName = 'Lista Carte Disney - Foglio1.csv';
            $possiblePaths = [
                base_path('TOIMPORT/' . $fileName),
                base_path('../TOIMPORT/' . $fileName),
                base_path('storage/app/' . $fileName),
                base_path('storage/' . $fileName),
                '/home/forge/www.cardswaptcg.com/current/TOIMPORT/' . $fileName,
                '/home/forge/www.cardswaptcg.com/shared/TOIMPORT/' . $fileName,
                '/home/forge/www.cardswaptcg.com/TOIMPORT/' . $fileName,
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $filePath = $path;
                    $this->info("✅ File trovato in: {$filePath}");
                    break;
                }
            }
            
            // Se non trovato, cerca in modo ricorsivo nelle directory comuni
            if (!$filePath) {
                $this->info("🔍 Cercando il file in modo ricorsivo...");
                $searchDirs = [
                    base_path(),
                    dirname(base_path()),
                    '/home/forge/www.cardswaptcg.com/current',
                    '/home/forge/www.cardswaptcg.com/shared',
                ];
                
                foreach ($searchDirs as $dir) {
                    if (is_dir($dir)) {
                        $found = $this->findFileRecursive($dir, $fileName);
                        if ($found) {
                            $filePath = $found;
                            $this->info("✅ File trovato in: {$filePath}");
                            break;
                        }
                    }
                }
            }
            
            if (!$filePath) {
                $this->error("File non trovato. Percorsi cercati:");
                foreach ($possiblePaths as $path) {
                    $this->line("  - {$path}");
                }
                $this->error("\nCerca manualmente con:");
                $this->line("  find /home/forge/www.cardswaptcg.com -name '{$fileName}' -type f");
                $this->error("\nOppure usa --file=/percorso/completo/al/file.csv per specificare il percorso manualmente");
                return 1;
            }
        }
        
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $this->chunkSize = (int) $this->option('chunk');

        if (!file_exists($filePath)) {
            $this->error("File non trovato: {$filePath}");
            return 1;
        }

        $this->info("🚀 Inizio importazione carte Disney...");

        $category = Category::where('slug', 'disney')->first();
        if (!$category) {
            $category = Category::create([
                'name' => 'Disney',
                'slug' => 'disney',
                'description' => 'Carte da collezione Disney',
                'is_active' => true,
                'sort_order' => 4,
            ]);
            $this->info("✅ Creata categoria Disney");
        }

        $this->processFile($filePath, $category, $limit);

        $this->info("✅ Importazione completata!");
        return 0;
    }

    private function processFile($filePath, $category, $limit = null)
    {
        $handle = fopen($filePath, 'r');
        $header = fgetcsv($handle);
        
        $this->info("📊 Header CSV: " . implode(', ', $header));

        $totalProcessed = 0;
        $totalSkipped = 0;
        $chunkCount = 0;
        $chunk = [];

        while (($row = fgetcsv($handle)) !== false && ($limit === null || $totalProcessed < $limit)) {
            if (count($row) >= 9) {
                $chunk[] = array_combine($header, $row);
                
                if (count($chunk) >= $this->chunkSize) {
                    $result = $this->processChunk($chunk, $category);
                    $totalProcessed += $result['processed'];
                    $totalSkipped += $result['skipped'];
                    $chunkCount++;
                    $this->info("📦 Chunk {$chunkCount}: {$result['processed']} processate, {$result['skipped']} saltate");
                    $chunk = [];
                }
            }
        }

        if (!empty($chunk)) {
            $result = $this->processChunk($chunk, $category);
            $totalProcessed += $result['processed'];
            $totalSkipped += $result['skipped'];
            $chunkCount++;
            $this->info("📦 Chunk finale {$chunkCount}: {$result['processed']} processate, {$result['skipped']} saltate");
        }

        fclose($handle);
        $this->info("📈 Totale: {$totalProcessed} carte, {$totalSkipped} saltate");
    }

    private function processChunk($chunk, $category)
    {
        $processed = 0;
        $skipped = 0;

        DB::beginTransaction();
        try {
            foreach ($chunk as $row) {
                try {
                    $this->processRow($row, $category);
                    $processed++;
                } catch (\Exception $e) {
                    $cardName = $row['Name'] ?? 'Unknown';
                    $cardNumber = $row['Number'] ?? 'Unknown';
                    $this->error("❌ Errore processando riga (Name: {$cardName}, Number: {$cardNumber}): " . $e->getMessage());
                    \Log::error("ImportDisneyCards - Errore riga", [
                        'name' => $cardName,
                        'number' => $cardNumber,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $skipped++;
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        return ['processed' => $processed, 'skipped' => $skipped];
    }

    private function processRow($row, $category)
    {
        $cardNumber = trim($row['Number'] ?? '');
        $name = trim($row['Name'] ?? '');
        $numbered = trim($row['Numbered'] ?? '');
        // Nota: ci sono due colonne "Rarity" nel CSV, prendiamo la prima
        $rarity = trim($row['Rarity'] ?? 'Base');
        $rarityVariation = trim($row['Rarity Variation'] ?? '');
        $brand = strtoupper(trim($row['BRAND'] ?? ''));
        $setName = trim($row['SET'] ?? '');
        $year = trim($row['YEAR'] ?? '');

        $cardSet = $this->getOrCreateCardSet($brand, $setName, $year, $category);

        $cardName = "{$name} - {$cardSet->name}";
        if (!empty($rarityVariation)) {
            $cardName .= " ({$rarityVariation})";
        }

        $attributes = [];
        if (!empty(trim($row['AUTOGRAPH'] ?? ''))) $attributes[] = 'autograph';
        if (!empty(trim($row['RELIC'] ?? ''))) $attributes[] = 'relic';
        if (!empty(trim($row['ON CARD AUTO'] ?? ''))) $attributes[] = 'on_card_auto';
        if (!empty(trim($row['SKETCH'] ?? ''))) $attributes[] = 'sketch';
        if (!empty(trim($row['BOOKLET'] ?? ''))) $attributes[] = 'booklet';
        if (!empty(trim($row['DUAL'] ?? ''))) $attributes[] = 'dual';
        if (!empty(trim($row['TRIPLE'] ?? ''))) $attributes[] = 'triple';
        if (!empty(trim($row['QUAD'] ?? ''))) $attributes[] = 'quad';
        if (!empty($numbered)) $attributes[] = 'numbered';

        // Crea uno slug univoco basato sui dati della carta
        // IMPORTANTE: NON includere rarity nello slug perché può cambiare (es. da "common" a "Cinderella 75th Story")
        // La chiave univoca è: name + set + number + rarity_variation
        $uniqueData = json_encode([
            'name' => $cardName,
            'set' => $cardSet->name,
            'number' => $cardNumber,
            'rarity_variation' => $rarityVariation, // Usa rarity_variation invece di rarity
            'numbered' => $numbered,
        ]);
        $uniqueHash = substr(md5($uniqueData), 0, 8);
        $uniqueSlug = Str::slug($cardName) . '-' . $uniqueHash;
        
        // Usa updateOrCreate invece di create per aggiornare le carte esistenti
        CardModel::updateOrCreate(
            ['slug' => $uniqueSlug],
            [
                'category_id' => $category->id,
                'card_set_id' => $cardSet->id,
                'player_id' => null,
                'team_id' => null,
                'league_id' => null,
                'name' => $cardName,
                'set_name' => $cardSet->name,
                'year' => $this->extractYear($year),
                // Per Disney, salva il valore originale della rarity dal CSV invece di mapparlo
                // Es: "Cinderella 75th Story", "Base Tier 2", "Sketch", ecc.
                'rarity' => !empty($rarity) ? $rarity : 'common',
                'rarity_variation' => !empty($rarityVariation) ? $rarityVariation : null,
                'card_number' => $cardNumber,
                'card_number_in_set' => !empty($numbered) ? $numbered : null,
                'is_rookie' => false,
                'is_star' => false,
                'is_legend' => false,
                'is_autograph' => !empty(trim($row['AUTOGRAPH'] ?? '')),
                'is_relic' => !empty(trim($row['RELIC'] ?? '')),
                'attributes' => $attributes,
                'is_active' => true,
            ]
        );
    }

    private function getOrCreateCardSet($brand, $setName, $year, $category)
    {
        $fullSetName = "{$brand} {$setName}";
        
        if (isset($this->cache['card_sets'][$fullSetName])) {
            return $this->cache['card_sets'][$fullSetName];
        }
        
        $cardSet = CardSet::where('name', $fullSetName)->first();
        
        if (!$cardSet) {
            $cardSet = CardSet::create([
                'category_id' => $category->id,
                'name' => $fullSetName,
                'slug' => Str::slug($fullSetName),
                'brand' => $brand,
                'year' => $this->extractYear($year),
                'season' => $year,
                'is_official' => true,
                'is_active' => true,
                'sort_order' => 1,
            ]);
        }

        $this->cache['card_sets'][$fullSetName] = $cardSet;
        return $cardSet;
    }

    private function mapRarity($rarity)
    {
        $rarityLower = strtolower($rarity);
        if (str_contains($rarityLower, 'base')) return 'common';
        if (str_contains($rarityLower, 'insert')) return 'uncommon';
        if (str_contains($rarityLower, 'parallel')) return 'rare';
        if (str_contains($rarityLower, 'auto')) return 'epic';
        if (str_contains($rarityLower, 'relic')) return 'epic';
        if (str_contains($rarityLower, 'sketch')) return 'legendary';
        if (str_contains($rarityLower, 'booklet')) return 'mythic';
        return 'common';
    }

    private function extractYear($year)
    {
        if (preg_match('/(\d{4})/', $year, $matches)) {
            return (int) $matches[1];
        }
        return 2025;
    }

    /**
     * Cerca un file in modo ricorsivo in una directory
     */
    private function findFileRecursive($dir, $fileName, $maxDepth = 3, $currentDepth = 0)
    {
        if ($currentDepth >= $maxDepth) {
            return null;
        }

        if (!is_dir($dir) || !is_readable($dir)) {
            return null;
        }

        $items = @scandir($dir);
        if ($items === false) {
            return null;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;

            // Evita di cercare in directory che non servono
            if (is_dir($path)) {
                $skipDirs = ['node_modules', '.git', 'vendor', 'bootstrap/cache', 'storage/framework'];
                $shouldSkip = false;
                foreach ($skipDirs as $skipDir) {
                    if (strpos($path, $skipDir) !== false) {
                        $shouldSkip = true;
                        break;
                    }
                }
                if ($shouldSkip) {
                    continue;
                }

                $found = $this->findFileRecursive($path, $fileName, $maxDepth, $currentDepth + 1);
                if ($found) {
                    return $found;
                }
            } elseif (is_file($path) && basename($path) === $fileName) {
                return $path;
            }
        }

        return null;
    }
}

