<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h3 class="text-lg font-futura-bold text-gray-900">Verifica Identità</h3>
        <p class="text-sm text-gray-600 mt-1">
          Completa la verifica per vendere e acquistare sulla piattaforma
        </p>
      </div>
      <div v-if="kycStatus.is_kyc_complete" class="flex items-center text-green-600">
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <span class="text-sm font-gill-sans-semibold">Verificato</span>
      </div>
    </div>

    <!-- Stato KYC -->
    <div class="mb-6">
      <div class="flex items-center space-x-4">
        <div class="flex items-center">
          <div :class="[
            'w-3 h-3 rounded-full mr-2',
            kycStatus.is_kyc_complete ? 'bg-green-500' : 'bg-yellow-500'
          ]"></div>
          <span class="text-sm font-gill-sans text-gray-700">
            {{ getStatusText() }}
          </span>
        </div>
      </div>
    </div>

    <!-- Informazioni Account -->
    <div v-if="kycStatus.account_type" class="mb-6 p-4 bg-gray-50 rounded-lg">
      <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-2">
        {{ kycStatus.account_type === 'company' ? 'Informazioni Azienda' : 'Informazioni Personali' }}
      </h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
        <div v-if="kycStatus.account_type === 'company'">
          <span class="text-gray-600">Ragione Sociale:</span>
          <span class="font-gill-sans text-gray-900">{{ kycStatus.business_name || 'Non specificata' }}</span>
        </div>
        <div v-if="kycStatus.account_type === 'company'">
          <span class="text-gray-600">Partita IVA:</span>
          <span class="font-gill-sans text-gray-900">{{ kycStatus.vat_number || 'Non specificata' }}</span>
        </div>
      </div>
    </div>

    <!-- Azioni -->
    <div v-if="!kycStatus.is_kyc_complete" class="space-y-4">
      <!-- Pulsante Avvia KYC -->
      <button
        @click="startKyc"
        :disabled="loading"
        class="w-full bg-primary text-white px-4 py-2 rounded-md hover:bg-secondary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed font-gill-sans-semibold"
      >
        <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ loading ? 'Avvio in corso...' : 'Avvia Verifica Identità' }}
      </button>

      <!-- Link Verifica Stripe -->
      <div v-if="verificationUrl" class="text-center">
        <a
          :href="verificationUrl"
          target="_blank"
          class="inline-flex items-center px-4 py-2 border border-primary text-primary rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 font-gill-sans-semibold"
        >
          <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
          </svg>
          Completa Verifica su Stripe
        </a>
      </div>

      <!-- Pulsante Controlla Stato -->
      <button
        @click="checkStatus"
        :disabled="loading"
        class="w-full bg-gray-100 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed font-gill-sans"
      >
        Controlla Stato Verifica
      </button>
    </div>

    <!-- Messaggi di Errore -->
    <div v-if="errorMessage" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
      <div class="flex">
        <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <div>
          <h4 class="text-sm font-gill-sans-semibold text-red-800">Errore</h4>
          <p class="text-sm text-red-700 mt-1">{{ errorMessage }}</p>
        </div>
      </div>
    </div>

    <!-- Messaggi di Successo -->
    <div v-if="successMessage" class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
      <div class="flex">
        <svg class="w-5 h-5 text-green-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
        </svg>
        <div>
          <h4 class="text-sm font-gill-sans-semibold text-green-800">Successo</h4>
          <p class="text-sm text-green-700 mt-1">{{ successMessage }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'

const kycStatus = ref({
  kyc_status: 'not_submitted',
  stripe_identity_verified: false,
  account_type: 'private',
  business_name: null,
  vat_number: null,
  is_kyc_complete: false,
  requires_kyc: true
})

const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')
const verificationUrl = ref('')

// Carica stato KYC all'avvio
onMounted(() => {
  loadKycStatus()
})

const loadKycStatus = async () => {
  try {
    const response = await fetch('/api/kyc/status', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      kycStatus.value = data.data
    }
  } catch (error) {
    console.error('Errore nel caricamento stato KYC:', error)
  }
}

const startKyc = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch('/api/kyc/start', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      }
    })

    const data = await response.json()

    if (data.success) {
      verificationUrl.value = data.data.verification_url
      successMessage.value = 'Sessione di verifica creata. Clicca sul link per completare la verifica.'
    } else {
      errorMessage.value = data.message || 'Errore nella creazione della sessione di verifica'
    }
  } catch (error) {
    errorMessage.value = 'Errore di connessione'
  } finally {
    loading.value = false
  }
}

const checkStatus = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await fetch('/api/kyc/check-status', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })

    const data = await response.json()

    if (data.success) {
      kycStatus.value = { ...kycStatus.value, ...data.data }
      
      if (data.data.is_complete) {
        successMessage.value = 'Verifica identità completata con successo!'
        verificationUrl.value = ''
      } else {
        successMessage.value = 'Stato aggiornato. La verifica è ancora in corso.'
      }
    } else {
      errorMessage.value = data.message || 'Errore nel controllo dello stato'
    }
  } catch (error) {
    errorMessage.value = 'Errore di connessione'
  } finally {
    loading.value = false
  }
}

const getStatusText = () => {
  if (kycStatus.value.is_kyc_complete) {
    return 'Verifica identità completata'
  } else if (kycStatus.value.kyc_status === 'pending') {
    return 'Verifica in corso...'
  } else {
    return 'Verifica identità richiesta'
  }
}
</script>
