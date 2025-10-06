<?php

namespace App\Services;

use App\Models\CardListing;
use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AvailabilityService
{
    /**
     * Verifica la disponibilità real-time di un'inserzione
     */
    public function checkListingAvailability(int $listingId, int $requestedQuantity = 1): array
    {
        $listing = CardListing::find($listingId);
        
        if (!$listing) {
            return [
                'available' => false,
                'reason' => 'Inserzione non trovata',
                'available_quantity' => 0
            ];
        }

        // Verifica se l'inserzione è attiva
        if (!$listing->isAvailable()) {
            return [
                'available' => false,
                'reason' => 'Inserzione non disponibile',
                'available_quantity' => 0
            ];
        }

        // Calcola quantità già prenotata in ordini pending
        $reservedQuantity = $this->getReservedQuantity($listingId);
        $availableQuantity = $listing->quantity - $reservedQuantity;

        if ($availableQuantity < $requestedQuantity) {
            return [
                'available' => false,
                'reason' => 'Quantità insufficiente',
                'available_quantity' => max(0, $availableQuantity),
                'requested_quantity' => $requestedQuantity
            ];
        }

        return [
            'available' => true,
            'available_quantity' => $availableQuantity,
            'requested_quantity' => $requestedQuantity
        ];
    }

    /**
     * Verifica la disponibilità di multiple inserzioni
     */
    public function checkMultipleListingsAvailability(array $items): array
    {
        $results = [];
        $allAvailable = true;

        foreach ($items as $item) {
            $result = $this->checkListingAvailability(
                $item['listing_id'], 
                $item['quantity']
            );
            
            $results[$item['listing_id']] = $result;
            
            if (!$result['available']) {
                $allAvailable = false;
            }
        }

        return [
            'all_available' => $allAvailable,
            'items' => $results
        ];
    }

    /**
     * Prenota temporaneamente le quantità (per checkout)
     */
    public function reserveQuantities(array $items, int $reservationMinutes = 15): array
    {
        $reservationId = uniqid('res_', true);
        $reservedItems = [];

        foreach ($items as $item) {
            $availability = $this->checkListingAvailability(
                $item['listing_id'], 
                $item['quantity']
            );

            if (!$availability['available']) {
                return [
                    'success' => false,
                    'reason' => 'Quantità non disponibile per inserzione ' . $item['listing_id'],
                    'availability' => $availability
                ];
            }

            // Crea prenotazione temporanea
            $reservedItems[] = [
                'listing_id' => $item['listing_id'],
                'quantity' => $item['quantity'],
                'reservation_id' => $reservationId,
                'expires_at' => now()->addMinutes($reservationMinutes)
            ];
        }

        // Salva prenotazioni in cache
        Cache::put("reservation_{$reservationId}", $reservedItems, $reservationMinutes * 60);

        return [
            'success' => true,
            'reservation_id' => $reservationId,
            'expires_at' => now()->addMinutes($reservationMinutes),
            'items' => $reservedItems
        ];
    }

    /**
     * Rilascia una prenotazione
     */
    public function releaseReservation(string $reservationId): bool
    {
        return Cache::forget("reservation_{$reservationId}");
    }

    /**
     * Conferma una prenotazione (dopo pagamento riuscito)
     */
    public function confirmReservation(string $reservationId): bool
    {
        $reservation = Cache::get("reservation_{$reservationId}");
        
        if (!$reservation) {
            return false;
        }

        // Aggiorna le quantità delle inserzioni
        foreach ($reservation as $item) {
            $listing = CardListing::find($item['listing_id']);
            if ($listing) {
                $listing->decrement('quantity', $item['quantity']);
            }
        }

        // Rimuovi la prenotazione
        Cache::forget("reservation_{$reservationId}");
        
        return true;
    }

    /**
     * Calcola la quantità riservata per un'inserzione
     */
    private function getReservedQuantity(int $listingId): int
    {
        // Conta quantità in ordini pending
        $pendingQuantity = OrderItem::whereHas('order', function ($query) {
            $query->whereIn('status', ['pending', 'pending_payment', 'confirmed']);
        })
        ->where('card_listing_id', $listingId)
        ->sum('quantity');

        // Conta quantità in prenotazioni attive
        $reservedQuantity = 0;
        $reservations = Cache::get('active_reservations', []);
        
        foreach ($reservations as $reservation) {
            foreach ($reservation as $item) {
                if ($item['listing_id'] == $listingId) {
                    $reservedQuantity += $item['quantity'];
                }
            }
        }

        return $pendingQuantity + $reservedQuantity;
    }

    /**
     * Aggiorna la cache delle disponibilità
     */
    public function updateAvailabilityCache(int $listingId): void
    {
        $listing = CardListing::find($listingId);
        if ($listing) {
            $availableQuantity = $listing->quantity - $this->getReservedQuantity($listingId);
            
            Cache::put("availability_{$listingId}", [
                'available_quantity' => max(0, $availableQuantity),
                'updated_at' => now()
            ], 300); // 5 minuti
        }
    }

    /**
     * Ottieni disponibilità da cache
     */
    public function getCachedAvailability(int $listingId): ?array
    {
        return Cache::get("availability_{$listingId}");
    }

    /**
     * Pulisci prenotazioni scadute
     */
    public function cleanExpiredReservations(): int
    {
        $cleaned = 0;
        $reservations = Cache::get('active_reservations', []);
        
        foreach ($reservations as $reservationId => $reservation) {
            if (isset($reservation[0]['expires_at']) && 
                now()->isAfter($reservation[0]['expires_at'])) {
                Cache::forget("reservation_{$reservationId}");
                unset($reservations[$reservationId]);
                $cleaned++;
            }
        }
        
        Cache::put('active_reservations', $reservations, 3600);
        return $cleaned;
    }
}
