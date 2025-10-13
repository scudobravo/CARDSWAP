<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ShippingZone extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'country_code',
        'region',
        'city',
        'postal_code',
        'shipping_cost',
        'base_cost',
        'cost_per_kg',
        'cost_per_euro',
        'free_shipping_threshold',
        'max_weight_kg',
        'max_value_euro',
        'requires_seller_approval',
        'allowed_seller_roles',
        'min_seller_rating',
        'min_seller_sales',
        'delivery_days_min',
        'delivery_days_max',
        'is_active'
    ];

    protected $casts = [
        'shipping_cost' => 'decimal:2',
        'base_cost' => 'decimal:2',
        'cost_per_kg' => 'decimal:2',
        'cost_per_euro' => 'decimal:2',
        'free_shipping_threshold' => 'decimal:2',
        'max_weight_kg' => 'decimal:2',
        'max_value_euro' => 'decimal:2',
        'requires_seller_approval' => 'boolean',
        'allowed_seller_roles' => 'array',
        'min_seller_rating' => 'integer',
        'min_seller_sales' => 'integer',
        'delivery_days_min' => 'integer',
        'delivery_days_max' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Relazione con l'utente proprietario della zona
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relazione con le inserzioni che supportano questa zona di spedizione
     */
    public function cardListings(): BelongsToMany
    {
        return $this->belongsToMany(CardListing::class, 'card_listing_shipping_zones');
    }

    /**
     * Scope per zone attive
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope per paese specifico
     */
    public function scopeForCountry($query, $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    /**
     * Scope per zone di un utente specifico
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Calcola il costo di spedizione basato sul valore dell'ordine e peso
     */
    public function calculateShippingCost($orderValue = 0, $weight = 0)
    {
        // Se c'è una soglia di spedizione gratuita e l'ordine la supera
        if ($this->free_shipping_threshold && $orderValue >= $this->free_shipping_threshold) {
            return 0;
        }

        // Verifica limiti massimi
        if ($this->max_weight_kg && $weight > $this->max_weight_kg) {
            throw new \Exception("Peso massimo superato per questa zona di spedizione");
        }

        if ($this->max_value_euro && $orderValue > $this->max_value_euro) {
            throw new \Exception("Valore massimo superato per questa zona di spedizione");
        }

        // Calcola costo basato su peso e valore
        $cost = $this->base_cost;
        
        // Aggiungi costo per peso (in kg)
        if ($this->cost_per_kg > 0 && $weight > 0) {
            $cost += $weight * $this->cost_per_kg;
        }
        
        // Aggiungi costo per valore (in euro)
        if ($this->cost_per_euro > 0 && $orderValue > 0) {
            $cost += $orderValue * $this->cost_per_euro;
        }

        // Se non ci sono costi dinamici, usa il costo fisso
        if ($cost == $this->base_cost && $this->shipping_cost > 0) {
            $cost = $this->shipping_cost;
        }

        return max(0, $cost);
    }

    /**
     * Verifica se la zona supporta un indirizzo specifico
     */
    public function supportsAddress($countryCode, $region = null, $city = null, $postalCode = null)
    {
        // Verifica paese
        if ($this->country_code !== $countryCode) {
            return false;
        }

        // Se specificata una regione, deve corrispondere
        if ($this->region && $region && $this->region !== $region) {
            return false;
        }

        // Se specificata una città, deve corrispondere
        if ($this->city && $city && $this->city !== $city) {
            return false;
        }

        // Se specificato un CAP, deve corrispondere
        if ($this->postal_code && $postalCode && $this->postal_code !== $postalCode) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se un venditore può usare questa zona di spedizione
     */
    public function canBeUsedBySeller($seller)
    {
        // Verifica se richiede approvazione venditore
        if ($this->requires_seller_approval && !$seller->is_approved_seller) {
            return false;
        }

        // Verifica ruoli consentiti
        if ($this->allowed_seller_roles && !in_array($seller->role, $this->allowed_seller_roles)) {
            return false;
        }

        // Verifica rating minimo
        if ($this->min_seller_rating && $seller->average_rating < $this->min_seller_rating) {
            return false;
        }

        // Verifica vendite minime
        if ($this->min_seller_sales && $seller->total_sales < $this->min_seller_sales) {
            return false;
        }

        return true;
    }

    /**
     * Ottieni le zone disponibili per un venditore
     */
    public static function getAvailableForSeller($seller)
    {
        return static::active()->get()->filter(function ($zone) use ($seller) {
            return $zone->canBeUsedBySeller($seller);
        });
    }

    /**
     * Calcola il peso stimato di un ordine
     */
    public function calculateOrderWeight($items)
    {
        $totalWeight = 0;
        
        foreach ($items as $item) {
            // Peso stimato per carta: 0.01 kg (10g)
            $cardWeight = 0.01;
            $totalWeight += $cardWeight * $item['quantity'];
        }
        
        return $totalWeight;
    }
}
