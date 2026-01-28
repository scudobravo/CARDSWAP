<?php

namespace App\Enums;

/**
 * Enum per i metodi di spedizione nel sistema di pricing CardSwap V1
 * 
 * Definisce i metodi di spedizione disponibili per il calcolo delle tariffe.
 * 
 * NOTA: TRACKED_INSURED è un metodo "virtuale" usato lato ordine,
 * ma NON viene salvato nella tabella rates. Viene calcolato combinando
 * un metodo tracked con l'assicurazione abilitata.
 */
class ShippingMethod
{
    /**
     * Spedizione standard non tracciata
     * Valida SOLO per bucket LETTER (validazione applicativa)
     */
    public const UNTRACKED_STANDARD = 'UNTRACKED_STANDARD';

    /**
     * Spedizione standard tracciata
     */
    public const TRACKED_STANDARD = 'TRACKED_STANDARD';

    /**
     * Spedizione express tracciata
     */
    public const TRACKED_EXPRESS = 'TRACKED_EXPRESS';

    /**
     * Spedizione tracciata con assicurazione (VIRTUALE)
     * Non viene salvato in rates, ma usato lato ordine quando
     * si combina un metodo tracked con assicurazione abilitata
     */
    public const TRACKED_INSURED = 'TRACKED_INSURED';

    /**
     * Restituisce tutti i valori possibili (esclusi quelli virtuali)
     * 
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::UNTRACKED_STANDARD,
            self::TRACKED_STANDARD,
            self::TRACKED_EXPRESS,
        ];
    }

    /**
     * Restituisce tutti i valori inclusi quelli virtuali
     * 
     * @return array<string>
     */
    public static function allIncludingVirtual(): array
    {
        return [
            self::UNTRACKED_STANDARD,
            self::TRACKED_STANDARD,
            self::TRACKED_EXPRESS,
            self::TRACKED_INSURED,
        ];
    }

    /**
     * Restituisce solo i metodi tracciati
     * 
     * @return array<string>
     */
    public static function tracked(): array
    {
        return [
            self::TRACKED_STANDARD,
            self::TRACKED_EXPRESS,
        ];
    }

    /**
     * Verifica se un metodo è tracciato
     * 
     * @param string $method
     * @return bool
     */
    public static function isTracked(string $method): bool
    {
        return in_array($method, self::tracked(), true) || $method === self::TRACKED_INSURED;
    }

    /**
     * Verifica se un valore è valido (esclusi quelli virtuali)
     * 
     * @param string $value
     * @return bool
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::all(), true);
    }

    /**
     * Verifica se un valore è valido (inclusi quelli virtuali)
     * 
     * @param string $value
     * @return bool
     */
    public static function isValidIncludingVirtual(string $value): bool
    {
        return in_array($value, self::allIncludingVirtual(), true);
    }

    /**
     * Restituisce l'etichetta leggibile per un metodo
     * 
     * @param string $method
     * @return string
     */
    public static function label(string $method): string
    {
        return match ($method) {
            self::UNTRACKED_STANDARD => 'Standard Non Tracciata',
            self::TRACKED_STANDARD => 'Standard Tracciata',
            self::TRACKED_EXPRESS => 'Express Tracciata',
            self::TRACKED_INSURED => 'Tracciata con Assicurazione',
            default => $method,
        };
    }

    /**
     * Verifica se un metodo richiede tracking
     * 
     * @param string $method
     * @return bool
     */
    public static function requiresTracking(string $method): bool
    {
        return self::isTracked($method);
    }
}
