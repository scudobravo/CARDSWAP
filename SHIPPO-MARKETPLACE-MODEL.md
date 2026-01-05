# Messaggio Shippo: Modello Marketplace Peer-to-Peer

## 📧 Contenuto del Messaggio

Shippo ha confermato che:

1. **Non è obbligatorio connettere account corrieri personali** per usare Shippo
2. **Shippo fornisce account corrieri pre-negoziati** (USPS, UPS, DHL Express, ecc.)
3. **LIMITAZIONE IMPORTANTE**: Gli account principali di Shippo supportano principalmente spedizioni che **partono dagli USA**
4. **Raccomandazione per marketplace peer-to-peer globali** (come CardSwap):
   - Usare gli account corrieri **built-in di Shippo** così i venditori in tutto il mondo possono generare etichette basate sul loro paese di origine
   - La piattaforma (CardSwap) può pagare per l'etichetta al momento della creazione
   - Poi passare il costo al venditore o compratore come parte del checkout o del flusso di settlement
   - Opzione alternativa: integrazione "Gray label"

## 🔍 Analisi della Situazione Attuale

### Configurazione Corrente

Il sistema CardSwap attualmente:

1. ✅ **Supporta venditori multipli** con indirizzi diversi (funzione `calculateRatesForOrder`)
2. ✅ **Usa account corrieri built-in di Shippo** (non account personali)
3. ⚠️ **Problema potenziale**: Gli account corrieri di Shippo potrebbero non supportare bene spedizioni che partono da paesi diversi dagli USA

### Corrieri Attualmente Configurati

Dal file `config/services.php`:
- **Poste Italiane** (solo IT → IT)
- **Chronopost** (Francia)
- **Colissimo** (Francia)
- **Deutsche Post** (Germania)
- **Correos** (Spagna)
- **CouriersPlease** (Australia)

**Nota**: Questi corrieri sono disponibili nel tuo account Shippo, ma potrebbero avere limitazioni geografiche.

## ⚠️ Implicazioni per CardSwap

### Problema Principale

Se CardSwap ha venditori in **paesi diversi dall'Italia** (o dagli USA), potrebbero esserci problemi:

1. **Corrieri limitati**: Gli account built-in di Shippo potrebbero non supportare tutti i corrieri per tutti i paesi di origine
2. **Tariffe non disponibili**: Alcune combinazioni origine-destinazione potrebbero non avere corrieri disponibili
3. **Costi più alti**: I corrieri disponibili potrebbero essere più costosi

### Cosa Significa "Gray Label Integration"

L'integrazione "Gray label" menzionata nel messaggio è un'opzione avanzata dove:
- La piattaforma (CardSwap) gestisce l'interfaccia utente
- Shippo gestisce le operazioni di backend
- Permette più flessibilità per marketplace globali

**Documentazione**: https://docs.goshippo.com/docs/partner_integration/int_decisions/#user-interface

## ✅ Cosa Fare

### 1. Verificare Corrieri Disponibili per Ogni Paese

**Azione immediata**: Testare quali corrieri sono disponibili per spedizioni che partono da:
- Italia (attuale)
- Altri paesi UE (se hai venditori)
- USA (se hai venditori)
- Altri paesi (se hai venditori)

**Script di test**: Usa `test-shippo-available-carriers.php` per verificare.

### 2. Implementare Gestione Multi-Paese

Se hai venditori in paesi diversi:

**Opzione A: Usare account built-in di Shippo** (raccomandato da Shippo)
- ✅ Non richiede configurazione account corrieri personali
- ✅ Funziona automaticamente per tutti i paesi supportati
- ⚠️ Limitato ai corrieri disponibili per ogni paese

**Opzione B: Chiedere ai venditori di connettere i propri account corrieri**
- ✅ Più flessibilità
- ✅ Corrieri locali disponibili
- ❌ Complessità maggiore
- ❌ Richiede onboarding più complesso

### 3. Gestione Pagamento Etichette

Come raccomandato da Shippo:

1. **CardSwap paga l'etichetta** al momento della creazione
2. **Il costo viene passato** al venditore o compratore:
   - Nel checkout (acquirente paga)
   - Nel settlement (venditore paga)
   - Diviso tra acquirente e venditore

**Implementazione attuale**: Verificare come viene gestito il pagamento delle etichette nel flusso di checkout.

### 4. Testare Scenario Multi-Paese

**⚠️ IMPORTANTE**: I test in locale potrebbero fallire se usi una chiave API di TEST. Le chiavi di test hanno spesso limitazioni geografiche.

**Test da eseguire in LOCALE**:

```bash
# Test completo multi-paese
php test-shippo-multi-origin.php
```

**Test da eseguire in PRODUZIONE** (raccomandato):

```bash
# Connettiti al server di produzione
ssh user@server

# Vai nella directory dell'applicazione
cd /path/to/cardswap

# Esegui il test
php test-shippo-multi-origin.php
```

**Perché testare in produzione?**
- Le chiavi API di produzione hanno meno limitazioni
- Gli account corrieri potrebbero essere configurati diversamente
- I test in produzione danno risultati più accurati

**Se i test falliscono anche in produzione:**

1. **Verifica configurazione account Shippo**:
   - Vai su: https://goshippo.com/dashboard/settings/carrier-accounts
   - Verifica che i corrieri siano attivi
   - Controlla che i corrieri supportino i paesi di origine dei tuoi venditori

2. **Contatta supporto Shippo**:
   - Email: support@shippo.com
   - Chiedi specificamente:
     - Quali corrieri sono disponibili per spedizioni dall'Italia?
     - Quali corrieri supportano spedizioni da altri paesi UE?
     - Come funziona l'integrazione "Gray label" per marketplace globali?

3. **Considera alternative**:
   - Permettere ai venditori di connettere account corrieri locali
   - Usare un mix: account built-in per alcuni paesi, account locali per altri
   - Implementare integrazione "Gray label" se disponibile

## 📋 Checklist Azioni

### Immediate
- [ ] Verificare quali paesi hanno venditori attivi su CardSwap
- [ ] Testare disponibilità corrieri per ogni paese di origine
- [ ] Verificare che il sistema gestisca correttamente indirizzi venditori diversi
- [ ] Controllare il flusso di pagamento etichette

### A Medio Termine
- [ ] Documentare quali corrieri sono disponibili per ogni paese
- [ ] Implementare fallback se un corriere non è disponibile
- [ ] Considerare integrazione "Gray label" se necessario
- [ ] Ottimizzare selezione corrieri per costo/tempo

### A Lungo Termine
- [ ] Valutare se permettere ai venditori di connettere account corrieri personali
- [ ] Implementare dashboard per monitorare corrieri disponibili
- [ ] Creare alert se un corriere non è più disponibile

## 🔗 Risorse

- **Documentazione Shippo**: https://docs.goshippo.com/
- **Gray Label Integration**: https://docs.goshippo.com/docs/partner_integration/int_decisions/#user-interface
- **Help Center Shippo**: Per informazioni sui corrieri supportati per paese
- **Supporto Shippo**: support@shippo.com

## 💡 Raccomandazioni

1. **Per ora**: Continuare a usare account built-in di Shippo (come raccomandato)
2. **Monitorare**: Verificare che i corrieri funzionino per tutti i paesi di origine dei venditori
3. **Comunicare**: Informare i venditori se ci sono limitazioni geografiche
4. **Valutare**: Considerare "Gray label" solo se ci sono problemi significativi con account built-in

## ❓ Domande da Porre a Shippo

1. Quali corrieri sono disponibili per spedizioni che partono dall'Italia?
2. Quali corrieri sono disponibili per altri paesi UE?
3. C'è un limite al numero di paesi di origine supportati?
4. Come funziona esattamente l'integrazione "Gray label"?
5. Quali sono i costi aggiuntivi per "Gray label"?

---

**Data analisi**: Gennaio 2025
**Status**: Da implementare/testare
