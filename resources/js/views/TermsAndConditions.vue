<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <!-- Content Section -->
    <div class="bg-white py-16 sm:py-24">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Title -->
        <div class="text-center mb-12">
          <h1 class="text-4xl font-futura-bold text-primary sm:text-5xl mb-4">Termini e Condizioni di Utilizzo di CardSwap Srl</h1>
          <p class="text-lg text-gray-600 font-gill-sans">Ultimo aggiornamento: {{ lastUpdated }}</p>
        </div>

        <!-- Titolare -->
        <div class="prose prose-lg max-w-none font-gill-sans text-gray-700 mb-8">
          <div class="bg-gray-50 p-6 rounded-lg">
            <p class="font-gill-sans-semibold text-primary mb-2">Titolare del Servizio:</p>
            <p class="mb-1"><strong>Cardswap S.r.l.</strong> – Via Umberto Eco 2, 40012 Calderara di Reno (BO)</p>
            <p class="mb-1">P.IVA / C.F. 04320241203 – PEC: <a href="mailto:cardswap@arubapec.it" class="text-secondary hover:underline">cardswap@arubapec.it</a></p>
            <p>E-mail: <a href="mailto:info@cardswaptcg.com" class="text-secondary hover:underline">info@cardswaptcg.com</a></p>
          </div>
        </div>

        <!-- Content -->
        <div class="prose prose-lg max-w-none font-gill-sans text-gray-700">
          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">1. Oggetto</h2>
            <p class="mb-4">
              <strong>1.1</strong> Il presente documento stabilisce i termini e le condizioni generali di utilizzo della piattaforma Cardswaptcg (di seguito, "Piattaforma"), accessibile all'indirizzo <a href="https://www.cardswaptcg.com" class="text-secondary hover:underline">www.cardswaptcg.com</a>.
            </p>
            <p class="mb-4">
              <strong>1.2</strong> La Piattaforma consente agli utenti registrati di vendere e acquistare carte da collezione in modo sicuro e trasparente.
            </p>
            <p class="mb-4">
              <strong>1.3</strong> CardSwap opera come intermediario tecnico, mettendo in contatto venditori e acquirenti senza partecipare direttamente ai contratti di compravendita conclusi tra gli utenti.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">2. Ruolo di CardSwap</h2>
            <p class="mb-4">
              <strong>2.1</strong> CardSwap non è parte del contratto di vendita tra gli utenti.
            </p>
            <p class="mb-4">
              <strong>2.2</strong> Il pagamento avviene tramite un sistema sicuro gestito da un fornitore esterno (es. Stripe). Eventuali tempistiche di trattenimento e rilascio dei fondi sono determinate esclusivamente dal provider di pagamento, secondo i propri termini e condizioni. CardSwap non controlla tali tempistiche né i flussi di pagamento.
            </p>
            <p class="mb-4">
              <strong>2.3</strong> Le modalità e i costi di spedizione sono stabiliti autonomamente dal venditore per ciascuna inserzione. Il venditore è responsabile dell'organizzazione, gestione e corretta esecuzione della spedizione, inclusa la scelta del corriere e deve essere eseguita tramite la modalità richiesta e pagata dall'acquirente.
            </p>
            <p class="mb-4">
              <strong>2.4</strong> CardSwap non detiene fisicamente i prodotti, non effettua consegne né riceve o conserva direttamente somme di denaro.
            </p>
            <p class="mb-4">
              <strong>2.5</strong> Il Titolare non è responsabile di eventuali ritardi, smarrimenti, danneggiamenti o contestazioni legate alle spedizioni, né della qualità, autenticità o conformità delle carte vendute.
            </p>
            <p class="mb-4">
              <strong>2.6</strong> In caso di controversie tra utenti, CardSwap potrà offrire assistenza e mediazione, senza assumere obblighi di risultato.
            </p>
            <p class="mb-4">
              <strong>2.7</strong> In caso di contestazioni o chargeback tramite il provider di pagamento, CardSwap si riserva il diritto di: sospendere temporaneamente gli account coinvolti; richiedere informazioni e prove agli utenti; collaborare con il provider di pagamento. CardSwap non garantisce l'esito delle controversie.
            </p>
            <p class="mb-4">
              <strong>2.8</strong> CardSwap non agisce come agente, mandatario o rappresentante degli utenti.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">3. Registrazione e Account</h2>
            <p class="mb-4">
              <strong>3.1</strong> Per utilizzare la Piattaforma è necessario registrarsi, fornendo dati veritieri e aggiornati.
            </p>
            <p class="mb-4">
              <strong>3.2</strong> L'utente deve essere maggiorenne e capace di agire.
            </p>
            <p class="mb-4">
              <strong>3.3</strong> Ogni utente è responsabile della custodia delle proprie credenziali di accesso e di tutte le attività svolte tramite il proprio account.
            </p>
            <p class="mb-4">
              <strong>3.4</strong> Il Titolare potrà sospendere o chiudere un account in caso di violazioni dei presenti Termini, di comportamenti scorretti o di utilizzo illecito della Piattaforma.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">4. Vendite, Pagamenti e Commissioni</h2>
            <p class="mb-4">
              <strong>4.1</strong> Il pagamento avviene tramite un sistema sicuro gestito da un fornitore esterno (es. Stripe). Eventuali tempistiche di trattenimento e rilascio dei fondi sono determinate esclusivamente dal provider di pagamento, secondo i propri termini e condizioni. CardSwap non controlla tali tempistiche né i flussi di pagamento.
            </p>
            <p class="mb-4">
              <strong>4.2</strong> CardSwap non gestisce, detiene né controlla direttamente i fondi degli utenti e non conserva dati finanziari degli utenti.
            </p>
            <p class="mb-4">
              <strong>4.3 Commissioni:</strong>
            </p>
            <ul class="list-disc pl-6 mb-4">
              <li>al venditore viene applicata una commissione fissa pari al 6% sul prezzo di vendita di ciascuna transazione conclusa. La commissione è calcolata sul solo valore della carta oggetto della vendita (e non sul costo di spedizione) e viene trattenuta automaticamente al momento del pagamento;</li>
              <li>all'acquirente viene applicata una commissione dell'1,5% sul solo valore della carta oggetto della vendita e chiaramente indicata prima della conferma dell'ordine.</li>
            </ul>
            <p class="mb-4">
              <strong>4.4</strong> Le commissioni e i costi di servizio coprono la gestione della Piattaforma e il supporto tecnico. Salvo diversa disposizione o obblighi di legge, tali importi non sono rimborsabili.
            </p>
            <p class="mb-4">
              <strong>4.5</strong> I pagamenti possono essere effettuati in diverse valute, secondo quanto supportato dal provider di pagamento. Eventuali commissioni di conversione valutaria o costi aggiuntivi applicati dal provider di pagamento sono a carico dell'utente.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">5. Spedizioni</h2>
            <p class="mb-4">
              <strong>5.1</strong> Il venditore è responsabile della corretta spedizione del prodotto, nel rispetto dei tempi indicati e con un imballaggio adeguato.
            </p>
            <p class="mb-4">
              <strong>5.2</strong> La spedizione deve essere eseguita tramite la modalità richiesta e pagata dall'acquirente.
            </p>
            <p class="mb-4">
              <strong>5.3</strong> Le modalità e i costi di spedizione sono stabiliti autonomamente dal venditore per ciascuna inserzione. Il venditore è responsabile dell'organizzazione, gestione e corretta esecuzione della spedizione.
            </p>
            <p class="mb-4">
              <strong>5.4</strong> CardSwap non è responsabile per ritardi, danni o errori imputabili ai servizi di spedizione.
            </p>
            <p class="mb-4">
              <strong>5.5</strong> In caso di spedizioni internazionali, eventuali dazi doganali, imposte o costi di importazione sono a carico dell'acquirente, salvo diversa indicazione.
            </p>
            <p class="mb-4">
              <strong>5.6</strong> Eventuali controversie relative alla spedizione e alla consegna del prodotto sono tra venditore e acquirente. Il venditore è responsabile della consegna del prodotto all'acquirente, salvo diverso accordo tra le parti.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">6. Comportamento degli Utenti</h2>
            <p class="mb-4">
              <strong>6.1</strong> Gli utenti si impegnano a utilizzare la Piattaforma in modo lecito e conforme ai presenti Termini.
            </p>
            <p class="mb-4">
              <strong>6.2</strong> È vietato vendere carte false, contraffatte, rubate o non conformi alla descrizione.
            </p>
            <p class="mb-4">
              <strong>6.3</strong> È vietato pubblicare contenuti offensivi, diffamatori, ingannevoli o contrari alla legge.
            </p>
            <p class="mb-4">
              <strong>6.4</strong> CardSwap può rimuovere contenuti o sospendere utenti che violino tali regole, anche senza preavviso.
            </p>
            <p class="mb-4">
              <strong>6.5</strong> L'utilizzo della piattaforma avviene ad esclusivo rischio dell'utente.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">7. Responsabilità e Limitazioni</h2>
            <p class="mb-4">
              <strong>7.1</strong> La Piattaforma è fornita "così com'è", senza garanzia di disponibilità continua o assenza di errori.
            </p>
            <p class="mb-4">
              <strong>7.2</strong> Il Titolare non risponde di malfunzionamenti, interruzioni di rete, guasti tecnici o problemi relativi ai servizi di pagamento e spedizione gestiti da terzi.
            </p>
            <p class="mb-4">
              <strong>7.3</strong> CardSwap non risponde di danni indiretti, perdite economiche o mancati guadagni derivanti dall'uso della Piattaforma.
            </p>
            <p class="mb-4">
              <strong>7.4</strong> In ogni caso, la responsabilità massima del Titolare, per qualsiasi titolo, non potrà superare il maggiore tra: il doppio della commissione trattenuta sulla transazione oggetto di contestazione, oppure 50,00 euro.
            </p>
            <p class="mb-4">
              <strong>7.5</strong> CardSwap si riserva il diritto di limitare o impedire l'accesso alla Piattaforma da determinati Paesi o territori, in conformità alla normativa applicabile o per motivi di sicurezza.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">8. Privacy e Protezione dei Dati</h2>
            <p class="mb-4">
              <strong>8.1</strong> Il trattamento dei dati personali avviene in conformità al Regolamento (UE) 2016/679 (GDPR) e alla normativa italiana.
            </p>
            <p class="mb-4">
              <strong>8.2</strong> Per informazioni dettagliate si rimanda alla <router-link to="/privacy-policy" class="text-secondary hover:underline">Privacy Policy</router-link> e alla <router-link to="/cookie-policy" class="text-secondary hover:underline">Cookie Policy</router-link>.
            </p>
            <p class="mb-4">
              <strong>8.3</strong> I dati personali degli utenti residenti al di fuori dell'Unione Europea saranno trattati nel rispetto delle normative applicabili e, ove necessario, trasferiti secondo adeguate garanzie.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">9. Legge Applicabile e Foro Competente</h2>
            <p class="mb-4">
              <strong>9.1</strong> I presenti Termini sono regolati dalla legge italiana. Per gli utenti consumatori residenti al di fuori dell'Unione Europea, eventuali diritti inderogabili previsti dalla normativa locale restano comunque applicabili.
            </p>
            <p class="mb-4">
              <strong>9.2</strong> Per gli utenti consumatori, è competente il foro del luogo di residenza o domicilio dell'utente.
            </p>
            <p class="mb-4">
              <strong>9.3</strong> Per gli utenti professionisti, è competente in via esclusiva il Foro di Bologna.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">10. Risoluzione delle Controversie Online</h2>
            <p class="mb-4">
              <strong>10.1</strong> Ai sensi del Regolamento UE n. 524/2013, l'utente consumatore può ricorrere alla piattaforma europea per la risoluzione delle controversie online (ODR) disponibile all'indirizzo: <a href="https://ec.europa.eu/consumers/odr/" target="_blank" rel="noopener noreferrer" class="text-secondary hover:underline">https://ec.europa.eu/consumers/odr/</a>
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">11. Modifiche ai Termini</h2>
            <p class="mb-4">
              <strong>11.1</strong> CardSwap potrà modificare i presenti Termini in qualsiasi momento.
            </p>
            <p class="mb-4">
              <strong>11.2</strong> Le modifiche entreranno in vigore al momento della pubblicazione sul sito.
            </p>
            <p class="mb-4">
              <strong>11.3</strong> L'utilizzo continuato della Piattaforma dopo la pubblicazione implica accettazione delle nuove condizioni.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">12. Clausole da approvare espressamente ai sensi degli artt. 1341 e 1342 c.c.</h2>
            <p class="mb-4">
              L'utente dichiara di aver letto e di approvare specificamente le seguenti clausole:
            </p>
            <ul class="list-disc pl-6 mb-4">
              <li>art. 2 (Ruolo di CardSwap e limitazione di responsabilità)</li>
              <li>art. 5 (Spedizioni e trasferimento del rischio)</li>
              <li>art. 7 (Limitazioni di responsabilità)</li>
              <li>art. 9 (Foro competente)</li>
            </ul>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">13. Segnalazione contenuti e comportamenti illeciti</h2>
            <p class="mb-4">
              Gli utenti possono segnalare contenuti o comportamenti ritenuti illegali o contrari ai presenti Termini scrivendo a <a href="mailto:info@cardswaptcg.com" class="text-secondary hover:underline">info@cardswaptcg.com</a>. CardSwap si riserva il diritto di valutare le segnalazioni e di adottare le misure ritenute opportune, inclusa la rimozione dei contenuti e la sospensione o chiusura degli account coinvolti.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">14. Uso di marchi e diritti di proprietà intellettuale</h2>
            <p class="mb-4">
              I marchi, loghi e nomi commerciali eventualmente presenti sulla Piattaforma (es. Topps, Disney, Spongebob ecc.) appartengono ai rispettivi titolari dei diritti.
            </p>
            <p class="mb-4">
              CardSwap non è affiliata, sponsorizzata o autorizzata da tali marchi, salvo ove espressamente indicato.
            </p>
            <p class="mb-4">
              Gli utenti sono responsabili dei contenuti pubblicati e garantiscono che la vendita dei prodotti non viola diritti di proprietà intellettuale di terzi.
            </p>
            <p class="mb-4">
              CardSwap si riserva il diritto di rimuovere contenuti che violino diritti di proprietà intellettuale su segnalazione dei titolari dei diritti o di terzi.
            </p>
            <p class="mb-4">
              I marchi citati appartengono ai rispettivi proprietari e sono utilizzati esclusivamente a fini descrittivi.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">Contatti</h2>
            <div class="bg-gray-50 p-6 rounded-lg">
              <p>Contatti: <a href="mailto:info@cardswaptcg.com" class="text-secondary hover:underline">info@cardswaptcg.com</a></p>
            </div>
          </section>
        </div>
      </div>
    </div>
    
    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'

const lastUpdated = ref('02 marzo 2026')
</script>
