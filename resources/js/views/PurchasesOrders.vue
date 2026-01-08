<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        I miei Ordini
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Visualizza e gestisci i tuoi ordini
      </p>
    </div>

    <!-- KYC Warning -->
    <div v-if="!kycCompleted" class="mb-8">
      <div class="rounded-md bg-yellow-50 p-4 text-center">
        <div class="flex justify-center items-center mb-2">
          <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 mr-2" />
          <h3 class="text-sm font-gill-sans-semibold text-yellow-800">
            Verifica KYC Richiesta
          </h3>
        </div>
        <div class="text-sm text-yellow-700 mb-4">
          <p>Per completare ordini e accedere a tutte le funzionalità, devi completare la verifica KYC. Questo processo è necessario per garantire la sicurezza della piattaforma.</p>
        </div>
        <div>
          <router-link
            to="/dashboard/kyc"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-gill-sans-semibold rounded-md text-yellow-800 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
          >
            Inizia Verifica KYC
          </router-link>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-flex items-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Caricamento ordini...
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="!loading && orders.length === 0" class="text-center py-12">
        <ShoppingBagIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Nessun ordine</h3>
        <p class="mt-1 text-sm text-gray-500">I tuoi ordini appariranno qui quando effettuerai degli acquisti.</p>
        <div class="mt-6">
          <router-link
            to="/"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-gill-sans-semibold rounded-md text-white bg-primary hover:bg-primary/90"
          >
            Inizia a Comprare
          </router-link>
        </div>
      </div>

      <!-- Lista Ordini -->
      <div v-else class="divide-y divide-gray-200">
        <div 
          v-for="order in orders" 
          :key="order.id"
          class="p-6 hover:bg-gray-50 transition-colors duration-200"
        >
          <div class="flex items-center justify-between">
            <!-- Info Ordine -->
            <div class="flex-1">
              <div class="flex items-center space-x-4">
                <div>
                  <h4 class="text-lg font-futura-bold text-gray-900">
                    Ordine #{{ order.order_number }}
                  </h4>
                  <p class="text-sm text-gray-500">
                    {{ formatDate(order.created_at) }} • {{ order.seller?.name || 'Venditore' }}
                  </p>
                </div>
                
                <!-- Badge Stato -->
                <span :class="getStatusBadgeClass(order.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-gill-sans-semibold">
                  {{ getStatusLabel(order.status) }}
                </span>
              </div>

              <!-- Dettagli Prodotti -->
              <div class="mt-3">
                <div class="flex items-center space-x-4 text-sm text-gray-600">
                  <span>{{ order.order_items?.length || order.orderItems?.length || 0 }} articoli</span>
                  <span>•</span>
                  <span class="font-gill-sans-semibold text-gray-900">€{{ formatPriceItaliana(order.total_amount) }}</span>
                </div>
                
                <!-- Lista Prodotti -->
                <div v-if="order.order_items || order.orderItems" class="mt-2 space-y-1">
                  <div 
                    v-for="item in (order.order_items || order.orderItems).slice(0, 2)" 
                    :key="item.id"
                    class="text-sm text-gray-600"
                  >
                    {{ item.name || item.cardListing?.cardModel?.name || 'Prodotto' }} 
                    <span class="text-gray-400">({{ getConditionLabel(item.condition) }})</span>
                    <span class="text-gray-400">x{{ item.quantity }}</span>
                  </div>
                  <div v-if="(order.order_items || order.orderItems).length > 2" class="text-sm text-gray-400">
                    +{{ (order.order_items || order.orderItems).length - 2 }} altri articoli
                  </div>
                </div>
              </div>
            </div>

            <!-- Azioni -->
            <div class="flex items-center space-x-3 flex-wrap gap-2">
              <!-- Tracking -->
              <div v-if="order.tracking_number" class="text-sm text-gray-600">
                <p class="font-gill-sans-semibold">Tracking:</p>
                <p class="font-mono">{{ order.tracking_number }}</p>
              </div>

              <!-- Bottone Apri Dispute (solo per ordini consegnati in attesa di 72h) -->
              <button
                v-if="order.status === 'delivered_pending_72h' && !order.has_dispute"
                @click="$router.push(`/order-confirmation/${order.id}`)"
                class="px-3 py-1 text-sm font-gill-sans-semibold text-red-700 bg-red-50 border border-red-300 rounded-md hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500"
              >
                Apri Dispute
              </button>
              
              <!-- Badge Dispute Aperta -->
              <div
                v-if="order.has_dispute"
                class="px-3 py-1 text-sm font-gill-sans-semibold text-red-700 bg-red-50 border border-red-300 rounded-md"
              >
                Dispute Aperta
              </div>

              <!-- Pulsante Dettagli -->
              <router-link
                :to="`/order-confirmation/${order.id}`"
                class="px-3 py-1 text-sm font-gill-sans-semibold text-primary bg-primary-light border border-primary rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary"
              >
                Dettagli
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { ShoppingBagIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import { formatPriceItaliana } from '../utils/priceFormatter'
import axios from 'axios'

const authStore = useAuthStore()
const kycCompleted = computed(() => authStore.user?.kyc_status === 'approved')

// Stato reattivo
const orders = ref([])
const loading = ref(false)

// Metodi
const loadOrders = async () => {
  loading.value = true
  
  try {
    const response = await axios.get('/api/orders', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.data.success) {
      // Gestisci sia formato paginato che formato semplice
      orders.value = response.data.data?.data || response.data.data || []
    }
  } catch (error) {
    console.error('Errore nel caricamento ordini:', error)
    orders.value = []
  } finally {
    loading.value = false
  }
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'In attesa',
    pending_payment: 'Pagamento in attesa',
    paid_funds_held: 'Fondi trattenuti',
    label_created: 'Etichetta creata',
    in_transit_verified: 'In transito',
    delivered_pending_72h: 'Consegnato (72h)',
    dispute_hold: 'Dispute aperta',
    completed: 'Completato',
    confirmed: 'Confermato',
    shipped: 'Spedito',
    delivered: 'Consegnato',
    cancelled: 'Cancellato',
    refunded: 'Rimborsato'
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    pending_payment: 'bg-orange-100 text-orange-800',
    paid_funds_held: 'bg-blue-100 text-blue-800',
    label_created: 'bg-indigo-100 text-indigo-800',
    in_transit_verified: 'bg-purple-100 text-purple-800',
    delivered_pending_72h: 'bg-green-100 text-green-800',
    dispute_hold: 'bg-red-100 text-red-800',
    completed: 'bg-emerald-100 text-emerald-800',
    confirmed: 'bg-blue-100 text-blue-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getConditionLabel = (condition) => {
  const labels = {
    mint: 'Mint',
    near_mint: 'Near Mint',
    excellent: 'Eccellente',
    good: 'Buona',
    light_played: 'Leggermente giocata',
    played: 'Giocata',
    poor: 'Scarsa'
  }
  return labels[condition] || condition
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('it-IT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
}

// Lifecycle
onMounted(() => {
  loadOrders()
})
</script>