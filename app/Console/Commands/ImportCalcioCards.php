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

class ImportCalcioCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:calcio-cards 
                            {--file= : Path al file CSV}
                            {--limit= : Limite di righe da processare}
                            {--chunk=1000 : Dimensione del chunk per l\'elaborazione}
                            {--clear : Svuota le tabelle prima dell\'importazione}
                            {--dry-run : Modalità dry run senza salvare dati}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa le carte di calcio dal file CSV con gestione memoria ottimizzata';

    /**
     * Dimensione del chunk per l'elaborazione
     */
    private $chunkSize = 1000;

    /**
     * Mappa delle squadre alle leghe
     */
    private $teamLeagueMap = [
        'Paris Saint-Germain' => 'Ligue 1',
        'FC Salzburg' => 'Bundesliga',
        'Real Sociedad de Fútbol' => 'La Liga',
        'Club Brugge' => 'Belgian Pro League',
        'Tottenham Hotspur' => 'Premier League',
        'FC Internazionale Milano' => 'Serie A',
        'Manchester United' => 'Premier League',
        'FC Barcelona' => 'La Liga',
        'AC Milan' => 'Serie A',
        'Real Madrid C.F.' => 'La Liga',
        'Feyenoord' => 'Eredivisie',
        'FC Bayern München' => 'Bundesliga',
        'Celtic FC' => 'Scottish Premiership',
        'RB Leipzig' => 'Bundesliga',
        'Chelsea FC' => 'Premier League',
        'Arsenal FC' => 'Premier League',
        'Liverpool FC' => 'Premier League',
        'Manchester City' => 'Premier League',
        'Atletico Madrid' => 'La Liga',
        'Juventus FC' => 'Serie A',
        'AS Roma' => 'Serie A',
        'Napoli' => 'Serie A',
        'SL Benfica' => 'Primeira Liga',
        'FC Porto' => 'Primeira Liga',
        'Sporting CP' => 'Primeira Liga',
        'Ajax Amsterdam' => 'Eredivisie',
        'PSV Eindhoven' => 'Eredivisie',
        'Borussia Dortmund' => 'Bundesliga',
        'Bayer Leverkusen' => 'Bundesliga',
        'AS Monaco' => 'Ligue 1',
        'Olympique Lyon' => 'Ligue 1',
        'Olympique Marseille' => 'Ligue 1',
        'Valencia CF' => 'La Liga',
        'Sevilla FC' => 'La Liga',
        'Villarreal CF' => 'La Liga',
        'Real Betis' => 'La Liga',
        'Lazio' => 'Serie A',
        'Fiorentina' => 'Serie A',
        'Atalanta' => 'Serie A',
        'Bologna' => 'Serie A',
        'Torino' => 'Serie A',
        'Genoa' => 'Serie A',
        'Sampdoria' => 'Serie A',
        'Udinese' => 'Serie A',
        'Verona' => 'Serie A',
        'Sassuolo' => 'Serie A',
        'Empoli' => 'Serie A',
        'Spezia' => 'Serie A',
        'Cagliari' => 'Serie A',
        'Salernitana' => 'Serie A',
        'Lecce' => 'Serie A',
        'Monza' => 'Serie A',
        'Cremonese' => 'Serie A',
    ];

    /**
     * Cache per evitare query ripetute
     */
    private $cache = [
        'leagues' => [],
        'teams' => [],
        'players' => [],
        'card_sets' => [],
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->option('file') ?: base_path('CALCIO-CARDS.csv');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $this->chunkSize = (int) $this->option('chunk');
        $clearTables = $this->option('clear');
        $dryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("File non trovato: {$filePath}");
            return 1;
        }

        $this->info("🚀 Inizio importazione carte di calcio...");
        $this->info("📁 File: {$filePath}");
        $this->info("📦 Chunk size: {$this->chunkSize}");
        
        if ($dryRun) {
            $this->warn("⚠️  Modalità DRY RUN - Nessun dato verrà salvato");
        }

        if ($clearTables) {
            $this->clearTables($dryRun);
        }

        // Crea o trova la categoria Calcio
        $category = $this->getOrCreateCategory($dryRun);

        // Processa il file in chunk
        $this->processFileInChunks($filePath, $category, $limit, $dryRun);

        $this->info("✅ Importazione completata!");
        return 0;
    }

    /**
     * Svuota le tabelle prima dell'importazione
     */
    private function clearTables($dryRun = false)
    {
        if ($dryRun) {
            $this->warn("⚠️  DRY RUN: Le tabelle non verranno svuotate");
            return;
        }

        $this->info("🗑️  Svuotamento tabelle...");
        
        // Disabilita i controlli di foreign key temporaneamente
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Svuota le tabelle in ordine corretto
        $tables = [
            'card_models',
            'players', 
            'teams',
            'card_sets',
            'leagues',
            'categories'
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
            $this->info("   ✅ Tabella {$table} svuotata");
        }

        // Riabilita i controlli di foreign key
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info("✅ Tutte le tabelle sono state svuotate");
    }

    /**
     * Processa il file CSV in chunk per gestire la memoria
     */
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
            if (count($row) >= 9) { // Assicurati che ci siano abbastanza colonne
                $chunk[] = array_combine($header, $row);
                
                // Processa il chunk quando raggiunge la dimensione massima
                if (count($chunk) >= $this->chunkSize) {
                    $result = $this->processChunk($chunk, $category, $dryRun);
                    $totalProcessed += $result['processed'];
                    $totalSkipped += $result['skipped'];
                    $chunkCount++;
                    
                    $this->info("📦 Chunk {$chunkCount} completato: {$result['processed']} processate, {$result['skipped']} saltate");
                    
                    // Libera la memoria
                    $chunk = [];
                    unset($result);
                    
                    // Forza il garbage collection
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                }
            }
        }

        // Processa l'ultimo chunk se non è vuoto
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

    /**
     * Processa un singolo chunk di dati
     */
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

    /**
     * Crea o trova la categoria Calcio
     */
    private function getOrCreateCategory($dryRun = false)
    {
        $category = Category::where('slug', 'calcio')->first();
        
        if (!$category) {
            if (!$dryRun) {
                $category = Category::create([
                    'name' => 'Calcio',
                    'slug' => 'calcio',
                    'description' => 'Carte da collezione di calcio',
                    'is_active' => true,
                    'sort_order' => 1,
                ]);
                $this->info("✅ Creata categoria Calcio");
            } else {
                $category = (object) [
                    'id' => 1,
                    'name' => 'Calcio',
                    'slug' => 'calcio',
                ];
            }
        }

        return $category;
    }

    /**
     * Processa una singola riga del CSV
     */
    private function processCardRow($row, $category, $dryRun = false)
    {
        // Estrai i dati dalla riga
        $cardNumber = trim($row['Numero'] ?? '');
        $playerName = trim($row['Player'] ?? '');
        $isRookie = !empty(trim($row['ROOKIE'] ?? ''));
        $teamName = trim($row['Team'] ?? '');
        $rarity = trim($row['Rarity'] ?? 'Base card');
        $brand = strtoupper(trim($row['BRAND'] ?? '')); // Normalizza in uppercase
        $setName = trim($row['SET'] ?? '');
        $year = trim($row['YEAR'] ?? '');
        
        // Crea o trova la lega (con cache)
        $league = $this->getOrCreateLeague($teamName, $dryRun);
        
        // Crea o trova la squadra (con cache)
        $team = $this->getOrCreateTeam($teamName, $league, $dryRun);
        
        // Crea o trova il giocatore (con cache)
        $player = $this->getOrCreatePlayer($playerName, $team, $dryRun);
        
        // Crea o trova il set (con cache)
        $cardSet = $this->getOrCreateCardSet($brand, $setName, $year, $category, $dryRun);
        
        // Crea la carta
        $this->createCardModel($row, $category, $cardSet, $player, $team, $league, $dryRun);
    }

    /**
     * Crea o trova una lega (con cache)
     */
    private function getOrCreateLeague($teamName, $dryRun = false)
    {
        $leagueName = $this->teamLeagueMap[$teamName] ?? 'Altre Leghe';
        
        // Controlla la cache
        if (isset($this->cache['leagues'][$leagueName])) {
            return $this->cache['leagues'][$leagueName];
        }
        
        $league = League::where('name', $leagueName)->first();
        
        if (!$league) {
            if (!$dryRun) {
                $league = League::create([
                    'name' => $leagueName,
                    'slug' => Str::slug($leagueName),
                    'country' => $this->getCountryFromLeague($leagueName),
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

        // Salva nella cache
        $this->cache['leagues'][$leagueName] = $league;
        return $league;
    }

    /**
     * Crea o trova una squadra (con cache)
     */
    private function getOrCreateTeam($teamName, $league, $dryRun = false)
    {
        // Controlla la cache
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

        // Salva nella cache
        $this->cache['teams'][$teamName] = $team;
        return $team;
    }

    /**
     * Crea o trova un giocatore (con cache)
     */
    private function getOrCreatePlayer($playerName, $team, $dryRun = false)
    {
        // Controlla la cache
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

        // Salva nella cache
        $this->cache['players'][$playerName] = $player;
        return $player;
    }

    /**
     * Crea o trova un set di carte (con cache)
     */
    private function getOrCreateCardSet($brand, $setName, $year, $category, $dryRun = false)
    {
        $fullSetName = "{$brand} {$setName}";
        
        // Controlla la cache
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

        // Salva nella cache
        $this->cache['card_sets'][$fullSetName] = $cardSet;
        return $cardSet;
    }

    /**
     * Crea un modello di carta
     */
    private function createCardModel($row, $category, $cardSet, $player, $team, $league, $dryRun = false)
    {
        $cardNumber = trim($row['Numero'] ?? '');
        $playerName = trim($row['Player'] ?? '');
        $isRookie = !empty(trim($row['ROOKIE'] ?? ''));
        $rarity = trim($row['Rarity'] ?? 'Base card');
        $year = trim($row['YEAR'] ?? '');
        
        // Determina la rarità
        $rarityMap = [
            'Base card' => 'common',
            'Future Star' => 'uncommon',
            'Rookie' => 'rare',
            'Star' => 'rare',
            'Legend' => 'mythic',
        ];
        
        $mappedRarity = $rarityMap[$rarity] ?? 'common';
        
        // Crea il nome della carta
        $cardName = "{$playerName} - {$cardSet->name}";
        
        // Crea gli attributi speciali
        $attributes = [];
        if (!empty(trim($row['AUTOGRAPH'] ?? ''))) $attributes[] = 'autograph';
        if (!empty(trim($row['RELIC'] ?? ''))) $attributes[] = 'relic';
        if (!empty(trim($row['ON CARD AUTO'] ?? ''))) $attributes[] = 'on_card_auto';
        if (!empty(trim($row['JEWEL'] ?? ''))) $attributes[] = 'jewel';
        if (!empty(trim($row['MULTI AUTOGRAPH BOOKLET'] ?? ''))) $attributes[] = 'multi_autograph_booklet';
        if (!empty(trim($row['MULTI AUTOGRAPH DUAL'] ?? ''))) $attributes[] = 'multi_autograph_dual';
        if (!empty(trim($row['MULTI AUTOGRAPH TRIPLE'] ?? ''))) $attributes[] = 'multi_autograph_triple';
        if (!empty(trim($row['MULTI AUTOGRAPH QUAD'] ?? ''))) $attributes[] = 'multi_autograph_quad';
        
        if (!$dryRun) {
            CardModel::create([
                'category_id' => $category->id,
                'card_set_id' => $cardSet->id,
                'player_id' => $player->id,
                'team_id' => $team->id,
                'league_id' => $league->id,
                'name' => $cardName,
                'slug' => Str::slug($cardName),
                'set_name' => $cardSet->name,
                'year' => $this->extractYear($year),
                'rarity' => $mappedRarity,
                'card_number' => $cardNumber,
                'card_number_in_set' => $cardNumber,
                'is_rookie' => $isRookie,
                'is_star' => str_contains($rarity, 'Star'),
                'is_legend' => str_contains($rarity, 'Legend'),
                'is_autograph' => str_contains($rarity, 'Autograph') || str_contains($rarity, 'Auto'),
                'is_relic' => str_contains($rarity, 'Relic') || str_contains($rarity, 'Patch'),
                'attributes' => $attributes,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Estrae l'anno dal campo year
     */
    private function extractYear($year)
    {
        if (preg_match('/(\d{4})/', $year, $matches)) {
            return (int) $matches[1];
        }
        return 2023; // Default
    }

    /**
     * Ottiene il paese dalla lega
     */
    private function getCountryFromLeague($leagueName)
    {
        $leagueCountries = [
            'Serie A' => 'Italia',
            'Premier League' => 'Inghilterra',
            'La Liga' => 'Spagna',
            'Bundesliga' => 'Germania',
            'Ligue 1' => 'Francia',
            'Eredivisie' => 'Olanda',
            'Primeira Liga' => 'Portogallo',
            'Scottish Premiership' => 'Scozia',
            'Belgian Pro League' => 'Belgio',
        ];
        
        return $leagueCountries[$leagueName] ?? 'Internazionale';
    }
}