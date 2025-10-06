<template>
  <div class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
      <div class="text-center">
        <router-link to="/">
          <img src="/images/logos/logo-blu.svg" alt="CARDSWAP" class="h-16 w-auto mx-auto" />
        </router-link>
        <h2 class="mt-10 text-center text-2xl/9 font-futura-bold tracking-tight text-primary">Crea il tuo account</h2>
        <p class="mt-2 text-sm font-gill-sans text-gray-600">
          Oppure
          <router-link to="/login" class="font-gill-sans-semibold text-primary hover:text-secondary">accedi al tuo account esistente</router-link>
        </p>
      </div>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
      <form class="space-y-4" @submit.prevent="handleRegister">
        <!-- Nome e Cognome in una riga -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="first_name" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Nome</label>
            <div class="mt-2">
              <input
                id="first_name"
                v-model="form.first_name"
                name="first_name"
                type="text"
                autocomplete="given-name"
                required
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                placeholder="Il tuo nome"
              />
            </div>
            <div v-if="errors.first_name" class="mt-2 text-sm text-accent-red font-gill-sans">
              {{ errors.first_name }}
            </div>
          </div>

          <div>
            <label for="last_name" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Cognome</label>
            <div class="mt-2">
              <input
                id="last_name"
                v-model="form.last_name"
                name="last_name"
                type="text"
                autocomplete="family-name"
                required
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                placeholder="Il tuo cognome"
              />
            </div>
            <div v-if="errors.last_name" class="mt-2 text-sm text-accent-red font-gill-sans">
              {{ errors.last_name }}
            </div>
          </div>
        </div>

        <!-- Username e Email in una riga -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="username" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Nome utente</label>
            <div class="mt-2">
              <input
                id="username"
                v-model="form.username"
                name="username"
                type="text"
                autocomplete="username"
                required
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                placeholder="Scegli un nome utente"
              />
            </div>
            <div v-if="errors.username" class="mt-2 text-sm text-accent-red font-gill-sans">
              {{ errors.username }}
            </div>
          </div>

          <div>
            <label for="email" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Indirizzo email</label>
            <div class="mt-2">
              <input
                id="email"
                v-model="form.email"
                name="email"
                type="email"
                autocomplete="email"
                required
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                placeholder="La tua email"
              />
            </div>
            <div v-if="errors.email" class="mt-2 text-sm text-accent-red font-gill-sans">
              {{ errors.email }}
            </div>
          </div>
        </div>

        <!-- Password e Conferma password in una riga -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label for="password" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Password</label>
            <div class="mt-2">
              <input
                id="password"
                v-model="form.password"
                name="password"
                type="password"
                autocomplete="new-password"
                required
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                placeholder="Scegli una password sicura"
              />
            </div>
            <div v-if="errors.password" class="mt-2 text-sm text-accent-red font-gill-sans">
              {{ errors.password }}
            </div>
          </div>

          <div>
            <label for="password_confirmation" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Conferma password</label>
            <div class="mt-2">
              <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                name="password_confirmation"
                type="password"
                autocomplete="new-password"
                required
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                placeholder="Conferma la password"
              />
            </div>
            <div v-if="errors.password_confirmation" class="mt-2 text-sm text-accent-red font-gill-sans">
              {{ errors.password_confirmation }}
            </div>
          </div>
        </div>

        <!-- Tipo Utente: Privato/Azienda -->
        <div>
          <fieldset>
            <legend class="block text-sm/6 font-gill-sans-semibold text-gray-900">Tipo di account</legend>
            <div class="mt-2 flex space-x-6">
              <div class="flex items-center">
                <input
                  id="private"
                  v-model="form.account_type"
                  name="account_type"
                  type="radio"
                  value="private"
                  class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                />
                <label for="private" class="ml-2 block text-sm font-gill-sans text-gray-900">
                  Persona fisica
                </label>
              </div>
              <div class="flex items-center">
                <input
                  id="company"
                  v-model="form.account_type"
                  name="account_type"
                  type="radio"
                  value="company"
                  class="h-4 w-4 text-primary focus:ring-primary border-gray-300"
                />
                <label for="company" class="ml-2 block text-sm font-gill-sans text-gray-900">
                  Azienda
                </label>
              </div>
            </div>
          </fieldset>
        </div>

        <!-- Campi Azienda (condizionali) -->
        <div v-if="form.account_type === 'company'" class="space-y-4">
          <div>
            <label for="business_name" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Ragione Sociale</label>
            <div class="mt-2">
              <input
                id="business_name"
                v-model="form.business_name"
                name="business_name"
                type="text"
                autocomplete="organization"
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                placeholder="Nome dell'azienda"
              />
            </div>
            <div v-if="errors.business_name" class="mt-2 text-sm text-accent-red font-gill-sans">
              {{ errors.business_name }}
            </div>
          </div>

          <div>
            <label for="vat_number" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Partita IVA</label>
            <div class="mt-2">
              <input
                id="vat_number"
                v-model="form.vat_number"
                name="vat_number"
                type="text"
                autocomplete="off"
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                placeholder="IT12345678901"
              />
            </div>
            <div v-if="errors.vat_number" class="mt-2 text-sm text-accent-red font-gill-sans">
              {{ errors.vat_number }}
            </div>
          </div>
        </div>

        <div class="flex items-center">
          <input
            id="terms"
            v-model="form.terms"
            name="terms"
            type="checkbox"
            required
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <label for="terms" class="ml-2 block text-sm font-gill-sans text-gray-900">
            Accetto i
            <a href="#" class="text-primary hover:text-secondary">Termini e Condizioni</a>
            e la
            <a href="#" class="text-primary hover:text-secondary">Privacy Policy</a>
          </label>
        </div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="flex w-full justify-center rounded-md bg-primary px-3 py-1.5 text-sm/6 font-gill-sans-semibold text-white shadow-xs hover:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ loading ? 'Registrazione in corso...' : 'Crea account' }}
          </button>
        </div>

        <div v-if="errorMessage" class="rounded-md bg-red-50 p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-accent-red" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-gill-sans-semibold text-red-800">
                Errore di registrazione
              </h3>
              <div class="mt-2 text-sm font-gill-sans text-red-700">
                {{ errorMessage }}
              </div>
            </div>
          </div>
        </div>
      </form>


      <p class="mt-10 text-center text-sm/6 font-gill-sans text-gray-500">
        Hai già un account?
        {{ ' ' }}
        <router-link to="/login" class="font-gill-sans-semibold text-primary hover:text-secondary">Accedi al tuo account</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()

const form = reactive({
  first_name: '',
  last_name: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  account_type: 'private', // Default a privato
  business_name: '',
  vat_number: '',
  terms: false
})

const errors = ref({})
const loading = ref(false)
const errorMessage = ref('')

const handleRegister = async () => {
  loading.value = true
  errors.value = {}
  errorMessage.value = ''

  try {
    // Validazione
    if (!form.first_name) {
      errors.value.first_name = 'Il nome è obbligatorio'
    }
    if (!form.last_name) {
      errors.value.last_name = 'Il cognome è obbligatorio'
    }
    if (!form.username) {
      errors.value.username = 'Il nome utente è obbligatorio'
    }
    if (!form.email) {
      errors.value.email = 'L\'email è obbligatoria'
    }
    if (!form.password) {
      errors.value.password = 'La password è obbligatoria'
    }
    if (form.password.length < 8) {
      errors.value.password = 'La password deve essere di almeno 8 caratteri'
    }
    if (form.password !== form.password_confirmation) {
      errors.value.password_confirmation = 'Le password non coincidono'
    }
    
    // Validazione campi azienda
    if (form.account_type === 'company') {
      if (!form.business_name) {
        errors.value.business_name = 'La ragione sociale è obbligatoria'
      }
      if (!form.vat_number) {
        errors.value.vat_number = 'La partita IVA è obbligatoria'
      } else if (!/^IT\d{11}$/.test(form.vat_number)) {
        errors.value.vat_number = 'Formato partita IVA non valido (es. IT12345678901)'
      }
    }
    
    if (!form.terms) {
      errorMessage.value = 'Devi accettare i termini e condizioni'
    }

    if (Object.keys(errors.value).length > 0 || errorMessage.value) {
      loading.value = false
      return
    }

    // Chiamata API reale
    const authStore = useAuthStore()
    const result = await authStore.register(form)
    
    if (result.success) {
      // Reindirizzamento alla dashboard dopo la registrazione
      router.push('/dashboard')
    } else {
      errorMessage.value = result.error || 'Errore durante la registrazione'
    }
    
  } catch (error) {
    errorMessage.value = 'Errore durante la registrazione. Riprova.'
  } finally {
    loading.value = false
  }
}
</script>
