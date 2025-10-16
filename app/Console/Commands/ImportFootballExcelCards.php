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

class ImportFootballExcelCards extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:football-excel-cards 
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
    protected $description = 'Importa le carte di calcio dal file Excel con la nuova struttura';

    /**
     * Dimensione del chunk per l'elaborazione
     */
    private $chunkSize = 1000;

    /**
     * Mappa delle squadre alle leghe
     */
    private $teamLeagueMap = [
        // Serie A
        'Juventus' => 'Serie A',
        'Inter' => 'Serie A',
        'Milan' => 'Serie A',
        'Napoli' => 'Serie A',
        'Roma' => 'Serie A',
        'Lazio' => 'Serie A',
        'Atalanta' => 'Serie A',
        'Fiorentina' => 'Serie A',
        'Torino' => 'Serie A',
        'Bologna' => 'Serie A',
        'Genoa' => 'Serie A',
        'Sassuolo' => 'Serie A',
        'Udinese' => 'Serie A',
        'Verona' => 'Serie A',
        'Lecce' => 'Serie A',
        'Monza' => 'Serie A',
        'Cremonese' => 'Serie A',
        
        // Premier League
        'Manchester United' => 'Premier League',
        'Manchester City' => 'Premier League',
        'Liverpool' => 'Premier League',
        'Chelsea' => 'Premier League',
        'Arsenal' => 'Premier League',
        'Tottenham' => 'Premier League',
        'Newcastle' => 'Premier League',
        'Brighton' => 'Premier League',
        'West Ham' => 'Premier League',
        'Aston Villa' => 'Premier League',
        'Crystal Palace' => 'Premier League',
        'Fulham' => 'Premier League',
        'Brentford' => 'Premier League',
        'Wolves' => 'Premier League',
        'Everton' => 'Premier League',
        'Nottingham Forest' => 'Premier League',
        'Burnley' => 'Premier League',
        'Sheffield United' => 'Premier League',
        'Luton Town' => 'Premier League',
        'Bournemouth' => 'Premier League',
        
        // La Liga
        'Real Madrid' => 'La Liga',
        'Barcelona' => 'La Liga',
        'Atletico Madrid' => 'La Liga',
        'Real Sociedad' => 'La Liga',
        'Villarreal' => 'La Liga',
        'Real Betis' => 'La Liga',
        'Valencia' => 'La Liga',
        'Sevilla' => 'La Liga',
        'Athletic Bilbao' => 'La Liga',
        'Osasuna' => 'La Liga',
        'Getafe' => 'La Liga',
        'Girona' => 'La Liga',
        'Las Palmas' => 'La Liga',
        'Mallorca' => 'La Liga',
        'Cadiz' => 'La Liga',
        'Rayo Vallecano' => 'La Liga',
        'Alaves' => 'La Liga',
        'Celta Vigo' => 'La Liga',
        'Almeria' => 'La Liga',
        'Granada' => 'La Liga',
        
        // Bundesliga
        'Bayern Munich' => 'Bundesliga',
        'Borussia Dortmund' => 'Bundesliga',
        'RB Leipzig' => 'Bundesliga',
        'Bayer Leverkusen' => 'Bundesliga',
        'Eintracht Frankfurt' => 'Bundesliga',
        'Freiburg' => 'Bundesliga',
        'Union Berlin' => 'Bundesliga',
        'Wolfsburg' => 'Bundesliga',
        'Mainz' => 'Bundesliga',
        'Borussia Mönchengladbach' => 'Bundesliga',
        'Köln' => 'Bundesliga',
        'Hoffenheim' => 'Bundesliga',
        'Werder Bremen' => 'Bundesliga',
        'Bochum' => 'Bundesliga',
        'Augsburg' => 'Bundesliga',
        'Stuttgart' => 'Bundesliga',
        'Heidenheim' => 'Bundesliga',
        'Darmstadt' => 'Bundesliga',
        
        // Ligue 1
        'PSG' => 'Ligue 1',
        'Marseille' => 'Ligue 1',
        'Monaco' => 'Ligue 1',
        'Lyon' => 'Ligue 1',
        'Lille' => 'Ligue 1',
        'Rennes' => 'Ligue 1',
        'Lens' => 'Ligue 1',
        'Nice' => 'Ligue 1',
        'Reims' => 'Ligue 1',
        'Montpellier' => 'Ligue 1',
        'Toulouse' => 'Ligue 1',
        'Brest' => 'Ligue 1',
        'Nantes' => 'Ligue 1',
        'Strasbourg' => 'Ligue 1',
        'Metz' => 'Ligue 1',
        'Lorient' => 'Ligue 1',
        'Clermont' => 'Ligue 1',
        'Le Havre' => 'Ligue 1',
        
        // MLS
        'Real Salt Lake' => 'MLS',
        'Los Angeles Fc' => 'MLS',
        'Los Angeles FC' => 'MLS',
        'LA Galaxy' => 'MLS',
        'Seattle Sounders' => 'MLS',
        'Portland Timbers' => 'MLS',
        'Vancouver Whitecaps' => 'MLS',
        'San Jose Earthquakes' => 'MLS',
        'Colorado Rapids' => 'MLS',
        'FC Dallas' => 'MLS',
        'Houston Dynamo' => 'MLS',
        'Austin FC' => 'MLS',
        'Sporting Kansas City' => 'MLS',
        'Minnesota United' => 'MLS',
        'Chicago Fire' => 'MLS',
        'Columbus Crew' => 'MLS',
        'FC Cincinnati' => 'MLS',
        'Nashville SC' => 'MLS',
        'Atlanta United' => 'MLS',
        'Orlando City' => 'MLS',
        'Inter Miami' => 'MLS',
        'New York City FC' => 'MLS',
        'New York Red Bulls' => 'MLS',
        'Philadelphia Union' => 'MLS',
        'DC United' => 'MLS',
        'New England Revolution' => 'MLS',
        'Toronto FC' => 'MLS',
        'CF Montreal' => 'MLS',
        
        // Squadre aggiuntive dai file CSV
        'Paris Saint-Germain' => 'Ligue 1',
        'Fc Salzburg' => 'Austrian Bundesliga',
        'Real Sociedad De Futbol' => 'La Liga',
        'Club Brugge' => 'Belgian Pro League',
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
        $filePath = $this->option('file') ?: base_path('FOOTBALL-EXCEL-CARDS.csv');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $this->chunkSize = (int) $this->option('chunk');
        $clearTables = $this->option('clear');
        $dryRun = $this->option('dry-run');

        if (!file_exists($filePath)) {
            $this->error("File non trovato: {$filePath}");
            return 1;
        }

        $this->info("🚀 Inizio importazione carte di calcio da Excel...");
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
            $this->info("🧹 DRY RUN: Svuotamento tabelle simulato");
            return;
        }

        $this->info("🧹 Svuotamento tabelle...");
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CardModel::truncate();
        CardSet::truncate();
        Player::truncate();
        Team::truncate();
        League::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info("✅ Tabelle svuotate");
    }

    /**
     * Processa il file in chunk per gestire file grandi
     */
    private function processFileInChunks($filePath, $category, $limit = null, $dryRun = false)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception("Impossibile aprire il file: {$filePath}");
        }

        // Leggi l'header
        $header = fgetcsv($handle);
        if (!$header) {
            throw new \Exception("File CSV vuoto o malformato");
        }

        $this->info("📋 Header rilevato: " . implode(', ', $header));

        $rowCount = 0;
        $processedCount = 0;
        $chunk = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            
            if ($limit && $rowCount > $limit) {
                break;
            }

            // Combina header con i dati
            // Assicurati che il numero di valori corrisponda al numero di colonne
            $row = array_pad($row, count($header), '');
            $rowData = array_combine($header, $row);
            $chunk[] = $rowData;

            // Processa il chunk quando raggiunge la dimensione
            if (count($chunk) >= $this->chunkSize) {
                $this->processChunk($chunk, $category, $dryRun, $processedCount);
                $processedCount += count($chunk);
                $this->info("📊 Processate {$processedCount} righe...");
                $chunk = [];
            }
        }

        // Processa l'ultimo chunk
        if (!empty($chunk)) {
            $this->processChunk($chunk, $category, $dryRun, $processedCount);
            $processedCount += count($chunk);
        }

        fclose($handle);
        $this->info("📊 Totale righe processate: {$processedCount}");
    }

    /**
     * Processa un chunk di righe
     */
    private function processChunk($chunk, $category, $dryRun = false, $startRowNumber = 0)
    {
        if ($dryRun) {
            $this->info("🔄 DRY RUN: Processamento chunk di " . count($chunk) . " righe");
            return;
        }

        DB::transaction(function () use ($chunk, $category, $startRowNumber) {
            foreach ($chunk as $index => $row) {
                $rowNumber = $startRowNumber + $index + 1;
                $this->processCardRow($row, $category, false, $rowNumber);
            }
        });
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
    private function processCardRow($row, $category, $dryRun = false, $rowNumber = null)
    {
        // Estrai i dati dalla riga seguendo la struttura Excel
        $cardNumber = trim($row['Numero'] ?? '');
        $playerName = trim($row['Player'] ?? '');
        $numberedValue = trim($row['NUMBERED /'] ?? ''); // Usa NUMBERED / per il valore
        $isNumbered = !empty($numberedValue);
        $isRookie = !empty(trim($row['ROOKIE'] ?? ''));
        $teamName = trim($row['Team'] ?? '');
        $rarity = trim($row['Rarity'] ?? 'Base Common');
        $rarityVariation = trim($row['Rarity Variation'] ?? '');
        $brand = strtoupper(trim($row['BRAND'] ?? ''));
        $setName = trim($row['SET'] ?? '');
        $year = trim($row['YEAR'] ?? '');
        
        // Campi boolean per le nuove caratteristiche
        $isAutograph = !empty(trim($row['AUTOGRAPH'] ?? ''));
        $isRelic = !empty(trim($row['RELIC'] ?? ''));
        $isOnCardAuto = !empty(trim($row['ON CARD AUTO'] ?? ''));
        $isJewel = !empty(trim($row['JEWEL'] ?? ''));
        $isBooklet = !empty(trim($row['BOOKLET'] ?? ''));
        $isMultiPlayerDual = !empty(trim($row['MULTI PLAYER - DUAL'] ?? ''));
        $isMultiPlayerTriple = !empty(trim($row['MULTI PLAYER - TRIPLE'] ?? ''));
        $isMultiPlayerQuad = !empty(trim($row['MULTI PLAYER - QUAD'] ?? ''));
        
        // Crea o trova la lega (con cache)
        $league = $this->getOrCreateLeague($teamName, $dryRun);
        
        // Crea o trova la squadra (con cache)
        $team = $this->getOrCreateTeam($teamName, $league, $dryRun);
        
        // Crea o trova il giocatore (con cache)
        $player = $this->getOrCreatePlayer($playerName, $team, $dryRun);
        
        // Crea o trova il set (con cache)
        $cardSet = $this->getOrCreateCardSet($brand, $setName, $year, $category, $dryRun);
        
        // Crea la carta
        $this->createCardModel($row, $category, $cardSet, $player, $team, $league, $dryRun, $rowNumber);
    }

    /**
     * Crea o trova una lega (con cache)
     */
    private function getOrCreateLeague($teamName, $dryRun = false)
    {
        $leagueName = $this->teamLeagueMap[$teamName] ?? 'Unknown League';
        
        if (isset($this->cache['leagues'][$leagueName])) {
            return $this->cache['leagues'][$leagueName];
        }

        $league = League::where('name', $leagueName)->first();
        
        if (!$league) {
            if (!$dryRun) {
                $league = League::firstOrCreate(
                    ['name' => $leagueName],
                    [
                        'slug' => Str::slug($leagueName),
                        'country' => $this->getCountryFromLeague($leagueName),
                        'is_active' => true,
                    ]
                );
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

    /**
     * Crea o trova una squadra (con cache)
     */
    private function getOrCreateTeam($teamName, $league, $dryRun = false)
    {
        if (isset($this->cache['teams'][$teamName])) {
            return $this->cache['teams'][$teamName];
        }

        $team = Team::where('name', $teamName)->first();
        
        if (!$team) {
            if (!$dryRun) {
                // Crea uno slug unico che include la lega per evitare conflitti
                $uniqueSlug = Str::slug($teamName . ' ' . $league->name);
                
                $team = Team::firstOrCreate(
                    ['slug' => $uniqueSlug],
                    [
                        'name' => $teamName,
                        'league_id' => $league->id,
                        'is_active' => true,
                    ]
                );
            } else {
                $team = (object) [
                    'id' => 1,
                    'name' => $teamName,
                    'slug' => Str::slug($teamName),
                    'league_id' => $league->id,
                ];
            }
        }

        $this->cache['teams'][$teamName] = $team;
        return $team;
    }

    /**
     * Crea o trova un giocatore (con cache)
     */
    private function getOrCreatePlayer($playerName, $team, $dryRun = false)
    {
        $cacheKey = $playerName . '_' . $team->id;
        
        if (isset($this->cache['players'][$cacheKey])) {
            return $this->cache['players'][$cacheKey];
        }

        $player = Player::where('name', $playerName)
                       ->where('team_id', $team->id)
                       ->first();
        
        if (!$player) {
            if (!$dryRun) {
                // Crea uno slug unico combinando nome e team
                $uniqueSlug = Str::slug($playerName . ' ' . $team->name);
                $player = Player::firstOrCreate(
                    ['slug' => $uniqueSlug],
                    [
                        'name' => $playerName,
                        'team_id' => $team->id,
                        'is_active' => true,
                    ]
                );
            } else {
                $player = (object) [
                    'id' => 1,
                    'name' => $playerName,
                    'slug' => Str::slug($playerName . ' ' . $team->name),
                    'team_id' => $team->id,
                ];
            }
        }

        $this->cache['players'][$cacheKey] = $player;
        return $player;
    }

    /**
     * Crea o trova un set (con cache)
     */
    private function getOrCreateCardSet($brand, $setName, $year, $category, $dryRun = false)
    {
        $cacheKey = $brand . '_' . $setName . '_' . $year;
        
        if (isset($this->cache['card_sets'][$cacheKey])) {
            return $this->cache['card_sets'][$cacheKey];
        }

        $cardSet = CardSet::where('brand', $brand)
                         ->where('name', $setName)
                         ->where('year', $year)
                         ->first();
        
        if (!$cardSet) {
            if (!$dryRun) {
                $cardSet = CardSet::firstOrCreate(
                    [
                        'name' => $setName,
                        'brand' => $brand,
                        'year' => $year,
                    ],
                    [
                        'category_id' => $category->id,
                        'slug' => Str::slug($brand . ' ' . $setName . ' ' . $year),
                        'is_active' => true,
                    ]
                );
            } else {
                $cardSet = (object) [
                    'id' => 1,
                    'name' => $setName,
                    'slug' => Str::slug($brand . ' ' . $setName . ' ' . $year),
                    'brand' => $brand,
                    'year' => $year,
                    'category_id' => $category->id,
                ];
            }
        }

        $this->cache['card_sets'][$cacheKey] = $cardSet;
        return $cardSet;
    }

    /**
     * Crea un modello di carta
     */
    private function createCardModel($row, $category, $cardSet, $player, $team, $league, $dryRun = false, $rowNumber = null)
    {
        $cardNumber = trim($row['Numero'] ?? '');
        $playerName = trim($row['Player'] ?? '');
        $numberedValue = trim($row['NUMBERED /'] ?? ''); // Usa NUMBERED / per il valore
        $isNumbered = !empty($numberedValue);
        $isRookie = !empty(trim($row['ROOKIE'] ?? ''));
        $rarity = trim($row['Rarity'] ?? 'Base Common');
        $rarityVariation = trim($row['Rarity Variation'] ?? '');
        $year = trim($row['YEAR'] ?? '');
        
        // Campi boolean per le nuove caratteristiche
        $isAutograph = !empty(trim($row['AUTOGRAPH'] ?? ''));
        $isRelic = !empty(trim($row['RELIC'] ?? ''));
        $isOnCardAuto = !empty(trim($row['ON CARD AUTO'] ?? ''));
        $isJewel = !empty(trim($row['JEWEL'] ?? ''));
        $isBooklet = !empty(trim($row['BOOKLET'] ?? ''));
        $isMultiPlayerDual = !empty(trim($row['MULTI PLAYER - DUAL'] ?? ''));
        $isMultiPlayerTriple = !empty(trim($row['MULTI PLAYER - TRIPLE'] ?? ''));
        $isMultiPlayerQuad = !empty(trim($row['MULTI PLAYER - QUAD'] ?? ''));

        // Genera il nome della carta
        $cardName = $playerName;
        if ($isRookie) {
            $cardName .= ' (RC)';
        }
        if ($isNumbered && $numberedValue) {
            $cardName .= ' #' . $numberedValue;
        }

        if (!$dryRun) {
            // Crea uno slug unico che include tutte le caratteristiche per distinguere le varianti
            $slugParts = [
                $cardName,
                $cardSet->brand,
                $cardSet->name,
                $cardNumber,
                $rarity,
                $rarityVariation
            ];
            
            // Aggiungi caratteristiche speciali allo slug
            if ($isRookie) $slugParts[] = 'rookie';
            if ($isAutograph) $slugParts[] = 'autograph';
            if ($isRelic) $slugParts[] = 'relic';
            if ($isOnCardAuto) $slugParts[] = 'on-card-auto';
            if ($isJewel) $slugParts[] = 'jewel';
            if ($isBooklet) $slugParts[] = 'booklet';
            if ($isMultiPlayerDual) $slugParts[] = 'dual-player';
            if ($isMultiPlayerTriple) $slugParts[] = 'triple-player';
            if ($isMultiPlayerQuad) $slugParts[] = 'quad-player';
            
            // Crea uno slug più corto per evitare problemi di lunghezza
            $shortSlug = Str::slug($cardName . ' ' . $cardSet->brand . ' ' . $cardNumber);
            
            // Se lo slug è troppo lungo, usa solo il primo giocatore e il set
            if (strlen($shortSlug) > 100) {
                $firstPlayer = explode('/', $playerName)[0];
                $shortSlug = Str::slug($firstPlayer . ' ' . $cardSet->brand . ' ' . $cardNumber);
            }
            
            // Crea un hash univoco basato sui dati della carta
            $uniqueData = json_encode([
                'player' => $playerName,
                'team' => $team->name,
                'set' => $cardSet->name,
                'brand' => $cardSet->brand,
                'number' => $cardNumber,
                'rarity' => $rarity,
                'rarity_variation' => $rarityVariation,
                'rookie' => $isRookie,
                'autograph' => $isAutograph,
                'relic' => $isRelic,
                'on_card_auto' => $isOnCardAuto,
                'jewel' => $isJewel,
                'booklet' => $isBooklet,
                'dual' => $isMultiPlayerDual,
                'triple' => $isMultiPlayerTriple,
                'quad' => $isMultiPlayerQuad,
                'row' => $rowNumber
            ]);
            
            $uniqueHash = substr(md5($uniqueData), 0, 8);
            $uniqueSlug = $shortSlug . '-' . $uniqueHash;
            
            CardModel::firstOrCreate(
                ['slug' => $uniqueSlug],
                [
                    'category_id' => $category->id,
                    'card_set_id' => $cardSet->id,
                    'player_id' => $player->id,
                    'team_id' => $team->id,
                    'league_id' => $league->id,
                    'name' => $cardName,
                    'set_name' => $cardSet->name,
                    'year' => $year, // Ora è stringa per supportare "1967/68"
                    'rarity' => $this->normalizeRarity($rarity),
                    'rarity_variation' => $rarityVariation,
                    'card_number' => $numberedValue, // Usa il valore da NUMBERED /
                    'card_number_in_set' => $cardNumber,
                    'is_rookie' => $isRookie,
                    'is_autograph' => $isAutograph,
                    'is_relic' => $isRelic,
                    'is_on_card_auto' => $isOnCardAuto,
                    'is_jewel' => $isJewel,
                    'is_booklet' => $isBooklet,
                    'is_multi_player_dual' => $isMultiPlayerDual,
                    'is_multi_player_triple' => $isMultiPlayerTriple,
                    'is_multi_player_quad' => $isMultiPlayerQuad,
                    'is_active' => true,
                ]
            );
        }
    }

    /**
     * Normalizza la rarità
     */
    private function normalizeRarity($rarity)
    {
        $rarityMap = [
            'Base Common' => 'common',
            'Base Uncommon' => 'uncommon',
            'Base Rare' => 'rare',
            'Insert' => 'rare',
            'Parallel' => 'rare',
            'Autograph' => 'rare',
            'Relic' => 'rare',
            'Patch' => 'rare',
            'Serial Numbered' => 'rare',
            'Super Rare' => 'mythic',
            'Ultra Rare' => 'mythic',
        ];

        return $rarityMap[$rarity] ?? 'common';
    }

    /**
     * Ottiene il paese dalla lega
     */
    private function getCountryFromLeague($leagueName)
    {
        $leagueCountries = [
            'Serie A' => 'Italy',
            'Premier League' => 'England',
            'La Liga' => 'Spain',
            'Bundesliga' => 'Germany',
            'Ligue 1' => 'France',
            'MLS' => 'USA',
            'Austrian Bundesliga' => 'Austria',
            'Belgian Pro League' => 'Belgium',
            'Unknown League' => 'Unknown',
        ];

        return $leagueCountries[$leagueName] ?? 'Unknown';
    }
}