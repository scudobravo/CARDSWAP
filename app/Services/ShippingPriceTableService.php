<?php

namespace App\Services;

use App\Models\User;
use App\Models\ShippingPriceTable;
use App\Models\ShippingPriceTableCountry;
use App\Models\ShippingPriceTableRate;
use App\Enums\ShippingPackageBucket;
use App\Enums\ShippingMethod;
use DomainException;

/**
 * Service per la gestione delle tabelle prezzi di spedizione CardSwap V1
 * 
 * Contiene la logica di dominio per validazioni e vincoli applicativi.
 */
class ShippingPriceTableService
{
    /**
     * Verifica se un venditore può creare una nuova tabella prezzi
     * 
     * Controlla il limite massimo di tabelle per venditore definito in config.
     * 
     * @param User $seller
     * @return bool
     */
    public static function canCreateForSeller(User $seller): bool
    {
        $maxTables = config('shipping.max_price_tables_per_seller', 4);
        $currentCount = ShippingPriceTable::where('seller_id', $seller->id)->count();
        
        return $currentCount < $maxTables;
    }

    /**
     * Verifica se esiste già un'associazione paese per un venditore
     * 
     * Questo metodo permette di verificare PRIMA di tentare l'inserimento,
     * evitando errori di vincolo UNIQUE a livello database.
     * 
     * @param int $sellerId
     * @param string $countryCode
     * @return bool
     */
    public static function existsCountryForSeller(int $sellerId, string $countryCode): bool
    {
        return ShippingPriceTableCountry::existsForSellerAndCountry($sellerId, $countryCode);
    }

    /**
     * Valida una combinazione bucket/metodo
     * 
     * Regola: UNTRACKED_STANDARD è valido SOLO per LETTER
     * 
     * @param string $packageBucket
     * @param string $shippingMethod
     * @return void
     * @throws DomainException Se la combinazione non è valida
     */
    public static function validateRateCombination(string $packageBucket, string $shippingMethod): void
    {
        // Verifica che il bucket sia valido
        if (!ShippingPackageBucket::isValid($packageBucket)) {
            throw new DomainException("Bucket non valido: {$packageBucket}");
        }

        // Verifica che il metodo sia valido
        if (!ShippingMethod::isValid($shippingMethod)) {
            throw new DomainException("Metodo di spedizione non valido: {$shippingMethod}");
        }

        // Regola: UNTRACKED_STANDARD è valido SOLO per LETTER
        if ($shippingMethod === ShippingMethod::UNTRACKED_STANDARD && $packageBucket !== ShippingPackageBucket::LETTER) {
            throw new DomainException(
                "Il metodo UNTRACKED_STANDARD è valido solo per il bucket LETTER. " .
                "Bucket fornito: {$packageBucket}"
            );
        }
    }

    /**
     * Trova la tabella prezzi per un venditore e paese
     * 
     * @param int $sellerId
     * @param string $countryCode
     * @return ShippingPriceTable|null
     */
    public static function findTableForSellerAndCountry(int $sellerId, string $countryCode): ?ShippingPriceTable
    {
        $country = ShippingPriceTableCountry::forSellerAndCountry($sellerId, $countryCode)->first();
        
        return $country ? $country->shippingPriceTable : null;
    }

    /**
     * Ottiene la tariffa per una combinazione specifica
     * 
     * @param ShippingPriceTable $table
     * @param string $packageBucket
     * @param string $shippingMethod
     * @return ShippingPriceTableRate|null
     */
    public static function getRate(
        ShippingPriceTable $table,
        string $packageBucket,
        string $shippingMethod
    ): ?ShippingPriceTableRate {
        return ShippingPriceTableRate::where('shipping_price_table_id', $table->id)
            ->where('package_bucket', $packageBucket)
            ->where('shipping_method', $shippingMethod)
            ->first();
    }

    /**
     * Verifica se l'assicurazione è disponibile per un bucket in una tabella
     * 
     * @param ShippingPriceTable $table
     * @param string $packageBucket
     * @return bool
     */
    public static function isInsuranceAvailable(ShippingPriceTable $table, string $packageBucket): bool
    {
        $insuredConfig = $table->insuredConfigs()
            ->where('package_bucket', $packageBucket)
            ->first();

        return $insuredConfig && $insuredConfig->isEnabled();
    }
}
