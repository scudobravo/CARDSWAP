<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\CardModel;
use App\Models\CardListing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WishlistController extends Controller
{
    /**
     * Get user's wishlist
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Wishlist::with([
                'cardModel.category',
                'cardModel.cardSet',
                'cardModel.player',
                'cardModel.team',
                'cardModel.league'
            ])->where('user_id', Auth::id());

            // Filtra per tipo se specificato
            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            // Ordinamento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginazione
            $perPage = $request->get('per_page', 20);
            $wishlist = $query->paginate($perPage);

            // Aggiungi informazioni sui prezzi più bassi disponibili
            $wishlist->getCollection()->transform(function ($item) {
                $lowestPrice = CardListing::where('card_model_id', $item->card_model_id)
                    ->where('status', 'active')
                    ->min('price');
                
                $item->lowest_price = $lowestPrice;
                $item->has_active_listings = CardListing::where('card_model_id', $item->card_model_id)
                    ->where('status', 'active')
                    ->exists();
                
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $wishlist->items(),
                'pagination' => [
                    'current_page' => $wishlist->currentPage(),
                    'last_page' => $wishlist->lastPage(),
                    'per_page' => $wishlist->perPage(),
                    'total' => $wishlist->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il recupero della wishlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add item to wishlist
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'card_model_id' => 'required|exists:card_models,id',
            'type' => 'required|in:want,have',
            'notes' => 'nullable|string|max:500',
            'priority' => 'nullable|in:low,medium,high',
            'max_price' => 'nullable|numeric|min:0.01',
            'condition_preference' => 'nullable|in:mint,near_mint,excellent,good,light_played,played,poor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Verifica se l'elemento è già nella wishlist
            $existingItem = Wishlist::where('user_id', Auth::id())
                ->where('card_model_id', $request->card_model_id)
                ->where('type', $request->type)
                ->first();

            if ($existingItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Elemento già presente nella wishlist'
                ], 409);
            }

            $wishlistData = $request->all();
            $wishlistData['user_id'] = Auth::id();

            $wishlist = Wishlist::create($wishlistData);

            return response()->json([
                'success' => true,
                'message' => 'Elemento aggiunto alla wishlist',
                'data' => $wishlist->load([
                    'cardModel.category',
                    'cardModel.cardSet',
                    'cardModel.player',
                    'cardModel.team',
                    'cardModel.league'
                ])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiunta alla wishlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update wishlist item
     */
    public function update(Request $request, Wishlist $wishlist): JsonResponse
    {
        // Verifica che l'utente sia il proprietario
        if ($wishlist->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorizzato a modificare questo elemento'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:want,have',
            'notes' => 'nullable|string|max:500',
            'priority' => 'nullable|in:low,medium,high',
            'max_price' => 'nullable|numeric|min:0.01',
            'condition_preference' => 'nullable|in:mint,near_mint,excellent,good,light_played,played,poor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $wishlist->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Elemento wishlist aggiornato',
                'data' => $wishlist->fresh()->load([
                    'cardModel.category',
                    'cardModel.cardSet',
                    'cardModel.player',
                    'cardModel.team',
                    'cardModel.league'
                ])
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiornamento dell\'elemento wishlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove item from wishlist
     */
    public function destroy(Wishlist $wishlist): JsonResponse
    {
        // Verifica che l'utente sia il proprietario
        if ($wishlist->user_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorizzato a rimuovere questo elemento'
            ], 403);
        }

        try {
            $wishlist->delete();

            return response()->json([
                'success' => true,
                'message' => 'Elemento rimosso dalla wishlist'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la rimozione dell\'elemento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add multiple items to wishlist
     */
    public function addMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.card_model_id' => 'required|exists:card_models,id',
            'items.*.type' => 'required|in:want,have',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.priority' => 'nullable|in:low,medium,high',
            'items.*.max_price' => 'nullable|numeric|min:0.01',
            'items.*.condition_preference' => 'nullable|in:mint,near_mint,excellent,good,light_played,played,poor',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $addedItems = [];
            $skippedItems = [];

            foreach ($request->items as $itemData) {
                // Verifica se l'elemento è già nella wishlist
                $existingItem = Wishlist::where('user_id', Auth::id())
                    ->where('card_model_id', $itemData['card_model_id'])
                    ->where('type', $itemData['type'])
                    ->first();

                if ($existingItem) {
                    $skippedItems[] = [
                        'card_model_id' => $itemData['card_model_id'],
                        'reason' => 'Elemento già presente nella wishlist'
                    ];
                    continue;
                }

                $itemData['user_id'] = Auth::id();
                $wishlist = Wishlist::create($itemData);
                
                $addedItems[] = $wishlist->load([
                    'cardModel.category',
                    'cardModel.cardSet',
                    'cardModel.player',
                    'cardModel.team',
                    'cardModel.league'
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($addedItems) . ' elementi aggiunti alla wishlist',
                'data' => [
                    'added_items' => $addedItems,
                    'skipped_items' => $skippedItems,
                    'total_added' => count($addedItems),
                    'total_skipped' => count($skippedItems),
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'aggiunta multipla alla wishlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get wishlist statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $userId = Auth::id();

            $stats = [
                'total_items' => Wishlist::where('user_id', $userId)->count(),
                'want_items' => Wishlist::where('user_id', $userId)->where('type', 'want')->count(),
                'have_items' => Wishlist::where('user_id', $userId)->where('type', 'have')->count(),
                'by_priority' => [
                    'high' => Wishlist::where('user_id', $userId)->where('priority', 'high')->count(),
                    'medium' => Wishlist::where('user_id', $userId)->where('priority', 'medium')->count(),
                    'low' => Wishlist::where('user_id', $userId)->where('priority', 'low')->count(),
                ],
                'by_category' => DB::table('wishlists')
                    ->join('card_models', 'wishlists.card_model_id', '=', 'card_models.id')
                    ->join('categories', 'card_models.category_id', '=', 'categories.id')
                    ->where('wishlists.user_id', $userId)
                    ->select('categories.name', DB::raw('count(*) as count'))
                    ->groupBy('categories.id', 'categories.name')
                    ->orderBy('count', 'desc')
                    ->get(),
                'recent_additions' => Wishlist::where('user_id', $userId)
                    ->with(['cardModel.category', 'cardModel.cardSet'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante il recupero delle statistiche',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search wishlist items
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q', '');
        
        if (strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => []
            ]);
        }

        try {
            $wishlistItems = Wishlist::with([
                'cardModel.category',
                'cardModel.cardSet',
                'cardModel.player',
                'cardModel.team',
                'cardModel.league'
            ])
            ->where('user_id', Auth::id())
            ->whereHas('cardModel', function ($q) use ($query) {
                $q->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('set_name', 'LIKE', "%{$query}%")
                  ->orWhere('description', 'LIKE', "%{$query}%");
            })
            ->orWhere('notes', 'LIKE', "%{$query}%")
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

            return response()->json([
                'success' => true,
                'data' => $wishlistItems
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante la ricerca nella wishlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export wishlist
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'all'); // all, want, have
            $format = $request->get('format', 'json'); // json, csv

            $query = Wishlist::with([
                'cardModel.category',
                'cardModel.cardSet',
                'cardModel.player',
                'cardModel.team',
                'cardModel.league'
            ])->where('user_id', Auth::id());

            if ($type !== 'all') {
                $query->where('type', $type);
            }

            $wishlistItems = $query->orderBy('created_at', 'desc')->get();

            $exportData = $wishlistItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => $item->type,
                    'card_name' => $item->cardModel->name,
                    'set_name' => $item->cardModel->set_name,
                    'year' => $item->cardModel->year,
                    'category' => $item->cardModel->category->name ?? '',
                    'player' => $item->cardModel->player->name ?? '',
                    'team' => $item->cardModel->team->name ?? '',
                    'priority' => $item->priority,
                    'max_price' => $item->max_price,
                    'condition_preference' => $item->condition_preference,
                    'notes' => $item->notes,
                    'added_date' => $item->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'format' => $format,
                    'type' => $type,
                    'total_items' => $exportData->count(),
                    'export_date' => now()->format('Y-m-d H:i:s'),
                    'items' => $exportData,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore durante l\'esportazione della wishlist',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
