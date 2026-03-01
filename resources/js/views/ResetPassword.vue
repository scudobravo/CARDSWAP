<template>
  <div class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
      <div class="text-center">
        <router-link to="/">
          <img src="/images/logos/logo-blu.svg" alt="CardSwap" class="h-16 w-auto mx-auto" />
        </router-link>
        <h2 class="mt-10 text-center text-2xl/9 font-futura-bold tracking-tight text-primary">Nuova password</h2>
        <p class="mt-2 text-sm font-gill-sans text-gray-600">
          Inserisci la nuova password per il tuo account.
        </p>
      </div>
    </div>

    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
      <form class="space-y-4" @submit.prevent="handleSubmit">
        <div v-if="!token || !emailFromQuery" class="rounded-md bg-red-50 p-4 text-sm text-red-800">
          Link non valido o scaduto. Richiedi un nuovo link da
          <router-link to="/forgot-password" class="font-semibold underline">Recupera password</router-link>.
        </div>
        <template v-else>
          <div>
            <label for="password" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Nuova password</label>
            <div class="mt-2">
              <input
                id="password"
                v-model="password"
                type="password"
                name="password"
                autocomplete="new-password"
                required
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                placeholder="Nuova password"
              />
            </div>
            <div v-if="errors.password" class="mt-2 text-sm text-accent-red font-gill-sans">{{ errors.password }}</div>
          </div>
          <div>
            <label for="password_confirmation" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Conferma password</label>
            <div class="mt-2">
              <input
                id="password_confirmation"
                v-model="passwordConfirmation"
                type="password"
                name="password_confirmation"
                autocomplete="new-password"
                required
                class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
                placeholder="Conferma password"
              />
            </div>
            <div v-if="errors.password_confirmation" class="mt-2 text-sm text-accent-red font-gill-sans">{{ errors.password_confirmation }}</div>
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
              {{ loading ? 'Salvataggio...' : 'Reimposta password' }}
            </button>
          </div>
        </template>

        <div v-if="successMessage" class="rounded-md bg-green-50 p-4 text-sm font-gill-sans text-green-800">
          {{ successMessage }}
          <router-link to="/login" class="block mt-2 font-semibold text-primary">Vai al login</router-link>
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
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const token = ref('')
const emailFromQuery = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const loading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')
const errors = reactive({ password: '', password_confirmation: '' })

onMounted(() => {
  token.value = route.query.token || ''
  emailFromQuery.value = route.query.email || ''
})

const handleSubmit = async () => {
  loading.value = true
  successMessage.value = ''
  errorMessage.value = ''
  errors.password = ''
  errors.password_confirmation = ''

  if (password.value.length < 8) {
    errors.password = 'La password deve essere di almeno 8 caratteri'
    loading.value = false
    return
  }
  if (password.value !== passwordConfirmation.value) {
    errors.password_confirmation = 'Le password non coincidono'
    loading.value = false
    return
  }

  try {
    const response = await fetch('/api/auth/reset-password', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({
        token: token.value,
        email: emailFromQuery.value,
        password: password.value,
        password_confirmation: passwordConfirmation.value
      })
    })
    const data = await response.json().catch(() => ({}))

    if (response.ok) {
      successMessage.value = data.message || 'Password reimpostata con successo.'
    } else {
      errorMessage.value = data.message || 'Impossibile reimpostare la password. Il link potrebbe essere scaduto.'
    }
  } catch (e) {
    errorMessage.value = 'Errore di connessione. Riprova.'
  } finally {
    loading.value = false
  }
}
</script>
