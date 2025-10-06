<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserAddressController extends Controller
{
    /**
     * Ottieni tutti gli indirizzi dell'utente
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();
            $addresses = UserAddress::where('user_id', $user->id)
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $addresses,
                'message' => 'Indirizzi recuperati con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nel recupero degli indirizzi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crea un nuovo indirizzo
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'label' => 'required|string|max:255',
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'company' => 'nullable|string|max:255',
                'address_line_1' => 'required|string|max:255',
                'address_line_2' => 'nullable|string|max:255',
                'city' => 'required|string|max:255',
                'state_province' => 'nullable|string|max:255',
                'postal_code' => 'required|string|max:10',
                'country' => 'required|string|max:2',
                'phone' => 'nullable|string|max:20',
                'is_default' => 'boolean',
                'is_billing' => 'boolean',
                'is_shipping' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dati di validazione non validi',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Auth::user();

            // Se questo deve essere l'indirizzo predefinito, rimuovi il flag dagli altri
            if ($request->input('is_default', false)) {
                UserAddress::where('user_id', $user->id)
                    ->update(['is_default' => false]);
            }

            $address = UserAddress::create([
                'user_id' => $user->id,
                'label' => $request->input('label'),
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'company' => $request->input('company'),
                'address_line_1' => $request->input('address_line_1'),
                'address_line_2' => $request->input('address_line_2'),
                'city' => $request->input('city'),
                'state_province' => $request->input('state_province'),
                'postal_code' => $request->input('postal_code'),
                'country' => $request->input('country'),
                'phone' => $request->input('phone'),
                'is_default' => $request->input('is_default', false),
                'is_billing' => $request->input('is_billing', false),
                'is_shipping' => $request->input('is_shipping', true)
            ]);

            return response()->json([
                'success' => true,
                'data' => $address,
                'message' => 'Indirizzo creato con successo'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione dell\'indirizzo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aggiorna un indirizzo esistente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $address = UserAddress::where('user_id', $user->id)->find($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Indirizzo non trovato'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'label' => 'sometimes|string|max:255',
                'first_name' => 'sometimes|string|max:255',
                'last_name' => 'sometimes|string|max:255',
                'company' => 'nullable|string|max:255',
                'address_line_1' => 'sometimes|string|max:255',
                'address_line_2' => 'nullable|string|max:255',
                'city' => 'sometimes|string|max:255',
                'state_province' => 'nullable|string|max:255',
                'postal_code' => 'sometimes|string|max:10',
                'country' => 'sometimes|string|max:2',
                'phone' => 'nullable|string|max:20',
                'is_default' => 'boolean',
                'is_billing' => 'boolean',
                'is_shipping' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dati di validazione non validi',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Se questo deve essere l'indirizzo predefinito, rimuovi il flag dagli altri
            if ($request->input('is_default', false)) {
                UserAddress::where('user_id', $user->id)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            $address->update($request->only([
                'label', 'first_name', 'last_name', 'company',
                'address_line_1', 'address_line_2', 'city',
                'state_province', 'postal_code', 'country', 'phone',
                'is_default', 'is_billing', 'is_shipping'
            ]));

            return response()->json([
                'success' => true,
                'data' => $address,
                'message' => 'Indirizzo aggiornato con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento dell\'indirizzo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Elimina un indirizzo
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $address = UserAddress::where('user_id', $user->id)->find($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Indirizzo non trovato'
                ], 404);
            }

            $address->delete();

            return response()->json([
                'success' => true,
                'message' => 'Indirizzo eliminato con successo'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'eliminazione dell\'indirizzo',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Imposta un indirizzo come predefinito
     */
    public function setDefault(int $id): JsonResponse
    {
        try {
            $user = Auth::user();
            $address = UserAddress::where('user_id', $user->id)->find($id);

            if (!$address) {
                return response()->json([
                    'success' => false,
                    'message' => 'Indirizzo non trovato'
                ], 404);
            }

            $address->setAsDefault();

            return response()->json([
                'success' => true,
                'data' => $address,
                'message' => 'Indirizzo impostato come predefinito'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'impostazione dell\'indirizzo predefinito',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}