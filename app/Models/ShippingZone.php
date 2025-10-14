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
        'is_active',
        'zone_type',
        'included_countries',
        'excluded_countries',
        'included_regions',
        'excluded_regions',
        'use_shippo_pricing',
        'shippo_carrier',
        'shippo_service_type',
        'shippo_markup',
        'shippo_require_insurance',
        'is_worldwide',
        'description',
        'sort_order'
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
        'is_active' => 'boolean',
        'included_countries' => 'array',
        'excluded_countries' => 'array',
        'included_regions' => 'array',
        'excluded_regions' => 'array',
        'use_shippo_pricing' => 'boolean',
        'shippo_markup' => 'decimal:2',
        'shippo_require_insurance' => 'boolean',
        'is_worldwide' => 'boolean',
        'sort_order' => 'integer'
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

    /**
     * Verifica se un paese è supportato da questa zona
     */
    public function supportsCountry($countryCode)
    {
        // Se è una zona mondiale
        if ($this->is_worldwide) {
            // Controlla se il paese è escluso
            if ($this->excluded_countries && in_array($countryCode, $this->excluded_countries)) {
                return false;
            }
            return true;
        }

        // Se ci sono paesi inclusi specifici
        if ($this->included_countries && !empty($this->included_countries)) {
            if (!in_array($countryCode, $this->included_countries)) {
                return false;
            }
        }

        // Controlla se il paese è escluso
        if ($this->excluded_countries && in_array($countryCode, $this->excluded_countries)) {
            return false;
        }

        // Per zone legacy con country_code
        if ($this->country_code && $this->country_code !== $countryCode) {
            return false;
        }

        return true;
    }

    /**
     * Verifica se una regione è supportata da questa zona
     */
    public function supportsRegion($region)
    {
        // Se ci sono regioni incluse specifiche
        if ($this->included_regions && !empty($this->included_regions)) {
            if (!in_array($region, $this->included_regions)) {
                return false;
            }
        }

        // Controlla se la regione è esclusa
        if ($this->excluded_regions && in_array($region, $this->excluded_regions)) {
            return false;
        }

        return true;
    }

    /**
     * Ottieni tutti i paesi supportati da questa zona
     */
    public function getSupportedCountries()
    {
        if ($this->is_worldwide) {
            // Lista di tutti i paesi del mondo (codici ISO)
            $allCountries = [
                'AD', 'AE', 'AF', 'AG', 'AI', 'AL', 'AM', 'AO', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AW', 'AX', 'AZ',
                'BA', 'BB', 'BD', 'BE', 'BF', 'BG', 'BH', 'BI', 'BJ', 'BL', 'BM', 'BN', 'BO', 'BQ', 'BR', 'BS',
                'BT', 'BV', 'BW', 'BY', 'BZ', 'CA', 'CC', 'CD', 'CF', 'CG', 'CH', 'CI', 'CK', 'CL', 'CM', 'CN',
                'CO', 'CR', 'CU', 'CV', 'CW', 'CX', 'CY', 'CZ', 'DE', 'DJ', 'DK', 'DM', 'DO', 'DZ', 'EC', 'EE',
                'EG', 'EH', 'ER', 'ES', 'ET', 'FI', 'FJ', 'FK', 'FM', 'FO', 'FR', 'GA', 'GB', 'GD', 'GE', 'GF',
                'GG', 'GH', 'GI', 'GL', 'GM', 'GN', 'GP', 'GQ', 'GR', 'GS', 'GT', 'GU', 'GW', 'GY', 'HK', 'HM',
                'HN', 'HR', 'HT', 'HU', 'ID', 'IE', 'IL', 'IM', 'IN', 'IO', 'IQ', 'IR', 'IS', 'IT', 'JE', 'JM',
                'JO', 'JP', 'KE', 'KG', 'KH', 'KI', 'KM', 'KN', 'KP', 'KR', 'KW', 'KY', 'KZ', 'LA', 'LB', 'LC',
                'LI', 'LK', 'LR', 'LS', 'LT', 'LU', 'LV', 'LY', 'MA', 'MC', 'MD', 'ME', 'MF', 'MG', 'MH', 'MK',
                'ML', 'MM', 'MN', 'MO', 'MP', 'MQ', 'MR', 'MS', 'MT', 'MU', 'MV', 'MW', 'MX', 'MY', 'MZ', 'NA',
                'NC', 'NE', 'NF', 'NG', 'NI', 'NL', 'NO', 'NP', 'NR', 'NU', 'NZ', 'OM', 'PA', 'PE', 'PF', 'PG',
                'PH', 'PK', 'PL', 'PM', 'PN', 'PR', 'PS', 'PT', 'PW', 'PY', 'QA', 'RE', 'RO', 'RS', 'RU', 'RW',
                'SA', 'SB', 'SC', 'SD', 'SE', 'SG', 'SH', 'SI', 'SJ', 'SK', 'SL', 'SM', 'SN', 'SO', 'SR', 'SS',
                'ST', 'SV', 'SX', 'SY', 'SZ', 'TC', 'TD', 'TF', 'TG', 'TH', 'TJ', 'TK', 'TL', 'TM', 'TN', 'TO',
                'TR', 'TT', 'TV', 'TW', 'TZ', 'UA', 'UG', 'UM', 'US', 'UY', 'UZ', 'VA', 'VC', 'VE', 'VG', 'VI',
                'VN', 'VU', 'WF', 'WS', 'YE', 'YT', 'ZA', 'ZM', 'ZW'
            ];

            // Rimuovi paesi esclusi
            if ($this->excluded_countries) {
                return array_diff($allCountries, $this->excluded_countries);
            }

            return $allCountries;
        }

        // Per zone specifiche
        if ($this->included_countries) {
            return $this->included_countries;
        }

        // Per zone legacy
        if ($this->country_code) {
            return [$this->country_code];
        }

        return [];
    }

    /**
     * Calcola il costo di spedizione usando SHIPPO se abilitato
     */
    public function calculateShippingCostWithShippo($orderValue = 0, $weight = 0, $destinationCountry = null)
    {
        // Se SHIPPO è abilitato e abbiamo un paese di destinazione
        if ($this->use_shippo_pricing && $destinationCountry) {
            try {
                $shippoService = app(\App\Services\ShippoService::class);
                
                // Crea indirizzo mittente (CardSwap)
                $fromAddress = config('services.shippo.sender');
                
                // Crea indirizzo destinatario (usa un indirizzo generico per il calcolo)
                $toAddress = [
                    'country' => $destinationCountry,
                    'city' => 'City',
                    'state' => 'State',
                    'zip' => '00000',
                    'street1' => 'Street 1',
                    'name' => 'Recipient'
                ];

                // Crea pacco
                $parcel = [
                    'length' => '22',
                    'width' => '15', 
                    'height' => '3',
                    'distance_unit' => 'cm',
                    'weight' => max(0.1, $weight), // Minimo 100g
                    'mass_unit' => 'kg',
                ];

                // Crea shipment
                $shipment = $shippoService->createShipment([
                    'address_from' => $fromAddress,
                    'address_to' => $toAddress,
                    'parcels' => [$parcel],
                ], false);

                if (isset($shipment['rates']) && !empty($shipment['rates'])) {
                    // Filtra per tipo di servizio se specificato
                    $rates = $shipment['rates'];
                    if ($this->shippo_service_type) {
                        $rates = array_filter($rates, function($rate) {
                            $serviceType = $this->categorizeShippoService($rate['servicelevel']['name']);
                            return $serviceType === $this->shippo_service_type;
                        });
                    }

                    if (!empty($rates)) {
                        // Prendi la tariffa più economica
                        $cheapestRate = min($rates, function($a, $b) {
                            return $a['amount'] <=> $b['amount'];
                        });

                        $originalAmount = floatval($cheapestRate['amount']);
                        $amountWithMarkup = $originalAmount + $this->shippo_markup;

                        return max(0, $amountWithMarkup);
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Errore calcolo SHIPPO per zona ' . $this->id, [
                    'error' => $e->getMessage(),
                    'destination_country' => $destinationCountry
                ]);
            }
        }

        // Fallback al calcolo tradizionale
        return $this->calculateShippingCost($orderValue, $weight);
    }

    /**
     * Categorizza il servizio SHIPPO
     */
    private function categorizeShippoService(string $serviceName): string
    {
        $serviceName = strtolower($serviceName);
        
        if (strpos($serviceName, 'express') !== false || 
            strpos($serviceName, 'priority') !== false ||
            strpos($serviceName, 'overnight') !== false) {
            return 'express';
        }
        
        if (strpos($serviceName, 'insured') !== false ||
            strpos($serviceName, 'signature') !== false) {
            return 'insured';
        }
        
        return 'standard';
    }

    /**
     * Scope per zone ordinate
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Scope per zone mondiali
     */
    public function scopeWorldwide($query)
    {
        return $query->where('is_worldwide', true);
    }

    /**
     * Scope per zone che supportano un paese specifico
     */
    public function scopeForCountry($query, $countryCode)
    {
        return $query->where(function($q) use ($countryCode) {
            $q->where('is_worldwide', true)
              ->orWhereJsonContains('included_countries', $countryCode)
              ->orWhere('country_code', $countryCode);
        })->where(function($q) use ($countryCode) {
            $q->whereNull('excluded_countries')
              ->orWhereJsonDoesntContain('excluded_countries', $countryCode);
        });
    }
}
