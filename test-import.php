<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🚀 Test importazione dati...\n";

try {
    // Test connessione database
    echo "📊 Test connessione database...\n";
    $result = DB::select('SELECT 1 as test');
    echo "✅ Connessione database OK\n";
    
    // Test lettura file CSV
    echo "📄 Test lettura file CSV...\n";
    $csvPath = base_path('IMPORT/Card Condition.csv');
    echo "Percorso file: {$csvPath}\n";
    
    if (!file_exists($csvPath)) {
        echo "❌ File non trovato: {$csvPath}\n";
        exit(1);
    }
    
    $data = [];
    $handle = fopen($csvPath, 'r');
    if ($handle !== false) {
        $count = 0;
        while (($row = fgetcsv($handle, 1000, ',')) !== false && $count < 5) {
            $data[] = $row;
            $count++;
        }
        fclose($handle);
    }
    
    echo "✅ File CSV letto correttamente. Prime 5 righe:\n";
    foreach ($data as $i => $row) {
        echo "  Riga {$i}: " . implode(' | ', $row) . "\n";
    }
    
    // Test inserimento semplice
    echo "📝 Test inserimento database...\n";
    
    // Verifica se la tabella grading_companies esiste
    $tables = DB::select("SHOW TABLES LIKE 'grading_companies'");
    if (empty($tables)) {
        echo "❌ Tabella grading_companies non esiste\n";
        exit(1);
    }
    
    echo "✅ Tabella grading_companies esiste\n";
    
    // Test inserimento
    $testId = DB::table('grading_companies')->insertGetId([
        'name' => 'Test Company',
        'slug' => 'test-company',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "✅ Inserimento test riuscito. ID: {$testId}\n";
    
    // Pulisci il test
    DB::table('grading_companies')->where('id', $testId)->delete();
    echo "✅ Test pulito\n";
    
    echo "🎉 Tutti i test sono passati!\n";
    
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
