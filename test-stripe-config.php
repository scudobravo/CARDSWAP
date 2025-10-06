<?php
/**
 * Script per testare la configurazione Stripe Identity
 * Esegui con: php test-stripe-config.php
 */

require_once 'vendor/autoload.php';

// Carica configurazione Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Test Configurazione Stripe Identity\n";
echo "=====================================\n\n";

// Verifica configurazione
$stripeKey = config('services.stripe.key');
$stripeSecret = config('services.stripe.secret');
$identityEnabled = config('services.stripe.identity_enabled');
$appUrl = config('app.url');

echo "📋 Configurazione:\n";
echo "   - Stripe Key: " . ($stripeKey ? substr($stripeKey, 0, 20) . '...' : '❌ NON CONFIGURATA') . "\n";
echo "   - Stripe Secret: " . ($stripeSecret ? substr($stripeSecret, 0, 20) . '...' : '❌ NON CONFIGURATA') . "\n";
echo "   - Identity Enabled: " . ($identityEnabled ? '✅ Yes' : '❌ No') . "\n";
echo "   - App URL: " . ($appUrl ?: '❌ NON CONFIGURATA') . "\n\n";

if (!$stripeKey || !$stripeSecret) {
    echo "❌ Configurazione incompleta! Aggiungi le chiavi Stripe al file .env\n";
    exit(1);
}

// Test connessione Stripe
echo "🔌 Test connessione Stripe...\n";
try {
    \Stripe\Stripe::setApiKey($stripeSecret);
    $stripe = new \Stripe\StripeClient($stripeSecret);
    
    // Test semplice: recupera account info
    $account = $stripe->accounts->retrieve();
    echo "✅ Connessione Stripe OK\n";
    echo "   - Account ID: " . $account->id . "\n";
    echo "   - Country: " . $account->country . "\n";
    echo "   - Charges Enabled: " . ($account->charges_enabled ? 'Yes' : 'No') . "\n\n";
    
} catch (Exception $e) {
    echo "❌ Errore connessione Stripe: " . $e->getMessage() . "\n";
    exit(1);
}

// Test creazione sessione Identity
echo "🆔 Test creazione sessione Identity...\n";
try {
    $user = \App\Models\User::first();
    if (!$user) {
        echo "❌ Nessun utente trovato nel database\n";
        exit(1);
    }
    
    $stripeService = new \App\Services\StripeService();
    $result = $stripeService->createIdentityVerificationSession($user);
    
    if ($result['success']) {
        echo "✅ Sessione Identity creata con successo!\n";
        echo "   - Session ID: " . $result['session_id'] . "\n";
        echo "   - URL: " . $result['url'] . "\n\n";
        
        echo "💡 Per testare:\n";
        echo "   1. Vai su: " . $result['url'] . "\n";
        echo "   2. Usa documenti di test (es: 4000000000000002)\n";
        echo "   3. Controlla lo stato con: php artisan stripe:check-status " . $user->id . "\n";
        
    } else {
        echo "❌ Errore creazione sessione: " . $result['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Errore: " . $e->getMessage() . "\n";
}

echo "\n✅ Test completato!\n";
