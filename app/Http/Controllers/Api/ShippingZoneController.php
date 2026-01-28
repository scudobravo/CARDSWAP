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
     * Ottieni tutte le zone di spedizione disponibili per l'utente autenticato (VENDITORE)
     * Restituisce SOLO le zone personali del venditore, non quelle globali
     * 
     * @param Request $request
     * @param bool $activeOnly - Se true, restituisce solo zone attive (default: false per gestione completa)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()->id;
            
            // Controlla se richiediamo solo zone attive (per listing)
            $activeOnly = $request->query('active_only', false);
            
            // Query base: SOLO le zone personali del venditore
            $query = ShippingZone::where('user_id', $userId)
                ->whereIn('zone_type', ['worldwide', 'continent', 'country', 'region']);
            
            // Se richiesto, filtra solo zone attive
            if ($activeOnly) {
                $query->active();
            }
            
            $zones = $query->ordered()
                ->get()
                ->map(function ($zone) {
                    return [
                        'id' => $zone->id,
                        'name' => $zone->name,
                        'country_code' => $zone->country_code,
                        'zone_type' => $zone->zone_type,
                        'is_worldwide' => $zone->is_worldwide,
                        'is_active' => $zone->is_active, // Aggiunto is_active
                        'included_countries' => $zone->included_countries,
                        'excluded_countries' => $zone->excluded_countries,
                        // NOTA: use_shippo_pricing, shippo_service_type, shippo_markup rimossi (legacy pricing)
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

    // ============================================
    // ENDPOINT LEGACY PRICING RIMOSSI
    // ============================================
    // I seguenti endpoint sono stati rimossi definitivamente:
    // - calculatePrice() - RIMOSSO
    // - calculateMultiplePrices() - RIMOSSO
    // - calculateCountryPrices() - RIMOSSO
    // 
    // Usa invece POST /api/shipping/v1/calculate-rates per CardSwap Shipping V1.
    // ============================================

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
            
            // Se l'utente è autenticato, verifica che la zona appartenga all'utente
            if ($request->user() && $zone->user_id !== $request->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non autorizzato ad accedere a questa zona di spedizione'
                ], 403);
            }
            
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

    // ============================================
    // METODI PRIVATI LEGACY PRICING RIMOSSI
    // ============================================
    // I seguenti metodi privati sono stati rimossi definitivamente:
    // - calculateShippoPrice() - RIMOSSO (usato solo da endpoint pricing rimossi)
    // - categorizeShippoService() - RIMOSSO (usato solo da calculateShippoPrice)
    // - findBestZoneForCountry() - RIMOSSO (usato solo da endpoint pricing rimossi)
    // - getDefaultPriceForCountry() - RIMOSSO (usato solo da endpoint pricing rimossi)
    // - getCountryName() - RIMOSSO (usato solo da endpoint pricing rimossi)
    // ============================================

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
                // NOTA: Campi legacy pricing rimossi (shipping_cost, base_cost, cost_per_kg, cost_per_euro, free_shipping_threshold, use_shippo_pricing, shippo_markup)
                'max_weight_kg' => 'nullable|numeric|min:0',
                'max_value_euro' => 'nullable|numeric|min:0',
                'requires_seller_approval' => 'nullable|boolean',
                'allowed_seller_roles' => 'nullable|array',
                'min_seller_rating' => 'nullable|integer|min:0',
                'min_seller_sales' => 'nullable|integer|min:0',
                'shippo_carrier' => 'nullable|string|max:255',
                'shippo_service_type' => 'nullable|string|max:255',
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
            
            // Se is_active non è specificato o è null, impostalo a true di default
            if (!isset($zoneData['is_active']) || $zoneData['is_active'] === null) {
                $zoneData['is_active'] = true;
            }
            
            // Assicurati che is_active sia un booleano
            $zoneData['is_active'] = (bool) $zoneData['is_active'];
            
            Log::info('Dati zona prima della creazione:', [
                'is_active' => $zoneData['is_active'],
                'is_active_type' => gettype($zoneData['is_active']),
                'all_data' => $zoneData
            ]);
            
            $zone = ShippingZone::create($zoneData);
            
            Log::info('Zona creata:', [
                'id' => $zone->id,
                'name' => $zone->name,
                'is_active' => $zone->is_active,
                'is_active_type' => gettype($zone->is_active),
                'all_data' => $zone->toArray()
            ]);

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
                // NOTA: Campi legacy pricing rimossi (shipping_cost, base_cost, cost_per_kg, cost_per_euro, free_shipping_threshold, use_shippo_pricing, shippo_markup)
                'max_weight_kg' => 'nullable|numeric|min:0',
                'max_value_euro' => 'nullable|numeric|min:0',
                'requires_seller_approval' => 'nullable|boolean',
                'allowed_seller_roles' => 'nullable|array',
                'min_seller_rating' => 'nullable|integer|min:0',
                'min_seller_sales' => 'nullable|integer|min:0',
                'shippo_carrier' => 'nullable|string|max:255',
                'shippo_service_type' => 'nullable|string|max:255',
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

    /**
     * @deprecated Shippo NON fa parte di CardSwap Shipping V1
     * 
     * Ottieni corrieri disponibili per un paese
     * 
     * NOTA: CardSwap V1 usa shipping_price_tables per determinare i metodi disponibili.
     * Questo endpoint è deprecato e NON fa parte del flusso CardSwap V1.
     */
    public function getAvailableCarriers(Request $request): JsonResponse
    {
        Log::warning('ShippingZoneController::getAvailableCarriers called - Shippo is deprecated and not used by CardSwap Shipping V1', [
            'request_data' => $request->all(),
            'note' => 'CardSwap V1 uses shipping_price_tables to determine available methods'
        ]);
        
        $request->validate([
            'country' => 'required|string|size:2'
        ]);

        try {
            $countryCode = $request->input('country');
            $carriers = $this->shippoService->getAvailableCarriers($countryCode);

            return response()->json([
                'success' => true,
                'data' => [
                    'country' => $countryCode,
                    'carriers' => $carriers
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Errore recupero corrieri disponibili', [
                'country' => $request->input('country'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dei corrieri disponibili'
            ], 500);
        }
    }
}
