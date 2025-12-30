<template>
  <div>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Verifica Identità (KYC)
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Completa la verifica per iniziare a comprare o vendere carte
      </p>
    </div>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto">
        <!-- Progress Steps -->
        <div class="mb-8">
          <nav aria-label="Progress">
            <ol class="flex items-center justify-center space-x-8">
              <li v-for="(step, index) in steps" :key="step.id" class="flex items-center">
                <div class="flex items-center">
                  <div :class="[
                    'flex items-center justify-center w-8 h-8 rounded-full border-2',
                    step.status === 'completed' ? 'bg-primary border-primary text-white' :
                    step.status === 'current' ? 'border-primary text-primary' :
                    'border-gray-300 text-gray-500'
                  ]">
                    <CheckIcon v-if="step.status === 'completed'" class="w-5 h-5" />
                    <span v-else class="text-sm font-gill-sans-semibold">{{ index + 1 }}</span>
                  </div>
                  <span :class="[
                    'ml-3 text-sm font-gill-sans-semibold',
                    step.status === 'completed' || step.status === 'current' ? 'text-primary' : 'text-gray-500'
                  ]">
                    {{ step.name }}
                  </span>
                </div>
                <div v-if="index < steps.length - 1" class="ml-8 w-16 h-0.5 bg-gray-300"></div>
              </li>
            </ol>
          </nav>
        </div>

        <!-- Step Content -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <!-- Step 1: Personal Information -->
          <div v-if="currentStep === 1" class="space-y-6">
            <div>
              <h3 class="text-lg font-futura-bold text-gray-900 mb-2">Informazioni Personali</h3>
              <p class="text-sm text-gray-600">Inserisci le tue informazioni personali per iniziare la verifica.</p>
            </div>

            <form @submit.prevent="submitPersonalInfo" class="space-y-6">
              <!-- Nome e Cognome -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label for="firstName" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Nome *</label>
                  <div class="mt-2">
                    <input
                      id="firstName"
                      v-model="personalInfo.firstName"
                      type="text"
                      required
                      class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                      placeholder="Nome"
                    />
                  </div>
                </div>
                <div>
                  <label for="lastName" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Cognome *</label>
                  <div class="mt-2">
                    <input
                      id="lastName"
                      v-model="personalInfo.lastName"
                      type="text"
                      required
                      class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                      placeholder="Cognome"
                    />
                  </div>
                </div>
              </div>

              <!-- Data di Nascita e Codice Fiscale -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label for="dateOfBirth" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Data di Nascita *</label>
                  <div class="mt-2">
                    <input
                      id="dateOfBirth"
                      v-model="personalInfo.dateOfBirth"
                      type="date"
                      required
                      class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                    />
                  </div>
                </div>
                <div>
                  <label for="fiscalCode" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Codice Fiscale *</label>
                  <div class="mt-2">
                    <input
                      id="fiscalCode"
                      v-model="personalInfo.fiscalCode"
                      type="text"
                      required
                      maxlength="16"
                      class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                      placeholder="RSSMRA80A01H501U"
                    />
                  </div>
                </div>
              </div>

              <!-- Indirizzo -->
              <div>
                <label for="address" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Indirizzo *</label>
                <div class="mt-2">
                  <input
                    id="address"
                    v-model="personalInfo.address"
                    type="text"
                    required
                    class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                    placeholder="Via, numero civico"
                  />
                </div>
              </div>

              <!-- Città, CAP e Nazione -->
              <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                  <label for="city" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Città *</label>
                  <div class="mt-2">
                    <input
                      id="city"
                      v-model="personalInfo.city"
                      type="text"
                      required
                      class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                      placeholder="Città"
                    />
                  </div>
                </div>
                <div>
                  <label for="postalCode" class="block text-sm/6 font-gill-sans-semibold text-gray-900">CAP *</label>
                  <div class="mt-2">
                    <input
                      id="postalCode"
                      v-model="personalInfo.postalCode"
                      type="text"
                      required
                      maxlength="5"
                      class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                      placeholder="00100"
                    />
                  </div>
                </div>
                <div>
                  <label for="country" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Nazione *</label>
                  <div class="mt-2">
                    <select
                      id="country"
                      v-model="personalInfo.country"
                      required
                      class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                    >
                      <option value="">Seleziona nazione</option>
                      <option value="Italia">🇮🇹 Italia</option>
                      <option value="Francia">🇫🇷 Francia</option>
                      <option value="Germania">🇩🇪 Germania</option>
                      <option value="Spagna">🇪🇸 Spagna</option>
                      <option value="Regno Unito">🇬🇧 Regno Unito</option>
                      <option value="Stati Uniti">🇺🇸 Stati Uniti</option>
                      <option value="Altro">🌍 Altro</option>
                    </select>
                  </div>
                </div>
              </div>

              <div class="flex justify-end">
                <button
                  type="submit"
                  :disabled="!canProceedStep1"
                  class="flex justify-center rounded-md bg-primary px-6 py-3 text-sm/6 font-gill-sans-semibold text-white shadow-xs hover:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  Continua
                  <ArrowRightIcon class="ml-2 h-4 w-4" />
                </button>
              </div>
            </form>
          </div>

          <!-- Step 2: Document Upload -->
          <div v-if="currentStep === 2" class="space-y-6">
            <div>
              <h3 class="text-lg font-futura-bold text-gray-900 mb-2">Carica Documenti</h3>
              <p class="text-sm text-gray-600">Carica i documenti richiesti per la verifica della tua identità.</p>
            </div>

            <div class="space-y-6">
              <!-- Identity Document -->
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                <div class="text-center">
                  <DocumentIcon class="mx-auto h-12 w-12 text-gray-400" />
                  <h4 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Documento d'Identità</h4>
                  <p class="mt-1 text-sm text-gray-500">Carta d'identità o passaporto</p>
                  <div class="mt-4">
                    <input
                      ref="identityDocument"
                      type="file"
                      accept="image/*,.pdf"
                      @change="handleIdentityDocument"
                      class="hidden"
                    />
                    <button
                      type="button"
                      @click="$refs.identityDocument.click()"
                      class="flex justify-center rounded-md bg-white px-4 py-2 text-sm/6 font-gill-sans-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >
                      <CloudArrowUpIcon class="h-4 w-4 mr-2" />
                      Carica Documento
                    </button>
                  </div>
                  <p v-if="documents.identity" class="mt-2 text-sm text-green-600">
                    ✓ Documento caricato: {{ documents.identity.name }}
                  </p>
                </div>
              </div>

              <!-- Address Proof -->
              <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                <div class="text-center">
                  <HomeIcon class="mx-auto h-12 w-12 text-gray-400" />
                  <h4 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Prova di Residenza</h4>
                  <p class="mt-1 text-sm text-gray-500">Bolletta, estratto conto o documento equivalente</p>
                  <div class="mt-4">
                    <input
                      ref="addressDocument"
                      type="file"
                      accept="image/*,.pdf"
                      @change="handleAddressDocument"
                      class="hidden"
                    />
                    <button
                      type="button"
                      @click="$refs.addressDocument.click()"
                      class="flex justify-center rounded-md bg-white px-4 py-2 text-sm/6 font-gill-sans-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
                    >
                      <CloudArrowUpIcon class="h-4 w-4 mr-2" />
                      Carica Documento
                    </button>
                  </div>
                  <p v-if="documents.address" class="mt-2 text-sm text-green-600">
                    ✓ Documento caricato: {{ documents.address.name }}
                  </p>
                </div>
              </div>
            </div>

            <div class="flex justify-between">
              <button
                type="button"
                @click="previousStep"
                class="flex justify-center rounded-md bg-white px-6 py-3 text-sm/6 font-gill-sans-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
              >
                <ArrowLeftIcon class="h-4 w-4 mr-2" />
                Indietro
              </button>
              <button
                type="button"
                @click="nextStep"
                :disabled="!canProceedStep2"
                class="flex justify-center rounded-md bg-primary px-6 py-3 text-sm/6 font-gill-sans-semibold text-white shadow-xs hover:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Continua
                <ArrowRightIcon class="ml-2 h-4 w-4" />
              </button>
            </div>
          </div>

          <!-- Step 3: Stripe Connect Setup -->
          <div v-if="currentStep === 3" class="space-y-6">
            <div>
              <h3 class="text-lg font-futura-bold text-gray-900 mb-2">Configurazione Pagamenti</h3>
              <p class="text-sm text-gray-600">Configura il tuo account Stripe per ricevere i pagamenti.</p>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <InformationCircleIcon class="h-5 w-5 text-blue-400" />
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

            <!-- Messaggio di errore -->
            <div v-if="stripeError" class="bg-red-50 border border-red-200 rounded-lg p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3 flex-1">
                  <h4 class="text-sm font-gill-sans-semibold text-red-800 mb-1">Errore</h4>
                  <p class="text-sm text-red-700 mb-3">{{ stripeError }}</p>
                  <!-- Link per abilitare Stripe Connect se l'errore è relativo a Connect non abilitato -->
                  <div v-if="stripeError.includes('Stripe Connect') || stripeError.includes('signed up for Connect') || stripeError.includes('Marketplace')" class="mt-3 space-y-2">
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

            <!-- Stato account esistente -->
            <div v-if="stripeAccountStatus?.charges_enabled && stripeAccountStatus?.payouts_enabled" class="bg-green-50 border border-green-200 rounded-lg p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <CheckIcon class="h-5 w-5 text-green-400" />
                </div>
                <div class="ml-3">
                  <h4 class="text-sm font-gill-sans-semibold text-green-800">Account Stripe già configurato</h4>
                  <p class="mt-1 text-sm text-green-700">
                    Il tuo account Stripe Connect è già configurato e pronto per ricevere pagamenti.
                  </p>
                </div>
              </div>
            </div>

            <div class="text-center">
              <button
                type="button"
                @click="setupStripeConnect"
                :disabled="stripeLoading || (stripeAccountStatus?.charges_enabled && stripeAccountStatus?.payouts_enabled)"
                class="flex justify-center rounded-md bg-primary px-6 py-3 text-base font-gill-sans-semibold text-white shadow-xs hover:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <CreditCardIcon v-if="!stripeLoading" class="h-5 w-5 mr-2" />
                <div v-else class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                {{ stripeLoading ? 'Configurazione in corso...' : (stripeAccountStatus?.charges_enabled ? 'Account già configurato' : 'Configura Stripe Connect') }}
              </button>
            </div>

            <div class="flex justify-start">
              <button
                type="button"
                @click="previousStep"
                class="flex justify-center rounded-md bg-white px-6 py-3 text-sm/6 font-gill-sans-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
              >
                <ArrowLeftIcon class="h-4 w-4 mr-2" />
                Indietro
              </button>
            </div>
          </div>

          <!-- Step 4: Review and Submit -->
          <div v-if="currentStep === 4" class="space-y-6">
            <div>
              <h3 class="text-lg font-futura-bold text-gray-900 mb-2">Riepilogo e Invio</h3>
              <p class="text-sm text-gray-600">Controlla le tue informazioni prima di inviare la richiesta di verifica.</p>
            </div>

            <div class="space-y-4">
              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-2">Informazioni Personali</h4>
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                  <div>
                    <dt class="font-gill-sans text-gray-500">Nome:</dt>
                    <dd class="text-gray-900">{{ personalInfo.firstName }} {{ personalInfo.lastName }}</dd>
                  </div>
                  <div>
                    <dt class="font-gill-sans text-gray-500">Data di Nascita:</dt>
                    <dd class="text-gray-900">{{ formatDate(personalInfo.dateOfBirth) }}</dd>
                  </div>
                  <div>
                    <dt class="font-gill-sans text-gray-500">Codice Fiscale:</dt>
                    <dd class="text-gray-900">{{ personalInfo.fiscalCode }}</dd>
                  </div>
                  <div>
                    <dt class="font-gill-sans text-gray-500">Indirizzo:</dt>
                    <dd class="text-gray-900">{{ personalInfo.address }}, {{ personalInfo.city }} {{ personalInfo.postalCode }}, {{ personalInfo.country }}</dd>
                  </div>
                </dl>
              </div>

              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-2">Documenti</h4>
                <div class="space-y-2 text-sm">
                  <div class="flex items-center">
                    <CheckIcon class="h-4 w-4 text-green-500 mr-2" />
                    <span class="text-gray-900">Documento d'identità: {{ documents.identity?.name || 'Non caricato' }}</span>
                  </div>
                  <div class="flex items-center">
                    <CheckIcon class="h-4 w-4 text-green-500 mr-2" />
                    <span class="text-gray-900">Prova di residenza: {{ documents.address?.name || 'Non caricato' }}</span>
                  </div>
                </div>
              </div>

              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-2">Pagamenti</h4>
                <div class="flex items-center">
                  <CheckIcon class="h-4 w-4 text-green-500 mr-2" />
                  <span class="text-gray-900">Stripe Connect configurato</span>
                </div>
              </div>
            </div>

            <div class="flex justify-between">
              <button
                type="button"
                @click="previousStep"
                class="flex justify-center rounded-md bg-white px-6 py-3 text-sm/6 font-gill-sans-semibold text-gray-900 shadow-xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-gray-500"
              >
                <ArrowLeftIcon class="h-4 w-4 mr-2" />
                Indietro
              </button>
              <button
                type="button"
                @click="submitKyc"
                :disabled="submitting"
                class="flex justify-center rounded-md bg-primary px-6 py-3 text-sm/6 font-gill-sans-semibold text-white shadow-xs hover:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <div v-if="submitting" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
                {{ submitting ? 'Invio in corso...' : 'Invia Richiesta' }}
              </button>
            </div>
          </div>
        </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  CheckIcon,
  ArrowRightIcon,
  ArrowLeftIcon,
  DocumentIcon,
  CloudArrowUpIcon,
  HomeIcon,
  InformationCircleIcon,
  CreditCardIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()

// Step management
const currentStep = ref(1)
const steps = ref([
  { id: 1, name: 'Informazioni', status: 'current' },
  { id: 2, name: 'Documenti', status: 'pending' },
  { id: 3, name: 'Pagamenti', status: 'pending' },
  { id: 4, name: 'Invio', status: 'pending' }
])

// Form data
const personalInfo = reactive({
  firstName: '',
  lastName: '',
  dateOfBirth: '',
  fiscalCode: '',
  address: '',
  city: '',
  postalCode: '',
  country: ''
})

const documents = reactive({
  identity: null,
  address: null
})

const stripeLoading = ref(false)
const submitting = ref(false)
const stripeAccountStatus = ref(null)
const stripeError = ref('')
const onboardingUrl = ref('')

// Computed properties
const canProceedStep1 = computed(() => {
  return personalInfo.firstName && 
         personalInfo.lastName && 
         personalInfo.dateOfBirth && 
         personalInfo.fiscalCode && 
         personalInfo.address && 
         personalInfo.city && 
         personalInfo.postalCode && 
         personalInfo.country
})

const canProceedStep2 = computed(() => {
  return documents.identity && documents.address
})

// Methods
const nextStep = () => {
  if (currentStep.value < steps.value.length) {
    steps.value[currentStep.value - 1].status = 'completed'
    currentStep.value++
    steps.value[currentStep.value - 1].status = 'current'
  }
}

const previousStep = () => {
  if (currentStep.value > 1) {
    steps.value[currentStep.value - 1].status = 'pending'
    currentStep.value--
    steps.value[currentStep.value - 1].status = 'current'
  }
}

const submitPersonalInfo = () => {
  if (canProceedStep1.value) {
    nextStep()
  }
}

const handleIdentityDocument = (event) => {
  const file = event.target.files[0]
  if (file) {
    documents.identity = file
  }
}

const handleAddressDocument = (event) => {
  const file = event.target.files[0]
  if (file) {
    documents.address = file
  }
}

const setupStripeConnect = async () => {
  stripeLoading.value = true
  stripeError.value = ''
  
  try {
    const token = localStorage.getItem('token')
    if (!token) {
      stripeError.value = 'Non sei autenticato. Effettua il login.'
      stripeLoading.value = false
      return
    }

    // Verifica se l'utente ha già un account Stripe
    let accountId = null
    
    // Controlla se esiste già un account
    const statusResponse = await fetch('/api/stripe/account/status', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })

    if (statusResponse.ok) {
      const statusData = await statusResponse.json()
      accountId = statusData.account_id
      
      // Se l'account esiste e può ricevere pagamenti, passa allo step successivo
      if (statusData.charges_enabled && statusData.payouts_enabled) {
        stripeAccountStatus.value = {
          charges_enabled: true,
          payouts_enabled: true,
          details_submitted: true
        }
        nextStep()
        stripeLoading.value = false
        return
      }
    }

    // Se non esiste un account, crealo
    if (!accountId) {
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
        throw new Error(errorData.message || 'Errore nella creazione dell\'account Stripe')
      }

      const createData = await createResponse.json()
      accountId = createData.account_id
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
      throw new Error(errorData.message || 'Errore nella generazione del link di onboarding')
    }

    const onboardingData = await onboardingResponse.json()
    onboardingUrl.value = onboardingData.onboarding_url

    // Reindirizza l'utente a Stripe per completare l'onboarding
    if (onboardingUrl.value) {
      window.location.href = onboardingUrl.value
    } else {
      throw new Error('Link di onboarding non disponibile')
    }
  } catch (error) {
    console.error('Error setting up Stripe Connect:', error)
    stripeError.value = error.message || 'Errore nella configurazione di Stripe Connect'
  } finally {
    stripeLoading.value = false
  }
}

const submitKyc = async () => {
  submitting.value = true
  try {
    // Simulate KYC submission
    await new Promise(resolve => setTimeout(resolve, 2000))
    
    // Update user KYC status
    if (authStore.user) {
      authStore.user.kyc_status = 'pending'
    }
    
    // Redirect to dashboard
    router.push('/dashboard')
  } catch (error) {
    console.error('Error submitting KYC:', error)
  } finally {
    submitting.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('it-IT')
}

// Load existing user data on mount
onMounted(async () => {
  // Forza il refresh dell'utente dall'API per avere i dati più aggiornati
  if (authStore.token) {
    await authStore.fetchUser()
  }
  
  loadExistingUserData()
  checkStripeAccountStatus()
})

// Verifica lo stato dell'account Stripe
const checkStripeAccountStatus = async () => {
  try {
    const token = localStorage.getItem('token')
    if (!token) return

    const response = await fetch('/api/stripe/account/status', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      stripeAccountStatus.value = {
        charges_enabled: data.charges_enabled,
        payouts_enabled: data.payouts_enabled,
        details_submitted: data.details_submitted
      }
    }
  } catch (error) {
    console.error('Errore nel controllo stato Stripe:', error)
  }
}

// Watch for user changes and reload data
watch(() => authStore.user, (newUser) => {
  if (newUser) {
    loadExistingUserData()
  }
}, { deep: true })

const loadExistingUserData = () => {
  const user = authStore.user
  if (user) {
    // Pre-popola i campi con i dati esistenti dell'utente
    personalInfo.firstName = user.first_name || ''
    personalInfo.lastName = user.last_name || ''
    
    // Se first_name/last_name non sono disponibili, usa il campo name
    if (!personalInfo.firstName && !personalInfo.lastName && user.name) {
      const nameParts = user.name.split(' ')
      personalInfo.firstName = nameParts[0] || ''
      personalInfo.lastName = nameParts.slice(1).join(' ') || ''
    }
    
    // Formatta la data di nascita per l'input date
    if (user.birth_date) {
      const date = new Date(user.birth_date)
      personalInfo.dateOfBirth = date.toISOString().split('T')[0]
    }
    
    personalInfo.fiscalCode = user.fiscal_code || ''
    personalInfo.address = user.address || ''
    personalInfo.city = user.city || ''
    personalInfo.postalCode = user.postal_code || ''
    personalInfo.country = user.country || ''
    
    console.log('KYC - Dati utente dal database:', {
      country: user.country,
      nationality: user.nationality,
      address: user.address,
      city: user.city,
      postal_code: user.postal_code
    })
    
    // Se l'utente ha già completato alcuni step, aggiorna lo stato
    if (personalInfo.firstName && personalInfo.lastName && personalInfo.dateOfBirth && personalInfo.fiscalCode) {
      console.log('Dati personali esistenti caricati per KYC:', personalInfo)
    }
  }
}
</script>
