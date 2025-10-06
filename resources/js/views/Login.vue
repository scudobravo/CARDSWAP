<template>
  <div class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
      <div class="text-center">
        <router-link to="/">
          <img src="/images/logos/logo-blu.svg" alt="CARDSWAP" class="h-16 w-auto mx-auto" />
        </router-link>
        <h2 class="mt-10 text-center text-2xl/9 font-futura-bold tracking-tight text-primary">Accedi al tuo account</h2>
        <p class="mt-2 text-sm font-gill-sans text-gray-600">
          Oppure
          <router-link to="/register" class="font-gill-sans-semibold text-primary hover:text-secondary">registrati per un nuovo account</router-link>
        </p>
      </div>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
      <form class="space-y-6" @submit.prevent="handleLogin">
        <div>
          <label for="email" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Indirizzo email</label>
          <div class="mt-2">
            <input 
              v-model="form.email"
              type="email" 
              name="email" 
              id="email" 
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

        <div>
          <div class="flex items-center justify-between">
            <label for="password" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Password</label>
            <div class="text-sm">
              <a href="#" class="font-gill-sans-semibold text-primary hover:text-secondary">Password dimenticata?</a>
            </div>
          </div>
          <div class="mt-2">
            <input 
              v-model="form.password"
              type="password" 
              name="password" 
              id="password" 
              autocomplete="current-password" 
              required 
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans" 
              placeholder="La tua password"
            />
          </div>
          <div v-if="errors.password" class="mt-2 text-sm text-accent-red font-gill-sans">
            {{ errors.password }}
          </div>
        </div>

        <div class="flex items-center">
          <input
            id="remember-me"
            v-model="form.remember"
            name="remember-me"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <label for="remember-me" class="ml-2 block text-sm font-gill-sans text-gray-900">
            Ricordami
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
            {{ loading ? 'Accesso in corso...' : 'Accedi' }}
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
                Errore di accesso
              </h3>
              <div class="mt-2 text-sm font-gill-sans text-red-700">
                {{ errorMessage }}
              </div>
            </div>
          </div>
        </div>
      </form>


      <p class="mt-10 text-center text-sm/6 font-gill-sans text-gray-500">
        Non sei un membro?
        {{ ' ' }}
        <router-link to="/register" class="font-gill-sans-semibold text-primary hover:text-secondary">Inizia una prova gratuita di 14 giorni</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({
  email: '',
  password: '',
  remember: false
})

const errors = ref({})
const loading = ref(false)
const errorMessage = ref('')

const handleLogin = async () => {
  loading.value = true
  errors.value = {}
  errorMessage.value = ''

  try {
    // Validazione
    if (!form.email) {
      errors.value.email = 'L\'email è obbligatoria'
    }
    if (!form.password) {
      errors.value.password = 'La password è obbligatoria'
    }

    if (Object.keys(errors.value).length > 0) {
      loading.value = false
      return
    }

    // Usa l'auth store per il login
    const result = await authStore.login({
      email: form.email,
      password: form.password
    })

    if (result.success) {
      // Reindirizzamento alla dashboard dopo il login
      router.push('/dashboard')
    } else {
      errorMessage.value = result.error || 'Errore durante l\'accesso. Riprova.'
    }
    
  } catch (error) {
    errorMessage.value = 'Errore durante l\'accesso. Riprova.'
  } finally {
    loading.value = false
  }
}
</script>
