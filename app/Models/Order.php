<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_number',
        'buyer_id',
        'seller_id',
        'status',
        'subtotal',
        'shipping_cost',
        'tax_amount',
        'total_amount',
        'shipping_address',
        'billing_address',
        'tracking_number',
        'carrier_code',
        'tracking_url',
        'shipped_at',
        'delivered_at',
        'last_shipment_reminder_at',
        'notes',
        'stripe_payment_intent_id',
        'paid_at',
        'refunded_at',
        'refund_reason'
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'last_shipment_reminder_at' => 'datetime',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime'
    ];

    /**
     * Relazione con l'acquirente
     */
    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    /**
     * Relazione con il venditore principale
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Relazione con gli articoli dell'ordine
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Eventi di tracking associati all'ordine
     */
    public function trackingEvents(): HasMany
    {
        return $this->hasMany(OrderTrackingEvent::class);
    }

    /**
     * Feedback ricevuti per questo ordine
     */
    public function feedbacks(): HasMany
    {
        return $this->hasMany(OrderFeedback::class);
    }

    /**
     * Scope per ordini di un acquirente
     */
    public function scopeForBuyer($query, $buyerId)
    {
        return $query->where('buyer_id', $buyerId);
    }

    /**
     * Scope per ordini di un venditore
     */
    public function scopeForSeller($query, $sellerId)
    {
        return $query->whereHas('orderItems.cardListing', function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        });
    }

    /**
     * Scope per stato specifico
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Verifica se l'ordine può essere cancellato
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'pending_payment', 'confirmed']);
    }

    /**
     * Verifica se l'ordine può essere rimborsato
     */
    public function canBeRefunded(): bool
    {
        return in_array($this->status, ['confirmed', 'shipped', 'delivered']) && $this->paid_at;
    }

    /**
     * Ottiene tutti i venditori coinvolti nell'ordine
     */
    public function getSellers()
    {
        return $this->orderItems()
            ->with('cardListing.seller')
            ->get()
            ->pluck('cardListing.seller')
            ->unique('id');
    }

    /**
     * Calcola il totale per un venditore specifico
     */
    public function getTotalForSeller($sellerId): float
    {
        return $this->orderItems()
            ->whereHas('cardListing', function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->sum('total_price');
    }
}
