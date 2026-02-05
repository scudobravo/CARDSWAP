<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\ShippingPriceTable;
use App\Models\ShippingPriceTableCountry;
use App\Models\ShippingPriceTableRate;
use App\Models\ShippingPriceTableInsured;
use App\Services\ShippingPriceTableService;
use App\Enums\ShippingPackageBucket;
use App\Enums\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use DomainException;

class ShippingPriceTableController extends Controller
{
    /**
     * Lista tutte le tabelle prezzi del venditore autenticato
     * 
     * Include paesi, tariffe e configurazioni assicurazione
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $seller = $request->user();
            
            if (!$seller || !$seller->isSeller()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accesso negato. Solo i venditori possono accedere a questa risorsa.'
                ], 403);
            }

            $tables = ShippingPriceTable::forSeller($seller->id)
                ->with(['countries', 'rates', 'insuredConfigs'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($table) {
                    return [
                        'id' => $table->id,
                        'name' => $table->name,
                        'seller_id' => $table->seller_id,
                        'countries' => $table->countries->map(function ($country) {
                            return [
                                'id' => $country->id,
                                'country_code' => $country->country_code,
                            ];
                        }),
                        'rates' => $table->rates->map(function ($rate) {
                            return [
                                'id' => $rate->id,
                                'package_bucket' => $rate->package_bucket,
                                'shipping_method' => $rate->shipping_method,
                                'price_eur' => $rate->price_eur,
                                'is_available' => $rate->isAvailable(),
                            ];
                        }),
                        'insured_configs' => $table->insuredConfigs->map(function ($insured) {
                            return [
                                'id' => $insured->id,
                                'package_bucket' => $insured->package_bucket,
                                'enabled' => $insured->enabled,
                            ];
                        }),
                        'created_at' => $table->created_at,
                        'updated_at' => $table->updated_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $tables
            ]);

        } catch (\Exception $e) {
            Log::error('Errore caricamento tabelle prezzi', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel caricamento delle tabelle prezzi'
            ], 500);
        }
    }

    /**
     * Crea una nuova tabella prezzi
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati di validazione non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $seller = $request->user();
            
            if (!$seller || !$seller->isSeller()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accesso negato. Solo i venditori possono creare tabelle prezzi.'
                ], 403);
            }

            // Verifica limite tabelle per venditore
            if (!ShippingPriceTableService::canCreateForSeller($seller)) {
                $maxTables = config('shipping.max_price_tables_per_seller', 4);
                return response()->json([
                    'success' => false,
                    'message' => "Hai raggiunto il limite massimo di {$maxTables} tabelle prezzi. Elimina una tabella esistente per crearne una nuova.",
                    'max_tables' => $maxTables
                ], 422);
            }

            $table = ShippingPriceTable::create([
                'seller_id' => $seller->id,
                'name' => $request->name,
            ]);

            Log::info('Tabella prezzi creata', [
                'table_id' => $table->id,
                'seller_id' => $seller->id,
                'name' => $table->name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tabella prezzi creata con successo',
                'data' => [
                    'id' => $table->id,
                    'name' => $table->name,
                    'seller_id' => $table->seller_id,
                    'created_at' => $table->created_at,
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Errore creazione tabella prezzi', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nella creazione della tabella prezzi'
            ], 500);
        }
    }

    /**
     * Aggiorna il nome di una tabella prezzi
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati di validazione non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $seller = $request->user();
            
            if (!$seller || !$seller->isSeller()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accesso negato'
                ], 403);
            }

            $table = ShippingPriceTable::forSeller($seller->id)->findOrFail($id);

            $table->update([
                'name' => $request->name,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tabella prezzi aggiornata con successo',
                'data' => [
                    'id' => $table->id,
                    'name' => $table->name,
                    'updated_at' => $table->updated_at,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tabella prezzi non trovata o non autorizzato'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Errore aggiornamento tabella prezzi', [
                'table_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'aggiornamento della tabella prezzi'
            ], 500);
        }
    }

    /**
     * Elimina una tabella prezzi
     * 
     * Cascade elimina automaticamente paesi, tariffe e configurazioni assicurazione
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $seller = $request->user();
            
            if (!$seller || !$seller->isSeller()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accesso negato'
                ], 403);
            }

            $table = ShippingPriceTable::forSeller($seller->id)->findOrFail($id);

            $table->delete();

            Log::info('Tabella prezzi eliminata', [
                'table_id' => $id,
                'seller_id' => $seller->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tabella prezzi eliminata con successo'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tabella prezzi non trovata o non autorizzato'
            ], 404);
        } catch (\Exception $e) {
            Log::error('Errore eliminazione tabella prezzi', [
                'table_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'eliminazione della tabella prezzi'
            ], 500);
        }
    }

    /**
     * Assegna paesi a una tabella prezzi
     */
    public function addCountries(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'countries' => 'required|array|min:1',
            'countries.*' => 'required|string|size:2|regex:/^[A-Z]{2}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati di validazione non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $seller = $request->user();
            
            if (!$seller || !$seller->isSeller()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accesso negato'
                ], 403);
            }

            $table = ShippingPriceTable::forSeller($seller->id)->findOrFail($id);

            $countries = $request->input('countries');
            $added = [];
            $skipped = [];

            DB::beginTransaction();

            foreach ($countries as $countryCode) {
                $countryCode = strtoupper($countryCode);

                // Verifica se il paese esiste già per questo venditore
                if (ShippingPriceTableService::existsCountryForSeller($seller->id, $countryCode)) {
                    $skipped[] = [
                        'country_code' => $countryCode,
                        'reason' => 'Paese già associato a un\'altra tabella'
                    ];
                    continue;
                }

                ShippingPriceTableCountry::create([
                    'shipping_price_table_id' => $table->id,
                    'seller_id' => $seller->id,
                    'country_code' => $countryCode,
                ]);

                $added[] = $countryCode;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paesi assegnati con successo',
                'data' => [
                    'added' => $added,
                    'skipped' => $skipped,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tabella prezzi non trovata o non autorizzato'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore assegnazione paesi', [
                'table_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nell\'assegnazione dei paesi'
            ], 500);
        }
    }

    /**
     * Sincronizza l'elenco paesi della tabella: sostituisce con la lista inviata.
     * Consente di rimuovere paesi deselezionati (non solo aggiungere).
     */
    public function syncCountries(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'countries' => 'required|array',
            'countries.*' => 'required|string|size:2|regex:/^[A-Za-z]{2}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati di validazione non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $seller = $request->user();

            if (!$seller || !$seller->isSeller()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accesso negato'
                ], 403);
            }

            $table = ShippingPriceTable::forSeller($seller->id)->findOrFail($id);

            $requested = array_map(function ($code) {
                return strtoupper($code);
            }, array_values(array_unique($request->input('countries'))));

            DB::beginTransaction();

            ShippingPriceTableCountry::where('shipping_price_table_id', $table->id)->delete();

            $added = [];
            $skipped = [];

            foreach ($requested as $countryCode) {
                if (ShippingPriceTableService::existsCountryForSeller($seller->id, $countryCode)) {
                    $skipped[] = [
                        'country_code' => $countryCode,
                        'reason' => 'Paese già in un\'altra tabella',
                    ];
                    continue;
                }

                ShippingPriceTableCountry::create([
                    'shipping_price_table_id' => $table->id,
                    'seller_id' => $seller->id,
                    'country_code' => $countryCode,
                ]);

                $added[] = $countryCode;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Paesi sincronizzati',
                'data' => [
                    'added' => $added,
                    'skipped' => $skipped,
                ]
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tabella prezzi non trovata o non autorizzato'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Errore sync paesi tabella prezzi', [
                'table_id' => $id,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Errore nella sincronizzazione dei paesi'
            ], 500);
        }
    }

    /**
     * Salva matrice prezzi per una tabella
     * 
     * Input: array di tariffe con package_bucket, shipping_method, price_eur
     */
    public function saveRates(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'rates' => 'required|array',
            'rates.*.package_bucket' => 'required|string|in:LETTER,PARCEL_S,PARCEL_M,PARCEL_L',
            'rates.*.shipping_method' => 'required|string|in:UNTRACKED_STANDARD,TRACKED_STANDARD,TRACKED_EXPRESS',
            'rates.*.price_eur' => 'nullable|numeric|min:0|max:9999.99',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati di validazione non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $seller = $request->user();
            
            if (!$seller || !$seller->isSeller()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accesso negato'
                ], 403);
            }

            $table = ShippingPriceTable::forSeller($seller->id)->findOrFail($id);

            $rates = $request->input('rates');
            $saved = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($rates as $rateData) {
                try {
                    // Valida combinazione bucket/metodo
                    ShippingPriceTableService::validateRateCombination(
                        $rateData['package_bucket'],
                        $rateData['shipping_method']
                    );

                    // Cerca tariffa esistente o crea nuova
                    $rate = ShippingPriceTableRate::updateOrCreate(
                        [
                            'shipping_price_table_id' => $table->id,
                            'package_bucket' => $rateData['package_bucket'],
                            'shipping_method' => $rateData['shipping_method'],
                        ],
                        [
                            'price_eur' => isset($rateData['price_eur']) && $rateData['price_eur'] !== '' 
                                ? (float) $rateData['price_eur'] 
                                : null,
                        ]
                    );

                    $saved[] = [
                        'id' => $rate->id,
                        'package_bucket' => $rate->package_bucket,
                        'shipping_method' => $rate->shipping_method,
                        'price_eur' => $rate->price_eur,
                    ];

                } catch (DomainException $e) {
                    $errors[] = [
                        'package_bucket' => $rateData['package_bucket'],
                        'shipping_method' => $rateData['shipping_method'],
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tariffe salvate con successo',
                'data' => [
                    'saved' => $saved,
                    'errors' => $errors,
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tabella prezzi non trovata o non autorizzato'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore salvataggio tariffe', [
                'table_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nel salvataggio delle tariffe'
            ], 500);
        }
    }

    /**
     * Configura assicurazione per bucket in una tabella
     */
    public function configureInsurance(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'configs' => 'required|array',
            'configs.*.package_bucket' => 'required|string|in:LETTER,PARCEL_S,PARCEL_M,PARCEL_L',
            'configs.*.enabled' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dati di validazione non validi',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $seller = $request->user();
            
            if (!$seller || !$seller->isSeller()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Accesso negato'
                ], 403);
            }

            $table = ShippingPriceTable::forSeller($seller->id)->findOrFail($id);

            $configs = $request->input('configs');
            $saved = [];

            DB::beginTransaction();

            foreach ($configs as $configData) {
                $insured = ShippingPriceTableInsured::updateOrCreate(
                    [
                        'shipping_price_table_id' => $table->id,
                        'package_bucket' => $configData['package_bucket'],
                    ],
                    [
                        'enabled' => (bool) $configData['enabled'],
                    ]
                );

                $saved[] = [
                    'id' => $insured->id,
                    'package_bucket' => $insured->package_bucket,
                    'enabled' => $insured->enabled,
                ];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Configurazione assicurazione salvata con successo',
                'data' => $saved
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tabella prezzi non trovata o non autorizzato'
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Errore configurazione assicurazione', [
                'table_id' => $id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore nella configurazione dell\'assicurazione'
            ], 500);
        }
    }
}
