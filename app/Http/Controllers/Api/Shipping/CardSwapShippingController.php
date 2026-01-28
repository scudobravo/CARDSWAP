<?php

namespace App\Http\Controllers\Api\Shipping;

use App\Http\Controllers\Controller;
use App\Models\CardListing;
use App\Models\User;
use App\Services\ShippingPriceTableService;
use App\Enums\ShippingPackageBucket;
use App\Enums\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CardSwapShippingController extends Controller
{
    /**
     * Calcola le opzioni di spedizione per checkout
     * 
     * Input:
     * - sellers: array di venditori con items
     * - shipping_address.country_code: codice paese destinazione
     * 
     * Output:
     * - Opzioni spedizione per ogni venditore con:
     *   - shipping_method
     *   - label
     *   - price
     *   - insurance_fee (se applicabile)
     */
    public function calculateRates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sellers' => 'required|array|min:1',
            'sellers.*.seller_id' => 'required|integer|exists:users,id',
            'sellers.*.items' => 'required|array|min:1',
            'sellers.*.items.*.listing_id' => 'required|integer|exists:card_listings,id',
            'sellers.*.items.*.quantity' => 'required|integer|min:1',
            'shipping_address' => 'required|array',
            'shipping_address.country_code' => 'required|string|size:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati di validazione non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sellers = $request->input('sellers');
            $countryCode = strtoupper($request->input('shipping_address.country_code'));
            $results = [];

            foreach ($sellers as $sellerData) {
                $sellerId = (int) $sellerData['seller_id'];
                $seller = User::find($sellerId);

                if (!$seller) {
                    $results[$sellerId] = [
                        'error' => 'Venditore non trovato',
                        'seller_id' => $sellerId,
                    ];
                    continue;
                }

                // Calcola subtotale e analizza items per determinare package bucket
                $subtotal = 0;
                $singleCardQty = 0;
                $packQty = 0;
                $boxQty = 0;
                
                foreach ($sellerData['items'] as $itemData) {
                    $listing = CardListing::find($itemData['listing_id']);
                    if ($listing) {
                        $subtotal += $listing->price * $itemData['quantity'];
                        
                        // Mappa listing_type a category_type
                        $categoryType = $this->mapListingTypeToCategoryType($listing->listing_type ?? 'single');
                        $quantity = (int) $itemData['quantity'];
                        
                        switch ($categoryType) {
                            case 'SINGLE_CARD':
                                $singleCardQty += $quantity;
                                break;
                            case 'PACK':
                                $packQty += $quantity;
                                break;
                            case 'BOX':
                                $boxQty += $quantity;
                                break;
                        }
                    }
                }

                // Trova tabella prezzi per venditore e paese
                $table = ShippingPriceTableService::findTableForSellerAndCountry($sellerId, $countryCode);

                if (!$table) {
                    $results[$sellerId] = [
                        'error' => 'Nessuna tabella prezzi disponibile per questo venditore e paese',
                        'seller_id' => $sellerId,
                        'country_code' => $countryCode,
                    ];
                    continue;
                }

                // Calcola package bucket secondo specifica CardSwap V1
                $bucketResult = $this->calculatePackageBucket($singleCardQty, $packQty, $boxQty);
                $packageBucket = $bucketResult['bucket'];
                $logisticUnitsTotal = $bucketResult['logistic_units_total'];

                // Ottieni tutte le tariffe disponibili per questo bucket
                $availableRates = $table->rates()
                    ->where('package_bucket', $packageBucket)
                    ->whereNotNull('price_eur')
                    ->get();

                if ($availableRates->isEmpty()) {
                    $results[$sellerId] = [
                        'error' => 'Nessuna tariffa disponibile per questo tipo di pacco',
                        'seller_id' => $sellerId,
                        'package_bucket' => $packageBucket,
                    ];
                    continue;
                }

                // Verifica se assicurazione è disponibile
                $insuranceAvailable = ShippingPriceTableService::isInsuranceAvailable($table, $packageBucket);
                $insuranceRequired = $subtotal >= config('shipping.insured_min_subtotal_eur', 200.00);

                // Costruisci opzioni spedizione
                $options = [];

                foreach ($availableRates as $rate) {
                    $method = $rate->shipping_method;
                    $price = (float) $rate->price_eur;

                    // Applica regole business
                    // 1. UNTRACKED_STANDARD solo se subtotale <= soglia
                    if ($method === ShippingMethod::UNTRACKED_STANDARD) {
                        $maxSubtotal = config('shipping.untracked_max_subtotal_eur', 20.00);
                        if ($subtotal > $maxSubtotal) {
                            continue; // Salta questo metodo
                        }
                    }

                    // 2. Se assicurazione richiesta, aggiungi costo
                    $insuranceFee = 0;
                    if ($insuranceRequired && $insuranceAvailable) {
                        $insuranceRate = config('shipping.insurance_rate', 0.012);
                        $insuranceMinFee = config('shipping.insurance_min_fee_eur', 5.00);
                        $insuranceFee = max($subtotal * $insuranceRate, $insuranceMinFee);
                    }

                    $options[] = [
                        'shipping_method' => $method,
                        'label' => ShippingMethod::label($method),
                        'price' => round($price, 2),
                        'insurance_available' => $insuranceAvailable,
                        'insurance_required' => $insuranceRequired,
                        'insurance_fee' => $insuranceRequired && $insuranceAvailable ? round($insuranceFee, 2) : 0,
                        'total_price' => round($price + ($insuranceRequired && $insuranceAvailable ? $insuranceFee : 0), 2),
                        'package_bucket' => $packageBucket,
                        'package_bucket_label' => ShippingPackageBucket::label($packageBucket),
                    ];
                }

                // Aggiungi opzione TRACKED_INSURED se disponibile
                // (combinazione di metodo tracked + assicurazione)
                if ($insuranceAvailable && $insuranceRequired) {
                    foreach ($availableRates as $rate) {
                        if (ShippingMethod::isTracked($rate->shipping_method)) {
                            $basePrice = (float) $rate->price_eur;
                            $insuranceRate = config('shipping.insurance_rate', 0.012);
                            $insuranceMinFee = config('shipping.insurance_min_fee_eur', 5.00);
                            $insuranceFee = max($subtotal * $insuranceRate, $insuranceMinFee);

                            $options[] = [
                                'shipping_method' => ShippingMethod::TRACKED_INSURED,
                                'label' => ShippingMethod::label(ShippingMethod::TRACKED_INSURED),
                                'price' => round($basePrice, 2),
                                'insurance_available' => true,
                                'insurance_required' => true,
                                'insurance_fee' => round($insuranceFee, 2),
                                'total_price' => round($basePrice + $insuranceFee, 2),
                                'package_bucket' => $packageBucket,
                                'package_bucket_label' => ShippingPackageBucket::label($packageBucket),
                            ];
                            break; // Aggiungi solo una volta
                        }
                    }
                }

                // Ordina per prezzo totale
                usort($options, function ($a, $b) {
                    return $a['total_price'] <=> $b['total_price'];
                });

                $results[$sellerId] = [
                    'seller_id' => $sellerId,
                    'seller_name' => $seller->name,
                    'subtotal' => round($subtotal, 2),
                    'logistic_units_total' => $logisticUnitsTotal,
                    'package_bucket' => $packageBucket,
                    'package_bucket_label' => ShippingPackageBucket::label($packageBucket),
                    'options' => $options,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Errore calcolo tariffe CardSwap V1', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel calcolo delle tariffe di spedizione'
            ], 500);
        }
    }

    /**
     * Mappa listing_type a category_type
     * 
     * @param string $listingType
     * @return string
     */
    private function mapListingTypeToCategoryType(string $listingType): string
    {
        return match ($listingType) {
            'single' => 'SINGLE_CARD',
            'sealed-pack' => 'PACK',
            'sealed-box' => 'BOX',
            'bulk' => 'SINGLE_CARD', // Bulk = multiple single cards
            'lot' => 'SINGLE_CARD',  // Lot = multiple single cards
            default => 'SINGLE_CARD',
        };
    }

    /**
     * Calcola il package bucket secondo specifica CardSwap V1
     * 
     * REGOLE:
     * 1. LETTER è consentita SOLO SE:
     *    - ordine contiene SOLO SINGLE_CARD
     *    - quantità totale SINGLE_CARD ≤ 5
     * 
     * 2. Se LETTER non è consentita:
     *    - calcola total_units = single_qty * 0.2 + pack_qty * 0.5 + box_qty * 2.0
     *    - Mapping:
     *      - total_units ≤ 1.0  → PARCEL_S
     *      - 1.0 < total_units ≤ 4  → PARCEL_M
     *      - total_units > 4.0  → PARCEL_L
     * 
     * Pesi unitari:
     * - SINGLE_CARD = 0.2
     * - PACK = 0.5
     * - BOX = 2.0
     * 
     * @param int $singleCardQty
     * @param int $packQty
     * @param int $boxQty
     * @return array{ bucket: string, logistic_units_total: float }
     */
    private function calculatePackageBucket(int $singleCardQty, int $packQty, int $boxQty): array
    {
        // Pesi unitari secondo specifica CardSwap V1
        $singleCardWeight = 0.2;
        $packWeight = 0.5;
        $boxWeight = 2.0;

        // Verifica se LETTER è consentita
        // LETTER solo se: SOLO SINGLE_CARD E quantità ≤ 5
        $isOnlySingleCards = ($packQty === 0 && $boxQty === 0);
        $canUseLetter = $isOnlySingleCards && $singleCardQty <= 5;

        if ($canUseLetter) {
            return [
                'bucket' => ShippingPackageBucket::LETTER,
                'logistic_units_total' => $singleCardQty * $singleCardWeight,
            ];
        }

        // Calcola total_units per determinare bucket
        $totalUnits = ($singleCardQty * $singleCardWeight) + 
                      ($packQty * $packWeight) + 
                      ($boxQty * $boxWeight);

        // Determina bucket basato su total_units
        if ($totalUnits <= 1.0) {
            $bucket = ShippingPackageBucket::PARCEL_S;
        } elseif ($totalUnits <= 4.0) {
            $bucket = ShippingPackageBucket::PARCEL_M;
        } else {
            $bucket = ShippingPackageBucket::PARCEL_L;
        }

        return [
            'bucket' => $bucket,
            'logistic_units_total' => $totalUnits,
        ];
    }
}
