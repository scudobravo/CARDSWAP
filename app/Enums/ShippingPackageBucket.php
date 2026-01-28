<?php

namespace App\Enums;

/**
 * Enum per i bucket di pacchi nel sistema di pricing CardSwap V1
 * 
 * Definisce le categorie di pacchi disponibili per il calcolo delle tariffe.
 */
class ShippingPackageBucket
{
    /**
     * Lettera (piccolo, fino a ~100g)
     */
    public const LETTER = 'LETTER';

    /**
     * Pacco piccolo (S)
     */
    public const PARCEL_S = 'PARCEL_S';

    /**
     * Pacco medio (M)
     */
    public const PARCEL_M = 'PARCEL_M';

    /**
     * Pacco grande (L)
     */
    public const PARCEL_L = 'PARCEL_L';

    /**
     * Restituisce tutti i valori possibili
     * 
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::LETTER,
            self::PARCEL_S,
            self::PARCEL_M,
            self::PARCEL_L,
        ];
    }

    /**
     * Verifica se un valore è valido
     * 
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }

    /**
     * Restituisce l'etichetta leggibile per un bucket
     * 
     * @param string $bucket
     * @return string
     */
    public static function label(string $bucket): string
    {
        return match ($bucket) {
            self::LETTER => 'Lettera',
            self::PARCEL_S => 'Pacco Piccolo (S)',
            self::PARCEL_M => 'Pacco Medio (M)',
            self::PARCEL_L => 'Pacco Grande (L)',
            default => $bucket,
        };
    }
}
