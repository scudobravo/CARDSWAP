<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <!-- Content Section -->
    <div class="bg-white py-16 sm:py-24">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Title -->
        <div class="text-center mb-12">
          <h1 class="text-4xl font-futura-bold text-primary sm:text-5xl mb-4">Cookie Policy</h1>
          <p class="text-lg text-gray-600 font-gill-sans">Ultimo aggiornamento: {{ lastUpdated }}</p>
        </div>

        <!-- Content -->
        <div class="prose prose-lg max-w-none font-gill-sans text-gray-700">
          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">1. Cosa sono i Cookie</h2>
            <p class="mb-4">
              I cookie sono piccoli file di testo che vengono memorizzati sul tuo dispositivo quando visiti un sito web. Vengono utilizzati per migliorare la tua esperienza di navigazione, ricordare le tue preferenze e fornire funzionalità personalizzate.
            </p>
            <p class="mb-4">
              Cardswap S.r.l. utilizza cookie per garantire il corretto funzionamento del sito e per offrirti la migliore esperienza possibile.
            </p>
            <p>
              I cookie di prestazione, funzionali e di marketing vengono utilizzati solo previo consenso dell'utente, che può essere espresso o modificato tramite il banner cookie.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">2. Tipi di Cookie Utilizzati</h2>
            
            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">2.1 Cookie Essenziali (Necessari)</h3>
            <p class="mb-4">
              Questi cookie sono necessari per il funzionamento del sito e non possono essere disabilitati. Includono cookie per:
            </p>
            <ul class="list-disc pl-6 mb-6">
              <li>Mantenere la sessione di login</li>
              <li>Ricordare le preferenze di sicurezza</li>
              <li>Gestire il carrello degli acquisti</li>
              <li>Garantire la sicurezza delle transazioni</li>
              <li>Prevenire attacchi CSRF</li>
            </ul>

            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">2.2 Cookie di Prestazioni</h3>
            <p class="mb-4">
              Questi cookie ci aiutano a capire come gli utenti interagiscono con il sito, raccogliendo informazioni in forma anonima:
            </p>
            <ul class="list-disc pl-6 mb-6">
              <li>Pagine più visitate</li>
              <li>Tempo di permanenza sul sito</li>
              <li>Errori riscontrati</li>
              <li>Percorsi di navigazione</li>
            </ul>

            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">2.3 Cookie Funzionali</h3>
            <p class="mb-4">
              Questi cookie permettono al sito di ricordare le tue scelte e fornire funzionalità migliorate:
            </p>
            <ul class="list-disc pl-6 mb-4">
              <li>Preferenze di lingua</li>
              <li>Impostazioni di visualizzazione</li>
              <li>Filtri di ricerca salvati</li>
              <li>Preferenze di notifica</li>
            </ul>
            <p class="mb-6">
              Alcuni cookie funzionali possono richiedere il consenso dell'utente, in base alle funzionalità attivate.
            </p>

            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">2.4 Cookie di Marketing</h3>
            <p class="mb-4">
              Questi cookie vengono utilizzati per mostrare annunci pertinenti e misurare l'efficacia delle campagne:
            </p>
            <ul class="list-disc pl-6 mb-6">
              <li>Targeting pubblicitario</li>
              <li>Misurazione delle conversioni</li>
              <li>Retargeting</li>
              <li>Analisi del comportamento di acquisto</li>
            </ul>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">3. Cookie di Terze Parti</h2>
            <p class="mb-4">
              L'elenco aggiornato dei cookie utilizzati, inclusi quelli di terze parti, è disponibile tramite il banner di gestione del consenso (Cookiebot).
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">4. Durata dei Cookie</h2>
            <p class="mb-4">
              I cookie hanno diverse durate di vita:
            </p>
            <ul class="list-disc pl-6 mb-4">
              <li><strong>Cookie di sessione:</strong> Vengono eliminati quando chiudi il browser</li>
              <li><strong>Cookie persistenti:</strong> Rimangono sul dispositivo per un periodo specifico</li>
              <li><strong>Cookie di prima parte:</strong> Impostati direttamente da Cardswap S.r.l.</li>
              <li><strong>Cookie di terze parti:</strong> Impostati da servizi esterni</li>
            </ul>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">5. Gestione dei Cookie</h2>
            <p class="mb-4">
              Puoi gestire le tue preferenze sui cookie in diversi modi:
            </p>

            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">5.1 Impostazioni del Browser</h3>
            <p class="mb-4">
              La maggior parte dei browser ti permette di:
            </p>
            <ul class="list-disc pl-6 mb-6">
              <li>Visualizzare i cookie memorizzati</li>
              <li>Eliminare cookie singoli o tutti</li>
              <li>Bloccare cookie di terze parti</li>
              <li>Ricevere notifiche quando vengono impostati nuovi cookie</li>
            </ul>

            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">5.2 Banner di Consenso</h3>
            <p class="mb-6">
              Al primo accesso al sito, ti mostreremo un banner per gestire le tue preferenze sui cookie. L'utente può modificare o revocare il proprio consenso in qualsiasi momento tramite le impostazioni del banner cookie.
            </p>

            <h3 class="text-xl font-gill-sans-semibold text-primary mb-3">5.3 Link Utili per la Gestione</h3>
            <div class="bg-gray-50 p-6 rounded-lg">
              <p class="mb-2"><strong>Chrome:</strong> Impostazioni > Privacy e sicurezza > Cookie e altri dati dei siti</p>
              <p class="mb-2"><strong>Firefox:</strong> Opzioni > Privacy e sicurezza > Cookie e dati dei siti</p>
              <p class="mb-2"><strong>Safari:</strong> Preferenze > Privacy > Gestisci dati sito web</p>
              <p class="mb-2"><strong>Edge:</strong> Impostazioni > Cookie e autorizzazioni sito</p>
            </div>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">6. Impatto della Disabilitazione</h2>
            <p class="mb-4">
              Disabilitare alcuni cookie potrebbe influire sulla funzionalità del sito:
            </p>
            <ul class="list-disc pl-6 mb-4">
              <li>Potresti non riuscire ad accedere ad alcune funzionalità</li>
              <li>Le tue preferenze potrebbero non essere salvate</li>
              <li>Il carrello potrebbe non funzionare correttamente</li>
              <li>Alcune pagine potrebbero caricarsi più lentamente</li>
            </ul>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">7. Cookie e Minori</h2>
            <p class="mb-4">
              Il nostro servizio non è destinato a minori di 16 anni. Non raccogliamo consapevolmente informazioni personali da minori di 16 anni attraverso i cookie.
            </p>
            <p>
              Se sei un genitore e ritieni che tuo figlio abbia fornito informazioni personali, contattaci immediatamente.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">8. Aggiornamenti a Questa Policy</h2>
            <p class="mb-4">
              Questa Cookie Policy può essere aggiornata periodicamente per riflettere cambiamenti nelle nostre pratiche o per altri motivi operativi, legali o normativi.
            </p>
            <p>
              Ti consigliamo di rivedere questa pagina regolarmente per rimanere informato sui nostri utilizzi dei cookie.
            </p>
          </section>

          <section class="mb-8">
            <h2 class="text-2xl font-futura-bold text-primary mb-4">9. Contatti</h2>
            <p class="mb-4">
              Per domande sui cookie o per esercitare i tuoi diritti:
            </p>
            <div class="bg-gray-50 p-6 rounded-lg">
              <p class="font-gill-sans-semibold text-primary mb-2">Cardswap S.r.l.</p>
              <p class="mb-1">Via Umberto Eco 2, 40012 Calderara di Reno (BO)</p>
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

const lastUpdated = ref('27 febbraio 2026')
</script>
