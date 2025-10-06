<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Availability;
use App\Models\CardListing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AvailabilityController extends Controller
{

    /**
     * Verifica disponibilità di una singola inserzione
     */
    public function checkSingle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer|exists:card_listings,id',
            'quantity' => 'required|integer|min:1|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        $listing = CardListing::with('availability')->find($request->listing_id);
        
        if (!$listing) {
            return response()->json([
                'success' => false,
                'message' => 'Inserzione non trovata'
            ], 404);
        }

        // Crea disponibilità se non esiste
        if (!$listing->availability) {
            $availability = Availability::createForListing($listing);
        } else {
            $availability = $listing->availability;
            $availability->syncWithListing();
        }

        $isAvailable = $availability->isAvailable() && $availability->quantity_available >= $request->quantity;

        return response()->json([
            'success' => true,
            'data' => [
                'listing_id' => $listing->id,
                'available' => $isAvailable,
                'quantity_available' => $availability->quantity_available,
                'quantity_requested' => $request->quantity,
                'status' => $availability->status,
                'locked_until' => $availability->locked_until,
                'reserved_until' => $availability->reserved_until
            ]
        ]);
    }

    /**
     * Verifica disponibilità di multiple inserzioni
     */
    public function checkMultiple(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.listing_id' => 'required|integer|exists:card_listings,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        $results = [];
        $allAvailable = true;

        foreach ($request->items as $item) {
            $listing = CardListing::with('availability')->find($item['listing_id']);
            
            if (!$listing) {
                $results[] = [
                    'listing_id' => $item['listing_id'],
                    'available' => false,
                    'error' => 'Inserzione non trovata'
                ];
                $allAvailable = false;
                continue;
            }

            // Crea disponibilità se non esiste
            if (!$listing->availability) {
                $availability = Availability::createForListing($listing);
            } else {
                $availability = $listing->availability;
                $availability->syncWithListing();
            }

            $isAvailable = $availability->isAvailable() && $availability->quantity_available >= $item['quantity'];

            $results[] = [
                'listing_id' => $listing->id,
                'available' => $isAvailable,
                'quantity_available' => $availability->quantity_available,
                'quantity_requested' => $item['quantity'],
                'status' => $availability->status
            ];

            if (!$isAvailable) {
                $allAvailable = false;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'all_available' => $allAvailable,
                'items' => $results
            ]
        ]);
    }

    /**
     * Blocca temporaneamente le quantità per il checkout
     */
    public function lock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.listing_id' => 'required|integer|exists:card_listings,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
            'lock_minutes' => 'integer|min:5|max:30'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();
        $lockMinutes = $request->get('lock_minutes', 15);
        $results = [];
        $allLocked = true;

        DB::beginTransaction();

        try {
            foreach ($request->items as $item) {
                $listing = CardListing::with('availability')->find($item['listing_id']);
                
                if (!$listing) {
                    $results[] = [
                        'listing_id' => $item['listing_id'],
                        'locked' => false,
                        'error' => 'Inserzione non trovata'
                    ];
                    $allLocked = false;
                    continue;
                }

                // Crea disponibilità se non esiste
                if (!$listing->availability) {
                    $availability = Availability::createForListing($listing);
                } else {
                    $availability = $listing->availability;
                    $availability->syncWithListing();
                }

                // Prova a bloccare
                $locked = $availability->lockForUser($userId, $item['quantity'], $lockMinutes);

                $results[] = [
                    'listing_id' => $listing->id,
                    'locked' => $locked,
                    'quantity_locked' => $locked ? $item['quantity'] : 0,
                    'locked_until' => $locked ? $availability->locked_until : null,
                    'error' => $locked ? null : 'Quantità non disponibile'
                ];

                if (!$locked) {
                    $allLocked = false;
                }
            }

            if ($allLocked) {
                DB::commit();
                return response()->json([
                    'success' => true,
                    'message' => 'Quantità bloccate per il checkout',
                    'data' => [
                        'locked_until' => now()->addMinutes($lockMinutes),
                        'items' => $results
                    ]
                ]);
            } else {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Alcune quantità non sono disponibili',
                    'data' => [
                        'items' => $results
                    ]
                ]);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Errore nel blocco: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rilascia una prenotazione
     */
    public function release(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer|exists:card_listings,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();
        $listing = CardListing::with('availability')->find($request->listing_id);
        
        if (!$listing || !$listing->availability) {
            return response()->json([
                'success' => false,
                'message' => 'Inserzione o disponibilità non trovata'
            ], 404);
        }

        $availability = $listing->availability;
        
        // Verifica che l'utente abbia il lock/prenotazione
        if ($availability->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai il permesso di rilasciare questa prenotazione'
            ], 403);
        }

        $released = false;
        if ($availability->isLocked()) {
            $released = $availability->releaseLock();
        } elseif ($availability->isReserved()) {
            $released = $availability->releaseReservation();
        }

        return response()->json([
            'success' => $released,
            'message' => $released ? 'Prenotazione rilasciata' : 'Errore nel rilascio della prenotazione'
        ]);
    }

    /**
     * Conferma una prenotazione (vendita)
     */
    public function confirm(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer|exists:card_listings,id',
            'quantity' => 'required|integer|min:1|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();
        $listing = CardListing::with('availability')->find($request->listing_id);
        
        if (!$listing || !$listing->availability) {
            return response()->json([
                'success' => false,
                'message' => 'Inserzione o disponibilità non trovata'
            ], 404);
        }

        $availability = $listing->availability;
        
        // Verifica che l'utente abbia il lock/prenotazione
        if ($availability->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai il permesso di confermare questa prenotazione'
            ], 403);
        }

        $confirmed = $availability->confirmSale($request->quantity);

        return response()->json([
            'success' => $confirmed,
            'message' => $confirmed ? 'Vendita confermata' : 'Errore nella conferma della vendita'
        ]);
    }

    /**
     * Pulisci prenotazioni scadute (endpoint admin)
     */
    public function cleanExpired(): JsonResponse
    {
        $expiredLocks = Availability::expiredLocks()->get();
        $expiredReservations = Availability::expiredReservations()->get();
        
        $cleaned = 0;
        
        foreach ($expiredLocks as $availability) {
            if ($availability->releaseLock()) {
                $cleaned++;
            }
        }
        
        foreach ($expiredReservations as $availability) {
            if ($availability->releaseReservation()) {
                $cleaned++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Pulite {$cleaned} prenotazioni scadute",
            'cleaned_count' => $cleaned
        ]);
    }

    /**
     * Estende una prenotazione
     */
    public function extend(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'listing_id' => 'required|integer|exists:card_listings,id',
            'minutes' => 'required|integer|min:5|max:60'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = Auth::id();
        $listing = CardListing::with('availability')->find($request->listing_id);
        
        if (!$listing || !$listing->availability) {
            return response()->json([
                'success' => false,
                'message' => 'Inserzione o disponibilità non trovata'
            ], 404);
        }

        $availability = $listing->availability;
        
        // Verifica che l'utente abbia il lock/prenotazione
        if ($availability->user_id !== $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Non hai il permesso di estendere questa prenotazione'
            ], 403);
        }

        $extended = false;
        if ($availability->isLocked()) {
            $extended = $availability->extendLock($request->minutes);
        } elseif ($availability->isReserved()) {
            $extended = $availability->extendReservation($request->minutes);
        }

        return response()->json([
            'success' => $extended,
            'message' => $extended ? 'Prenotazione estesa' : 'Errore nell\'estensione della prenotazione',
            'data' => [
                'extended_until' => $extended ? $availability->fresh()->locked_until ?? $availability->fresh()->reserved_until : null
            ]
        ]);
    }
}