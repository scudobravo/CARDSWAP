<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingPriceTableRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_price_table_id',
        'package_bucket',
        'shipping_method',
        'price_eur',
    ];

    protected $casts = [
        'price_eur' => 'decimal:2',
    ];

    /**
     * Relazione con la tabella prezzi
     */
    public function shippingPriceTable(): BelongsTo
    {
        return $this->belongsTo(ShippingPriceTable::class);
    }

    /**
     * Verifica se questa tariffa è disponibile
     * 
     * Una tariffa è disponibile se price_eur non è NULL
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->price_eur !== null;
    }

    /**
     * Ottiene il prezzo o restituisce null se non disponibile
     * 
     * @return float|null
     */
    public function getPrice(): ?float
    {
        return $this->price_eur;
    }
}
