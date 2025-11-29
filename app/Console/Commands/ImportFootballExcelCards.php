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
        $sanitizedTeamName = $this->sanitizeText($teamName);
        if (isset($this->cache['teams'][$sanitizedTeamName])) {
            return $this->cache['teams'][$sanitizedTeamName];
        }

        $team = Team::where('name', $sanitizedTeamName)->first();
        
        if (!$team) {
            if (!$dryRun) {
                // Crea uno slug unico che include la lega per evitare conflitti
                $uniqueSlug = Str::slug($sanitizedTeamName . ' ' . $league->name);
                
                $team = Team::firstOrCreate(
                    ['slug' => $uniqueSlug],
                    [
                        'name' => $sanitizedTeamName,
                        'league_id' => $league->id,
                        'is_active' => true,
                    ]
                );
            } else {
                $team = (object) [
                    'id' => 1,
                    'name' => $sanitizedTeamName,
                    'slug' => Str::slug($sanitizedTeamName),
                    'league_id' => $league->id,
                ];
            }
        }

        $this->cache['teams'][$sanitizedTeamName] = $team;
        return $team;
    }

    /**
     * Crea o trova un giocatore (con cache)
     */
    private function getOrCreatePlayer($playerName, $team, $dryRun = false)
    {
        $sanitizedName = $this->sanitizeText($playerName);
        $cacheKey = $sanitizedName . '_' . $team->id;
        
        if (isset($this->cache['players'][$cacheKey])) {
            return $this->cache['players'][$cacheKey];
        }

        $player = Player::where('name', $sanitizedName)
                       ->where('team_id', $team->id)
                       ->first();
        
        if (!$player) {
            if (!$dryRun) {
                // Crea uno slug unico combinando nome e team
                $uniqueSlug = Str::slug($sanitizedName . ' ' . $team->name);
                $player = Player::firstOrCreate(
                    ['slug' => $uniqueSlug],
                    [
                        'name' => $sanitizedName,
                        'team_id' => $team->id,
                        'is_active' => true,
                    ]
                );
            } else {
                $player = (object) [
                    'id' => 1,
                    'name' => $sanitizedName,
                    'slug' => Str::slug($sanitizedName . ' ' . $team->name),
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
        $sanitizedName = $this->sanitizeText($setName);
        $cacheKey = $brand . '_' . $sanitizedName . '_' . $year;
        
        if (isset($this->cache['card_sets'][$cacheKey])) {
            return $this->cache['card_sets'][$cacheKey];
        }

        $sanitizedName = $this->sanitizeText($setName);
        $cardSet = CardSet::where('brand', $brand)
                         ->where('name', $sanitizedName)
                         ->where('year', $year)
                         ->first();
        
        if (!$cardSet) {
            if (!$dryRun) {
                $cardSet = CardSet::firstOrCreate(
                    [
                        'name' => $sanitizedName,
                        'brand' => $brand,
                        'year' => $year,
                    ],
                    [
                        'category_id' => $category->id,
                        'slug' => Str::slug($brand . ' ' . $sanitizedName . ' ' . $year),
                        'is_active' => true,
                    ]
                );
            } else {
                $cardSet = (object) [
                    'id' => 1,
                    'name' => $sanitizedName,
                    'slug' => Str::slug($brand . ' ' . $sanitizedName . ' ' . $year),
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
        $cardNumber = $this->sanitizeText($row['Numero'] ?? '');
        $playerName = $this->sanitizeText($row['Player'] ?? '');
        $numberedValue = $this->sanitizeText($row['NUMBERED /'] ?? ''); // Usa NUMBERED / per il valore
        $isNumbered = !empty($numberedValue);
        $isRookie = !empty($this->sanitizeText($row['ROOKIE'] ?? ''));
        $rarity = $this->preserveRarity($this->sanitizeText($row['Rarity'] ?? 'Base Common'));
        $rarityVariation = $this->sanitizeText($row['Rarity Variation'] ?? '');
        $year = $this->sanitizeText($row['YEAR'] ?? '');
        
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
                    'name' => $this->sanitizeText($cardName),
                    'set_name' => $this->sanitizeText($cardSet->name),
                    'year' => $this->sanitizeText($year), // Ora è stringa per supportare "1967/68"
                    'rarity' => $this->preserveRarity($rarity),
                    'rarity_variation' => $this->sanitizeText($rarityVariation),
                    'card_number' => $this->sanitizeText($cardNumber), // Usa Numero (prima colonna)
                    'card_number_in_set' => !empty($numberedValue) ? $this->sanitizeText($numberedValue) : null, // Usa NUMBERED / se presente
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
     * Preserva la rarità originale dal CSV
     */
    private function preserveRarity($rarity)
    {
        // Pulisci la rarità ma mantieni il valore originale
        $cleanRarity = trim($rarity);
        
        // Se è vuota o contiene solo "-", usa un default
        if (empty($cleanRarity) || $cleanRarity === '-') {
            return 'Base card';
        }
        
        // Mantieni la rarità originale dal CSV esattamente come è
        return $cleanRarity;
    }

    /**
     * Sanitizza il testo per il database
     */
    private function sanitizeText($text)
    {
        if (empty($text)) {
            return $text;
        }
        
        // Converti encoding problematici
        $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Sostituisci caratteri problematici
        $text = str_replace('°', '°', $text); // Gradi
        $text = str_replace('', '', $text); // Caratteri non validi
        
        // Pulisci e trim
        $text = trim($text);
        
        return $text;
    }

    /**
     * Normalizza la rarità (DEPRECATED - ora usiamo preserveRarity)
     */
    private function normalizeRarity($rarity)
    {
        $rarityMap = [
            // Base cards - Common
            'Base card' => 'common',
            'Base' => 'common',
            'Base Set' => 'common',
            'Base set' => 'common',
            'Base Set - Terrace' => 'common',
            'Base Set - Field Level' => 'common',
            'Base Set - Mezzanine' => 'common',
            'Base Terrace' => 'common',
            'Base Mezzanine' => 'common',
            'Base Field Level' => 'common',
            'Base Prizm' => 'common',
            'Terrace' => 'common',
            'Mezzanine' => 'common',
            'Field Level' => 'common',
            'Base Common' => 'common',
            'Base Portrait' => 'common',
            'Base Optic' => 'common',
            'Optic Base' => 'common',
            'Base Donruss Premier League' => 'common',
            'Base Uncommon' => 'uncommon',
            
            // Autographs - Rare
            'Autograph' => 'rare',
            'Autographs' => 'rare',
            'Base Autograph' => 'rare',
            'Base Autographs' => 'rare',
            'Chrome Autograph' => 'rare',
            'Autograph Mosaic' => 'rare',
            'Signatures' => 'rare',
            'Select Signatures' => 'rare',
            'Pitchside Signatures' => 'rare',
            'Score Signatures' => 'rare',
            'Dual Autographs' => 'rare',
            'Dual Signatures' => 'rare',
            'Triple Autographs' => 'rare',
            'Trio Signatures' => 'rare',
            'Quad Signatures' => 'rare',
            'Signature Moments' => 'rare',
            'Marquee Signatures' => 'rare',
            'Modern Marks' => 'rare',
            'Heralded Signatures' => 'rare',
            'Shadowbox Signatures' => 'rare',
            'Signature Moments Prizms' => 'rare',
            'Dual Signatures Prizms' => 'rare',
            'Quad Signatures Prizms' => 'rare',
            'Trio Signatures Prizms' => 'rare',
            'Signatures Prizms' => 'rare',
            'Immaculate Autographs' => 'rare',
            'All-Time Greats Autographs' => 'rare',
            'Historic Signatures' => 'rare',
            'International Ink' => 'rare',
            'Ink' => 'rare',
            'Celebration Signatures' => 'rare',
            'Signature Series' => 'rare',
            'The Beautiful Game Autograph' => 'rare',
            'Beautiful Game Dual Autographs' => 'rare',
            'Illustrious Ink' => 'rare',
            
            // Relics - Rare
            'Relics' => 'rare',
            'Select Swatches' => 'rare',
            'Jumbo Swatches' => 'rare',
            'Select Memorabilia' => 'rare',
            'Camp Nou Seat Relic' => 'rare',
            'Camp Nou Jersey Relic' => 'rare',
            'The Greatest Maradona Boot Relics' => 'rare',
            'Argentine Tango Relics' => 'rare',
            'Display Decadence Relics' => 'rare',
            'Dreamworthy Double Act Dual Patch' => 'rare',
            'The Kings Colors Relics' => 'rare',
            
            // Special/Insert cards - Rare
            'Insert' => 'rare',
            'Parallel' => 'rare',
            'Serial Numbered' => 'rare',
            'Patch' => 'rare',
            'Stained Glass' => 'rare',
            'Visionary' => 'rare',
            'Artistic Impressions' => 'rare',
            'Artistry' => 'rare',
            'Ageless Alchemy' => 'rare',
            'Legends' => 'rare',
            'D10S' => 'rare',
            'The Man' => 'rare',
            'White Noise' => 'rare',
            'White Night' => 'rare',
            'Rainbow Flick' => 'rare',
            'Metamorphosis' => 'rare',
            'Color Wheel' => 'rare',
            'Camino' => 'rare',
            'Black Color Blast' => 'rare',
            'Manga' => 'rare',
            'National Pride' => 'rare',
            'National Landmarks' => 'rare',
            'Moments' => 'rare',
            'Immaculate Moments' => 'rare',
            'Marks of Greatness' => 'rare',
            'Historical Significance' => 'rare',
            'Intergalactic' => 'rare',
            'Spanning Time' => 'rare',
            'Milestones' => 'rare',
            'Forever Moments' => 'rare',
            'Dreamworthy Duo' => 'rare',
            'Bookends' => 'rare',
            'Bona Fide Baller' => 'rare',
            'Argentine Tango' => 'rare',
            'Argentina Legends' => 'rare',
            'Phenomenon' => 'rare',
            'Scorers Club' => 'rare',
            'Breakthrough' => 'rare',
            'Unstoppable' => 'rare',
            'Select Future' => 'rare',
            'Equalizers' => 'rare',
            'Will to Win' => 'rare',
            'Base Parallels' => 'rare',
            
            // New rarities from Elenco2.csv
            'Greats' => 'rare',
            'Snapshots' => 'rare',
            'Heroes' => 'rare',
            'Full Bleed' => 'rare',
            'Heritage' => 'rare',
            'Timeless' => 'rare',
            'Majestic' => 'rare',
            'Legends Series' => 'rare',
            'Legendary Talents' => 'rare',
            'Fileteado' => 'rare',
            'Elite' => 'rare',
            'Rated Rookies' => 'rare',
            'Landscape' => 'rare',
            'Star Quality' => 'rare',
            'Current Stars' => 'rare',
            'Prestige' => 'rare',
            'Maestro' => 'rare',
            'Superstars' => 'rare',
            'Illusions Serie A' => 'rare',
            'Illusions Premier League' => 'rare',
            'Certified Serie A' => 'rare',
            'Contenders Historic Rookie Ticket La Liga' => 'rare',
            'Then & Now' => 'rare',
            'Golden Hour' => 'rare',
            
            // Super Rare - Mythic
            'Super Rare' => 'mythic',
            'Ultra Rare' => 'mythic',
            'Base 125 Legacy' => 'mythic',
            'Supernova' => 'mythic',
            'Ultimate Stage Chrome' => 'mythic',
            'Sapphire Selections' => 'mythic',
            'Super Six Booklet' => 'mythic',
            'Super Six Autographs Booklet' => 'mythic',
            'UEFA Players of the Year Autographed Book Superfractors' => 'mythic',
            'Superior Swatch Signatures Gold' => 'mythic',
            'Chrome Quad Autographs Pundits' => 'mythic',
            'Ultimate Showdown' => 'mythic',
            'Rule the World' => 'mythic',
            'Global Domination Triple Autographs' => 'mythic',
            'Masterminds Autographs' => 'mythic',
            'Chrome Quad Autographs' => 'mythic',
            'The Moments' => 'mythic',
            'Pristine Borders Autograph Relic' => 'mythic',
            'Albärt' => 'mythic',
        ];

        // If not in direct mapping, use pattern matching
        if (!isset($rarityMap[$rarity])) {
            $lowerRarity = strtolower($rarity);
            
            // Mythic patterns (check first)
            if (strpos($lowerRarity, 'supernova') !== false ||
                strpos($lowerRarity, 'ultimate') !== false ||
                strpos($lowerRarity, 'sapphire') !== false ||
                strpos($lowerRarity, 'superfractor') !== false ||
                strpos($lowerRarity, 'quad autograph') !== false ||
                strpos($lowerRarity, 'booklet') !== false ||
                strpos($lowerRarity, 'super six') !== false ||
                strpos($lowerRarity, 'global domination') !== false ||
                strpos($lowerRarity, 'masterminds') !== false ||
                strpos($lowerRarity, 'rule the world') !== false ||
                strpos($lowerRarity, 'ultimate showdown') !== false) {
                return 'mythic';
            }
            
            // Autograph patterns
            if (strpos($lowerRarity, 'autograph') !== false || 
                strpos($lowerRarity, 'signature') !== false ||
                strpos($lowerRarity, 'ink') !== false) {
                return 'rare';
            }
            
            // Relic patterns
            if (strpos($lowerRarity, 'relic') !== false || 
                strpos($lowerRarity, 'swatch') !== false ||
                strpos($lowerRarity, 'memorabilia') !== false ||
                strpos($lowerRarity, 'patch') !== false) {
                return 'rare';
            }
            
            // Base patterns (but check for exceptions)
            if (strpos($lowerRarity, 'base') !== false) {
                // Special cases where "Base" is actually rare
                if (strpos($lowerRarity, 'base rare') !== false || 
                    strpos($lowerRarity, 'base autograph') !== false) {
                    return 'rare';
                }
                return 'common';
            }
            
            // Insert/Parallel patterns
            if (strpos($lowerRarity, 'insert') !== false || 
                strpos($lowerRarity, 'parallel') !== false ||
                strpos($lowerRarity, 'numbered') !== false) {
                return 'rare';
            }
            
            // Special/Insert patterns
            if (strpos($lowerRarity, 'prizm') !== false ||
                strpos($lowerRarity, 'chrome') !== false ||
                strpos($lowerRarity, 'mosaic') !== false ||
                strpos($lowerRarity, 'select') !== false ||
                strpos($lowerRarity, 'optic') !== false ||
                strpos($lowerRarity, 'donruss') !== false ||
                strpos($lowerRarity, 'prestige') !== false ||
                strpos($lowerRarity, 'elite') !== false ||
                strpos($lowerRarity, 'certified') !== false ||
                strpos($lowerRarity, 'illusions') !== false) {
                return 'rare';
            }
            
            // Default to common for unknown patterns
            return 'common';
        }

        return $rarityMap[$rarity];
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