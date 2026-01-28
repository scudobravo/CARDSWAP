<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingPriceTableCountry extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_price_table_id',
        'seller_id',
        'country_code',
    ];

    protected $casts = [
        // Nessun cast necessario per i campi attuali
    ];

    /**
     * Relazione con la tabella prezzi
     */
    public function shippingPriceTable(): BelongsTo
    {
        return $this->belongsTo(ShippingPriceTable::class);
    }

    /**
     * Relazione con il venditore (ridondante ma necessaria per vincolo UNIQUE)
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Scope per filtrare per venditore e paese
     */
    public function scopeForSellerAndCountry($query, $sellerId, $countryCode)
    {
        return $query->where('seller_id', $sellerId)
            ->where('country_code', $countryCode);
    }

    /**
     * Verifica se esiste già un'associazione per venditore e paese
     * 
     * @param int $sellerId
     * @param string $countryCode
     * @return bool
     */
    public static function existsForSellerAndCountry(int $sellerId, string $countryCode): bool
    {
        return static::where('seller_id', $sellerId)
            ->where('country_code', $countryCode)
            ->exists();
    }
}
