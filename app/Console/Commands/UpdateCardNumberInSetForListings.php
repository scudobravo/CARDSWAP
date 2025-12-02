<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardModel;
use App\Models\CardListing;
use App\Models\Player;
use App\Models\Team;
use App\Models\CardSet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UpdateCardNumberInSetForListings extends Command
{
    protected $signature = 'update:card-number-in-set-for-listings 
                            {--file= : Path to CSV file}
                            {--dry-run : Show what would be updated without making changes}
                            {--category= : Category ID (1=Calcio, 2=Basket, etc.)}';

    protected $description = 'Update card_number_in_set for cards that have active listings, based on CSV data';

    private $updateCount = 0;
    private $skipCount = 0;
    private $errorCount = 0;
    private $notFoundCount = 0;

    public function handle()
    {
        $file = $this->option('file');
        $dryRun = $this->option('dry-run');
        $categoryId = $this->option('category');

        if (!$file) {
            $this->error('❌ Please specify a CSV file with --file option');
            return 1;
        }

        if (!file_exists($file)) {
            $this->error("❌ File not found: {$file}");
            return 1;
        }

        $this->info("🚀 Starting card_number_in_set update for cards with active listings...");
        $this->info("📁 File: {$file}");
        $this->info("⚠️  " . ($dryRun ? 'DRY RUN MODE - No changes will be made' : 'LIVE MODE - Changes will be applied'));

        try {
            // Get all cards with active listings
            $query = CardModel::query()
                ->whereHas('cardListings', function($q) {
                    $q->whereIn('status', ['active', 'pending_review', 'approved']);
                });

            if ($categoryId) {
                $query->where('category_id', $categoryId);
            }

            $cardsWithListings = $query->get();
            $this->info("📊 Found {$cardsWithListings->count()} cards with active listings");

            // Load CSV data into memory
            $csvData = $this->loadCsvData($file);
            $this->info("📊 Loaded " . count($csvData) . " rows from CSV");

            // Update each card
            foreach ($cardsWithListings as $card) {
                $this->updateCardFromCsv($card, $csvData, $dryRun);
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }

        $this->info("\n📊 Summary:");
        $this->info("✅ Records updated: {$this->updateCount}");
        $this->info("⏭️  Records skipped: {$this->skipCount}");
        $this->info("❌ Errors: {$this->errorCount}");
        $this->info("🔍 Not found in CSV: {$this->notFoundCount}");

        return 0;
    }

    private function loadCsvData($file)
    {
        $handle = fopen($file, 'r');
        if (!$handle) {
            throw new \Exception("Cannot open file: {$file}");
        }

        // Read header
        $header = fgetcsv($handle);
        if (!$header) {
            throw new \Exception("Cannot read header from file");
        }

        // Find column indices
        $numeroIndex = array_search('Numero', $header);
        $playerIndex = array_search('Player', $header);
        $numberedIndex = array_search('NUMBERED /', $header);
        $teamIndex = array_search('Team', $header);
        $brandIndex = array_search('BRAND', $header);
        $setIndex = array_search('SET', $header);
        $yearIndex = array_search('YEAR', $header);

        if ($numeroIndex === false || $playerIndex === false || $numberedIndex === false) {
            throw new \Exception("Required columns not found in CSV");
        }

        $data = [];
        $rowNum = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            
            $numero = trim($row[$numeroIndex] ?? '');
            $playerName = trim($row[$playerIndex] ?? '');
            $numbered = trim($row[$numberedIndex] ?? '');
            $teamName = trim($row[$teamIndex] ?? '');
            $brand = strtoupper(trim($row[$brandIndex] ?? ''));
            $setName = trim($row[$setIndex] ?? '');
            $year = trim($row[$yearIndex] ?? '');

            if (empty($playerName) || empty($numero)) {
                continue;
            }

            // Create a unique key for matching
            $key = $this->createMatchKey($playerName, $numero, $teamName, $brand, $setName, $year);
            
            $data[$key] = [
                'numero' => $numero,
                'player_name' => $playerName,
                'numbered' => !empty($numbered) ? $numbered : null,
                'team_name' => $teamName,
                'brand' => $brand,
                'set_name' => $setName,
                'year' => $year,
            ];
        }

        fclose($handle);
        return $data;
    }

    private function createMatchKey($playerName, $numero, $teamName, $brand, $setName, $year)
    {
        // Normalize for matching
        $playerName = strtolower(trim($playerName));
        $numero = strtolower(trim($numero));
        $teamName = strtolower(trim($teamName ?? ''));
        $brand = strtolower(trim($brand ?? ''));
        $setName = strtolower(trim($setName ?? ''));
        $year = strtolower(trim($year ?? ''));

        // Try multiple key combinations for better matching
        $keys = [];
        
        // Primary key: player + numero
        $keys[] = "{$playerName}|{$numero}";
        
        // With team
        if (!empty($teamName)) {
            $keys[] = "{$playerName}|{$numero}|{$teamName}";
        }
        
        // With set
        if (!empty($setName)) {
            $keys[] = "{$playerName}|{$numero}|{$setName}";
        }
        
        // With brand and set
        if (!empty($brand) && !empty($setName)) {
            $keys[] = "{$playerName}|{$numero}|{$brand}|{$setName}";
        }

        return $keys[0]; // Return primary key
    }

    private function updateCardFromCsv($card, $csvData, $dryRun)
    {
        try {
            // Load relationships
            $card->load(['player', 'team', 'cardSet']);

            if (!$card->player) {
                $this->skipCount++;
                return;
            }

            $playerName = strtolower(trim($card->player->name));
            $numero = strtolower(trim($card->card_number ?? ''));
            $teamName = $card->team ? strtolower(trim($card->team->name)) : '';
            $brand = $card->cardSet ? strtolower(trim($card->cardSet->brand ?? '')) : '';
            $setName = $card->cardSet ? strtolower(trim($card->cardSet->name ?? '')) : '';
            $year = strtolower(trim($card->year ?? ''));

            // Try to find matching CSV row
            $csvRow = null;
            
            // Try multiple matching strategies
            $matchKeys = [
                "{$playerName}|{$numero}",
            ];
            
            if (!empty($teamName)) {
                $matchKeys[] = "{$playerName}|{$numero}|{$teamName}";
            }
            
            if (!empty($setName)) {
                $matchKeys[] = "{$playerName}|{$numero}|{$setName}";
            }
            
            if (!empty($brand) && !empty($setName)) {
                $matchKeys[] = "{$playerName}|{$numero}|{$brand}|{$setName}";
            }

            foreach ($matchKeys as $key) {
                if (isset($csvData[$key])) {
                    $csvRow = $csvData[$key];
                    break;
                }
            }

            // If not found, try fuzzy matching on player name
            if (!$csvRow) {
                foreach ($csvData as $csvKey => $csvValue) {
                    $csvPlayerName = strtolower(trim($csvValue['player_name']));
                    $csvNumero = strtolower(trim($csvValue['numero']));
                    
                    // Check if player name matches (partial) and numero matches
                    if (str_contains($csvPlayerName, $playerName) || str_contains($playerName, $csvPlayerName)) {
                        if ($csvNumero === $numero) {
                            $csvRow = $csvValue;
                            break;
                        }
                    }
                }
            }

            if (!$csvRow) {
                $this->notFoundCount++;
                if ($this->notFoundCount <= 10) {
                    $this->warn("🔍 Not found in CSV: {$card->name} (ID: {$card->id}) - Player: {$card->player->name}, Numero: {$card->card_number}");
                }
                return;
            }

            $newNumbered = $csvRow['numbered'];

            // Skip if already correct
            if ($card->card_number_in_set === $newNumbered) {
                $this->skipCount++;
                return;
            }

            if ($dryRun) {
                $this->info("🔄 Would update: {$card->name} (ID: {$card->id})");
                $this->info("   - Current card_number_in_set: " . ($card->card_number_in_set ?? 'NULL'));
                $this->info("   - New card_number_in_set: " . ($newNumbered ?? 'NULL'));
                $this->updateCount++;
            } else {
                // Update the card model
                $card->card_number_in_set = $newNumbered;
                $card->save();

                $this->info("✅ Updated: {$card->name} (ID: {$card->id}) - card_number_in_set: " . ($newNumbered ?? 'NULL'));
                $this->updateCount++;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error updating card {$card->id}: " . $e->getMessage());
            $this->errorCount++;
        }
    }
}

