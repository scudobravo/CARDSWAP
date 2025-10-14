<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardModel;
use Illuminate\Http\Request;

class CardSearchController extends Controller
{
    /**
     * Search cards with dynamic filters
     */
    public function search(Request $request)
    {
        $query = CardModel::with([
            'category',
            'cardSet',
            'player',
            'team',
            'league',
            'gradingCompany',
            'cardListings' => function($q) {
                $q->where('status', 'active');
            }
        ])->active();

        // Filtri per categoria
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filtri per set
        if ($request->filled('card_set_id')) {
            $query->where('card_set_id', $request->card_set_id);
        }

        // Filtri per giocatore
        if ($request->filled('player_id')) {
            $query->where('player_id', $request->player_id);
        }

        // Filtri per squadra
        if ($request->filled('team_id')) {
            $query->where('team_id', $request->team_id);
        }

        // Filtri per lega
        if ($request->filled('league_id')) {
            $query->where('league_id', $request->league_id);
        }

        // Filtri per anno
        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        // Filtri per rarità
        if ($request->filled('rarity')) {
            $query->where('rarity', $request->rarity);
        }

        // Filtri per tipo di carta
        if ($request->filled('is_rookie') && $request->boolean('is_rookie')) {
            $query->rookie();
        }

        if ($request->filled('is_star') && $request->boolean('is_star')) {
            $query->star();
        }

        if ($request->filled('is_legend') && $request->boolean('is_legend')) {
            $query->legend();
        }

        if ($request->filled('is_autograph') && $request->boolean('is_autograph')) {
            $query->autograph();
        }

        if ($request->filled('is_relic') && $request->boolean('is_relic')) {
            $query->relic();
        }

        // Filtri per grading
        if ($request->filled('grading_company_id')) {
            $query->where('grading_company_id', $request->grading_company_id);
        }

        if ($request->filled('min_grading_score')) {
            $query->minGradingScore($request->min_grading_score);
        }

        // Filtri per prezzo (dalle inserzioni attive)
        if ($request->filled('min_price')) {
            $query->whereHas('cardListings', function($q) use ($request) {
                $q->where('price', '>=', $request->min_price);
            });
        }

        if ($request->filled('max_price')) {
            $query->whereHas('cardListings', function($q) use ($request) {
                $q->where('price', '<=', $request->max_price);
            });
        }

        // Filtri per condizione (dalle inserzioni)
        if ($request->filled('condition')) {
            $query->whereHas('cardListings', function($q) use ($request) {
                $q->where('condition', $request->condition);
            });
        }

        // Ricerca testuale
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('card_number', 'like', "%{$search}%")
                  ->orWhere('card_number_in_set', 'like', "%{$search}%")
                  ->orWhereHas('player', function($playerQuery) use ($search) {
                      $playerQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('team', function($teamQuery) use ($search) {
                      $teamQuery->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('league', function($leagueQuery) use ($search) {
                      $leagueQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Ordinamento
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');

        switch ($sortBy) {
            case 'price_low':
                $query->whereHas('cardListings', function($q) {
                    $q->orderBy('price', 'asc');
                });
                break;
            case 'price_high':
                $query->whereHas('cardListings', function($q) {
                    $q->orderBy('price', 'desc');
                });
                break;
            case 'year':
                $query->orderBy('year', $sortOrder);
                break;
            case 'rarity':
                $query->orderBy('rarity', $sortOrder);
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }

        // Paginazione
        $perPage = $request->get('per_page', 20);
        $cards = $query->paginate($perPage);

        // Aggiungo statistiche per i filtri
        $stats = [
            'total_cards' => $cards->total(),
            'price_range' => [
                'min' => $cards->getCollection()->min('cardListings.0.price') ?? 0,
                'max' => $cards->getCollection()->max('cardListings.0.price') ?? 0,
            ],
            'available_conditions' => $cards->getCollection()
                ->flatMap->cardListings
                ->pluck('condition')
                ->unique()
                ->values(),
        ];

        return response()->json([
            'cards' => $cards->items(),
            'pagination' => [
                'current_page' => $cards->currentPage(),
                'last_page' => $cards->lastPage(),
                'per_page' => $cards->perPage(),
                'total' => $cards->total(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Get card details with all related information
     */
    public function show(CardModel $card)
    {
        $card->load([
            'category',
            'cardSet',
            'player.team.league',
            'team.league',
            'league',
            'gradingCompany',
            'cardListings' => function($query) {
                $query->where('status', 'active')
                      ->with('user:id,name,username,rating');
            }
        ]);

        return response()->json([
            'card' => $card,
        ]);
    }
}
