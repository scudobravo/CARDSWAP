<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardModel;
use App\Models\Player;
use App\Models\Team;
use App\Models\CardSet;
use Illuminate\Support\Facades\DB;

class UpdateCardNumbersFromCsv extends Command
{
    protected $signature = 'update:card-numbers 
                            {--file= : Path to CSV file}
                            {--dry-run : Show what would be updated without making changes}
                            {--limit= : Limit number of records to process}';

    protected $description = 'Update card_number field from NUMBERED / column in CSV files';

    private $updateCount = 0;
    private $skipCount = 0;
    private $errorCount = 0;

    public function handle()
    {
        $file = $this->option('file');
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');

        if (!$file) {
            $this->error('❌ Please specify a CSV file with --file option');
            return 1;
        }

        if (!file_exists($file)) {
            $this->error("❌ File not found: {$file}");
            return 1;
        }

        $this->info("🚀 Starting card_number update from CSV...");
        $this->info("📁 File: {$file}");
        $this->info("⚠️  " . ($dryRun ? 'DRY RUN MODE - No changes will be made' : 'LIVE MODE - Changes will be applied'));

        try {
            $this->processCsvFile($file, $dryRun, $limit);
        } catch (\Exception $e) {
            $this->error("❌ Error processing file: " . $e->getMessage());
            return 1;
        }

        $this->info("\n📊 Summary:");
        $this->info("✅ Records updated: {$this->updateCount}");
        $this->info("⏭️  Records skipped: {$this->skipCount}");
        $this->info("❌ Errors: {$this->errorCount}");

        return 0;
    }

    private function processCsvFile($file, $dryRun, $limit)
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

        $this->info("📋 Header: " . implode(', ', $header));

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

        $this->info("🔍 Column mapping:");
        $this->info("  - Numero: {$numeroIndex}");
        $this->info("  - Player: {$playerIndex}");
        $this->info("  - NUMBERED /: {$numberedIndex}");
        $this->info("  - Team: " . ($teamIndex !== false ? $teamIndex : 'N/A'));
        $this->info("  - BRAND: " . ($brandIndex !== false ? $brandIndex : 'N/A'));
        $this->info("  - SET: " . ($setIndex !== false ? $setIndex : 'N/A'));
        $this->info("  - YEAR: " . ($yearIndex !== false ? $yearIndex : 'N/A'));

        $processed = 0;
        $batchSize = 100;
        $batch = [];

        while (($row = fgetcsv($handle)) !== false) {
            if ($limit && $processed >= $limit) {
                break;
            }

            $processed++;
            
            // Skip if NUMBERED / is empty
            $numberedValue = trim($row[$numberedIndex] ?? '');
            if (empty($numberedValue)) {
                $this->skipCount++;
                continue;
            }

            $numero = trim($row[$numeroIndex] ?? '');
            $playerName = trim($row[$playerIndex] ?? '');
            $teamName = trim($row[$teamIndex] ?? '');
            $brand = strtoupper(trim($row[$brandIndex] ?? ''));
            $setName = trim($row[$setIndex] ?? '');
            $year = trim($row[$yearIndex] ?? '');

            if (empty($playerName)) {
                $this->skipCount++;
                continue;
            }

            $batch[] = [
                'numero' => $numero,
                'player_name' => $playerName,
                'numbered_value' => $numberedValue,
                'team_name' => $teamName,
                'brand' => $brand,
                'set_name' => $setName,
                'year' => $year,
                'row_number' => $processed + 1
            ];

            // Process batch when it reaches batchSize
            if (count($batch) >= $batchSize) {
                $this->processBatch($batch, $dryRun);
                $batch = [];
            }

            if ($processed % 1000 === 0) {
                $this->info("📊 Processed {$processed} rows...");
            }
        }

        // Process remaining batch
        if (!empty($batch)) {
            $this->processBatch($batch, $dryRun);
        }

        fclose($handle);
        $this->info("📊 Total rows processed: {$processed}");
    }

    private function processBatch($batch, $dryRun)
    {
        foreach ($batch as $item) {
            try {
                $this->updateCardNumber($item, $dryRun);
            } catch (\Exception $e) {
                $this->error("❌ Error processing row {$item['row_number']}: " . $e->getMessage());
                $this->errorCount++;
            }
        }
    }

    private function updateCardNumber($item, $dryRun)
    {
        $numero = $item['numero'];
        $playerName = $item['player_name'];
        $numberedValue = $item['numbered_value'];
        $teamName = $item['team_name'];
        $brand = $item['brand'];
        $setName = $item['set_name'];
        $year = $item['year'];

        // Find the card model to update
        $query = CardModel::query()
            ->whereHas('player', function($q) use ($playerName) {
                $q->where('name', 'LIKE', '%' . $playerName . '%');
            })
            ->where('card_number_in_set', $numero);

        // Add additional filters if available
        if (!empty($teamName)) {
            $query->whereHas('team', function($q) use ($teamName) {
                $q->where('name', 'LIKE', '%' . $teamName . '%');
            });
        }

        if (!empty($brand) && !empty($setName)) {
            $query->whereHas('cardSet', function($q) use ($brand, $setName) {
                $q->where('brand', $brand)->where('name', 'LIKE', '%' . $setName . '%');
            });
        }

        if (!empty($year)) {
            $query->where('year', $year);
        }

        $cardModel = $query->first();

        if (!$cardModel) {
            $this->skipCount++;
            return;
        }

        // Check if card_number is already set and different
        if ($cardModel->card_number === $numberedValue) {
            $this->skipCount++;
            return;
        }

        if ($dryRun) {
            $this->info("🔄 Would update: {$cardModel->name} (ID: {$cardModel->id})");
            $this->info("   - Current card_number: " . ($cardModel->card_number ?? 'NULL'));
            $this->info("   - New card_number: {$numberedValue}");
            $this->info("   - card_number_in_set: {$cardModel->card_number_in_set}");
            $this->updateCount++;
        } else {
            // Update the card model
            $cardModel->card_number = $numberedValue;
            $cardModel->save();

            $this->info("✅ Updated: {$cardModel->name} (ID: {$cardModel->id}) - card_number: {$numberedValue}");
            $this->updateCount++;
        }
    }
}
