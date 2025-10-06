<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShippoService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ShippoController extends Controller
{
    private ShippoService $shippoService;

    public function __construct(ShippoService $shippoService)
    {
        $this->shippoService = $shippoService;
    }

    /**
     * Calcola tariffe di spedizione per il checkout
     */
    public function calculateRates(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'sellers' => 'required|array',
            'sellers.*.id' => 'required|integer',
            'sellers.*.name' => 'required|string',
            'sellers.*.address' => 'required|array',
            'sellers.*.address.street1' => 'required|string',
            'sellers.*.address.city' => 'required|string',
            'sellers.*.address.state' => 'required|string',
            'sellers.*.address.zip' => 'required|string',
            'sellers.*.address.country' => 'required|string',
            'shipping_address' => 'required|array',
            'shipping_address.name' => 'required|string',
            'shipping_address.street1' => 'required|string',
            'shipping_address.city' => 'required|string',
            'shipping_address.state' => 'required|string',
            'shipping_address.zip' => 'required|string',
            'shipping_address.country' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sellers = $request->input('sellers');
            $shippingAddress = $request->input('shipping_address');

            $rates = $this->shippoService->calculateRatesForOrder($sellers, $shippingAddress);

            return response()->json([
                'success' => true,
                'data' => $rates
            ]);

        } catch (\Exception $e) {
            Log::error('Errore calcolo tariffe Shippo', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel calcolo delle tariffe di spedizione',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Acquista etichetta di spedizione
     */
    public function purchaseLabel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rate_object_id' => 'required|string',
            'order_id' => 'required|integer',
            'seller_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $rateObjectId = $request->input('rate_object_id');
            $orderData = [
                'order_id' => $request->input('order_id'),
                'seller_id' => $request->input('seller_id'),
            ];

            $result = $this->shippoService->purchaseLabelForOrder($rateObjectId, $orderData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Errore acquisto etichetta Shippo', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'acquisto dell\'etichetta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ottieni tracking di una spedizione
     */
    public function getTracking(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'carrier' => 'required|string',
            'tracking_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $carrier = $request->input('carrier');
            $trackingNumber = $request->input('tracking_number');

            $tracking = $this->shippoService->getTracking($carrier, $trackingNumber);

            return response()->json([
                'success' => true,
                'data' => $tracking
            ]);

        } catch (\Exception $e) {
            Log::error('Errore tracking Shippo', [
                'error' => $e->getMessage(),
                'carrier' => $request->input('carrier'),
                'tracking_number' => $request->input('tracking_number')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero del tracking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Valida un indirizzo
     */
    public function validateAddress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'street1' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'zip' => 'required|string',
            'country' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $address = $request->only([
                'name', 'street1', 'street2', 'city', 'state', 'zip', 'country', 'phone', 'email'
            ]);

            $validation = $this->shippoService->validateAddress($address);

            return response()->json([
                'success' => true,
                'data' => $validation
            ]);

        } catch (\Exception $e) {
            Log::error('Errore validazione indirizzo Shippo', [
                'error' => $e->getMessage(),
                'address' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nella validazione dell\'indirizzo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Programma un ritiro
     */
    public function schedulePickup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'carrier_account' => 'required|string',
            'address' => 'required|array',
            'contact' => 'required|array',
            'requested_start_time' => 'required|date',
            'requested_end_time' => 'required|date',
            'transactions' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $pickupData = $request->all();
            $pickup = $this->shippoService->schedulePickup($pickupData);

            return response()->json([
                'success' => true,
                'data' => $pickup
            ]);

        } catch (\Exception $e) {
            Log::error('Errore programmazione ritiro Shippo', [
                'error' => $e->getMessage(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nella programmazione del ritiro',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rimborsa un'etichetta
     */
    public function refundLabel(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $transactionId = $request->input('transaction_id');
            $refund = $this->shippoService->refundLabel($transactionId);

            return response()->json([
                'success' => true,
                'data' => $refund
            ]);

        } catch (\Exception $e) {
            Log::error('Errore rimborso etichetta Shippo', [
                'error' => $e->getMessage(),
                'transaction_id' => $request->input('transaction_id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel rimborso dell\'etichetta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Lista carrier accounts disponibili
     */
    public function getCarrierAccounts(): JsonResponse
    {
        try {
            $carriers = $this->shippoService->listCarrierAccounts();

            return response()->json([
                'success' => true,
                'data' => $carriers
            ]);

        } catch (\Exception $e) {
            Log::error('Errore recupero carrier accounts Shippo', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero dei corrieri',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
