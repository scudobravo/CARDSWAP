<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Availability extends Model
{
    use HasFactory;

    protected $fillable = [
        'card_listing_id',
        'user_id',
        'status',
        'quantity_available',
        'quantity_locked',
        'quantity_reserved',
        'quantity_sold',
        'locked_until',
        'reserved_until',
        'lock_metadata'
    ];

    protected $casts = [
        'locked_until' => 'datetime',
        'reserved_until' => 'datetime',
        'lock_metadata' => 'array',
        'quantity_available' => 'integer',
        'quantity_locked' => 'integer',
        'quantity_reserved' => 'integer',
        'quantity_sold' => 'integer'
    ];

    /**
     * Relazione con CardListing
     */
    public function cardListing(): BelongsTo
    {
        return $this->belongsTo(CardListing::class);
    }

    /**
     * Relazione con User (utente che ha il lock)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope per disponibilità attive
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available')
                    ->where('quantity_available', '>', 0);
    }

    /**
     * Scope per lock scaduti
     */
    public function scopeExpiredLocks($query)
    {
        return $query->where('status', 'locked')
                    ->where('locked_until', '<', now());
    }

    /**
     * Scope per prenotazioni scadute
     */
    public function scopeExpiredReservations($query)
    {
        return $query->where('status', 'reserved')
                    ->where('reserved_until', '<', now());
    }

    /**
     * Verifica se l'inserzione è disponibile
     */
    public function isAvailable(): bool
    {
        return $this->status === 'available' && $this->quantity_available > 0;
    }

    /**
     * Verifica se l'inserzione è bloccata
     */
    public function isLocked(): bool
    {
        return $this->status === 'locked' && 
               $this->locked_until && 
               $this->locked_until->isFuture();
    }

    /**
     * Verifica se l'inserzione è prenotata
     */
    public function isReserved(): bool
    {
        return $this->status === 'reserved' && 
               $this->reserved_until && 
               $this->reserved_until->isFuture();
    }

    /**
     * Verifica se l'inserzione è venduta
     */
    public function isSold(): bool
    {
        return $this->status === 'sold';
    }

    /**
     * Blocca una quantità per un utente
     */
    public function lockForUser(int $userId, int $quantity, int $minutes = 15): bool
    {
        if (!$this->isAvailable() || $this->quantity_available < $quantity) {
            return false;
        }

        $this->update([
            'user_id' => $userId,
            'status' => 'locked',
            'quantity_locked' => $quantity,
            'quantity_available' => $this->quantity_available - $quantity,
            'locked_until' => now()->addMinutes($minutes),
            'lock_metadata' => [
                'locked_at' => now()->toISOString(),
                'quantity' => $quantity,
                'session_id' => session()->getId()
            ]
        ]);

        return true;
    }

    /**
     * Prenota una quantità per un utente
     */
    public function reserveForUser(int $userId, int $quantity, int $minutes = 30): bool
    {
        if (!$this->isAvailable() || $this->quantity_available < $quantity) {
            return false;
        }

        $this->update([
            'user_id' => $userId,
            'status' => 'reserved',
            'quantity_reserved' => $quantity,
            'quantity_available' => $this->quantity_available - $quantity,
            'reserved_until' => now()->addMinutes($minutes),
            'lock_metadata' => [
                'reserved_at' => now()->toISOString(),
                'quantity' => $quantity,
                'session_id' => session()->getId()
            ]
        ]);

        return true;
    }

    /**
     * Rilascia il lock
     */
    public function releaseLock(): bool
    {
        if (!$this->isLocked()) {
            return false;
        }

        $this->update([
            'user_id' => null,
            'status' => 'available',
            'quantity_available' => $this->quantity_available + $this->quantity_locked,
            'quantity_locked' => 0,
            'locked_until' => null,
            'lock_metadata' => null
        ]);

        return true;
    }

    /**
     * Rilascia la prenotazione
     */
    public function releaseReservation(): bool
    {
        if (!$this->isReserved()) {
            return false;
        }

        $this->update([
            'user_id' => null,
            'status' => 'available',
            'quantity_available' => $this->quantity_available + $this->quantity_reserved,
            'quantity_reserved' => 0,
            'reserved_until' => null,
            'lock_metadata' => null
        ]);

        return true;
    }

    /**
     * Conferma la vendita
     */
    public function confirmSale(int $quantity): bool
    {
        if (!$this->isAvailable() && !$this->isLocked() && !$this->isReserved()) {
            return false;
        }

        $this->update([
            'status' => 'sold',
            'quantity_sold' => $this->quantity_sold + $quantity,
            'quantity_available' => max(0, $this->quantity_available - $quantity),
            'quantity_locked' => max(0, $this->quantity_locked - $quantity),
            'quantity_reserved' => max(0, $this->quantity_reserved - $quantity),
            'user_id' => null,
            'locked_until' => null,
            'reserved_until' => null,
            'lock_metadata' => null
        ]);

        return true;
    }

    /**
     * Estende il lock
     */
    public function extendLock(int $minutes = 15): bool
    {
        if (!$this->isLocked()) {
            return false;
        }

        $this->update([
            'locked_until' => now()->addMinutes($minutes),
            'lock_metadata' => array_merge($this->lock_metadata ?? [], [
                'extended_at' => now()->toISOString(),
                'extended_minutes' => $minutes
            ])
        ]);

        return true;
    }

    /**
     * Estende la prenotazione
     */
    public function extendReservation(int $minutes = 30): bool
    {
        if (!$this->isReserved()) {
            return false;
        }

        $this->update([
            'reserved_until' => now()->addMinutes($minutes),
            'lock_metadata' => array_merge($this->lock_metadata ?? [], [
                'extended_at' => now()->toISOString(),
                'extended_minutes' => $minutes
            ])
        ]);

        return true;
    }

    /**
     * Ottiene il tempo rimanente per il lock
     */
    public function getLockTimeRemaining(): ?int
    {
        if (!$this->isLocked()) {
            return null;
        }

        return $this->locked_until->diffInSeconds(now());
    }

    /**
     * Ottiene il tempo rimanente per la prenotazione
     */
    public function getReservationTimeRemaining(): ?int
    {
        if (!$this->isReserved()) {
            return null;
        }

        return $this->reserved_until->diffInSeconds(now());
    }

    /**
     * Crea o aggiorna la disponibilità per un'inserzione
     */
    public static function createForListing(CardListing $listing): self
    {
        return self::updateOrCreate(
            ['card_listing_id' => $listing->id],
            [
                'status' => 'available',
                'quantity_available' => $listing->quantity,
                'quantity_locked' => 0,
                'quantity_reserved' => 0,
                'quantity_sold' => 0
            ]
        );
    }

    /**
     * Sincronizza la disponibilità con la quantità dell'inserzione
     */
    public function syncWithListing(): bool
    {
        $listing = $this->cardListing;
        if (!$listing) {
            return false;
        }

        $totalQuantity = $listing->quantity;
        $currentTotal = $this->quantity_available + $this->quantity_locked + $this->quantity_reserved + $this->quantity_sold;

        if ($currentTotal !== $totalQuantity) {
            $this->update([
                'quantity_available' => $totalQuantity - $this->quantity_sold - $this->quantity_locked - $this->quantity_reserved
            ]);
        }

        return true;
    }
}