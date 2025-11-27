<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Category;
use App\Models\League;
use App\Models\Team;
use App\Models\Player;
use App\Models\CardSet;
use App\Models\CardModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ImportBasketCards extends Command
{
    protected $signature = 'import:basket-cards 
                            {--file= : Path al file CSV}
                            {--limit= : Limite di righe da processare}
                            {--chunk=1000 : Dimensione del chunk per l\'elaborazione}
                            {--clear : Svuota le tabelle prima dell\'importazione}
                            {--dry-run : Modalità dry run senza salvare dati}';

    protected $description = 'Importa le carte di basket dal file CSV';

    private $chunkSize = 1000;

    private $cache = [
        'leagues' => [],
        'teams' => [],
        'players' => [],
        'card_sets' => [],
    ];

    public function handle()
    {
        $filePath = $this->option('file') ?: base_path('TOIMPORT/Elenco Set Basket 1 - Foglio1.csv');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $this->chunkSize = (int) $this->option('chunk');
        $clearTables = $this->option('clear');
        $dryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("File non trovato: {$filePath}");
            return 1;
        }

        $this->info("🚀 Inizio importazione carte di basket...");
        $this->info("📁 File: {$filePath}");
        $this->info("📦 Chunk size: {$this->chunkSize}");
        
        if ($dryRun) {
            $this->warn("⚠️  Modalità DRY RUN - Nessun dato verrà salvato");
        }

        // Crea o trova la categoria Basket
        $category = $this->getOrCreateCategory($dryRun);

        // Processa il file in chunk
        $this->processFileInChunks($filePath, $category, $limit, $dryRun);

        $this->info("✅ Importazione completata!");
        return 0;
    }

    private function processFileInChunks($filePath, $category, $limit = null, $dryRun = false)
    {
        $handle = fopen($filePath, 'r');
        
        if (!$handle) {
            throw new \Exception("Impossibile aprire il file CSV");
        }

        $header = fgetcsv($handle);
        if (!$header) {
            throw new \Exception("File CSV vuoto o malformato");
        }

        $this->info("📊 Header CSV: " . implode(', ', $header));

        $totalProcessed = 0;
        $totalSkipped = 0;
        $chunkCount = 0;
        $chunk = [];

        $this->info("🔄 Inizio elaborazione a chunk...");

        while (($row = fgetcsv($handle)) !== false && ($limit === null || $totalProcessed < $limit)) {
            if (count($row) >= 9) {
                $chunk[] = array_combine($header, $row);
                
                if (count($chunk) >= $this->chunkSize) {
                    $result = $this->processChunk($chunk, $category, $dryRun);
                    $totalProcessed += $result['processed'];
                    $totalSkipped += $result['skipped'];
                    $chunkCount++;
                    
                    $this->info("📦 Chunk {$chunkCount} completato: {$result['processed']} processate, {$result['skipped']} saltate");
                    
                    $chunk = [];
                    unset($result);
                    
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
            }
        }

        if (!empty($chunk)) {
            $result = $this->processChunk($chunk, $category, $dryRun);
            $totalProcessed += $result['processed'];
            $totalSkipped += $result['skipped'];
            $chunkCount++;
            
            $this->info("📦 Chunk finale {$chunkCount} completato: {$result['processed']} processate, {$result['skipped']} saltate");
        }

        fclose($handle);

        $this->info("📈 Statistiche finali:");
        $this->info("   📦 Chunk processati: {$chunkCount}");
        $this->info("   ✅ Carte processate: {$totalProcessed}");
        $this->info("   ⚠️  Carte saltate: {$totalSkipped}");
    }

    private function processChunk($chunk, $category, $dryRun = false)
    {
        $processed = 0;
        $skipped = 0;

        if (!$dryRun) {
            DB::beginTransaction();
        }

        try {
            foreach ($chunk as $row) {
                try {
                    $this->processCardRow($row, $category, $dryRun);
                    $processed++;
                } catch (\Exception $e) {
                    $this->warn("⚠️  Errore processando riga: " . $e->getMessage());
                    $skipped++;
                }
            }

            if (!$dryRun) {
                DB::commit();
            }

        } catch (\Exception $e) {
            if (!$dryRun) {
                DB::rollback();
            }
            throw $e;
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped
        ];
    }

    private function getOrCreateCategory($dryRun = false)
    {
        $category = Category::where('slug', 'basketball')->first();
        
        if (!$category) {
            $this->error("Categoria basketball non trovata!");
            return 1;
        }

        return $category;
    }

    private function processCardRow($row, $category, $dryRun = false)
    {
        $cardNumber = trim($row['Numero'] ?? '');
        $playerName = trim($row['Player'] ?? '');
        $numbered = trim($row['NUMBERED /'] ?? '');
        $isRookie = !empty(trim($row['ROOKIE'] ?? ''));
        $teamName = trim($row['Team'] ?? '');
        $rarity = trim($row['Rarity'] ?? 'Base');
        $rarityVariation = trim($row['Rarity Variation'] ?? '');
        $brand = strtoupper(trim($row['BRAND'] ?? ''));
        $setName = trim($row['SET'] ?? '');
        $year = trim($row['YEAR'] ?? '');
        
        // Crea o trova la lega (NBA default per basket)
        $league = $this->getOrCreateLeague($dryRun);
        
        // Crea o trova la squadra
        $team = $this->getOrCreateTeam($teamName, $league, $dryRun);
        
        // Crea o trova il giocatore
        $player = $this->getOrCreatePlayer($playerName, $team, $dryRun);
        
        // Crea o trova il set
        $cardSet = $this->getOrCreateCardSet($brand, $setName, $year, $category, $dryRun);
        
        // Crea la carta
        $this->createCardModel($row, $category, $cardSet, $player, $team, $league, $dryRun);
    }

    private function getOrCreateLeague($dryRun = false)
    {
        $leagueName = 'NBA';
        
        if (isset($this->cache['leagues'][$leagueName])) {
            return $this->cache['leagues'][$leagueName];
        }
        
        $league = League::where('name', $leagueName)->first();
        
        if (!$league) {
            if (!$dryRun) {
                $league = League::create([
                    'name' => $leagueName,
                    'slug' => Str::slug($leagueName),
                    'country' => 'USA',
                    'is_active' => true,
                    'sort_order' => 1,
                ]);
            } else {
                $league = (object) [
                    'id' => 1,
                    'name' => $leagueName,
                    'slug' => Str::slug($leagueName),
                ];
            }
        }

        $this->cache['leagues'][$leagueName] = $league;
        return $league;
    }

    private function getOrCreateTeam($teamName, $league, $dryRun = false)
    {
        if (isset($this->cache['teams'][$teamName])) {
            return $this->cache['teams'][$teamName];
        }
        
        $team = Team::where('name', $teamName)->first();
        
        if (!$team) {
            if (!$dryRun) {
                $team = Team::create([
                    'league_id' => $league->id,
                    'name' => $teamName,
                    'slug' => Str::slug($teamName),
                    'is_active' => true,
                    'sort_order' => 1,
                ]);
            } else {
                $team = (object) [
                    'id' => 1,
                    'name' => $teamName,
                    'slug' => Str::slug($teamName),
                ];
            }
        }

        $this->cache['teams'][$teamName] = $team;
        return $team;
    }

    private function getOrCreatePlayer($playerName, $team, $dryRun = false)
    {
        if (isset($this->cache['players'][$playerName])) {
            return $this->cache['players'][$playerName];
        }
        
        $player = Player::where('name', $playerName)->first();
        
        if (!$player) {
            if (!$dryRun) {
                $nameParts = explode(' ', $playerName, 2);
                $firstName = $nameParts[0] ?? '';
                $lastName = $nameParts[1] ?? '';
                
                $player = Player::create([
                    'team_id' => $team->id,
                    'name' => $playerName,
                    'slug' => Str::slug($playerName),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'is_active' => true,
                    'sort_order' => 1,
                ]);
            } else {
                $player = (object) [
                    'id' => 1,
                    'name' => $playerName,
                    'slug' => Str::slug($playerName),
                ];
            }
        }

        $this->cache['players'][$playerName] = $player;
        return $player;
    }

    private function getOrCreateCardSet($brand, $setName, $year, $category, $dryRun = false)
    {
        $fullSetName = "{$brand} {$setName}";
        
        if (isset($this->cache['card_sets'][$fullSetName])) {
            return $this->cache['card_sets'][$fullSetName];
        }
        
        $cardSet = CardSet::where('name', $fullSetName)->first();
        
        if (!$cardSet) {
            if (!$dryRun) {
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
            } else {
                $cardSet = (object) [
                    'id' => 1,
                    'name' => $fullSetName,
                    'slug' => Str::slug($fullSetName),
                ];
            }
        }

        $this->cache['card_sets'][$fullSetName] = $cardSet;
        return $cardSet;
    }

    private function createCardModel($row, $category, $cardSet, $player, $team, $league, $dryRun = false)
    {
        $cardNumber = trim($row['Numero'] ?? '');
        $playerName = trim($row['Player'] ?? '');
        $numbered = trim($row['NUMBERED /'] ?? '');
        $isRookie = !empty(trim($row['ROOKIE'] ?? ''));
        $rarity = trim($row['Rarity'] ?? 'Base');
        $rarityVariation = trim($row['Rarity Variation'] ?? '');
        $year = trim($row['YEAR'] ?? '');
        
        // Determina la rarità
        $mappedRarity = $this->mapRarity($rarity);
        
        // Crea il nome della carta
        $cardName = "{$playerName} - {$cardSet->name}";
        if (!empty($rarityVariation)) {
            $cardName .= " ({$rarityVariation})";
        }
        
        // Crea gli attributi speciali
        $attributes = [];
        if (!empty(trim($row['AUTOGRAPH'] ?? ''))) $attributes[] = 'autograph';
        if (!empty(trim($row['RELIC'] ?? ''))) $attributes[] = 'relic';
        if (!empty(trim($row['ON CARD AUTO'] ?? ''))) $attributes[] = 'on_card_auto';
        if (!empty(trim($row['JEWEL'] ?? ''))) $attributes[] = 'jewel';
        if (!empty(trim($row['BOOKLET'] ?? ''))) $attributes[] = 'booklet';
        if (!empty(trim($row['MULTI PLAYER - DUAL'] ?? ''))) $attributes[] = 'multi_player_dual';
        if (!empty(trim($row['MULTI PLAYER - TRIPLE'] ?? ''))) $attributes[] = 'multi_player_triple';
        if (!empty(trim($row['MULTI PLAYER - QUAD'] ?? ''))) $attributes[] = 'multi_player_quad';
        if (!empty($numbered)) $attributes[] = 'numbered';
        
        if (!$dryRun) {
            CardModel::create([
                'category_id' => $category->id,
                'card_set_id' => $cardSet->id,
                'player_id' => $player->id,
                'team_id' => $team->id,
                'league_id' => $league->id,
                'name' => $cardName,
                'slug' => Str::slug($cardName) . '-' . uniqid(),
                'set_name' => $cardSet->name,
                'year' => $this->extractYear($year),
                'rarity' => $rarity ?: 'Base',
                'card_number' => $cardNumber,
                'card_number_in_set' => $cardNumber,
                'is_rookie' => $isRookie,
                'is_star' => false,
                'is_legend' => false,
                'is_autograph' => !empty(trim($row['AUTOGRAPH'] ?? '')),
                'is_relic' => !empty(trim($row['RELIC'] ?? '')),
                'attributes' => $attributes,
                'is_active' => true,
            ]);
        }
    }

    private function mapRarity($rarity)
    {
        $rarityLower = strtolower($rarity);
        
        if (str_contains($rarityLower, 'base')) return 'common';
        if (str_contains($rarityLower, 'insert')) return 'uncommon';
        if (str_contains($rarityLower, 'parallel')) return 'rare';
        if (str_contains($rarityLower, 'auto')) return 'epic';
        if (str_contains($rarityLower, 'relic')) return 'epic';
        if (str_contains($rarityLower, 'patch')) return 'legendary';
        if (str_contains($rarityLower, 'booklet')) return 'mythic';
        
        return 'common';
    }

    private function extractYear($year)
    {
        if (preg_match('/(\d{4})/', $year, $matches)) {
            return (int) $matches[1];
        }
        return 2024;
    }
}

