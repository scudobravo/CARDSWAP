<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CardListing extends Model
{
    use HasFactory;
    protected $fillable = [
        'card_model_id',
        'seller_id',
        'price',
        'condition',
        'quantity',
        'language',
        'is_foil',
        'is_signed',
        'is_altered',
        'is_first_edition',
        'is_negotiable',
        'description',
        'status',
        'published_at',
        'rejection_reason',
        'images'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'is_foil' => 'boolean',
        'is_signed' => 'boolean',
        'is_altered' => 'boolean',
        'is_first_edition' => 'boolean',
        'is_negotiable' => 'boolean',
        'images' => 'array'
    ];

    /**
     * Relazione con il modello della carta
     */
    public function cardModel(): BelongsTo
    {
        return $this->belongsTo(CardModel::class);
    }

    /**
     * Relazione con il venditore
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Relazione con le zone di spedizione supportate
     */
    public function shippingZones(): BelongsToMany
    {
        return $this->belongsToMany(ShippingZone::class, 'card_listing_shipping_zones')
                    ->withPivot(['shipping_cost', 'delivery_days_min', 'delivery_days_max'])
                    ->withTimestamps();
    }

    /**
     * Relazione con la disponibilità
     */
    public function availability()
    {
        return $this->hasOne(Availability::class);
    }

    /**
     * Scope per inserzioni attive
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope per inserzioni di un venditore specifico
     */
    public function scopeForSeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Verifica se l'inserzione è disponibile
     */
    public function isAvailable()
    {
        return $this->status === 'active' && $this->quantity > 0;
    }

    /**
     * Calcola il costo di spedizione per una zona specifica
     */
    public function getShippingCostForZone($shippingZoneId)
    {
        $pivot = $this->shippingZones()->where('shipping_zone_id', $shippingZoneId)->first();
        
        if ($pivot && $pivot->pivot->shipping_cost) {
            return $pivot->pivot->shipping_cost;
        }

        // Fallback al costo della zona di spedizione
        $zone = ShippingZone::find($shippingZoneId);
        return $zone ? $zone->shipping_cost : 0;
    }

    /**
     * Scope per inserzioni in bozza
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope per inserzioni in attesa di revisione
     */
    public function scopePendingReview($query)
    {
        return $query->where('status', 'pending_review');
    }

    /**
     * Scope per inserzioni approvate
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope per inserzioni pausate
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    /**
     * Scope per inserzioni rifiutate
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope per inserzioni scadute
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope per inserzioni inattive
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope per inserzioni con prezzo negoziabile
     */
    public function scopeNegotiable($query)
    {
        return $query->where('is_negotiable', true);
    }

    /**
     * Scope per inserzioni foil
     */
    public function scopeFoil($query)
    {
        return $query->where('is_foil', true);
    }

    /**
     * Scope per inserzioni firmate
     */
    public function scopeSigned($query)
    {
        return $query->where('is_signed', true);
    }

    /**
     * Scope per inserzioni alterate
     */
    public function scopeAltered($query)
    {
        return $query->where('is_altered', true);
    }

    /**
     * Scope per prime edizioni
     */
    public function scopeFirstEdition($query)
    {
        return $query->where('is_first_edition', true);
    }

    /**
     * Verifica se l'inserzione è in bozza
     */
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    /**
     * Verifica se l'inserzione è in attesa di revisione
     */
    public function isPendingReview()
    {
        return $this->status === 'pending_review';
    }

    /**
     * Verifica se l'inserzione è approvata
     */
    public function isApproved()
    {
        return $this->status === 'approved';
    }

    /**
     * Verifica se l'inserzione è pausata
     */
    public function isPaused()
    {
        return $this->status === 'paused';
    }

    /**
     * Verifica se l'inserzione è rifiutata
     */
    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    /**
     * Verifica se l'inserzione è scaduta
     */
    public function isExpired()
    {
        return $this->status === 'expired';
    }

    /**
     * Verifica se l'inserzione è inattiva
     */
    public function isInactive()
    {
        return $this->status === 'inactive';
    }

    /**
     * Invia l'inserzione per revisione
     */
    public function submitForReview()
    {
        $this->update(['status' => 'pending_review']);
    }

    /**
     * Approva l'inserzione
     */
    public function approve()
    {
        $this->update(['status' => 'approved']);
    }

    /**
     * Rifiuta l'inserzione
     */
    public function reject($reason = null)
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason
        ]);
    }

    /**
     * Pubblica l'inserzione
     */
    public function publish()
    {
        $this->update([
            'status' => 'active',
            'published_at' => now()
        ]);
    }

    /**
     * Attiva l'inserzione
     */
    public function activate()
    {
        $this->update(['status' => 'active']);
    }

    /**
     * Mette in pausa l'inserzione
     */
    public function pause()
    {
        $this->update(['status' => 'paused']);
    }

    /**
     * Disattiva l'inserzione
     */
    public function deactivate()
    {
        $this->update(['status' => 'inactive']);
    }

    /**
     * Marca l'inserzione come scaduta
     */
    public function expire()
    {
        $this->update(['status' => 'expired']);
    }

    /**
     * Marca l'inserzione come venduta
     */
    public function markAsSold()
    {
        $this->update(['status' => 'sold']);
    }

    /**
     * Formatta il prezzo per la visualizzazione
     */
    public function getFormattedPriceAttribute()
    {
        return '€ ' . number_format($this->price, 2, ',', '.');
    }

    /**
     * Ottiene il titolo dell'inserzione basato sul modello della carta
     */
    public function getTitleAttribute()
    {
        $title = $this->cardModel->player->name ?? 'Carta';
        
        if ($this->is_foil) {
            $title .= ' (Foil)';
        }
        
        if ($this->is_signed) {
            $title .= ' (Firmata)';
        }
        
        if ($this->is_altered) {
            $title .= ' (Alterata)';
        }
        
        if ($this->is_first_edition) {
            $title .= ' (Prima Edizione)';
        }
        
        return $title;
    }
}
