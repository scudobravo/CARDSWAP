<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CardListing;
use App\Models\CardModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class CardListingController extends Controller
{
    /**
     * Display a listing of card listings with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = CardListing::with([
            'cardModel.category',
            'cardModel.cardSet',
            'cardModel.player',
            'cardModel.team',
            'cardModel.league',
            'seller'
        ])->where('status', 'active');

        // Applica filtri
        $query = $this->applyFilters($query, $request);

        // Applica ordinamento
        $query = $this->applySorting($query, $request);

        // Paginazione
        $perPage = $request->get('per_page', 20);
        $listings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $listings->items(),
            'pagination' => [
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
                'from' => $listings->firstItem(),
                'to' => $listings->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created card listing
     */
    public function store(Request $request): JsonResponse
    {
        // Convert string booleans to actual booleans for FormData BEFORE validation
        $data = $request->all();
        $booleanFields = ['is_foil', 'is_signed', 'is_altered', 'is_first_edition', 'is_negotiable'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = filter_var($data[$field], FILTER_VALIDATE_BOOLEAN);
            }
        }
        
        // Create a new request with converted data
        $request->merge($data);
        
        $validator = Validator::make($request->all(), [
            'card_model_id' => 'required|exists:card_models,id',
            'price' => 'required|numeric|min:0.01|max:999999.99',
            'condition' => 'required|in:mint,near_mint,excellent,good,light_played,played,poor',
            'quantity' => 'required|integer|min:1|max:1000',
            'language' => 'required|in:italian,english,spanish,french,german,portuguese',
            'is_foil' => 'boolean',
            'is_signed' => 'boolean',
            'is_altered' => 'boolean',
            'is_first_edition' => 'boolean',
            'description' => 'nullable|string|max:1000',
            'images' => 'nullable|array',
            'images.*' => 'nullable|file|image|max:2048',
            'shipping_zones' => 'required|array|min:1',
            'shipping_zones.*' => 'exists:shipping_zones,id',
            'status' => 'in:draft,active,paused,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Qualunque utente autenticato può creare inserzioni

        try {
            DB::beginTransaction();

            $listingData = $request->all();
            $listingData['seller_id'] = Auth::id();
            $listingData['status'] = $request->get('status', 'draft');

            // Gestione immagini
            if ($request->hasFile('images')) {
                $images = [];
                foreach ($request->file('images') as $image) {
                    if ($image && $image->isValid()) {
                        $path = $image->store('listings', 'public');
                        $images[] = $path;
                    }
                }
                $listingData['images'] = $images;
            }

            $cardListing = CardListing::create($listingData);

            // Gestione zone di spedizione
            if ($request->has('shipping_zones')) {
                $cardListing->shippingZones()->attach($request->shipping_zones);
            }

            // Approvazione automatica per utenti verificati
            if (Auth::user()->stripe_identity_verified && Auth::user()->kyc_status === 'approved') {
                $oldStatus = $cardListing->status;
                $cardListing->publish(); // publish() include già approve() e imposta published_at
                
                // Ricarica l'inserzione per avere i dati aggiornati
                $cardListing->refresh();
                
                // Trigger evento per notifiche
                event(new \App\Events\ListingStatusChanged($cardListing, $oldStatus, 'active'));
            } else {
                $cardListing->submitForReview();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => Auth::user()->stripe_identity_verified ? 
                    'Inserzione pubblicata con successo' : 
                    'Inserzione inviata per revisione',
                'data' => $cardListing->load([
                    'cardModel.category',
                    'cardModel.cardSet',
                    'cardModel.player',
                    'cardModel.team',
                    'cardModel.league',
                    'seller',
                    'shippingZones'
                ])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la creazione dell\'inserzione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crea inserzioni in bulk
     */
    public function storeBulk(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listings' => 'required|array|min:1|max:50',
            'listings.*.card_model_id' => 'required|exists:card_models,id',
            'listings.*.price' => 'required|numeric|min:0.01|max:999999.99',
            'listings.*.condition' => 'required|in:mint,near_mint,excellent,good,light_played,played,poor',
            'listings.*.quantity' => 'required|integer|min:1|max:1000',
            'listings.*.language' => 'required|in:italian,english,spanish,french,german,portuguese',
            'listings.*.is_foil' => 'boolean',
            'listings.*.is_signed' => 'boolean',
            'listings.*.is_altered' => 'boolean',
            'listings.*.is_first_edition' => 'boolean',
            'listings.*.is_negotiable' => 'boolean',
            'listings.*.description' => 'nullable|string|max:1000',
            'listings.*.shipping_zones' => 'required|array|min:1',
            'listings.*.shipping_zones.*' => 'exists:shipping_zones,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Qualunque utente autenticato può creare inserzioni

        $sellerId = Auth::id();
        $createdListings = [];

        DB::beginTransaction();
        
        try {
            foreach ($request->input('listings') as $listingData) {
                $listingData['seller_id'] = $sellerId;
                $listingData['status'] = 'draft';
                
                $listing = CardListing::create($listingData);
                
                // Gestione zone di spedizione
                if (!empty($listingData['shipping_zones'])) {
                    $listing->shippingZones()->attach($listingData['shipping_zones']);
                }
                
                $listing->load(['cardModel', 'seller', 'shippingZones']);
                $createdListings[] = $listing;
            }
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Inserzioni create con successo',
                'data' => [
                    'listings' => $createdListings,
                    'count' => count($createdListings)
                ]
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione inserzioni bulk',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified card listing
     */
    public function show(CardListing $cardListing): JsonResponse
    {
        $cardListing->load([
            'cardModel.category',
            'cardModel.cardSet',
            'cardModel.player',
            'cardModel.team',
            'cardModel.league',
            'seller',
            'shippingZones'
        ]);

        return response()->json([
            'success' => true,
            'data' => $cardListing
        ]);
    }

    /**
     * Update the specified card listing
     */
    public function update(Request $request, CardListing $cardListing): JsonResponse
    {
        // Verifica che l'utente sia il proprietario dell'inserzione
        if ($cardListing->seller_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorizzato a modificare questa inserzione'
            ], 403);
        }

        // Converti stringhe booleane in booleani veri PRIMA della validazione
        $data = $request->all();
        if (isset($data['is_foil'])) {
            $data['is_foil'] = filter_var($data['is_foil'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($data['is_signed'])) {
            $data['is_signed'] = filter_var($data['is_signed'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($data['is_altered'])) {
            $data['is_altered'] = filter_var($data['is_altered'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($data['is_first_edition'])) {
            $data['is_first_edition'] = filter_var($data['is_first_edition'], FILTER_VALIDATE_BOOLEAN);
        }
        if (isset($data['is_negotiable'])) {
            $data['is_negotiable'] = filter_var($data['is_negotiable'], FILTER_VALIDATE_BOOLEAN);
        }

        $validator = Validator::make($data, [
            'price' => 'sometimes|numeric|min:0.01|max:999999.99',
            'condition' => 'sometimes|in:mint,near_mint,excellent,good,light_played,played,poor',
            'quantity' => 'sometimes|integer|min:1|max:1000',
            'language' => 'sometimes|in:italian,english,spanish,french,german,portuguese',
            'is_foil' => 'boolean',
            'is_signed' => 'boolean',
            'is_altered' => 'boolean',
            'is_first_edition' => 'boolean',
            'description' => 'nullable|string|max:1000',
            // Rimuovi la validazione per images - vengono gestite separatamente
            'shipping_zones' => 'sometimes|array|min:1',
            'shipping_zones.*' => 'exists:shipping_zones,id',
            'status' => 'sometimes|in:draft,active,paused,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $updateData = $data; // Usa i dati già convertiti
            
            // Gestione immagini
            
            // Gestione immagini - preserva quelle esistenti se non vengono caricate nuove
            if ($request->hasFile('images')) {
                $newImages = [];
                foreach ($request->file('images') as $image) {
                    if ($image && $image->isValid()) {
                        $path = $image->store('listings', 'public');
                        $newImages[] = $path;
                    }
                }
                
                // Combina immagini esistenti con quelle nuove
                $existingImages = $cardListing->images;
                if (empty($existingImages) || is_null($existingImages)) {
                    $allImages = $newImages;
                } else {
                    $allImages = array_merge($existingImages, $newImages);
                }
                
                // Forza l'aggiornamento del campo images
                $updateData['images'] = $allImages;
            } else {
                // Se non vengono caricate nuove immagini, mantieni quelle esistenti
                unset($updateData['images']);
            }

            $cardListing->update($updateData);

            // Aggiorna zone di spedizione se fornite
            if ($request->has('shipping_zones')) {
                $cardListing->shippingZones()->sync($request->shipping_zones);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inserzione aggiornata con successo',
                'data' => $cardListing->fresh()->load([
                    'cardModel.category',
                    'cardModel.cardSet',
                    'cardModel.player',
                    'cardModel.team',
                    'cardModel.league',
                    'seller',
                    'shippingZones'
                ])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento dell\'inserzione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified card listing
     */
    public function destroy(CardListing $cardListing): JsonResponse
    {
        // Verifica che l'utente sia il proprietario dell'inserzione
        if ($cardListing->seller_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorizzato a eliminare questa inserzione'
            ], 403);
        }

        try {
            $cardListing->update(['status' => 'inactive']);

            return response()->json([
                'success' => true,
                'message' => 'Inserzione disattivata con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la disattivazione dell\'inserzione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get user's listings (for sellers)
     */
    public function myListings(Request $request): JsonResponse
    {
        $query = CardListing::with([
            'cardModel.category',
            'cardModel.cardSet',
            'cardModel.player',
            'cardModel.team',
            'cardModel.league',
            'shippingZones'
        ])->where('seller_id', Auth::id());

        // Filtra per status se specificato
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Ordinamento
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginazione
        $perPage = $request->get('per_page', 20);
        $listings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $listings->items(),
            'pagination' => [
                'current_page' => $listings->currentPage(),
                'last_page' => $listings->lastPage(),
                'per_page' => $listings->perPage(),
                'total' => $listings->total(),
            ]
        ]);
    }

    /**
     * Duplicate a listing
     */
    public function duplicate(CardListing $cardListing): JsonResponse
    {
        // Verifica che l'utente sia il proprietario dell'inserzione
        if ($cardListing->seller_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorizzato a duplicare questa inserzione'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $newListing = $cardListing->replicate();
            $newListing->status = 'draft';
            $newListing->created_at = now();
            $newListing->updated_at = now();
            $newListing->save();

            // Copia le zone di spedizione
            $shippingZones = $cardListing->shippingZones->pluck('id')->toArray();
            $newListing->shippingZones()->attach($shippingZones);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inserzione duplicata con successo',
                'data' => $newListing->load([
                    'cardModel.category',
                    'cardModel.cardSet',
                    'cardModel.player',
                    'cardModel.team',
                    'cardModel.league',
                    'shippingZones'
                ])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la duplicazione dell\'inserzione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Change listing status
     */
    public function changeStatus(Request $request, CardListing $cardListing): JsonResponse
    {
        // Verifica che l'utente sia il proprietario dell'inserzione
        if ($cardListing->seller_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorizzato a modificare questa inserzione'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:draft,active,paused,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $cardListing->update(['status' => $request->status]);

            return response()->json([
                'success' => true,
                'message' => 'Status inserzione aggiornato con successo',
                'data' => $cardListing
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento dello status',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search listings with advanced filters
     */
    public function search(Request $request): JsonResponse
    {
        $query = CardListing::with([
            'cardModel.category',
            'cardModel.cardSet',
            'cardModel.player',
            'cardModel.team',
            'cardModel.league',
            'seller'
        ])->where('status', 'active');

        // Applica filtri avanzati
        $query = $this->applyAdvancedFilters($query, $request);

        // Ordinamento
        $sortBy = $request->get('sort_by', 'price');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginazione
        $perPage = $request->get('per_page', 20);
        $results = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ]
        ]);
    }

    /**
     * Apply basic filters to the query
     */
    private function applyFilters(Builder $query, Request $request): Builder
    {
        if ($request->has('category_id')) {
            $query->whereHas('cardModel', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->has('card_set_id')) {
            $query->whereHas('cardModel', function ($q) use ($request) {
                $q->where('card_set_id', $request->card_set_id);
            });
        }

        if ($request->has('player_id')) {
            $query->whereHas('cardModel', function ($q) use ($request) {
                $q->where('player_id', $request->player_id);
            });
        }

        if ($request->has('team_id')) {
            $query->whereHas('cardModel', function ($q) use ($request) {
                $q->where('team_id', $request->team_id);
            });
        }

        if ($request->has('league_id')) {
            $query->whereHas('cardModel', function ($q) use ($request) {
                $q->where('league_id', $request->league_id);
            });
        }

        if ($request->has('condition')) {
            $query->where('condition', $request->condition);
        }

        if ($request->has('language')) {
            $query->where('language', $request->language);
        }

        if ($request->has('is_foil')) {
            $query->where('is_foil', $request->boolean('is_foil'));
        }

        if ($request->has('is_signed')) {
            $query->where('is_signed', $request->boolean('is_signed'));
        }

        if ($request->has('is_altered')) {
            $query->where('is_altered', $request->boolean('is_altered'));
        }

        if ($request->has('is_first_edition')) {
            $query->where('is_first_edition', $request->boolean('is_first_edition'));
        }

        if ($request->has('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->has('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        return $query;
    }

    /**
     * Apply advanced filters to the query
     */
    private function applyAdvancedFilters(Builder $query, Request $request): Builder
    {
        return $this->applyFilters($query, $request);
    }

    /**
     * Apply sorting to the query
     */
    private function applySorting(Builder $query, Request $request): Builder
    {
        $sortBy = $request->get('sort_by', 'price');
        $sortOrder = $request->get('sort_order', 'asc');

        $allowedSortFields = [
            'price', 'condition', 'quantity', 'created_at', 'updated_at'
        ];

        if (in_array($sortBy, $allowedSortFields)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('price', 'asc');
        }

        return $query;
    }

    /**
     * Get listing statistics for seller
     */
    public function getStats(): JsonResponse
    {
        $sellerId = Auth::id();
        
        $stats = [
            'total_listings' => CardListing::where('seller_id', $sellerId)->where('status', 'active')->count(),
            'active_listings' => CardListing::where('seller_id', $sellerId)->active()->count(),
            'draft_listings' => CardListing::where('seller_id', $sellerId)->draft()->count(),
            'paused_listings' => CardListing::where('seller_id', $sellerId)->paused()->count(),
            'inactive_listings' => CardListing::where('seller_id', $sellerId)->inactive()->count(),
            'total_value' => CardListing::where('seller_id', $sellerId)
                ->active()
                ->sum(DB::raw('price * quantity')),
            'average_price' => CardListing::where('seller_id', $sellerId)
                ->active()
                ->avg('price'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Bulk update listing status
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listing_ids' => 'required|array|min:1',
            'listing_ids.*' => 'integer|exists:card_listings,id',
            'status' => 'required|in:draft,active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sellerId = Auth::id();
            $listingIds = $request->listing_ids;
            $status = $request->status;

            // Verifica che tutte le inserzioni appartengano al venditore
            $listings = CardListing::whereIn('id', $listingIds)
                ->where('seller_id', $sellerId)
                ->get();

            if ($listings->count() !== count($listingIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una o più inserzioni non appartengono al venditore'
                ], 403);
            }

            // Aggiorna lo status
            CardListing::whereIn('id', $listingIds)
                ->where('seller_id', $sellerId)
                ->update(['status' => $status]);

            return response()->json([
                'success' => true,
                'message' => 'Status aggiornato per ' . count($listingIds) . ' inserzioni',
                'updated_count' => count($listingIds)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento bulk',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk delete listings
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listing_ids' => 'required|array|min:1',
            'listing_ids.*' => 'integer|exists:card_listings,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sellerId = Auth::id();
            $listingIds = $request->listing_ids;

            // Verifica che tutte le inserzioni appartengano al venditore
            $listings = CardListing::whereIn('id', $listingIds)
                ->where('seller_id', $sellerId)
                ->get();

            if ($listings->count() !== count($listingIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Una o più inserzioni non appartengono al venditore'
                ], 403);
            }

            // Disattiva le inserzioni invece di eliminarle
            CardListing::whereIn('id', $listingIds)
                ->where('seller_id', $sellerId)
                ->update(['status' => 'inactive']);

            return response()->json([
                'success' => true,
                'message' => 'Inserzioni disattivate con successo',
                'deleted_count' => count($listingIds)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'eliminazione bulk',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get listing analytics
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        $sellerId = Auth::id();
        $period = $request->get('period', '30'); // giorni

        $startDate = now()->subDays($period);

        $analytics = [
            'views' => [
                'total' => 0, // Implementare tracking delle visualizzazioni
                'trend' => 0
            ],
            'favorites' => [
                'total' => 0, // Implementare sistema di preferiti
                'trend' => 0
            ],
            'price_changes' => CardListing::where('seller_id', $sellerId)
                ->where('updated_at', '>=', $startDate)
                ->whereColumn('price', '!=', 'price')
                ->count(),
            'new_listings' => CardListing::where('seller_id', $sellerId)
                ->where('created_at', '>=', $startDate)
                ->count(),
            'status_changes' => CardListing::where('seller_id', $sellerId)
                ->where('updated_at', '>=', $startDate)
                ->whereColumn('status', '!=', 'status')
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Export listings to CSV
     */
    public function export(Request $request): JsonResponse
    {
        $sellerId = Auth::id();
        $status = $request->get('status', 'all');

        $query = CardListing::with([
            'cardModel.category',
            'cardModel.cardSet',
            'cardModel.player',
            'cardModel.team',
            'cardModel.league'
        ])->where('seller_id', $sellerId);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $listings = $query->get();

        $csvData = [];
        $csvData[] = [
            'ID', 'Titolo', 'Categoria', 'Set', 'Giocatore', 'Squadra', 'Lega',
            'Prezzo', 'Condizione', 'Quantità', 'Lingua', 'Foil', 'Firmata',
            'Alterata', 'Prima Edizione', 'Negoziabile', 'Status', 'Data Creazione'
        ];

        foreach ($listings as $listing) {
            $csvData[] = [
                $listing->id,
                $listing->title,
                $listing->cardModel->category->name ?? '',
                $listing->cardModel->cardSet->name ?? '',
                $listing->cardModel->player->name ?? '',
                $listing->cardModel->team->name ?? '',
                $listing->cardModel->league->name ?? '',
                $listing->price,
                $listing->condition,
                $listing->quantity,
                $listing->language ?? '',
                $listing->is_foil ? 'Sì' : 'No',
                $listing->is_signed ? 'Sì' : 'No',
                $listing->is_altered ? 'Sì' : 'No',
                $listing->is_first_edition ? 'Sì' : 'No',
                $listing->is_negotiable ? 'Sì' : 'No',
                $listing->status,
                $listing->created_at->format('d/m/Y H:i')
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $csvData,
            'filename' => 'card_listings_' . now()->format('Y-m-d_H-i-s') . '.csv'
        ]);
    }
}
