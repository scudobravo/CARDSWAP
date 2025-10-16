<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ShippingZone;
use App\Models\CardListing;
use App\Services\ShippoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ShippingZoneController extends Controller
{
    private ShippoService $shippoService;

    public function __construct(ShippoService $shippoService)
    {
        $this->shippoService = $shippoService;
    }

    /**
     * Ottieni tutte le zone di spedizione disponibili
     */
    public function index(): JsonResponse
    {
        try {
            $zones = ShippingZone::active()
                ->whereIn('zone_type', ['worldwide', 'continent', 'country', 'region'])
                ->ordered()
                ->get()
                ->map(function ($zone) {
                    return [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'country_code' => $zone->country_code,
                        'zone_type' => $zone->zone_type,
                        'is_worldwide' => $zone->is_worldwide,
                        'included_countries' => $zone->included_countries,
                        'excluded_countries' => $zone->excluded_countries,
                        'use_shippo_pricing' => $zone->use_shippo_pricing,
                        'shippo_service_type' => $zone->shippo_service_type,
                        'shippo_markup' => $zone->shippo_markup,
                        'delivery_days_min' => $zone->delivery_days_min,
                        'delivery_days_max' => $zone->delivery_days_max,
                        'description' => $zone->description,
                        'sort_order' => $zone->sort_order
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $zones
            ]);

        } catch (\Exception $e) {
            Log::error('Errore caricamento zone spedizione', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel caricamento delle zone di spedizione'
            ], 500);
        }
    }

    /**
     * Calcola il prezzo di spedizione per una zona specifica
     */
    public function calculatePrice(Request $request): JsonResponse
    {
        $request->validate([
            'zone_id' => 'required|exists:shipping_zones,id',
            'listing_id' => 'nullable|exists:card_listings,id',
            'destination_country' => 'nullable|string|size:2',
            'weight' => 'nullable|numeric|min:0.01',
            'order_value' => 'nullable|numeric|min:0'
        ]);

        try {
            $zone = ShippingZone::findOrFail($request->zone_id);
            $listing = null;
            
            if ($request->listing_id) {
                $listing = CardListing::findOrFail($request->listing_id);
            }

            // Parametri per il calcolo
            $weight = $request->weight ?? 0.1; // Default 100g per una carta
            $orderValue = $request->order_value ?? ($listing ? $listing->price : 0);
            $destinationCountry = $request->destination_country;

            // Se la zona usa SHIPPO e abbiamo un paese di destinazione
            if ($zone->use_shippo_pricing && $destinationCountry) {
                $price = $this->calculateShippoPrice($zone, $destinationCountry, $weight, $orderValue);
            } else {
                // Calcolo tradizionale
                $price = $zone->calculateShippingCost($orderValue, $weight);
            }

            return response()->json([
                'success' => true,
                'price' => round($price, 2),
                'currency' => 'EUR',
                'zone' => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'delivery_days_min' => $zone->delivery_days_min,
                    'delivery_days_max' => $zone->delivery_days_max
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Errore calcolo prezzo spedizione', [
                'zone_id' => $request->zone_id,
                'listing_id' => $request->listing_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel calcolo del prezzo di spedizione'
            ], 500);
        }
    }

    /**
     * Calcola prezzi per multiple zone
     */
    public function calculateMultiplePrices(Request $request): JsonResponse
    {
        $request->validate([
            'zone_ids' => 'required|array',
            'zone_ids.*' => 'exists:shipping_zones,id',
            'listing_id' => 'nullable|exists:card_listings,id',
            'destination_country' => 'nullable|string|size:2',
            'weight' => 'nullable|numeric|min:0.01',
            'order_value' => 'nullable|numeric|min:0'
        ]);

        try {
            $zones = ShippingZone::whereIn('id', $request->zone_ids)->get();
            $listing = null;
            
            if ($request->listing_id) {
                $listing = CardListing::findOrFail($request->listing_id);
            }

            $weight = $request->weight ?? 0.1;
            $orderValue = $request->order_value ?? ($listing ? $listing->price : 0);
            $destinationCountry = $request->destination_country;

            $results = [];

            foreach ($zones as $zone) {
                try {
                    if ($zone->use_shippo_pricing && $destinationCountry) {
                        $price = $this->calculateShippoPrice($zone, $destinationCountry, $weight, $orderValue);
                    } else {
                        $price = $zone->calculateShippingCost($orderValue, $weight);
                    }

                    $results[] = [
                        'zone_id' => $zone->id,
                        'price' => round($price, 2),
                        'success' => true
                    ];
                } catch (\Exception $e) {
                    Log::warning('Errore calcolo prezzo per zona specifica', [
                        'zone_id' => $zone->id,
                        'error' => $e->getMessage()
                    ]);

                    $results[] = [
                        'zone_id' => $zone->id,
                        'price' => null,
                        'success' => false,
                        'error' => 'Impossibile calcolare il prezzo'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Errore calcolo prezzi multipli', [
                'zone_ids' => $request->zone_ids,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel calcolo dei prezzi di spedizione'
            ], 500);
        }
    }

    /**
     * Calcola prezzi per paesi individuali
     */
    public function calculateCountryPrices(Request $request): JsonResponse
    {
        $request->validate([
            'countries' => 'required|array',
            'countries.*' => 'string|size:2',
            'listing_id' => 'nullable|exists:card_listings,id',
            'weight' => 'nullable|numeric|min:0.01',
            'order_value' => 'nullable|numeric|min:0'
        ]);

        try {
            $listing = null;
            if ($request->listing_id) {
                $listing = CardListing::findOrFail($request->listing_id);
            }

            $weight = $request->weight ?? 0.1;
            $orderValue = $request->order_value ?? ($listing ? $listing->price : 0);
            $countries = $request->countries;

            $results = [];

            foreach ($countries as $countryCode) {
                try {
                    // Trova la zona più appropriata per questo paese
                    $zone = $this->findBestZoneForCountry($countryCode);
                    
                    if ($zone) {
                        if ($zone->use_shippo_pricing) {
                            $price = $this->calculateShippoPrice($zone, $countryCode, $weight, $orderValue);
                        } else {
                            $price = $zone->calculateShippingCost($orderValue, $weight);
                        }
                    } else {
                        // Usa prezzo di default se non trova zona specifica
                        $price = $this->getDefaultPriceForCountry($countryCode);
                    }

                    $results[$countryCode] = [
                        'country_code' => $countryCode,
                        'country_name' => $this->getCountryName($countryCode),
                        'price' => round($price, 2),
                        'currency' => 'EUR',
                        'success' => true
                    ];
                } catch (\Exception $e) {
                    Log::warning('Errore calcolo prezzo per paese', [
                        'country_code' => $countryCode,
                        'error' => $e->getMessage()
                    ]);

                    $results[$countryCode] = [
                        'country_code' => $countryCode,
                        'country_name' => $this->getCountryName($countryCode),
                        'price' => null,
                        'success' => false,
                        'error' => 'Impossibile calcolare il prezzo'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Errore calcolo prezzi paesi', [
                'countries' => $request->countries,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel calcolo dei prezzi per paesi'
            ], 500);
        }
    }

    /**
     * Verifica se una zona supporta un paese specifico
     */
    public function checkCountrySupport(Request $request): JsonResponse
    {
        $request->validate([
            'zone_id' => 'required|exists:shipping_zones,id',
            'country_code' => 'required|string|size:2'
        ]);

        try {
            $zone = ShippingZone::findOrFail($request->zone_id);
            $supports = $zone->supportsCountry($request->country_code);

            return response()->json([
                'success' => true,
                'supports' => $supports,
                'zone' => [
                    'id' => $zone->id,
                    'name' => $zone->name,
                    'is_worldwide' => $zone->is_worldwide,
                    'included_countries' => $zone->included_countries,
                    'excluded_countries' => $zone->excluded_countries
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Errore verifica supporto paese', [
                'zone_id' => $request->zone_id,
                'country_code' => $request->country_code,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nella verifica del supporto del paese'
            ], 500);
        }
    }

    /**
     * Calcola il prezzo usando SHIPPO
     */
    private function calculateShippoPrice(ShippingZone $zone, string $destinationCountry, float $weight, float $orderValue): float
    {
        try {
            // Indirizzo mittente (CardSwap)
            $fromAddress = config('services.shippo.sender');
            
            // Indirizzo destinatario (generico per calcolo)
            $toAddress = [
                'country' => $destinationCountry,
                'city' => 'City',
                'state' => 'State',
                'zip' => '00000',
                'street1' => 'Street 1',
                'name' => 'Recipient'
            ];

            // Pacco standard per carte
            $parcel = [
                'length' => '22',
                'width' => '15',
                'height' => '3',
                'distance_unit' => 'cm',
                'weight' => max(0.1, $weight), // Minimo 100g
                'mass_unit' => 'kg',
            ];

            // Crea shipment
            $shipment = $this->shippoService->createShipment([
                'address_from' => $fromAddress,
                'address_to' => $toAddress,
                'parcels' => [$parcel],
            ], false);

            if (isset($shipment['rates']) && !empty($shipment['rates'])) {
                // Filtra per tipo di servizio se specificato
                $rates = $shipment['rates'];
                if ($zone->shippo_service_type) {
                    $rates = array_filter($rates, function($rate) use ($zone) {
                        $serviceType = $this->categorizeShippoService($rate['servicelevel']['name']);
                        return $serviceType === $zone->shippo_service_type;
                    });
                }

                if (!empty($rates)) {
                    // Prendi la tariffa più economica
                    $cheapestRate = min($rates, function($a, $b) {
                        return $a['amount'] <=> $b['amount'];
                    });

                    $originalAmount = floatval($cheapestRate['amount']);
                    $amountWithMarkup = $originalAmount + $zone->shippo_markup;

                    return max(0, $amountWithMarkup);
                }
            }

            // Fallback al calcolo tradizionale se SHIPPO non restituisce tariffe
            return $zone->calculateShippingCost($orderValue, $weight);

        } catch (\Exception $e) {
            Log::error('Errore calcolo SHIPPO', [
                'zone_id' => $zone->id,
                'destination_country' => $destinationCountry,
                'error' => $e->getMessage()
            ]);

            // Fallback al calcolo tradizionale
            return $zone->calculateShippingCost($orderValue, $weight);
        }
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
     * Trova la zona migliore per un paese specifico
     */
    private function findBestZoneForCountry(string $countryCode): ?ShippingZone
    {
        // Prima cerca zone specifiche per paese
        $specificZone = ShippingZone::where('country_code', $countryCode)
            ->where('is_active', true)
            ->first();

        if ($specificZone) {
            return $specificZone;
        }

        // Poi cerca zone che includono questo paese
        $includedZone = ShippingZone::whereJsonContains('included_countries', $countryCode)
            ->where('is_active', true)
            ->where(function($query) use ($countryCode) {
                $query->whereNull('excluded_countries')
                      ->orWhereJsonDoesntContain('excluded_countries', $countryCode);
            })
            ->orderBy('sort_order')
            ->first();

        if ($includedZone) {
            return $includedZone;
        }

        // Infine cerca zone mondiali che non escludono questo paese
        $worldwideZone = ShippingZone::where('is_worldwide', true)
            ->where('is_active', true)
            ->where(function($query) use ($countryCode) {
                $query->whereNull('excluded_countries')
                      ->orWhereJsonDoesntContain('excluded_countries', $countryCode);
            })
            ->orderBy('sort_order')
            ->first();

        return $worldwideZone;
    }

    /**
     * Ottieni prezzo di default per un paese
     */
    private function getDefaultPriceForCountry(string $countryCode): float
    {
        $priceMap = [
            // Europa
            'IT' => 3.50, 'FR' => 5.50, 'DE' => 6.00, 'ES' => 5.50, 'GB' => 7.50,
            'NL' => 6.50, 'BE' => 6.00, 'AT' => 5.50, 'CH' => 7.00, 'SE' => 8.00,
            'NO' => 8.50, 'DK' => 7.50, 'FI' => 8.00, 'PL' => 6.50, 'CZ' => 6.00,
            'HU' => 6.50, 'PT' => 6.00, 'GR' => 6.50, 'RO' => 7.00, 'BG' => 7.50,
            'HR' => 6.50, 'SI' => 6.00, 'SK' => 6.50, 'LT' => 7.00, 'LV' => 7.50,
            'EE' => 8.00, 'IE' => 7.00, 'LU' => 6.00, 'MT' => 6.50, 'CY' => 7.00,
            
            // Asia
            'CN' => 12.00, 'JP' => 14.00, 'KR' => 13.50, 'IN' => 11.00, 'ID' => 15.00,
            'TH' => 13.00, 'VN' => 12.50, 'MY' => 14.50, 'SG' => 15.00, 'PH' => 16.00,
            'TW' => 14.00, 'HK' => 15.50, 'MN' => 11.50, 'KZ' => 10.00, 'UZ' => 10.50,
            'KG' => 10.00, 'TJ' => 10.50, 'TM' => 10.00, 'AF' => 9.50, 'PK' => 10.00,
            'BD' => 11.50, 'LK' => 12.00, 'NP' => 11.00, 'BT' => 11.50, 'MV' => 13.00,
            'MM' => 12.50, 'LA' => 13.00, 'KH' => 13.50, 'BN' => 15.00, 'TL' => 16.00,
            
            // America
            'US' => 16.00, 'CA' => 17.00, 'MX' => 14.00, 'BR' => 18.00, 'AR' => 19.00,
            'CL' => 20.00, 'CO' => 17.50, 'PE' => 18.50, 'VE' => 17.00, 'EC' => 18.00,
            'BO' => 19.00, 'PY' => 19.50, 'UY' => 20.00, 'GY' => 18.50, 'SR' => 18.00,
            'GF' => 17.50, 'CR' => 16.50, 'PA' => 16.00, 'GT' => 15.50, 'HN' => 15.00,
            'SV' => 15.00, 'NI' => 15.50, 'CU' => 16.00, 'DO' => 16.50, 'HT' => 16.00,
            'JM' => 17.00, 'TT' => 17.50, 'BB' => 18.00, 'BS' => 17.50, 'BZ' => 15.50,
            
            // Africa
            'ZA' => 20.00, 'EG' => 12.00, 'NG' => 18.00, 'KE' => 19.00, 'MA' => 8.00,
            'TN' => 7.50, 'DZ' => 8.50, 'LY' => 8.00, 'ET' => 18.50, 'GH' => 17.00,
            'CI' => 16.50, 'SN' => 15.00, 'ML' => 15.50, 'BF' => 15.50, 'NE' => 15.00,
            'TD' => 16.00, 'SD' => 17.00, 'UG' => 18.00, 'TZ' => 19.00, 'ZW' => 20.00,
            'ZM' => 20.00, 'BW' => 20.50, 'NA' => 21.00, 'AO' => 19.50, 'MZ' => 20.00,
            'MG' => 21.00, 'MU' => 20.50, 'SC' => 21.50, 'RW' => 18.50, 'BI' => 18.00,
            
            // Oceania
            'AU' => 20.00, 'NZ' => 21.00, 'FJ' => 22.00, 'PG' => 21.50, 'NC' => 22.50,
            'VU' => 22.00, 'SB' => 22.50, 'TO' => 23.00, 'WS' => 23.50, 'KI' => 24.00,
            'TV' => 24.50, 'NR' => 24.00, 'PW' => 25.00, 'FM' => 24.50, 'MH' => 25.00
        ];
        
        return $priceMap[$countryCode] ?? 15.00;
    }

    /**
     * Ottieni nome del paese dal codice
     */
    private function getCountryName(string $countryCode): string
    {
        $countries = [
            'IT' => 'Italia', 'FR' => 'Francia', 'DE' => 'Germania', 'ES' => 'Spagna', 'GB' => 'Regno Unito',
            'NL' => 'Paesi Bassi', 'BE' => 'Belgio', 'AT' => 'Austria', 'CH' => 'Svizzera', 'SE' => 'Svezia',
            'NO' => 'Norvegia', 'DK' => 'Danimarca', 'FI' => 'Finlandia', 'PL' => 'Polonia', 'CZ' => 'Repubblica Ceca',
            'HU' => 'Ungheria', 'PT' => 'Portogallo', 'GR' => 'Grecia', 'RO' => 'Romania', 'BG' => 'Bulgaria',
            'HR' => 'Croazia', 'SI' => 'Slovenia', 'SK' => 'Slovacchia', 'LT' => 'Lituania', 'LV' => 'Lettonia',
            'EE' => 'Estonia', 'IE' => 'Irlanda', 'LU' => 'Lussemburgo', 'MT' => 'Malta', 'CY' => 'Cipro',
            'CN' => 'Cina', 'JP' => 'Giappone', 'KR' => 'Corea del Sud', 'IN' => 'India', 'ID' => 'Indonesia',
            'TH' => 'Thailandia', 'VN' => 'Vietnam', 'MY' => 'Malesia', 'SG' => 'Singapore', 'PH' => 'Filippine',
            'TW' => 'Taiwan', 'HK' => 'Hong Kong', 'MN' => 'Mongolia', 'KZ' => 'Kazakistan', 'UZ' => 'Uzbekistan',
            'KG' => 'Kirghizistan', 'TJ' => 'Tagikistan', 'TM' => 'Turkmenistan', 'AF' => 'Afghanistan', 'PK' => 'Pakistan',
            'BD' => 'Bangladesh', 'LK' => 'Sri Lanka', 'NP' => 'Nepal', 'BT' => 'Bhutan', 'MV' => 'Maldive',
            'MM' => 'Myanmar', 'LA' => 'Laos', 'KH' => 'Cambogia', 'BN' => 'Brunei', 'TL' => 'Timor Est',
            'US' => 'Stati Uniti', 'CA' => 'Canada', 'MX' => 'Messico', 'BR' => 'Brasile', 'AR' => 'Argentina',
            'CL' => 'Cile', 'CO' => 'Colombia', 'PE' => 'Perù', 'VE' => 'Venezuela', 'EC' => 'Ecuador',
            'BO' => 'Bolivia', 'PY' => 'Paraguay', 'UY' => 'Uruguay', 'GY' => 'Guyana', 'SR' => 'Suriname',
            'GF' => 'Guyana Francese', 'CR' => 'Costa Rica', 'PA' => 'Panama', 'GT' => 'Guatemala', 'HN' => 'Honduras',
            'SV' => 'El Salvador', 'NI' => 'Nicaragua', 'CU' => 'Cuba', 'DO' => 'Repubblica Dominicana', 'HT' => 'Haiti',
            'JM' => 'Giamaica', 'TT' => 'Trinidad e Tobago', 'BB' => 'Barbados', 'BS' => 'Bahamas', 'BZ' => 'Belize',
            'ZA' => 'Sudafrica', 'EG' => 'Egitto', 'NG' => 'Nigeria', 'KE' => 'Kenya', 'MA' => 'Marocco',
            'TN' => 'Tunisia', 'DZ' => 'Algeria', 'LY' => 'Libia', 'ET' => 'Etiopia', 'GH' => 'Ghana',
            'CI' => 'Costa d\'Ivory', 'SN' => 'Senegal', 'ML' => 'Mali', 'BF' => 'Burkina Faso', 'NE' => 'Niger',
            'TD' => 'Ciad', 'SD' => 'Sudan', 'UG' => 'Uganda', 'TZ' => 'Tanzania', 'ZW' => 'Zimbabwe',
            'ZM' => 'Zambia', 'BW' => 'Botswana', 'NA' => 'Namibia', 'AO' => 'Angola', 'MZ' => 'Mozambico',
            'MG' => 'Madagascar', 'MU' => 'Mauritius', 'SC' => 'Seychelles', 'RW' => 'Ruanda', 'BI' => 'Burundi',
            'AU' => 'Australia', 'NZ' => 'Nuova Zelanda', 'FJ' => 'Fiji', 'PG' => 'Papua Nuova Guinea', 'NC' => 'Nuova Caledonia',
            'VU' => 'Vanuatu', 'SB' => 'Isole Salomone', 'TO' => 'Tonga', 'WS' => 'Samoa', 'KI' => 'Kiribati',
            'TV' => 'Tuvalu', 'NR' => 'Nauru', 'PW' => 'Palau', 'FM' => 'Micronesia', 'MH' => 'Isole Marshall'
        ];
        
        return $countries[$countryCode] ?? $countryCode;
    }

    /**
     * Crea una nuova zona di spedizione
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'country_code' => 'required|string|size:2',
                'zone_type' => 'required|string|in:worldwide,continent,country,region',
                'is_worldwide' => 'nullable|boolean',
                'included_countries' => 'nullable|array',
                'excluded_countries' => 'nullable|array',
                'included_regions' => 'nullable|array',
                'excluded_regions' => 'nullable|array',
                'shipping_cost' => 'nullable|numeric|min:0',
                'base_cost' => 'nullable|numeric|min:0',
                'cost_per_kg' => 'nullable|numeric|min:0',
                'cost_per_euro' => 'nullable|numeric|min:0',
                'free_shipping_threshold' => 'nullable|numeric|min:0',
                'max_weight_kg' => 'nullable|numeric|min:0',
                'max_value_euro' => 'nullable|numeric|min:0',
                'requires_seller_approval' => 'nullable|boolean',
                'allowed_seller_roles' => 'nullable|array',
                'min_seller_rating' => 'nullable|integer|min:0',
                'min_seller_sales' => 'nullable|integer|min:0',
                'use_shippo_pricing' => 'nullable|boolean',
                'shippo_carrier' => 'nullable|string|max:255',
                'shippo_service_type' => 'nullable|string|max:255',
                'shippo_markup' => 'nullable|numeric|min:0',
                'shippo_require_insurance' => 'nullable|boolean',
                'delivery_days_min' => 'nullable|integer|min:1',
                'delivery_days_max' => 'nullable|integer|min:1',
                'is_active' => 'nullable|boolean',
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer|min:0'
            ]);

            Log::info('Dati ricevuti per creazione zona:', $request->all());
            
            // Controlla se esiste già una zona con lo stesso nome per questo utente
            $existingZone = ShippingZone::where('user_id', $request->user()->id)
                ->where('name', $request->name)
                ->first();
                
            if ($existingZone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esiste già una zona di spedizione con questo nome'
                ], 422);
            }
            
            // Aggiungi l'user_id dell'utente autenticato
            $zoneData = $request->all();
            $zoneData['user_id'] = $request->user()->id;
            
            $zone = ShippingZone::create($zoneData);
            
            Log::info('Zona creata:', $zone->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Zona di spedizione creata con successo',
                'data' => $zone
            ]);

        } catch (\Exception $e) {
            Log::error('Errore creazione zona spedizione: ' . $e->getMessage(), [
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione della zona di spedizione'
            ], 500);
        }
    }

    /**
     * Aggiorna una zona di spedizione esistente
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $zone = ShippingZone::findOrFail($id);
            
            // Verifica che l'utente possa aggiornare solo le proprie zone
            if ($zone->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorizzato a modificare questa zona di spedizione'
                ], 403);
            }
            
            $request->validate([
                'name' => 'required|string|max:255',
                'country_code' => 'required|string|size:2',
                'zone_type' => 'required|string|in:worldwide,continent,country,region',
                'is_worldwide' => 'nullable|boolean',
                'included_countries' => 'nullable|array',
                'excluded_countries' => 'nullable|array',
                'included_regions' => 'nullable|array',
                'excluded_regions' => 'nullable|array',
                'shipping_cost' => 'nullable|numeric|min:0',
                'base_cost' => 'nullable|numeric|min:0',
                'cost_per_kg' => 'nullable|numeric|min:0',
                'cost_per_euro' => 'nullable|numeric|min:0',
                'free_shipping_threshold' => 'nullable|numeric|min:0',
                'max_weight_kg' => 'nullable|numeric|min:0',
                'max_value_euro' => 'nullable|numeric|min:0',
                'requires_seller_approval' => 'nullable|boolean',
                'allowed_seller_roles' => 'nullable|array',
                'min_seller_rating' => 'nullable|integer|min:0',
                'min_seller_sales' => 'nullable|integer|min:0',
                'use_shippo_pricing' => 'nullable|boolean',
                'shippo_carrier' => 'nullable|string|max:255',
                'shippo_service_type' => 'nullable|string|max:255',
                'shippo_markup' => 'nullable|numeric|min:0',
                'shippo_require_insurance' => 'nullable|boolean',
                'delivery_days_min' => 'nullable|integer|min:1',
                'delivery_days_max' => 'nullable|integer|min:1',
                'is_active' => 'nullable|boolean',
                'description' => 'nullable|string',
                'sort_order' => 'nullable|integer|min:0'
            ]);

            Log::info('Dati ricevuti per aggiornamento zona:', $request->all());
            
            $zone->update($request->all());
            
            Log::info('Zona aggiornata:', $zone->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Zona di spedizione aggiornata con successo',
                'data' => $zone
            ]);

        } catch (\Exception $e) {
            Log::error('Errore aggiornamento zona spedizione: ' . $e->getMessage(), [
                'zone_id' => $id,
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento della zona di spedizione'
            ], 500);
        }
    }

    /**
     * Elimina una zona di spedizione
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        try {
            $zone = ShippingZone::findOrFail($id);
            
            // Verifica che l'utente possa eliminare solo le proprie zone
            if ($zone->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorizzato a eliminare questa zona di spedizione'
                ], 403);
            }
            
            Log::info('Eliminazione zona spedizione:', ['zone_id' => $id, 'zone_name' => $zone->name]);
            
            $zone->delete();

            return response()->json([
                'success' => true,
                'message' => 'Zona di spedizione eliminata con successo'
            ]);

        } catch (\Exception $e) {
            Log::error('Errore eliminazione zona spedizione: ' . $e->getMessage(), [
                'zone_id' => $id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'eliminazione della zona di spedizione'
            ], 500);
        }
    }
}
