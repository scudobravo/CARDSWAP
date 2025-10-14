<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\CardSet;
use App\Models\CardModel;
use App\Models\Player;
use App\Models\Team;
use App\Models\GradingCompany;
use App\Models\GradingScore;

class ImportCardData extends Command
{
    protected $signature = 'import:card-data {--force : Forza l\'importazione anche se i dati esistono già}';
    protected $description = 'Importa i dati delle carte da calcio dai file CSV nella directory IMPORT';

    private $categoryId;
    private $leagueId;
    private $gradingCompanies = [];
    private $gradingScores = [];
    private $teams = [];
    private $players = [];
    private $cardSets = [];

    public function handle()
    {
        $this->info('🚀 Inizio importazione dati carte da calcio...');
        
        try {
            // Verifica che la categoria calcio esista
            $this->setupCategoryAndLeague();
            
            // Importa i dati di base (grading companies, scores, conditions)
            $this->importGradingCompanies();
            $this->importGradingScores();
            
            // Importa i set e le carte
            $this->importCardSets();
            $this->importCardModels();
            
            $this->info('✅ Importazione completata con successo!');
            
        } catch (\Exception $e) {
            $this->error('❌ Errore durante l\'importazione: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }

    private function setupCategoryAndLeague()
    {
        // Crea o trova la categoria "Calcio"
        $category = DB::table('categories')->where('name', 'Calcio')->first();
        if (!$category) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => 'Calcio',
                'slug' => 'calcio',
                'description' => 'Carte da calcio',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $categoryId = $category->id;
        }
        
        $this->categoryId = $categoryId;
        
        // Crea o trova la lega "Serie A" (o una lega generica)
        $league = DB::table('leagues')->where('name', 'Serie A')->first();
        if (!$league) {
            $leagueId = DB::table('leagues')->insertGetId([
                'name' => 'Serie A',
                'slug' => 'serie-a',
                'description' => 'Lega italiana di calcio',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $leagueId = $league->id;
        }
        
        $this->leagueId = $leagueId;
        
        $this->info("📁 Categoria ID: {$this->categoryId}, Lega ID: {$this->leagueId}");
    }

    private function importGradingCompanies()
    {
        $this->info('📊 Importazione grading companies...');
        
        $csvPath = base_path('IMPORT/Grading Company.csv');
        $data = $this->readCsv($csvPath);
        
        foreach ($data as $row) {
            $name = trim($row[0]);
            
            // Salta header, valori vuoti e punti
            if (empty($name) || $name === 'GRADING COMPANY' || $name === '.' || $name === '..') {
                continue;
            }
            
            $slug = Str::slug($name);
            
            $company = DB::table('grading_companies')->where('slug', $slug)->first();
            if (!$company) {
                $id = DB::table('grading_companies')->insertGetId([
                    'name' => $name,
                    'slug' => $slug,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $this->gradingCompanies[$name] = $id;
                $this->line("  ✓ Aggiunta: {$name}");
            } else {
                $this->gradingCompanies[$name] = $company->id;
            }
        }
    }

    private function importGradingScores()
    {
        $this->info('📊 Importazione grading scores...');
        
        $csvPath = base_path('IMPORT/Grading Vote.csv');
        $data = $this->readCsv($csvPath);
        
        foreach ($data as $index => $row) {
            if ($index === 0) continue; // Skip header
            
            $score = floatval($row[0]);
            $description = trim($row[1]);
            
            if (empty($description)) continue;
            
            // Cerca una company di default (PSA)
            $psaId = $this->gradingCompanies['PSA'] ?? null;
            
            $gradingScore = DB::table('grading_scores')
                ->where('score', $score)
                ->where('description', $description)
                ->first();
                
            if (!$gradingScore) {
                $id = DB::table('grading_scores')->insertGetId([
                    'grading_company_id' => $psaId,
                    'score' => $score,
                    'description' => $description,
                    'is_special' => str_contains($description, 'Black Label'),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $this->gradingScores["{$score}_{$description}"] = $id;
                $this->line("  ✓ Aggiunto: {$score} - {$description}");
            } else {
                $this->gradingScores["{$score}_{$description}"] = $gradingScore->id;
            }
        }
    }

    private function importCardSets()
    {
        $this->info('📦 Importazione card sets...');
        
        $csvPath = base_path('IMPORT/Set Calcio.csv');
        
        if (!file_exists($csvPath)) {
            $this->error("File non trovato: {$csvPath}");
            return;
        }
        
        $this->info("Lettura file: {$csvPath}");
        
        $sets = [];
        $handle = fopen($csvPath, 'r');
        $rowCount = 0;
        
        if ($handle !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rowCount++;
                
                if ($rowCount === 1) continue; // Skip header
                
                if ($rowCount % 10000 === 0) {
                    $this->info("  Processate {$rowCount} righe...");
                }
                
                $brand = strtoupper(trim($row[7] ?? '')); // Normalizza in uppercase
                $setName = trim($row[8] ?? '');
                $year = intval($row[9] ?? 0);
                
                if (empty($setName) || empty($brand)) continue;
                
                $setKey = "{$brand}_{$setName}_{$year}";
                if (!isset($sets[$setKey])) {
                    $sets[$setKey] = [
                        'brand' => $brand,
                        'set_name' => $setName,
                        'year' => $year
                    ];
                }
            }
            fclose($handle);
        }
        
        $this->info("Trovati " . count($sets) . " set unici da " . $rowCount . " righe");
        
        $inserted = 0;
        foreach ($sets as $setKey => $setData) {
            $slug = Str::slug("{$setData['brand']} {$setData['set_name']} {$setData['year']}");
            
            $cardSet = DB::table('card_sets')->where('slug', $slug)->first();
            if (!$cardSet) {
                try {
                    $id = DB::table('card_sets')->insertGetId([
                        'category_id' => $this->categoryId,
                        'name' => $setData['set_name'],
                        'slug' => $slug,
                        'brand' => $setData['brand'],
                        'year' => $setData['year'],
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    $this->cardSets[$setKey] = $id;
                    $inserted++;
                    $this->line("  ✓ Aggiunto set: {$setData['brand']} {$setData['set_name']} {$setData['year']}");
                } catch (\Exception $e) {
                    $this->warn("  ⚠️ Errore inserimento set {$setKey}: " . $e->getMessage());
                }
            } else {
                $this->cardSets[$setKey] = $cardSet->id;
            }
        }
        
        $this->info("✅ Importati {$inserted} nuovi set");
    }

    private function importCardModels()
    {
        $this->info('🃏 Importazione card models...');
        
        $csvPath = base_path('IMPORT/Set Calcio.csv');
        
        if (!file_exists($csvPath)) {
            $this->error("File non trovato: {$csvPath}");
            return;
        }
        
        $batchSize = 500; // Ridotto per evitare timeout
        $batch = [];
        $imported = 0;
        $rowCount = 0;
        
        $handle = fopen($csvPath, 'r');
        if ($handle !== false) {
            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $rowCount++;
                
                if ($rowCount === 1) continue; // Skip header
                
                if ($rowCount % 5000 === 0) {
                    $this->info("  Processate {$rowCount} righe, importate {$imported} carte...");
                }
                
                $playerName = trim($row[1] ?? '');
                $teamName = trim($row[4] ?? '');
                $rarity = $this->mapRarity(trim($row[5] ?? ''));
                $brand = strtoupper(trim($row[7] ?? '')); // Normalizza in uppercase
                $setName = trim($row[8] ?? '');
                $year = intval($row[9] ?? 0);
                $cardNumber = trim($row[0] ?? '');
                $isRookie = !empty(trim($row[3] ?? ''));
                
                if (empty($playerName) || empty($teamName)) continue;
                
                try {
                    // Crea o trova il team
                    $teamId = $this->getOrCreateTeam($teamName);
                    
                    // Crea o trova il player
                    $playerId = $this->getOrCreatePlayer($playerName, $teamId);
                    
                    // Trova il card set
                    $setKey = "{$brand}_{$setName}_{$year}";
                    $cardSetId = $this->cardSets[$setKey] ?? null;
                    
                    if (!$cardSetId) {
                        $this->warn("Set non trovato: {$setKey}");
                        continue;
                    }
                    
                    $batch[] = [
                        'category_id' => $this->categoryId,
                        'name' => $playerName,
                        'slug' => Str::slug("{$playerName} {$setName} {$year}"),
                        'set_name' => $setName,
                        'year' => $year,
                        'rarity' => $rarity,
                        'card_number' => $cardNumber,
                        'card_set_id' => $cardSetId,
                        'player_id' => $playerId,
                        'team_id' => $teamId,
                        'is_rookie' => $isRookie,
                        'is_star' => str_contains($rarity, 'Star'),
                        'is_legend' => str_contains($rarity, 'Legend'),
                        'is_autograph' => str_contains($rarity, 'Autograph') || str_contains($rarity, 'Auto'),
                        'is_relic' => str_contains($rarity, 'Relic') || str_contains($rarity, 'Patch'),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    
                    if (count($batch) >= $batchSize) {
                        $this->insertBatch($batch);
                        $imported += count($batch);
                        $this->line("  📦 Importate {$imported} carte...");
                        $batch = [];
                    }
                } catch (\Exception $e) {
                    $this->warn("  ⚠️ Errore processando riga {$rowCount}: " . $e->getMessage());
                    continue;
                }
            }
            fclose($handle);
        }
        
        // Inserisci il batch finale
        if (!empty($batch)) {
            $this->insertBatch($batch);
            $imported += count($batch);
        }
        
        $this->info("✅ Importate {$imported} card models totali da {$rowCount} righe");
    }

    private function getOrCreateTeam($teamName)
    {
        if (isset($this->teams[$teamName])) {
            return $this->teams[$teamName];
        }
        
        $slug = Str::slug($teamName);
        $team = DB::table('teams')->where('slug', $slug)->first();
        
        if (!$team) {
            $id = DB::table('teams')->insertGetId([
                'league_id' => $this->leagueId,
                'name' => $teamName,
                'slug' => $slug,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $id = $team->id;
        }
        
        $this->teams[$teamName] = $id;
        return $id;
    }

    private function getOrCreatePlayer($playerName, $teamId)
    {
        if (isset($this->players[$playerName])) {
            return $this->players[$playerName];
        }
        
        $slug = Str::slug($playerName);
        $player = DB::table('players')->where('slug', $slug)->first();
        
        if (!$player) {
            $id = DB::table('players')->insertGetId([
                'team_id' => $teamId,
                'name' => $playerName,
                'slug' => $slug,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } else {
            $id = $player->id;
        }
        
        $this->players[$playerName] = $id;
        return $id;
    }

    private function mapRarity($rarity)
    {
        $rarity = strtolower(trim($rarity));
        
        $mapping = [
            'base common' => 'common',
            'base card' => 'common',
            'common' => 'common',
            'uncommon' => 'uncommon',
            'rare' => 'rare',
            'mythic' => 'mythic',
            'special' => 'special',
            'future star' => 'rare',
            'title winner' => 'rare',
            'combo card' => 'special'
        ];
        
        return $mapping[$rarity] ?? 'common';
    }

    private function insertBatch($batch)
    {
        try {
            DB::table('card_models')->insert($batch);
        } catch (\Exception $e) {
            $this->warn("Errore batch insert: " . $e->getMessage());
            // Inserisci una per una in caso di errore
            foreach ($batch as $item) {
                try {
                    DB::table('card_models')->insert($item);
                } catch (\Exception $e) {
                    $this->warn("Errore inserimento singolo: " . $e->getMessage());
                }
            }
        }
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
