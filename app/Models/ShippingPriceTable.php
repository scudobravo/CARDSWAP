<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingPriceTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'name',
    ];

    protected $casts = [
        // Nessun cast necessario per i campi attuali
    ];

    /**
     * Relazione con il venditore proprietario della tabella
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    /**
     * Relazione con i paesi associati a questa tabella
     */
    public function countries(): HasMany
    {
        return $this->hasMany(ShippingPriceTableCountry::class);
    }

    /**
     * Relazione con le tariffe di questa tabella
     */
    public function rates(): HasMany
    {
        return $this->hasMany(ShippingPriceTableRate::class);
    }

    /**
     * Relazione con le configurazioni assicurazione di questa tabella
     */
    public function insuredConfigs(): HasMany
    {
        return $this->hasMany(ShippingPriceTableInsured::class);
    }

    /**
     * Scope per filtrare per venditore
     */
    public function scopeForSeller($query, $sellerId)
    {
        return $query->where('seller_id', $sellerId);
    }

    /**
     * Ottiene i codici paese associati a questa tabella
     * 
     * @return array<string>
     */
    public function countryCodes(): array
    {
        return $this->countries()
            ->pluck('country_code')
            ->toArray();
    }

    /**
     * Verifica se questa tabella supporta un paese specifico
     * 
     * @param string $countryCode
     * @return bool
     */
    public function supportsCountry(string $countryCode): bool
    {
        return $this->countries()
            ->where('country_code', $countryCode)
            ->exists();
    }
}
