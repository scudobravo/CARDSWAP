<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <!-- Content Section -->
    <div class="bg-white py-16 sm:py-24">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Title -->
        <div class="text-center mb-12">
          <h1 class="text-4xl font-futura-bold text-primary sm:text-5xl mb-4">Informativa Privacy di CardSwap</h1>
          <p class="text-lg text-gray-600 font-gill-sans">Ultimo aggiornamento: {{ lastUpdated }}</p>
        </div>

        <!-- Intro -->
        <div class="prose prose-lg max-w-none font-gill-sans text-gray-700 mb-8">
          <p class="mb-4">
            La presente informativa è resa ai sensi del Regolamento (UE) 2016/679 (GDPR) e descrive le modalità di trattamento dei dati personali effettuato da <strong>Cardswap S.r.l.</strong>, con sede in <strong>Via Umberto Eco 2, 40012 Calderara di Reno (BO)</strong>, P.IVA / C.F. <strong>04320241203</strong>, e-mail: <a href="mailto:info@cardswaptcg.com" class="text-secondary hover:underline">info@cardswaptcg.com</a> (di seguito, il "Titolare"), tramite il sito web <a href="https://www.cardswaptcg.com" class="text-secondary hover:underline">www.cardswaptcg.com</a> (la "Piattaforma").
          </p>
        </div>

        <!-- Content -->
        <div class="prose prose-lg max-w-none font-gill-sans text-gray-700">
          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">1. Tipologie di dati trattati</h2>
            <p class="mb-4">
              Il Titolare raccoglie e tratta i seguenti dati personali ("Dati"):
            </p>
            
            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">a) Dati forniti volontariamente dall'Utente</h3>
            <ul class="list-disc pl-6 mb-4">
              <li>Dati identificativi: nome, cognome, data di nascita, indirizzo, e-mail, numero di telefono;</li>
              <li>Credenziali di accesso e identificazione;</li>
              <li>Dati fiscali e di pagamento (es. codice fiscale, IBAN o estremi di pagamento);</li>
              <li>Dati relativi agli annunci pubblicati o ai prodotti venduti/acquistati;</li>
              <li>Eventuali immagini o descrizioni caricate dall'utente.</li>
            </ul>

            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">b) Dati raccolti automaticamente</h3>
            <ul class="list-disc pl-6 mb-4">
              <li>Dati tecnici: indirizzo IP, tipo di browser, sistema operativo, informazioni sul dispositivo;</li>
              <li>Dati di navigazione: pagine visitate, tempi di permanenza, interazioni con la Piattaforma;</li>
              <li>Cookie e tecnologie simili (vedi punto 2).</li>
            </ul>

            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">c) Dati di localizzazione (facoltativi)</h3>
            <p class="mb-4">
              La Piattaforma può raccogliere dati di posizione dell'utente solo previo consenso esplicito, per migliorare l'esperienza d'uso (es. suggerire venditori nella stessa area). Il consenso può essere revocato in qualsiasi momento.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">2. Cookie e tecnologie simili</h2>
            <p class="mb-4">
              La Piattaforma utilizza cookie tecnici e di profilazione per garantire il corretto funzionamento del sito, migliorare i servizi e personalizzare l'esperienza d'uso. Per maggiori informazioni consultare la <router-link to="/cookie-policy" class="text-secondary hover:underline">Cookie Policy</router-link>.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">3. Finalità e basi giuridiche del trattamento</h2>
            <p class="mb-4">
              I Dati Personali sono trattati per le seguenti finalità e basi giuridiche:
            </p>
            
            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">a) Esecuzione del contratto o di misure precontrattuali</h3>
            <ul class="list-disc pl-6 mb-4">
              <li>Creazione e gestione dell'account utente;</li>
              <li>Gestione delle vendite e degli acquisti sulla Piattaforma;</li>
              <li>Gestione dei pagamenti tramite fornitore esterno autorizzato;</li>
              <li>Gestione delle comunicazioni tra utenti e con il Titolare;</li>
              <li>Assistenza clienti e risposte a richieste di supporto.</li>
            </ul>

            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">b) Adempimento di obblighi legali</h3>
            <ul class="list-disc pl-6 mb-4">
              <li>Obblighi fiscali, contabili e amministrativi;</li>
              <li>Gestione di eventuali segnalazioni o ordini delle autorità competenti.</li>
            </ul>

            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">c) Legittimo interesse del Titolare</h3>
            <ul class="list-disc pl-6 mb-4">
              <li>Miglioramento e manutenzione tecnica della Piattaforma;</li>
              <li>Sicurezza, prevenzione frodi e tutela del patrimonio informatico;</li>
              <li>Analisi statistiche su dati aggregati e anonimi;</li>
              <li>Comunicazioni promozionali via e-mail relative a servizi analoghi a quelli già acquistati (soft marketing).</li>
            </ul>

            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">d) Consenso dell'Interessato</h3>
            <ul class="list-disc pl-6 mb-4">
              <li>Invio di comunicazioni commerciali personalizzate e promozioni;</li>
              <li>Attività di marketing e profilazione;</li>
              <li>Partecipazione a sondaggi, concorsi o iniziative promozionali.</li>
            </ul>
            <p class="mb-4">
              Il consenso può essere revocato in qualsiasi momento scrivendo a <a href="mailto:info@cardswaptcg.com" class="text-secondary hover:underline">info@cardswaptcg.com</a>.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">4. Modalità del trattamento e destinatari dei dati</h2>
            <p class="mb-4">
              I Dati sono trattati con strumenti elettronici e modalità organizzative adeguate a garantirne la sicurezza e la riservatezza. I Dati possono essere trattati da:
            </p>
            <ul class="list-disc pl-6 mb-4">
              <li>personale interno del Titolare debitamente autorizzato e formato;</li>
              <li>fornitori di servizi esterni che agiscono come responsabili del trattamento (es. servizi di pagamento, hosting, logistica, consulenti tecnici, società IT, servizi di assistenza clienti);</li>
              <li>soggetti terzi autonomi titolari, nei limiti delle finalità connesse all'erogazione dei servizi (es. corrieri, istituti bancari, enti pubblici, autorità giudiziarie, quando previsto dalla legge).</li>
            </ul>
            <p class="mb-4">
              Tutti i soggetti designati garantiscono adeguati livelli di protezione dei dati ai sensi del GDPR. I Dati non saranno diffusi pubblicamente.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">5. Luogo del trattamento</h2>
            <p class="mb-4">
              Il trattamento dei Dati avviene all'interno dello Spazio Economico Europeo (SEE). Qualora fosse necessario trasferire Dati verso paesi extra SEE, il Titolare assicurerà che il trasferimento avvenga nel rispetto delle clausole contrattuali standard approvate dalla Commissione Europea.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">6. Periodo di conservazione</h2>
            <p class="mb-4">
              I Dati Personali vengono conservati per il tempo necessario a conseguire le finalità per cui sono stati raccolti, in particolare:
            </p>
            <ul class="list-disc pl-6 mb-4">
              <li>per l'esecuzione del contratto: per tutta la durata del rapporto contrattuale e fino a 10 anni dopo la cessazione, in conformità ai termini di legge;</li>
              <li>per obblighi legali o fiscali: per il periodo previsto dalla normativa applicabile;</li>
              <li>per attività di marketing: fino alla revoca del consenso;</li>
              <li>per finalità di tutela in giudizio: fino alla definizione di eventuali controversie.</li>
            </ul>
            <p class="mb-4">
              Trascorsi i termini di conservazione, i Dati saranno cancellati o resi anonimi in modo irreversibile.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">7. Diritti dell'Interessato</h2>
            <p class="mb-4">
              L'utente, in qualità di Interessato, può esercitare in qualsiasi momento i seguenti diritti (artt. 15–22 GDPR):
            </p>
            <ul class="list-disc pl-6 mb-4">
              <li>ottenere conferma dell'esistenza dei propri dati e accedere al loro contenuto;</li>
              <li>chiedere la rettifica o l'aggiornamento dei dati inesatti;</li>
              <li>ottenere la cancellazione dei dati ("diritto all'oblio");</li>
              <li>limitare o opporsi al trattamento;</li>
              <li>richiedere la portabilità dei dati a un altro titolare;</li>
              <li>revocare il consenso prestato, senza pregiudicare la liceità del trattamento già effettuato;</li>
              <li>proporre reclamo al Garante per la Protezione dei Dati Personali (<a href="https://www.garanteprivacy.it" target="_blank" class="text-secondary hover:underline">www.garanteprivacy.it</a>).</li>
            </ul>
            <p class="mb-4">
              Le richieste possono essere inviate a: <a href="mailto:info@cardswaptcg.com" class="text-secondary hover:underline">info@cardswaptcg.com</a>
            </p>
            <p class="mb-4">
              Il Titolare risponderà entro 30 giorni dalla ricezione.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">8. Aggiornamenti della presente Informativa</h2>
            <p class="mb-4">
              Il Titolare può modificare la presente Informativa in qualsiasi momento. Le modifiche saranno pubblicate sulla Piattaforma con indicazione della data di aggiornamento. L'uso continuato della Piattaforma dopo la pubblicazione delle modifiche implica l'accettazione della nuova versione dell'Informativa.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">Titolare del Trattamento</h2>
            <div class="bg-gray-50 p-6 rounded-lg">
              <p class="font-gill-sans-semibold text-primary mb-2">Cardswap S.r.l.</p>
              <p class="mb-1">Via Umberto Eco 2</p>
              <p class="mb-1">40012 Calderara di Reno (BO)</p>
              <p class="mb-1">P.IVA / C.F.: 04320241203</p>
              <p>E-mail: <a href="mailto:info@cardswaptcg.com" class="text-secondary hover:underline">info@cardswaptcg.com</a></p>
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

const lastUpdated = ref('7 ottobre 2025')
</script>
