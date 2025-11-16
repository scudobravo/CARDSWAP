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
        ])
        ->active()
        ->whereHas('cardListings', function($q) {
            $q->where('status', 'active');
        });

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

        // Filtri per rarità: accetta sia categoria generale che variazione specifica
        if ($request->filled('rarity')) {
            $rarity = $request->get('rarity');
            $baseRarities = ['common', 'uncommon', 'rare', 'mythic', 'special'];
            if (in_array(strtolower($rarity), $baseRarities, true)) {
                $query->where('rarity', $rarity);
            } else {
                $query->where('rarity_variation', $rarity);
            }
        }

        // Filtro Numerazione (boolean): true = numerate (card_number valorizzato), false = non numerate
        if ($request->has('numbered')) {
            $numbered = filter_var($request->get('numbered'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
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

        // Filtro per range numerato (min e max)
        // Usa card_number_in_set e estrae il numero all'inizio della stringa
        // Gestisce formati come: "1", "5/100", "01-gen", "1-AU", "A-01" (estrae solo il numero iniziale)
        if ($request->filled('numbered_min') || $request->filled('numbered_max')) {
            $query->whereNotNull('card_number_in_set')
                  ->where('card_number_in_set', '!=', '');
            
            // Estrae solo i numeri all'inizio della stringa (prima di qualsiasi carattere non numerico)
            // Usa REGEXP_SUBSTR per estrarre la sequenza di numeri all'inizio
            // IMPORTANTE: Filtra solo le righe che hanno un numero all'inizio (REGEXP_SUBSTR non NULL)
            $query->whereRaw(
                "REGEXP_SUBSTR(
                    CASE 
                        WHEN LOCATE('/', card_number_in_set) > 0 
                        THEN SUBSTRING_INDEX(card_number_in_set, '/', 1)
                        ELSE card_number_in_set
                    END,
                    '^[0-9]+'
                ) IS NOT NULL"
            );
            
            if ($request->filled('numbered_min')) {
                $numberedMin = (int)$request->get('numbered_min');
                $query->whereRaw(
                    "CAST(
                        REGEXP_SUBSTR(
                            CASE 
                                WHEN LOCATE('/', card_number_in_set) > 0 
                                THEN SUBSTRING_INDEX(card_number_in_set, '/', 1)
                                ELSE card_number_in_set
                            END,
                            '^[0-9]+'
                        ) AS UNSIGNED
                    ) >= ?",
                    [$numberedMin]
                );
            }
            
            if ($request->filled('numbered_max')) {
                $numberedMax = (int)$request->get('numbered_max');
                $query->whereRaw(
                    "CAST(
                        REGEXP_SUBSTR(
                            CASE 
                                WHEN LOCATE('/', card_number_in_set) > 0 
                                THEN SUBSTRING_INDEX(card_number_in_set, '/', 1)
                                ELSE card_number_in_set
                            END,
                            '^[0-9]+'
                        ) AS UNSIGNED
                    ) <= ?",
                    [$numberedMax]
                );
            }
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
        $page = $request->get('page', 1);
        $cards = $query->paginate($perPage, ['*'], 'page', $page);

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

        // Formato compatibile con SearchResults.vue
        return response()->json([
            'data' => $cards->items(),
            'total' => $cards->total(),
            'current_page' => $cards->currentPage(),
            'last_page' => $cards->lastPage(),
            'per_page' => $cards->perPage(),
            'cards' => $cards->items(), // Mantenuto per retrocompatibilità
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
