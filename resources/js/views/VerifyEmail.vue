<template>
  <div class="flex min-h-full flex-1 flex-col justify-center px-6 py-12 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-sm text-center">
      <router-link to="/">
        <img src="/images/logos/logo-blu.svg" alt="CardSwap" class="h-16 w-auto mx-auto" />
      </router-link>
      <h2 class="mt-10 text-center text-2xl/9 font-futura-bold tracking-tight text-primary">Verifica email</h2>
      <p v-if="loading" class="mt-2 text-sm font-gill-sans text-gray-600">Verifica in corso...</p>
      <p v-else-if="success" class="mt-2 text-sm font-gill-sans text-green-700">Email verificata con successo. Puoi usare il tuo account.</p>
      <p v-else-if="error" class="mt-2 text-sm font-gill-sans text-red-700">{{ error }}</p>
      <div class="mt-6">
        <router-link to="/dashboard" class="font-gill-sans-semibold text-primary hover:text-secondary">Vai alla dashboard</router-link>
        <span class="mx-2">|</span>
        <router-link to="/" class="font-gill-sans-semibold text-primary hover:text-secondary">Home</router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const authStore = useAuthStore()
const loading = ref(true)
const success = ref(false)
const error = ref('')

onMounted(async () => {
  const token = route.query.token
  if (!token) {
    error.value = 'Link di verifica non valido.'
    loading.value = false
    return
  }
  try {
    const response = await fetch('/api/auth/verify-email', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ token })
    })
    const data = await response.json().catch(() => ({}))
    if (response.ok) {
      success.value = true
      if (data.user) authStore.setUser(data.user)
    } else {
      error.value = data.message || 'Link non valido o scaduto.'
    }
  } catch (e) {
    error.value = 'Errore di connessione.'
  } finally {
    loading.value = false
  }
})
</script>
