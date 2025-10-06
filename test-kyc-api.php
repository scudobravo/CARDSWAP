<?php
/**
 * Script per testare l'endpoint KYC API
 * Esegui con: php test-kyc-api.php
 */

require_once 'vendor/autoload.php';

// Carica configurazione Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Test Endpoint KYC API\n";
echo "========================\n\n";

// Trova un utente per il test
$user = \App\Models\User::first();
if (!$user) {
    echo "❌ Nessun utente trovato nel database\n";
    exit(1);
}

echo "👤 Testando con utente: {$user->name} ({$user->email})\n\n";

// Crea un token per l'utente
$token = $user->createToken('test-kyc')->plainTextToken;
echo "🔑 Token creato: " . substr($token, 0, 20) . "...\n\n";

// Test endpoint status
echo "📊 Testando /api/kyc/status...\n";
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => "Bearer {$token}",
    'Accept' => 'application/json'
])->get('http://localhost:8000/api/kyc/status');

if ($response->successful()) {
    $data = $response->json();
    echo "✅ Status OK: " . json_encode($data['data'], JSON_PRETTY_PRINT) . "\n\n";
} else {
    echo "❌ Status Error: " . $response->body() . "\n\n";
}

// Test endpoint start
echo "🚀 Testando /api/kyc/start...\n";
$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => "Bearer {$token}",
    'Accept' => 'application/json',
    'Content-Type' => 'application/json'
])->post('http://localhost:8000/api/kyc/start');

if ($response->successful()) {
    $data = $response->json();
    echo "✅ Start OK: " . json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($data['data']['verification_url'])) {
        echo "🔗 URL di verifica: {$data['data']['verification_url']}\n";
        echo "💡 Apri questo URL nel browser per testare la verifica\n";
    }
} else {
    echo "❌ Start Error: " . $response->body() . "\n\n";
}

echo "\n✅ Test completato!\n";
