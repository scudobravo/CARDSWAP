<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modello per i dati di spedizione CardSwap V1 per ogni seller in un ordine
 * 
 * Un ordine multi-seller avrà un record OrderShipping per ogni seller.
 * Contiene i dati specifici di spedizione per quel seller:
 * - shipping_method
 * - package_bucket
 * - logistic_units_total
 * - shipping_price
 * - insurance_fee
 */
class OrderShipping extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_id',
        'shipping_method',
        'package_bucket',
        'logistic_units_total',
        'shipping_price',
        'insurance_fee',
    ];

    protected $casts = [
        'logistic_units_total' => 'decimal:2',
        'shipping_price' => 'decimal:2',
        'insurance_fee' => 'decimal:2',
    ];

    /**
     * Relazione con l'ordine
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relazione con il venditore
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Calcola il costo totale di spedizione (prezzo + assicurazione)
     * 
     * @return float
     */
    public function getTotalShippingCostAttribute(): float
    {
        return (float) ($this->shipping_price ?? 0) + (float) ($this->insurance_fee ?? 0);
    }
}
