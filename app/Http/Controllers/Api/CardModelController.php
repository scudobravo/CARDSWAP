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
        }
        
        // Filtro per squadra
        if (!empty($filters['team'])) {
            $query->where('team_id', $filters['team']);
        }
        
        // Filtro per set
        if (!empty($filters['set'])) {
            $query->where('card_set_id', $filters['set']);
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
        
        // Filtro per numero numerato
        if (!empty($filters['numberedMin'])) {
            $query->where('card_number', '>=', $filters['numberedMin']);
        }
        if (!empty($filters['numberedMax'])) {
            $query->where('card_number', '<=', $filters['numberedMax']);
        }
        
        // Filtro per autografo
        if (!empty($filters['autograph'])) {
            $query->where('attributes->autograph', true);
        }
        
        // Filtro per reliquia
        if (!empty($filters['relic'])) {
            $query->where('attributes->relic', true);
        }
        
        // Filtro per on-card autograph
        if (!empty($filters['onCardAuto'])) {
            $query->where('attributes->on_card_auto', true);
        }
        
        // Filtro per jewel
        if (!empty($filters['jewel'])) {
            $query->where('attributes->jewel', true);
        }
        
        // Filtro per rookie
        if (!empty($filters['rookie'])) {
            $query->where('attributes->rookie', true);
        }
        
        // Filtro per multi-player
        if (!empty($filters['multiPlayer']) && is_array($filters['multiPlayer'])) {
            $query->whereIn('attributes->multi_player', $filters['multiPlayer']);
        }
        
        // Filtro per multi-autograph
        if (!empty($filters['multiAutograph']) && is_array($filters['multiAutograph'])) {
            $query->whereIn('attributes->multi_autograph', $filters['multiAutograph']);
        }
        
        // Filtro per grading
        if (!empty($filters['grading'])) {
            $query->where('attributes->grading', $filters['grading']);
        }
        
        // Filtro per punteggio grading
        if (!empty($filters['gradingScoreMin'])) {
            $query->where('attributes->grading_score', '>=', $filters['gradingScoreMin']);
        }
        if (!empty($filters['gradingScoreMax'])) {
            $query->where('attributes->grading_score', '<=', $filters['gradingScoreMax']);
        }
        
        // Filtro per aziende grading
        if (!empty($filters['gradingCompanies']) && is_array($filters['gradingCompanies'])) {
            $query->whereIn('attributes->grading_company', $filters['gradingCompanies']);
        }
        
        // Filtro per condizioni
        if (!empty($filters['conditions']) && is_array($filters['conditions'])) {
            $query->whereIn('attributes->condition', $filters['conditions']);
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
}