<?php

/**
 * Script per correggere le carte importate con problemi (nome vuoto e card_number contenente l'intera riga CSV)
 * 
 * Uso: php fix_malformed_cards.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\CardModel;
use Illuminate\Support\Facades\DB;

echo "🔧 Correzione carte malformate...\n";
echo "═══════════════════════════════════\n\n";

// Trova tutte le carte con nome vuoto e card_number molto lungo
$problemCards = CardModel::where(function($q) {
    $q->whereNull('name')->orWhere('name', '');
})
->where('card_number', 'LIKE', '%,%')
->whereRaw('LENGTH(card_number) > 50')
->get();

echo "Trovate " . $problemCards->count() . " carte con problemi\n\n";

$fixed = 0;
$deleted = 0;

foreach ($problemCards as $card) {
    // Prova a parsare il card_number come CSV
    $parts = str_getcsv($card->card_number, ',', '"', '\\');
    
    if (count($parts) >= 3) {
        // Estrai i dati
        $numero = trim($parts[0] ?? '');
        $playerName = trim($parts[1] ?? '');
        $numberedValue = trim($parts[2] ?? '');
        
        // Se abbiamo un player name valido, prova a correggere
        if (!empty($playerName) && strlen($playerName) < 200) {
            // Cerca di trovare il player nel database
            $player = DB::table('players')
                ->where('name', 'LIKE', '%' . $playerName . '%')
                ->first();
            
            if ($player) {
                // Aggiorna la carta
                $card->name = $playerName . ' #' . ($numberedValue ?: $numero);
                $card->card_number = $numero;
                $card->card_number_in_set = !empty($numberedValue) ? $numberedValue : null;
                $card->player_id = $player->id;
                $card->save();
                
                echo "✅ Corretta carta ID {$card->id}: {$card->name}\n";
                $fixed++;
            } else {
                // Se non troviamo il player, elimina la carta
                echo "❌ Carta ID {$card->id}: Player '{$playerName}' non trovato - eliminata\n";
                $card->delete();
                $deleted++;
            }
        } else {
            // Se non possiamo correggere, elimina la carta
            echo "❌ Carta ID {$card->id}: Impossibile correggere - eliminata\n";
            $card->delete();
            $deleted++;
        }
    } else {
        // Se non possiamo parsare, elimina la carta
        echo "❌ Carta ID {$card->id}: Impossibile parsare - eliminata\n";
        $card->delete();
        $deleted++;
    }
}

echo "\n═══════════════════════════════════\n";
echo "✅ Carte corrette: {$fixed}\n";
echo "🗑️  Carte eliminate: {$deleted}\n";
echo "═══════════════════════════════════\n";

