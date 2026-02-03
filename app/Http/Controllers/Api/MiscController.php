<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint vari (grading companies, shipping zones check, user shipping zones legacy).
 * Usato per evitare Closure nelle route e permettere route:cache in produzione.
 */
class MiscController extends Controller
{
    public function gradingCompanies(): JsonResponse
    {
        try {
            $companies = DB::table('grading_companies')
                ->select('id', 'name')
                ->orderBy('name')
                ->get();

            return response()->json($companies);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore nel caricamento grading companies',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Zone di spedizione dell'utente (legacy, formato semplice con description).
     */
    public function userShippingZones(Request $request): JsonResponse
    {
        try {
            $zones = DB::table('shipping_zones')
                ->select('id', 'name', 'country_code', 'shipping_cost', 'delivery_days_min', 'delivery_days_max', 'is_active')
                ->where('user_id', $request->user()->id)
                ->orderBy('name')
                ->get()
                ->map(function ($zone) {
                    $zone->description = "Spedizione in {$zone->country_code} - €{$zone->shipping_cost}";
                    return $zone;
                });

            return response()->json($zones);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Errore nel caricamento zone di spedizione',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verifica se l'utente ha zone di spedizione (personali o globali).
     */
    public function shippingZonesCheck(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'has_zones' => false,
                    'zones_count' => 0,
                    'error' => 'User not authenticated'
                ], 401);
            }

            $personalZonesCount = DB::table('shipping_zones')
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->count();

            $globalZonesCount = DB::table('shipping_zones')
                ->whereNull('user_id')
                ->where('is_active', true)
                ->count();

            $zonesCount = $personalZonesCount + $globalZonesCount;

            Log::info('Shipping zones check', [
                'user_id' => $user->id,
                'personal_zones_count' => $personalZonesCount,
                'global_zones_count' => $globalZonesCount,
                'total_zones_count' => $zonesCount,
                'has_zones' => $zonesCount > 0
            ]);

            return response()->json([
                'has_zones' => $zonesCount > 0,
                'zones_count' => $zonesCount
            ]);
        } catch (\Exception $e) {
            Log::error('Shipping zones check error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'has_zones' => false,
                'zones_count' => 0,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
