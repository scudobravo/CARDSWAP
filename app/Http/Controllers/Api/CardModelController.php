<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardModel;
use App\Models\Category;
use App\Models\CardSet;
use App\Models\Player;
use App\Models\Team;
use App\Models\League;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CardModelController extends Controller
{
    /**
     * Cerca modelli di carte con filtri avanzati
     */
    public function search(Request $request): JsonResponse
    {
        $filters = $request->all();
        
        $query = CardModel::with(['category', 'cardSet', 'player', 'team', 'league'])
            ->where('is_active', true);
        
        // Filtro per giocatori
        if (!empty($filters['selectedPlayers']) && is_array($filters['selectedPlayers'])) {
            $query->whereIn('player_id', $filters['selectedPlayers']);
        } elseif (!empty($filters['player'])) {
            // Supporto per compatibilità: accetta anche un singolo player_id
            $query->where('player_id', $filters['player']);
        } elseif (!empty($filters['player_id'])) {
            // Supporto per compatibilità: accetta anche player_id direttamente
            $query->where('player_id', $filters['player_id']);
        }
        
        // Filtro per squadra
        if (!empty($filters['team'])) {
            $query->where('team_id', $filters['team']);
        } elseif (!empty($filters['team_id'])) {
            // Supporto per compatibilità: accetta anche team_id direttamente
            $query->where('team_id', $filters['team_id']);
        }
        
        // Filtro per set
        if (!empty($filters['set'])) {
            $query->where('card_set_id', $filters['set']);
        } elseif (!empty($filters['set_id'])) {
            // Supporto per compatibilità: accetta anche set_id direttamente
            $query->where('card_set_id', $filters['set_id']);
        } elseif (!empty($filters['card_set_id'])) {
            // Supporto per compatibilità: accetta anche card_set_id
            $query->where('card_set_id', $filters['card_set_id']);
        }
        
        // Filtro per rarità
        if (!empty($filters['rarity'])) {
            $query->where('rarity', $filters['rarity']);
        }
        
        // Filtro per anno
        if (!empty($filters['year'])) {
            $query->where('year', $filters['year']);
        }
        
        // Filtro per brand (tramite set)
        if (!empty($filters['brand'])) {
            $query->whereHas('cardSet', function($q) use ($filters) {
                $q->where('brand', $filters['brand']);
            });
        }
        
        // Filtro Numerazione (boolean): true = numerate (card_number valorizzato), false = non numerate
        if (array_key_exists('numbered', $filters)) {
            $numbered = filter_var($filters['numbered'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($numbered === true) {
                $query->whereNotNull('card_number')
                    ->where('card_number', '!=', '');
            } elseif ($numbered === false) {
                $query->where(function($q) {
                    $q->whereNull('card_number')
                      ->orWhere('card_number', '');
                });
            }
        }
        
        // Filtro per autografo
        if (isset($filters['autograph']) && $filters['autograph'] !== '') {
            if ($filters['autograph'] === 'yes') {
                $query->where('attributes->autograph', true);
            } elseif ($filters['autograph'] === 'no') {
                $query->where(function($q) {
                    $q->whereNull('attributes->autograph')
                      ->orWhere('attributes->autograph', false);
                });
            }
        }
        
        // Filtro per reliquia
        if (isset($filters['relic']) && $filters['relic'] !== '') {
            if ($filters['relic'] === 'yes') {
                $query->where('attributes->relic', true);
            } elseif ($filters['relic'] === 'no') {
                $query->where(function($q) {
                    $q->whereNull('attributes->relic')
                      ->orWhere('attributes->relic', false);
                });
            }
        }
        
        // Filtro per on-card autograph
        if (isset($filters['onCardAuto']) && $filters['onCardAuto'] !== '') {
            if ($filters['onCardAuto'] === 'yes') {
                $query->where('attributes->on_card_auto', true);
            } elseif ($filters['onCardAuto'] === 'no') {
                $query->where(function($q) {
                    $q->whereNull('attributes->on_card_auto')
                      ->orWhere('attributes->on_card_auto', false);
                });
            }
        }
        
        // Filtro per jewel
        if (isset($filters['jewel']) && $filters['jewel'] !== '') {
            if ($filters['jewel'] === 'yes') {
                $query->where('attributes->jewel', true);
            } elseif ($filters['jewel'] === 'no') {
                $query->where(function($q) {
                    $q->whereNull('attributes->jewel')
                      ->orWhere('attributes->jewel', false);
                });
            }
        }
        
        // Filtro per rookie - usa campo diretto is_rookie
        if (isset($filters['rookie']) && $filters['rookie'] !== '') {
            if ($filters['rookie'] === 'yes') {
                $query->where('is_rookie', true);
            } elseif ($filters['rookie'] === 'no') {
                $query->where('is_rookie', false);
            }
        }
        
        // Filtro per multi-player
        if (!empty($filters['multiPlayer']) && is_array($filters['multiPlayer'])) {
            $query->whereIn('attributes->multi_player', $filters['multiPlayer']);
        }
        
        // Filtro per multi-autograph
        if (!empty($filters['multiAutograph'])) {
            if (is_array($filters['multiAutograph'])) {
                $query->whereIn('attributes->multi_autograph', $filters['multiAutograph']);
            } else {
                // Se è una stringa singola (es. "dual", "triple", ecc.)
                $query->where('attributes->multi_autograph_' . $filters['multiAutograph'], true);
            }
        }
        
        // Filtro per grading company - usa campo diretto grading_company_id
        if (!empty($filters['gradingCompany'])) {
            $query->where('grading_company_id', $filters['gradingCompany']);
        }
        
        // Filtro per punteggio grading - usa campo diretto grading_score
        if (!empty($filters['gradingScoreMin'])) {
            $query->where('grading_score', '>=', $filters['gradingScoreMin']);
        }
        if (!empty($filters['gradingScoreMax'])) {
            $query->where('grading_score', '<=', $filters['gradingScoreMax']);
        }
        
        // Filtro per condizione - questo è per le listings, non per i card models
        // I card models non hanno condizione, è una proprietà delle listings
        if (!empty($filters['condition'])) {
            // Non applicare questo filtro ai card models
            // Verrà applicato alle listings se necessario
        }
        
        // Ordinamento
        $query->orderBy('name', 'asc');
        
        // Paginazione
        $perPage = $request->get('per_page', 20);
        $cardModels = $query->paginate($perPage);
        
        return response()->json([
            'success' => true,
            'data' => [
                'card_models' => $cardModels->items(),
                'pagination' => [
                    'current_page' => $cardModels->currentPage(),
                    'last_page' => $cardModels->lastPage(),
                    'per_page' => $cardModels->perPage(),
                    'total' => $cardModels->total(),
                ]
            ]
        ]);
    }
    
    /**
     * Ottieni un modello di carta specifico
     */
    public function show(CardModel $cardModel): JsonResponse
    {
        $cardModel->load(['category', 'cardSet', 'player', 'team', 'league']);
        
        return response()->json([
            'success' => true,
            'data' => [
                'card_model' => $cardModel
            ]
        ]);
    }
    
    /**
     * Ottieni i dati per i filtri a catena
     */
    public function getChainedFilters(Request $request): JsonResponse
    {
        $filters = $request->all();
        
        $data = [
            'years' => [],
            'brands' => [],
            'rarities' => [],
            'teams' => [],
            'card_sets' => [],
            'players' => []
        ];
        
        // Anni disponibili
        $data['years'] = CardModel::select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
        
        // Brand disponibili
        $data['brands'] = CardSet::select('brand')
            ->distinct()
            ->whereNotNull('brand')
            ->orderBy('brand')
            ->pluck('brand')
            ->toArray();
        
        // Rarità disponibili
        $data['rarities'] = CardModel::select('rarity')
            ->distinct()
            ->orderBy('rarity')
            ->pluck('rarity')
            ->toArray();
        
        // Squadre disponibili (filtrate per giocatori selezionati)
        $teamsQuery = Team::select('teams.*')
            ->join('card_models', 'card_models.team_id', '=', 'teams.id')
            ->where('card_models.is_active', true);
        
        if (!empty($filters['selectedPlayers']) && is_array($filters['selectedPlayers'])) {
            $teamsQuery->whereIn('card_models.player_id', $filters['selectedPlayers']);
        }
        
        $data['teams'] = $teamsQuery->distinct()
            ->orderBy('teams.name')
            ->get()
            ->toArray();
        
        // Set disponibili (filtrati per squadra selezionata)
        $setsQuery = CardSet::select('card_sets.*')
            ->join('card_models', 'card_models.card_set_id', '=', 'card_sets.id')
            ->where('card_models.is_active', true);
        
        if (!empty($filters['team'])) {
            $setsQuery->where('card_models.team_id', $filters['team']);
        }
        
        $data['card_sets'] = $setsQuery->distinct()
            ->orderBy('card_sets.name')
            ->get()
            ->toArray();
        
        // Giocatori disponibili
        $playersQuery = Player::select('players.*')
            ->join('card_models', 'card_models.player_id', '=', 'players.id')
            ->where('card_models.is_active', true);
        
        if (!empty($filters['team'])) {
            $playersQuery->where('card_models.team_id', $filters['team']);
        }
        
        if (!empty($filters['set'])) {
            $playersQuery->where('card_models.card_set_id', $filters['set']);
        }
        
        $data['players'] = $playersQuery->distinct()
            ->orderBy('players.name')
            ->get()
            ->toArray();
        
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Aggiorna un modello di carta
     */
    public function update(Request $request, CardModel $cardModel): JsonResponse
    {
        try {
            $data = $request->all();

            // Converti i valori stringa "si"/"yes" in booleani per le caratteristiche
            $booleanFields = [
                'is_rookie',
                'is_star',
                'is_legend',
                'is_autograph',
                'is_relic',
                'is_on_card_auto',
                'is_jewel',
                'is_booklet',
                'is_multi_player_dual',
                'is_multi_player_triple',
                'is_multi_player_quad'
            ];

            foreach ($booleanFields as $field) {
                if (isset($data[$field])) {
                    $value = $data[$field];
                    // Gestisci diversi formati: "si", "yes", "1", "true", true, 1
                    if (is_string($value)) {
                        $value = strtolower(trim($value));
                        $data[$field] = in_array($value, ['si', 'yes', '1', 'true', 'sì']);
                    } elseif (is_numeric($value)) {
                        $data[$field] = (bool) $value;
                    } else {
                        $data[$field] = (bool) $value;
                    }
                }
            }

            // Gestione multi_player: se viene passato un valore per multiAutograph o multiPlayer,
            // aggiorna i campi corrispondenti
            if (isset($data['multiAutograph']) || isset($data['multiPlayer'])) {
                $multiValue = $data['multiAutograph'] ?? $data['multiPlayer'] ?? '';
                
                // Reset di tutti i campi multi_player
                $data['is_multi_player_dual'] = false;
                $data['is_multi_player_triple'] = false;
                $data['is_multi_player_quad'] = false;
                $data['is_booklet'] = false;
                
                // Imposta il campo corretto in base al valore
                if (!empty($multiValue)) {
                    switch (strtolower(trim($multiValue))) {
                        case 'dual':
                            $data['is_multi_player_dual'] = true;
                            break;
                        case 'triple':
                            $data['is_multi_player_triple'] = true;
                            break;
                        case 'quad':
                            $data['is_multi_player_quad'] = true;
                            break;
                        case 'booklet':
                            $data['is_booklet'] = true;
                            break;
                    }
                }
            }

            // Rimuovi campi che non sono nel fillable
            $fillable = [
                'category_id',
                'card_set_id',
                'player_id',
                'team_id',
                'league_id',
                'name',
                'slug',
                'description',
                'set_name',
                'year',
                'rarity',
                'rarity_variation',
                'card_number',
                'card_number_in_set',
                'parallel_type',
                'insert_type',
                'is_rookie',
                'is_star',
                'is_legend',
                'is_autograph',
                'is_relic',
                'is_on_card_auto',
                'is_jewel',
                'is_booklet',
                'is_multi_player_dual',
                'is_multi_player_triple',
                'is_multi_player_quad',
                'artist',
                'image_url',
                'grading_company_id',
                'grading_score',
                'grading_notes',
                'price',
                'attributes',
                'is_active',
            ];

            // Filtra solo i campi fillable
            $updateData = array_intersect_key($data, array_flip($fillable));

            // Aggiorna il modello
            $cardModel->update($updateData);

            // Ricarica le relazioni
            $cardModel->load(['category', 'cardSet', 'player', 'team', 'league']);

            return response()->json([
                'success' => true,
                'message' => 'Carta aggiornata con successo',
                'data' => [
                    'card_model' => $cardModel
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Errore nell\'aggiornamento della carta: ' . $e->getMessage()
            ], 500);
        }
    }
}