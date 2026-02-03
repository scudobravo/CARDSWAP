<?php

/**
 * Configurazione Sistema Spedizioni CardSwap V1
 * 
 * Questo file contiene tutte le costanti e configurazioni per il nuovo sistema
 * di spedizioni CardSwap V1, che sostituisce il sistema basato su shipping_zones
 * per il calcolo dei prezzi.
 * 
 * Tutti i valori possono essere sovrascritti tramite variabili d'ambiente nel file .env
 * 
 * @see INTEGRAZIONE_SHIPPING_V1_PIANO.md per il piano di integrazione completo
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Configurazione Tabelle Prezzi Venditore
    |--------------------------------------------------------------------------
    |
    | Limiti e configurazioni per le tabelle prezzi che ogni venditore può creare
    | per personalizzare i costi di spedizione.
    |
    */

    /**
     * Numero massimo di tabelle prezzi che un venditore può creare
     * 
     * Utilizzo futuro:
     * - Validazione creazione/modifica tabelle prezzi venditore
     * - Limite UI per gestione tabelle prezzi
     * - Business logic per upgrade account premium
     * 
     * @var int
     */
    'max_price_tables_per_seller' => env('SHIPPING_MAX_PRICE_TABLES_PER_SELLER', 4),

    /*
    |--------------------------------------------------------------------------
    | Configurazione Spedizioni Non Tracciate (Untracked)
    |--------------------------------------------------------------------------
    |
    | Regole per spedizioni senza tracking number, tipicamente per ordini
    | di valore basso o spedizioni economiche.
    |
    */

    /**
     * Soglia massima subtotale (EUR) per permettere spedizione non tracciata
     * 
     * Utilizzo futuro:
     * - Validazione checkout: se subtotale > soglia, richiedere tracking
     * - Logica selezione metodi spedizione disponibili
     * - Calcolo costi: spedizioni non tracciate sono più economiche
     * 
     * @var float
     */
    'untracked_max_subtotal_eur' => env('SHIPPING_UNTRACKED_MAX_SUBTOTAL_EUR', 20.00),

    /**
     * Giorni di attesa per spedizioni non tracciate domestiche prima di considerare problema
     * 
     * Utilizzo futuro:
     * - Job cron per verificare ordini non tracciati in ritardo
     * - Notifiche automatiche a buyer/vendor dopo timeout
     * - Logica dispute: se superato timeout, permettere apertura dispute
     * 
     * @var int
     */
    'untracked_domestic_wait_days' => env('SHIPPING_UNTRACKED_DOMESTIC_WAIT_DAYS', 14),

    /**
     * Giorni di attesa per spedizioni non tracciate internazionali prima di considerare problema
     * 
     * Utilizzo futuro:
     * - Job cron per verificare ordini non tracciati in ritardo
     * - Notifiche automatiche a buyer/vendor dopo timeout
     * - Logica dispute: se superato timeout, permettere apertura dispute
     * 
     * @var int
     */
    'untracked_intl_wait_days' => env('SHIPPING_UNTRACKED_INTL_WAIT_DAYS', 30),

    /**
     * Giorni per segnare come spedito (spedizione non tracciata) – FASE D3 reminder
     * Reminder inviato 1 giorno prima (reminder_untracked = untracked_mark_shipped_days - 1).
     */
    'untracked_mark_shipped_days' => env('SHIPPING_UNTRACKED_MARK_SHIPPED_DAYS', 5),

    /*
    |--------------------------------------------------------------------------
    | Configurazione Assicurazione
    |--------------------------------------------------------------------------
    |
    | Regole per l'assicurazione automatica o opzionale delle spedizioni
    | basate sul valore dell'ordine.
    |
    */

    /**
     * Soglia minima subtotale (EUR) per richiedere assicurazione obbligatoria
     * 
     * Utilizzo futuro:
     * - Validazione checkout: se subtotale >= soglia, assicurazione obbligatoria
     * - Calcolo costi: aggiungere costo assicurazione al totale
     * - Logica selezione metodi: filtrare metodi che supportano assicurazione
     * 
     * @var float
     */
    'insured_min_subtotal_eur' => env('SHIPPING_INSURED_MIN_SUBTOTAL_EUR', 200.00),

    /**
     * Percentuale di assicurazione sul valore dell'ordine
     * 
     * Utilizzo futuro:
     * - Calcolo costo assicurazione: subtotale * rate
     * - Validazione: assicurarsi che costo >= min_fee
     * - Display UI: mostrare breakdown costo assicurazione
     * 
     * @var float Percentuale (es. 0.02 = 2%)
     */
    'insurance_rate' => env('SHIPPING_INSURANCE_RATE', 0.012),

    /**
     * Costo minimo assicurazione in EUR (anche se percentuale è inferiore)
     * 
     * Utilizzo futuro:
     * - Calcolo costo assicurazione: max(subtotale * rate, min_fee)
     * - Validazione: assicurarsi che costo non sia inferiore a min_fee
     * - Display UI: mostrare costo minimo garantito
     * 
     * @var float
     */
    'insurance_min_fee_eur' => env('SHIPPING_INSURANCE_MIN_FEE_EUR', 5.00),

    /*
    |--------------------------------------------------------------------------
    | Configurazione Tempi Spedizione Venditore
    |--------------------------------------------------------------------------
    |
    | Regole per i tempi di spedizione che il venditore deve rispettare.
    |
    */

    /**
     * Numero di giorni entro cui il venditore deve spedire l'ordine
     * 
     * Utilizzo futuro:
     * - Validazione creazione ordine: calcolare data limite spedizione
     * - Job cron: verificare ordini non spediti entro deadline
     * - Notifiche: promemoria venditore se si avvicina deadline
     * - Logica dispute: se superato deadline, permettere apertura dispute
     * - Display UI: mostrare "Spedire entro [data]" nelle vendite
     * 
     * @var int
     */
    'seller_ship_by_days' => env('SHIPPING_SELLER_SHIP_BY_DAYS', 5),

    /**
     * Numero di giorni entro cui il venditore deve fornire tracking number
     * (se richiesto per il tipo di spedizione)
     * 
     * Utilizzo futuro:
     * - Validazione: se tracking richiesto, verificare che sia fornito entro deadline
     * - Job cron: verificare ordini senza tracking dopo deadline
     * - Notifiche: promemoria venditore se tracking mancante
     * - Logica dispute: se superato deadline, permettere apertura dispute
     * 
     * @var int
     */
    'tracking_required_within_days' => env('SHIPPING_TRACKING_REQUIRED_WITHIN_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Configurazione Trattenuta Fondi (Payout)
    |--------------------------------------------------------------------------
    |
    | Regole per la trattenuta dei fondi del venditore dopo la consegna.
    | Questa configurazione si integra con il sistema esistente di payout.
    |
    */

    /**
     * Ore di trattenuta fondi dopo la consegna prima del rilascio al venditore
     * 
     * Utilizzo futuro:
     * - Calcolo payout_scheduled_at: delivered_at + hours
     * - Job ReleaseSellerFunds: verificare se sono passate le ore
     * - Display UI: mostrare countdown rilascio fondi
     * - Logica dispute: se dispute aperta, bloccare payout
     * 
     * NOTA: Questo valore si integra con il sistema esistente di payout
     * che già gestisce il timer di 72h. Questo valore può essere usato
     * per override o per configurazione specifica del nuovo sistema.
     * 
     * @var int
     */
    'hold_after_delivered_hours' => env('SHIPPING_HOLD_AFTER_DELIVERED_HOURS', 72),

    /*
    |--------------------------------------------------------------------------
    | Configurazione Timeout Spedizioni Tracciate
    |--------------------------------------------------------------------------
    |
    | Regole per timeout di spedizioni tracciate che non risultano consegnate
    | dopo un certo periodo.
    |
    */

    /**
     * Giorni di timeout per spedizioni tracciate domestiche senza consegna
     * 
     * Utilizzo futuro:
     * - Job cron: verificare ordini tracciati senza "delivered" dopo timeout
     * - Logica dispute: se superato timeout, permettere apertura dispute
     * - Notifiche: avvisare buyer/vendor di possibile problema
     * - Display UI: mostrare warning se si avvicina timeout
     * 
     * @var int
     */
    'tracked_no_delivered_timeout_domestic_days' => env('SHIPPING_TRACKED_NO_DELIVERED_TIMEOUT_DOMESTIC_DAYS', 14),

    /**
     * Giorni di timeout per spedizioni tracciate internazionali senza consegna
     * 
     * Utilizzo futuro:
     * - Job cron: verificare ordini tracciati senza "delivered" dopo timeout
     * - Logica dispute: se superato timeout, permettere apertura dispute
     * - Notifiche: avvisare buyer/vendor di possibile problema
     * - Display UI: mostrare warning se si avvicina timeout
     * 
     * @var int
     */
    'tracked_no_delivered_timeout_intl_days' => env('SHIPPING_TRACKED_NO_DELIVERED_TIMEOUT_INTL_DAYS', 30),

];
