<template>
  <!-- Non mostrare l'header nella dashboard -->
  <header v-if="!isInDashboard">
    <!-- Top navigation - Blu scuro con logo bianco e icone a destra -->
    <div class="bg-primary text-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo CARDSWAP in bianco -->
          <div class="flex items-center">
            <router-link to="/">
              <img src="/images/logos/logo-bianco.svg" alt="CARDSWAP TCG" class="h-10 w-auto" />
            </router-link>
          </div>
          
          <!-- Icone a destra: carrello, cuore, chat, login/signup -->
          <div class="flex items-center space-x-6">
            <!-- Carrello -->
            <router-link to="/cart" class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
              </svg>
            </router-link>
            
            <!-- Cuore (wishlist) -->
            <router-link to="/wishlist" class="hover:opacity-80 transition-opacity">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
              </svg>
            </router-link>
            
            
            <!-- Sezione utente: Login/Signup -->
            <div v-if="!isLoggedIn" class="flex items-center space-x-4">
              <!-- Login -->
              <router-link to="/login" class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-sm font-gill-sans-semibold">Login</span>
              </router-link>
              
              <!-- Sign up -->
              <router-link to="/register" class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <span class="text-sm font-gill-sans-semibold">Sign up</span>
              </router-link>
            </div>
            
            <!-- Utente loggato: Nome utente (link alla dashboard) e Logout -->
            <div v-else class="flex items-center space-x-4">
              <!-- Nome utente cliccabile che porta alla dashboard -->
              <router-link to="/dashboard" class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span class="text-sm font-gill-sans-semibold">{{ userName }}</span>
              </router-link>
              
              <!-- Logout -->
              <button @click="handleLogout" class="flex items-center space-x-2 hover:opacity-80 transition-opacity">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="text-sm font-gill-sans-semibold">Logout</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Secondary section - Bianco con pulsante CATEGORY e campo ricerca -->
    <div class="bg-white border-b border-gray-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center h-16 space-x-4">
          <!-- Pulsante CATEGORY -->
          <router-link to="/categories" class="bg-secondary text-primary px-6 py-3 rounded-lg font-futura-bold text-sm uppercase hover:bg-opacity-90 transition-colors">
            CATEGORY
          </router-link>
          
          <!-- Campo ricerca con icona lente d'ingrandimento -->
          <div class="flex-1 relative">
            <div class="relative">
              <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              <input 
                type="text" 
                placeholder="Cerca su CardSwap" 
                class="w-full bg-gray-50 text-gray-900 placeholder-gray-400 rounded-full pl-12 pr-4 py-3 border border-gray-200 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent font-gill-sans text-sm"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth.js'

// Usa il Pinia store per l'autenticazione
const authStore = useAuthStore()
const route = useRoute()
const router = useRouter()

// Computed per determinare se siamo nella dashboard
const isInDashboard = computed(() => route.path === '/dashboard')

// Computed per lo stato di login e nome utente
const isLoggedIn = computed(() => authStore.isAuthenticated)
const userName = computed(() => authStore.user?.name || authStore.user?.first_name || 'Guest')

// Carica i dati utente se c'è un token ma non l'utente
onMounted(async () => {
  console.log('Header mounted')
  
  // Se c'è un token ma non c'è l'utente, caricalo
  if (authStore.token && !authStore.user) {
    console.log('Carico dati utente...')
    await authStore.fetchUser()
  }
})

// Funzione di logout
const handleLogout = async () => {
  try {
    // Chiama l'API di logout
    await fetch('/api/auth/logout', {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    })
  } catch (error) {
    console.error('Errore durante logout:', error)
  } finally {
    // Pulisci lo store
    authStore.logout()
    router.push('/')
  }
}
</script>
