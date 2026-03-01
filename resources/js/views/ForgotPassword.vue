<template>
  <div class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
      <div class="text-center">
        <router-link to="/">
          <img src="/images/logos/logo-blu.svg" alt="CardSwap" class="h-16 w-auto mx-auto" />
        </router-link>
        <h2 class="mt-10 text-center text-2xl/9 font-futura-bold tracking-tight text-primary">Recupera password</h2>
        <p class="mt-2 text-sm font-gill-sans text-gray-600">
          Inserisci la tua email e ti invieremo un link per reimpostare la password.
        </p>
      </div>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
      <form class="space-y-6" @submit.prevent="handleSubmit">
        <div>
          <label for="email" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Indirizzo email</label>
          <div class="mt-2">
            <input
              id="email"
              v-model="email"
              type="email"
              name="email"
              autocomplete="email"
              required
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
              placeholder="La tua email"
            />
          </div>
          <div v-if="errors.email" class="mt-2 text-sm text-accent-red font-gill-sans">{{ errors.email }}</div>
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
            {{ loading ? 'Invio in corso...' : 'Invia link' }}
          </button>
        </div>

        <div v-if="successMessage" class="rounded-md bg-green-50 p-4 text-sm font-gill-sans text-green-800">
          {{ successMessage }}
        </div>
        <div v-if="errorMessage" class="rounded-md bg-red-50 p-4 text-sm font-gill-sans text-red-800">
          {{ errorMessage }}
        </div>
      </form>

      <p class="mt-6 text-center text-sm font-gill-sans text-gray-500">
        <router-link to="/login" class="font-gill-sans-semibold text-primary hover:text-secondary">Torna al login</router-link>
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'

const email = ref('')
const loading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const errors = reactive({})

const handleSubmit = async () => {
  loading.value = true
  successMessage.value = ''
  errorMessage.value = ''
  errors.email = ''

  if (!email.value) {
    errors.email = 'L\'email è obbligatoria'
    loading.value = false
    return
  }

  try {
    const response = await fetch('/api/auth/forgot-password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ email: email.value })
    })
    const data = await response.json().catch(() => ({}))

    if (response.ok) {
      successMessage.value = data.message || 'Link per il reset della password inviato. Controlla la tua email.'
    } else {
      errorMessage.value = data.message || 'Impossibile inviare il link. Verifica l\'email e riprova.'
    }
  } catch (e) {
    errorMessage.value = 'Errore di connessione. Riprova.'
  } finally {
    loading.value = false
  }
}
</script>
