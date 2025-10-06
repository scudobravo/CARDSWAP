<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Availability;
use App\Models\CardListing;
use Illuminate\Support\Facades\Auth;

class CheckAvailability
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Verifica se la richiesta contiene informazioni sui prodotti
        $items = $request->input('items', []);
        
        if (empty($items)) {
            return $next($request);
        }

        $unavailableItems = [];
        $userId = Auth::id();

        foreach ($items as $item) {
            $listingId = $item['listing_id'] ?? $item['id'] ?? null;
            $quantity = $item['quantity'] ?? 1;

            if (!$listingId) {
                continue;
            }

            $listing = CardListing::with('availability')->find($listingId);
            
            if (!$listing) {
                $unavailableItems[] = [
                    'listing_id' => $listingId,
                    'error' => 'Inserzione non trovata'
                ];
                continue;
            }

            // Crea disponibilità se non esiste
            if (!$listing->availability) {
                $availability = Availability::createForListing($listing);
            } else {
                $availability = $listing->availability;
                $availability->syncWithListing();
            }

            // Verifica disponibilità
            if (!$availability->isAvailable() || $availability->quantity_available < $quantity) {
                $unavailableItems[] = [
                    'listing_id' => $listingId,
                    'error' => 'Quantità non disponibile',
                    'available' => $availability->quantity_available,
                    'requested' => $quantity,
                    'status' => $availability->status
                ];
                continue;
            }

            // Verifica se l'utente ha già una prenotazione per questo item
            if ($availability->user_id && $availability->user_id !== $userId) {
                $unavailableItems[] = [
                    'listing_id' => $listingId,
                    'error' => 'Già prenotato da un altro utente',
                    'status' => $availability->status
                ];
                continue;
            }
        }

        // Se ci sono item non disponibili, restituisci errore
        if (!empty($unavailableItems)) {
            return response()->json([
                'success' => false,
                'message' => 'Alcuni prodotti non sono disponibili',
                'unavailable_items' => $unavailableItems
            ], 422);
        }

        return $next($request);
    }
}