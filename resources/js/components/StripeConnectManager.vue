<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-futura-bold text-gray-900">Configurazione Pagamenti</h3>
        <p class="text-sm text-gray-600 mt-1">
          Configura il tuo account Stripe Connect per ricevere i pagamenti delle tue vendite
        </p>
      </div>
      <div v-if="accountStatus?.charges_enabled && accountStatus?.payouts_enabled" class="flex items-center text-green-600">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span class="text-sm font-gill-sans-semibold">Configurato</span>
      </div>
    </div>

    <!-- Stato Account -->
    <div v-if="loading" class="text-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
      <p class="text-sm text-gray-600">Caricamento stato account...</p>
    </div>

    <div v-else-if="accountStatus" class="space-y-4">
      <!-- Info Box -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3">
            <h4 class="text-sm font-gill-sans-semibold text-blue-800">Stripe Connect</h4>
            <p class="mt-1 text-sm text-blue-700">
              Stripe Connect ti permette di ricevere pagamenti direttamente sul tuo conto bancario. 
              I fondi vengono trasferiti automaticamente dopo ogni vendita.
            </p>
          </div>
        </div>
      </div>

      <!-- Stato Configurazione -->
      <div class="bg-gray-50 rounded-lg p-4">
        <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-3">Stato Account</h4>
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Account creato:</span>
            <span class="text-sm font-gill-sans text-gray-900">
              {{ accountStatus.has_account ? 'Sì' : 'No' }}
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Ricezione pagamenti:</span>
            <span :class="[
              'text-sm font-gill-sans-semibold',
              accountStatus.charges_enabled ? 'text-green-600' : 'text-red-600'
            ]">
              {{ accountStatus.charges_enabled ? 'Abilitata' : 'Non abilitata' }}
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Bonifici:</span>
            <span :class="[
              'text-sm font-gill-sans-semibold',
              accountStatus.payouts_enabled ? 'text-green-600' : 'text-red-600'
            ]">
              {{ accountStatus.payouts_enabled ? 'Abilitati' : 'Non abilitati' }}
            </span>
          </div>
          <div class="flex items-center justify-between">
            <span class="text-sm text-gray-600">Dettagli inviati:</span>
            <span :class="[
              'text-sm font-gill-sans-semibold',
              accountStatus.details_submitted ? 'text-green-600' : 'text-yellow-600'
            ]">
              {{ accountStatus.details_submitted ? 'Completati' : 'In attesa' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Azioni -->
      <div class="space-y-3">
        <!-- Account non configurato -->
        <div v-if="!accountStatus.has_account || (!accountStatus.charges_enabled || !accountStatus.payouts_enabled)">
          <button
            @click="setupStripeConnect"
            :disabled="loadingAction"
            class="w-full flex justify-center items-center rounded-md bg-primary px-6 py-3 text-base font-gill-sans-semibold text-white shadow-xs hover:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="!loadingAction" class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
            </svg>
            <div v-else class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
            {{ loadingAction ? 'Caricamento...' : (accountStatus.has_account ? 'Completa Configurazione' : 'Configura Stripe Connect') }}
          </button>
        </div>

        <!-- Account configurato -->
        <div v-else class="space-y-3">
          <button
            @click="openStripeDashboard"
            :disabled="loadingAction"
            class="w-full flex justify-center items-center rounded-md bg-white px-6 py-3 text-base font-gill-sans-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
            </svg>
            {{ loadingAction ? 'Caricamento...' : 'Apri Dashboard Stripe' }}
          </button>
          <button
            @click="refreshStatus"
            :disabled="loadingAction"
            class="w-full flex justify-center items-center rounded-md bg-gray-100 px-6 py-3 text-base font-gill-sans-semibold text-gray-700 hover:bg-gray-200 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Aggiorna Stato
          </button>
        </div>
      </div>

      <!-- Messaggi di errore -->
      <div v-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
          </div>
          <div class="ml-3 flex-1">
            <h4 class="text-sm font-gill-sans-semibold text-red-800 mb-1">Errore</h4>
            <p class="text-sm text-red-700 mb-3">{{ error }}</p>
            <!-- Link per abilitare Stripe Connect se l'errore è relativo a Connect non abilitato -->
            <div v-if="error.includes('Stripe Connect') || error.includes('signed up for Connect') || error.includes('Marketplace')" class="mt-3 space-y-2">
              <a 
                href="https://dashboard.stripe.com/connect/overview" 
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md shadow-sm text-sm font-gill-sans-semibold text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
              >
                <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                Configura Stripe Connect Marketplace
              </a>
              <a 
                href="https://docs.stripe.com/connect/marketplace" 
                target="_blank"
                rel="noopener noreferrer"
                class="block text-xs text-red-600 hover:text-red-800 underline"
              >
                📖 Leggi la guida completa per configurare il Marketplace
              </a>
              <p class="text-xs text-red-600 mt-2">
                Dopo aver configurato Stripe Connect Marketplace, ricarica questa pagina e riprova.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const loading = ref(true)
const loadingAction = ref(false)
const accountStatus = ref(null)
const error = ref('')

onMounted(() => {
  loadAccountStatus()
})

const loadAccountStatus = async () => {
  loading.value = true
  error.value = ''
  
  try {
    const token = localStorage.getItem('token')
    if (!token) {
      error.value = 'Non sei autenticato'
      loading.value = false
      return
    }

    const response = await fetch('/api/stripe/account/status', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      if (response.status === 404) {
        // Account non ancora creato
        accountStatus.value = {
          has_account: false,
          charges_enabled: false,
          payouts_enabled: false,
          details_submitted: false
        }
      } else {
        throw new Error('Errore nel caricamento dello stato')
      }
    } else {
      const data = await response.json()
      accountStatus.value = {
        has_account: true,
        charges_enabled: data.charges_enabled || false,
        payouts_enabled: data.payouts_enabled || false,
        details_submitted: data.details_submitted || false
      }
    }
  } catch (err) {
    console.error('Errore nel caricamento stato account:', err)
    error.value = err.message || 'Errore nel caricamento dello stato dell\'account'
  } finally {
    loading.value = false
  }
}

const setupStripeConnect = async () => {
  loadingAction.value = true
  error.value = ''
  
  try {
    const token = localStorage.getItem('token')
    if (!token) {
      error.value = 'Non sei autenticato'
      loadingAction.value = false
      return
    }

    // Se l'account non esiste, crealo
    if (!accountStatus.value?.has_account) {
      const createResponse = await fetch('/api/stripe/account/create', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })

      if (!createResponse.ok) {
        const errorData = await createResponse.json()
        throw new Error(errorData.message || 'Errore nella creazione dell\'account')
      }
    }

    // Genera il link di onboarding
    const onboardingResponse = await fetch('/api/stripe/account/onboarding', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    if (!onboardingResponse.ok) {
      const errorData = await onboardingResponse.json()
      throw new Error(errorData.message || 'Errore nella generazione del link')
    }

    const data = await onboardingResponse.json()
    if (data.onboarding_url) {
      window.location.href = data.onboarding_url
    } else {
      throw new Error('Link di onboarding non disponibile')
    }
  } catch (err) {
    console.error('Errore nella configurazione Stripe Connect:', err)
    error.value = err.message || 'Errore nella configurazione di Stripe Connect'
    loadingAction.value = false
  }
}

const openStripeDashboard = async () => {
  loadingAction.value = true
  error.value = ''
  
  try {
    const token = localStorage.getItem('token')
    const response = await fetch('/api/stripe/account/login', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.message || 'Errore nell\'apertura della dashboard')
    }

    const data = await response.json()
    if (data.login_url) {
      window.open(data.login_url, '_blank')
    }
  } catch (err) {
    console.error('Errore nell\'apertura dashboard Stripe:', err)
    error.value = err.message || 'Errore nell\'apertura della dashboard Stripe'
  } finally {
    loadingAction.value = false
  }
}

const refreshStatus = async () => {
  await loadAccountStatus()
}
</script>

