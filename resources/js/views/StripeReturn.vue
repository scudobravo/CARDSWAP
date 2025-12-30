<template>
  <div class="min-h-screen bg-gray-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
        <!-- Loading State -->
        <div v-if="loading" class="text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"></div>
          <h3 class="text-lg font-futura-bold text-gray-900 mb-2">Verifica configurazione...</h3>
          <p class="text-sm text-gray-600">Stiamo verificando lo stato del tuo account Stripe Connect.</p>
        </div>

        <!-- Success State -->
        <div v-else-if="success" class="text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 mb-4">
            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h3 class="text-lg font-futura-bold text-gray-900 mb-2">Configurazione completata!</h3>
          <p class="text-sm text-gray-600 mb-6">
            Il tuo account Stripe Connect è stato configurato con successo. Ora puoi ricevere pagamenti.
          </p>
          <div v-if="accountStatus" class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
            <div class="space-y-2 text-sm">
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Ricezione pagamenti:</span>
                <span :class="accountStatus.charges_enabled ? 'text-green-600 font-gill-sans-semibold' : 'text-red-600'">
                  {{ accountStatus.charges_enabled ? 'Abilitata' : 'Non abilitata' }}
                </span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-600">Bonifici:</span>
                <span :class="accountStatus.payouts_enabled ? 'text-green-600 font-gill-sans-semibold' : 'text-red-600'">
                  {{ accountStatus.payouts_enabled ? 'Abilitati' : 'Non abilitati' }}
                </span>
              </div>
            </div>
          </div>
          <div class="space-y-3">
            <button
              @click="goToDashboard"
              class="w-full flex justify-center rounded-md bg-primary px-6 py-3 text-base font-gill-sans-semibold text-white shadow-xs hover:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
            >
              Vai alla Dashboard
            </button>
            <button
              @click="goToStripeDashboard"
              class="w-full flex justify-center rounded-md bg-white px-6 py-3 text-base font-gill-sans-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
            >
              Apri Dashboard Stripe
            </button>
          </div>
        </div>

        <!-- Incomplete State -->
        <div v-else-if="incomplete" class="text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 mb-4">
            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <h3 class="text-lg font-futura-bold text-gray-900 mb-2">Configurazione incompleta</h3>
          <p class="text-sm text-gray-600 mb-6">
            Sembra che tu non abbia completato la configurazione su Stripe. Completa tutti i passaggi per ricevere pagamenti.
          </p>
          <button
            @click="continueOnboarding"
            :disabled="loadingOnboarding"
            class="w-full flex justify-center rounded-md bg-primary px-6 py-3 text-base font-gill-sans-semibold text-white shadow-xs hover:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <div v-if="loadingOnboarding" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
            {{ loadingOnboarding ? 'Caricamento...' : 'Continua Configurazione' }}
          </button>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center">
          <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </div>
          <h3 class="text-lg font-futura-bold text-gray-900 mb-2">Errore</h3>
          <p class="text-sm text-gray-600 mb-6">{{ error }}</p>
          <button
            @click="goToDashboard"
            class="w-full flex justify-center rounded-md bg-primary px-6 py-3 text-base font-gill-sans-semibold text-white shadow-xs hover:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary"
          >
            Torna alla Dashboard
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const loading = ref(true)
const success = ref(false)
const incomplete = ref(false)
const error = ref('')
const accountStatus = ref(null)
const loadingOnboarding = ref(false)

onMounted(async () => {
  await checkAccountStatus()
})

const checkAccountStatus = async () => {
  try {
    const token = localStorage.getItem('token')
    if (!token) {
      error.value = 'Non sei autenticato. Effettua il login.'
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
      throw new Error('Errore nel controllo dello stato dell\'account')
    }

    const data = await response.json()
    accountStatus.value = {
      charges_enabled: data.charges_enabled,
      payouts_enabled: data.payouts_enabled,
      details_submitted: data.details_submitted
    }

    // Verifica se la configurazione è completa
    if (data.charges_enabled && data.payouts_enabled) {
      success.value = true
    } else {
      incomplete.value = true
    }
  } catch (err) {
    console.error('Errore nel controllo stato account:', err)
    error.value = err.message || 'Errore nel controllo dello stato dell\'account'
  } finally {
    loading.value = false
  }
}

const continueOnboarding = async () => {
  loadingOnboarding.value = true
  try {
    const token = localStorage.getItem('token')
    const response = await fetch('/api/stripe/account/onboarding', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.message || 'Errore nella generazione del link')
    }

    const data = await response.json()
    if (data.onboarding_url) {
      window.location.href = data.onboarding_url
    } else {
      throw new Error('Link di onboarding non disponibile')
    }
  } catch (err) {
    console.error('Errore nel continuare onboarding:', err)
    error.value = err.message || 'Errore nel continuare la configurazione'
    incomplete.value = false
  } finally {
    loadingOnboarding.value = false
  }
}

const goToDashboard = () => {
  router.push('/dashboard')
}

const goToStripeDashboard = async () => {
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

    if (response.ok) {
      const data = await response.json()
      if (data.login_url) {
        window.open(data.login_url, '_blank')
      }
    }
  } catch (err) {
    console.error('Errore nell\'apertura dashboard Stripe:', err)
  }
}
</script>

