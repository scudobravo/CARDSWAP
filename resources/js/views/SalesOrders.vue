<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Ordini da Preparare
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Gestisci gli ordini ricevuti per le tue carte
      </p>
    </div>

    <!-- Filtri e Ricerca -->
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Filtro Stato -->
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
            Stato Ordine
          </label>
          <select 
            v-model="filters.status" 
            @change="loadOrders"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <option value="">Tutti gli stati</option>
            <option value="pending">In attesa</option>
            <option value="confirmed">Confermato</option>
            <option value="shipped">Spedito</option>
            <option value="delivered">Consegnato</option>
            <option value="cancelled">Cancellato</option>
          </select>
        </div>

        <!-- Filtro Data Da -->
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
            Data Da
          </label>
          <input 
            type="date" 
            v-model="filters.date_from" 
            @change="loadOrders"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>

        <!-- Filtro Data A -->
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
            Data A
          </label>
          <input 
            type="date" 
            v-model="filters.date_to" 
            @change="loadOrders"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>

        <!-- Pulsanti Azione -->
        <div class="flex items-end space-x-2">
          <button 
            @click="resetFilters"
            class="px-4 py-2 text-sm font-gill-sans-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            Reset
          </button>
          <button 
            @click="loadOrders"
            class="px-4 py-2 text-sm font-gill-sans-semibold text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary"
          >
            Cerca
          </button>
        </div>
      </div>
    </div>

    <!-- Statistiche Rapide -->
    <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ClockIcon class="h-8 w-8 text-yellow-500" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-gill-sans text-gray-500">In Attesa</p>
            <p class="text-2xl font-futura-bold text-gray-900">{{ stats.pending }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <TruckIcon class="h-8 w-8 text-blue-500" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-gill-sans text-gray-500">Spediti</p>
            <p class="text-2xl font-futura-bold text-gray-900">{{ stats.shipped }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-8 w-8 text-green-500" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-gill-sans text-gray-500">Consegnati</p>
            <p class="text-2xl font-futura-bold text-gray-900">{{ stats.delivered }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <CurrencyEuroIcon class="h-8 w-8 text-purple-500" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-gill-sans text-gray-500">Totale Vendite</p>
            <p class="text-2xl font-futura-bold text-gray-900">€{{ stats.total_sales.toFixed(2) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Lista Ordini -->
    <div class="bg-white rounded-lg border border-gray-200">
      <!-- Header Tabella -->
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-futura-bold text-gray-900">Ordini Ricevuti</h3>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="p-8 text-center">
        <div class="inline-flex items-center">
          <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          Caricamento ordini...
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="p-8 text-center">
        <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-red-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Errore nel caricamento</h3>
        <p class="mt-1 text-sm text-gray-500">{{ error }}</p>
        <button 
          @click="loadOrders"
          class="mt-4 px-4 py-2 text-sm font-gill-sans-semibold text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary"
        >
          Riprova
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="orders.length === 0" class="p-8 text-center">
        <DocumentDuplicateIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Nessun ordine</h3>
        <p class="mt-1 text-sm text-gray-500">Gli ordini per le tue carte appariranno qui.</p>
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
                    {{ formatDate(order.created_at) }} • {{ order.buyer?.name || 'Cliente' }}
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
                  <span>{{ order.orderItems?.length || 0 }} articoli</span>
                  <span>•</span>
                  <span class="font-gill-sans-semibold text-gray-900">€{{ order.total_amount }}</span>
                </div>
                
                <!-- Lista Prodotti -->
                <div v-if="order.orderItems" class="mt-2 space-y-1">
                  <div 
                    v-for="item in order.orderItems.slice(0, 2)" 
                    :key="item.id"
                    class="text-sm text-gray-600"
                  >
                    {{ item.cardListing?.cardModel?.name || 'Prodotto' }} 
                    <span class="text-gray-400">({{ getConditionLabel(item.condition) }})</span>
                    <span class="text-gray-400">x{{ item.quantity }}</span>
                  </div>
                  <div v-if="order.orderItems.length > 2" class="text-sm text-gray-400">
                    +{{ order.orderItems.length - 2 }} altri articoli
                  </div>
                </div>
              </div>
            </div>

            <!-- Azioni -->
            <div class="flex items-center space-x-3">
              <!-- Tracking -->
              <div v-if="order.tracking_number" class="text-sm text-gray-600">
                <p class="font-gill-sans-semibold">Tracking:</p>
                <p class="font-mono">{{ order.tracking_number }}</p>
              </div>

              <!-- Pulsanti Azione -->
              <div class="flex space-x-2">
                <button 
                  @click="viewOrderDetails(order)"
                  class="px-3 py-1 text-sm font-gill-sans-semibold text-primary bg-primary-light border border-primary rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary"
                >
                  Dettagli
                </button>
                
                <button 
                  v-if="canUpdateStatus(order.status)"
                  @click="openStatusModal(order)"
                  class="px-3 py-1 text-sm font-gill-sans-semibold text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary"
                >
                  Aggiorna
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginazione -->
      <div v-if="pagination && pagination.last_page > 1" class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Mostrando {{ pagination.from }} a {{ pagination.to }} di {{ pagination.total }} risultati
          </div>
          <div class="flex space-x-2">
            <button 
              @click="loadOrders(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
              class="px-3 py-1 text-sm font-gill-sans-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Precedente
            </button>
            <button 
              v-for="page in getPageNumbers()" 
              :key="page"
              @click="loadOrders(page)"
              :class="[
                'px-3 py-1 text-sm font-gill-sans-semibold rounded-md',
                page === pagination.current_page 
                  ? 'text-white bg-primary border border-transparent' 
                  : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'
              ]"
            >
              {{ page }}
            </button>
            <button 
              @click="loadOrders(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.last_page"
              class="px-3 py-1 text-sm font-gill-sans-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Successiva
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Dettagli Ordine -->
    <OrderDetailsModal 
      v-if="showOrderModal" 
      :order="selectedOrder"
      @close="closeOrderModal"
      @status-updated="handleStatusUpdated"
    />

    <!-- Modal Aggiorna Stato -->
    <UpdateStatusModal 
      v-if="showStatusModal" 
      :order="selectedOrder"
      @close="closeStatusModal"
      @status-updated="handleStatusUpdated"
    />
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import OrderDetailsModal from '@/components/orders/OrderDetailsModal.vue'
import UpdateStatusModal from '@/components/orders/UpdateStatusModal.vue'
import { 
  DocumentDuplicateIcon, 
  ClockIcon, 
  TruckIcon, 
  CheckCircleIcon, 
  CurrencyEuroIcon,
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline'

// Reactive data
const orders = ref([])
const loading = ref(false)
const error = ref(null)
const pagination = ref(null)
const showOrderModal = ref(false)
const showStatusModal = ref(false)
const selectedOrder = ref(null)

// Filtri
const filters = ref({
  status: '',
  date_from: '',
  date_to: ''
})

// Statistiche
const stats = ref({
  pending: 0,
  shipped: 0,
  delivered: 0,
  total_sales: 0
})

// Metodi
const loadOrders = async (page = 1) => {
  loading.value = true
  error.value = null
  
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      ...filters.value
    })
    
    const response = await fetch(`/api/orders/seller?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      orders.value = data.data.data || data.data || []
      pagination.value = data.data
      
      // Carica statistiche dal server
      await loadStatistics()
    } else {
      throw new Error(`Errore HTTP: ${response.status}`)
    }
  } catch (err) {
    console.error('Errore nel caricamento ordini:', err)
    error.value = err.message
    orders.value = []
  } finally {
    loading.value = false
  }
}

const loadStatistics = async () => {
  try {
    const response = await fetch('/api/orders/seller/statistics', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      stats.value = {
        pending: data.data.pending || 0,
        shipped: data.data.shipped || 0,
        delivered: data.data.delivered || 0,
        total_sales: data.data.total_sales || 0
      }
    }
  } catch (err) {
    console.error('Errore nel caricamento statistiche:', err)
    // Fallback alle statistiche calcolate localmente
    calculateStats()
  }
}

const calculateStats = () => {
  stats.value = {
    pending: orders.value.filter(o => o.status === 'pending').length,
    shipped: orders.value.filter(o => o.status === 'shipped').length,
    delivered: orders.value.filter(o => o.status === 'delivered').length,
    total_sales: orders.value.reduce((sum, order) => sum + parseFloat(order.total_amount || 0), 0)
  }
}

const resetFilters = () => {
  filters.value = {
    status: '',
    date_from: '',
    date_to: ''
  }
  loadOrders()
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'In attesa',
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
  return new Date(dateString).toLocaleDateString('it-IT')
}

const getPageNumbers = () => {
  if (!pagination.value) return []
  
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []
  
  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i)
  }
  
  return pages
}

const canUpdateStatus = (status) => {
  return ['pending', 'confirmed', 'shipped'].includes(status)
}

const viewOrderDetails = (order) => {
  selectedOrder.value = order
  showOrderModal.value = true
}

const closeOrderModal = () => {
  showOrderModal.value = false
  selectedOrder.value = null
}

const openStatusModal = (order) => {
  selectedOrder.value = order
  showStatusModal.value = true
}

const closeStatusModal = () => {
  showStatusModal.value = false
  selectedOrder.value = null
}

const handleStatusUpdated = () => {
  loadOrders()
  closeOrderModal()
  closeStatusModal()
}

// Lifecycle
onMounted(async () => {
  await loadOrders()
  await loadStatistics()
})
</script>

<style scoped>
.orders-container {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>