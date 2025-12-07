<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardSet;
use App\Models\CardModel;
use App\Models\CardListing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpongebobFilterController extends Controller
{
    /**
     * Get all available filter options for Spongebob cards
     */
    public function getFilterOptions()
    {
        // Rarities dinamiche dal DB
        $dynamicRarities = CardModel::whereHas('category', function($q) {
                $q->where('slug', 'spongebob');
            })
            ->where('is_active', true)
            ->whereNotNull('rarity')
            ->where('rarity', '!=', '')
            ->distinct()
            ->orderBy('rarity')
            ->pluck('rarity')
            ->map(function($rarity) {
                // Mappa "common" a "Base Card" per la visualizzazione
                return $rarity === 'common' ? 'Base Card' : $rarity;
            })
            ->toArray();

        // Card sets con conteggio carte
        $cardSets = CardSet::whereHas('cardModels', function($q) {
                $q->whereHas('category', function($catQ) {
                    $catQ->where('slug', 'spongebob');
                });
            })
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'brand', 'year', 'season']);

        // Years disponibili
        $years = CardModel::whereHas('category', function($q) {
                $q->where('slug', 'spongebob');
            })
            ->where('is_active', true)
            ->whereNotNull('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        // Brands disponibili
        $brands = CardSet::whereHas('cardModels', function($q) {
                $q->whereHas('category', function($catQ) {
                    $catQ->where('slug', 'spongebob');
                });
            })
            ->whereNotNull('brand')
            ->where('brand', '!=', '')
            ->distinct()
            ->orderBy('brand')
            ->pluck('brand')
            ->toArray();

        return response()->json([
            'card_sets' => $cardSets,
            'rarities' => $dynamicRarities,
            'years' => $years,
            'brands' => $brands,
            'conditions' => ['mint', 'near_mint', 'excellent', 'good', 'light_played', 'played', 'poor', 'fair', 'very_good'],
        ]);
    }

    /**
     * Search card sets
     */
    public function searchCardSets(Request $request)
    {
        $query = $request->get('q', '');
        
        $cardSets = CardSet::whereHas('cardModels', function($q) {
                $q->whereHas('category', function($catQ) {
                    $catQ->where('slug', 'spongebob');
                });
            })
            ->active()
            ->when($query, function($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%");
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'slug', 'brand', 'year', 'season']);

        return response()->json(['card_sets' => $cardSets]);
    }

    /**
     * Get filtered products
     */
    public function getFilteredProducts(Request $request)
    {
        $filters = $request->all();
        
        $isSealed = isset($filters['subcategory']) && 
                    in_array($filters['subcategory'], ['sealed-packs', 'sealed-boxes']);
        
        $query = CardListing::with([
                'cardModel.category',
                'cardModel.cardSet',
                'cardSet',
                'seller'
            ])
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereHas('cardModel', function($cardModelQ) {
                    $cardModelQ->where('is_active', true)
                      ->whereHas('category', function($catQ) {
                          $catQ->where('slug', 'spongebob');
                      });
                })
                ->orWhere(function($sealedQ) {
                    $sealedQ->where(function($subQ) {
                        $subQ->where('listing_type', 'sealed-pack')
                             ->orWhere('listing_type', 'sealed-box')
                             ->orWhere('listing_type', 'lot');
                    })
                    ->whereNull('card_model_id')
                    ->whereHas('cardSet', function($cardSetQ) {
                        $cardSetQ->whereHas('category', function($catQ) {
                            $catQ->where('slug', 'spongebob');
                        });
                    });
                });
            });

        // Filtri per set
        if (isset($filters['set_id']) && !empty($filters['set_id'])) {
            if ($isSealed) {
                $query->where('card_set_id', $filters['set_id']);
            } else {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    $q->where('card_set_id', $filters['set_id']);
                });
            }
        }

        // Filtri per year
        if (isset($filters['year']) && !empty($filters['year'])) {
            if ($isSealed) {
                $query->where('year', $filters['year']);
            } else {
                $query->whereHas('cardModel', function($q) use ($filters) {
                    $q->where('year', $filters['year']);
                });
            }
        }

        // Filtri per brand
        if (isset($filters['brand']) && !empty($filters['brand'])) {
            if ($isSealed) {
                $query->whereHas('cardSet', function($q) use ($filters) {
                    $q->where('brand', $filters['brand']);
                });
            } else {
                $query->whereHas('cardModel.cardSet', function($q) use ($filters) {
                    $q->where('brand', $filters['brand']);
                });
            }
        }

        // Filtri per rarity (solo per singles)
        // IMPORTANTE: Il filtro Rarity deve filtrare solo su rarity, non su rarity_variation
        if (!$isSealed && isset($filters['rarity']) && !empty($filters['rarity'])) {
            // Mappa "Base Card" a "common" per il filtro (nel DB è salvato come "common")
            $rarityFilter = $filters['rarity'];
            if ($rarityFilter === 'Base Card') {
                $rarityFilter = 'common';
            }
            
            $query->whereHas('cardModel', function($q) use ($rarityFilter) {
                $q->where('rarity', $rarityFilter);
            });
        }

        // Filtro per sottocategoria
        if (isset($filters['subcategory']) && !empty($filters['subcategory'])) {
            switch ($filters['subcategory']) {
                case 'singles':
                    $query->where(function($q) {
                        $q->where('listing_type', 'single')
                          ->orWhere('listing_type', 'bulk')
                          ->orWhereNull('listing_type');
                    })
                    ->where(function($notSealedQ) {
                        $notSealedQ->where('listing_type', '!=', 'sealed-pack')
                                   ->where('listing_type', '!=', 'sealed-box')
                                   ->where('listing_type', '!=', 'lot')
                                   ->orWhereNull('listing_type');
                    });
                    break;
                case 'sealed-packs':
                    $query->where('listing_type', 'sealed-pack')
                          ->whereNull('card_model_id');
                    break;
                case 'sealed-boxes':
                    $query->where('listing_type', 'sealed-box')
                          ->whereNull('card_model_id');
                    break;
                case 'lot':
                    $query->where('listing_type', 'lot')
                          ->whereNull('card_model_id');
                    break;
            }
        }

        // Ordinamento
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        
        $allowedSortFields = ['price', 'created_at', 'id'];
        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Paginazione
        $perPage = $filters['per_page'] ?? 20;
        $page = $filters['page'] ?? 1;
        
        $listings = $query->paginate($perPage, ['*'], 'page', $page);

        // Trasforma i dati per il frontend
        $transformedListings = $listings->getCollection()->map(function($listing) {
            $cardModel = $listing->cardModel;
            $cardSet = $cardModel ? $cardModel->cardSet : $listing->cardSet;
            
            if (!$cardModel) {
                // Sealed pack/box/lot
                $displayName = null;
                if ($listing->listing_type === 'lot') {
                    $displayName = $listing->title && $listing->title !== 'Carta' ? $listing->title : 'Lot';
                } elseif ($cardSet && $cardSet->name) {
                    $displayName = $cardSet->name;
                } elseif ($listing->title && $listing->title !== 'Carta') {
                    $displayName = $listing->title;
                } else {
                    $displayName = $listing->listing_type === 'sealed-pack' ? 'Sealed Pack' : 'Sealed Box';
                }
                
                return [
                    'id' => $listing->id,
                    'listing_id' => $listing->id,
                    'card_model_id' => null,
                    'name' => $displayName,
                    'slug' => \Illuminate\Support\Str::slug($displayName),
                    'set' => $cardSet->name ?? null,
                    'year' => $listing->year ?? ($cardSet->year ?? null),
                    'rarity' => null,
                    'price' => $listing->price,
                    'condition' => $listing->condition,
                    'images' => $listing->images ?? [],
                    'seller' => $listing->seller ? [
                        'id' => $listing->seller->id,
                        'name' => $listing->seller->name,
                    ] : null,
                ];
            }
            
            // Estrai solo il nome del personaggio (prima del trattino)
            // Es: "Spongebob - TOPPS SPONGEBOB (Orange Foil)" -> "Spongebob"
            $cardName = $cardModel->name;
            if (strpos($cardName, ' - ') !== false) {
                $cardName = trim(explode(' - ', $cardName)[0]);
            }
            
            // Mappa "common" a "Base Card" per Spongebob
            $displayRarity = $cardModel->rarity ?? null;
            if ($displayRarity === 'common') {
                $displayRarity = 'Base Card';
            }
            
            return [
                'id' => $cardModel->id,
                'listing_id' => $listing->id,
                'card_model_id' => $cardModel->id,
                'name' => $cardName,
                'slug' => $cardModel->slug,
                'set' => $cardSet->name ?? null,
                'year' => $cardModel->year ?? ($cardSet->year ?? null),
                'rarity' => $displayRarity,
                'rarity_variation' => $cardModel->rarity_variation ?? null,
                'price' => $listing->price,
                'condition' => $listing->condition,
                'images' => $listing->images ?? [],
                'seller' => $listing->seller ? [
                    'id' => $listing->seller->id,
                    'name' => $listing->seller->name,
                ] : null,
            ];
        });

        return response()->json([
            'data' => $transformedListings,
            'current_page' => $listings->currentPage(),
            'last_page' => $listings->lastPage(),
            'per_page' => $listings->perPage(),
            'total' => $listings->total(),
        ]);
    }

    /**
     * Search rarities with autocomplete (minimum 1 character)
     * Per Spongebob, cerca sia in rarity che in rarity_variation
     */
    public function searchRarities(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:100',
            'set_id' => 'nullable|integer',
            'year' => 'nullable|string',
            'brand' => 'nullable|string'
        ]);

        $query = $request->get('q', '');
        
        // Query base per le rarità: cerca sia in rarity che in rarity_variation
        $baseQuery = CardModel::whereHas('category', function($catQuery) {
                $catQuery->where('slug', 'spongebob');
            })
            ->where('is_active', true);
        
        // Applica filtri aggiuntivi per limitare i risultati
        if ($request->filled('set_id')) {
            $baseQuery->where('card_set_id', $request->set_id);
        }
        
        if ($request->filled('year')) {
            $baseQuery->where('year', $request->year);
        }
        
        if ($request->filled('brand')) {
            $baseQuery->whereHas('cardSet', function($setQuery) use ($request) {
                $setQuery->where('brand', $request->brand);
            });
        }
        
        // Estrai le rarità uniche: da rarity e rarity_variation
        $raritiesFromRarity = $baseQuery->clone()
            ->whereNotNull('rarity')
            ->where('rarity', '!=', '')
            ->when(!empty($query) && strlen($query) >= 1, function($q) use ($query) {
                $q->where('rarity', 'LIKE', "%{$query}%");
            })
            ->select('rarity')
            ->distinct()
            ->pluck('rarity')
            ->toArray();
        
        $raritiesFromVariation = $baseQuery->clone()
            ->whereNotNull('rarity_variation')
            ->where('rarity_variation', '!=', '')
            ->when(!empty($query) && strlen($query) >= 1, function($q) use ($query) {
                $q->where('rarity_variation', 'LIKE', "%{$query}%");
            })
            ->select('rarity_variation')
            ->distinct()
            ->pluck('rarity_variation')
            ->toArray();
        
        // Estrai anche dal nome della carta (parte tra parentesi) se rarity_variation è vuoto
        // Es: "Caterpillar - TOPPS DISNEY WONDER (Orange Foil)" -> "Orange Foil"
        $raritiesFromName = [];
        $cardsForNameExtraction = $baseQuery->clone()
            ->where(function($q) {
                $q->whereNull('rarity_variation')
                  ->orWhere('rarity_variation', '=', '');
            })
            ->where('name', 'LIKE', '%(%)%') // Solo carte con parentesi nel nome
            ->select('name')
            ->get();
        
        foreach ($cardsForNameExtraction as $card) {
            // Estrai la parte tra parentesi dal nome
            if (preg_match('/\(([^)]+)\)/', $card->name, $matches)) {
                $extractedRarity = trim($matches[1]);
                if (!empty($extractedRarity)) {
                    // Se c'è una query, verifica che corrisponda (cerca sia nel nome che nella parte estratta)
                    if (empty($query) || stripos($extractedRarity, $query) !== false || stripos($card->name, $query) !== false) {
                        $raritiesFromName[] = $extractedRarity;
                    }
                }
            }
        }
        
        // Combina e rimuovi duplicati
        $rarities = array_unique(array_merge($raritiesFromRarity, $raritiesFromVariation, $raritiesFromName));
        
        // Applica il filtro di ricerca anche sui risultati finali per sicurezza (case-insensitive)
        if (!empty($query) && strlen($query) >= 1) {
            $rarities = array_filter($rarities, function($rarity) use ($query) {
                return stripos($rarity, $query) !== false;
            });
        }
        
        // Ordina i risultati
        sort($rarities);
        
        // Limita a 100 risultati
        $rarities = array_slice($rarities, 0, 100);
        
        return response()->json(['rarities' => array_values($rarities)]);
    }
}

