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
            <p class="text-2xl font-futura-bold text-gray-900">€{{ formatPriceItaliana(stats.total_sales) }}</p>
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
                  <span>{{ getOrderItems(order)?.length || 0 }} articoli</span>
                  <span>•</span>
                  <span class="font-gill-sans-semibold text-gray-900">€{{ formatPriceItaliana(order.total_amount) }}</span>
                </div>
                
                <!-- Lista Prodotti -->
                <div v-if="getOrderItems(order) && getOrderItems(order).length > 0" class="mt-2 space-y-1">
                  <div 
                    v-for="item in getOrderItemsPreview(order)" 
                    :key="item.id || `item-${item.card_listing_id || item.cardListing?.id || Math.random()}`"
                    class="text-sm text-gray-600"
                  >
                    {{ getItemNameFromOrderItem(item) }} 
                    <span class="text-gray-400">({{ getConditionLabel(item.condition) }})</span>
                    <span class="text-gray-400">x{{ item.quantity || 1 }}</span>
                  </div>
                  <div v-if="getOrderItems(order).length > 2" class="text-sm text-gray-400">
                    +{{ getOrderItems(order).length - 2 }} altri articoli
                  </div>
                </div>
                <div v-else class="mt-2 text-sm text-gray-400 italic">
                  Nessun prodotto visibile
                </div>
              </div>
            </div>

            <!-- Azioni -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mt-4 sm:mt-0">
              <!-- Tracking -->
              <div v-if="order.tracking_number" class="text-sm text-gray-600">
                <p class="font-gill-sans-semibold">Tracking:</p>
                <p class="font-mono">{{ order.tracking_number }}</p>
              </div>

              <!-- Pulsanti Azione -->
              <div class="flex flex-wrap gap-2">
                <button 
                  @click="viewOrderDetails(order)"
                  class="px-3 py-1.5 text-sm font-gill-sans-semibold text-primary bg-primary-light border border-primary rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary whitespace-nowrap"
                >
                  Dettagli
                </button>
                
                <!-- Pulsante Crea Etichetta Shippo (solo per ordini confermati/paid_funds_held senza tracking) -->
                <button 
                  v-if="(order.status === 'confirmed' || order.status === 'paid_funds_held' || order.status === 'label_created') && !order.tracking_number"
                  @click="createShippoLabel(order)"
                  :disabled="creatingLabel === order.id"
                  class="px-3 py-1.5 text-sm font-gill-sans-semibold text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed whitespace-nowrap"
                >
                  <span v-if="creatingLabel === order.id" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Creando...
                  </span>
                  <span v-else>Crea Etichetta</span>
                </button>
                
                <button 
                  v-if="canUpdateStatus(order.status)"
                  @click="openStatusModal(order)"
                  class="px-3 py-1.5 text-sm font-gill-sans-semibold text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary whitespace-nowrap"
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
import { formatPriceItaliana } from '../utils/priceFormatter'
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
const creatingLabel = ref(null)

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

// Helper per gestire sia orderItems che order_items (snake_case vs camelCase)
const getOrderItems = (order) => {
  return order.orderItems || order.order_items || []
}

// Helper per ottenere preview dei prodotti (primi 2)
const getOrderItemsPreview = (order) => {
  const items = getOrderItems(order)
  return items.slice(0, 2)
}

// Helper per ottenere il nome del prodotto da un order item
const getItemNameFromOrderItem = (item) => {
  return item.cardListing?.cardModel?.name || 
         item.card_listing?.card_model?.name || 
         item.name || 
         'Prodotto'
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
  // Con il nuovo sistema, gli stati vengono gestiti automaticamente da Shippo
  // Il venditore può solo creare etichette, non aggiornare manualmente lo stato
  return false // Disabilitato - gli stati vengono gestiti da Shippo webhook
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

const createShippoLabel = async (order) => {
  if (!confirm('Vuoi creare l\'etichetta di spedizione Shippo per questo ordine?')) {
    return
  }

  creatingLabel.value = order.id
  error.value = null

  try {
    // Prima calcola le tariffe per questo ordine
    const shippingAddress = order.shipping_address
    if (!shippingAddress) {
      throw new Error('Indirizzo di spedizione non trovato')
    }

    // Recupera l'indirizzo del venditore dal database
    // Prima prova con defaultAddress, poi con il primo indirizzo disponibile
    let sellerAddress = null
    
    if (order.seller?.default_address) {
      sellerAddress = order.seller.default_address
    } else if (order.seller?.addresses && order.seller.addresses.length > 0) {
      sellerAddress = order.seller.addresses[0]
    } else {
      // Fallback: usa i campi diretti del User se disponibili
      if (order.seller?.address || order.seller?.city) {
        sellerAddress = {
          address_line_1: order.seller.address || '',
          city: order.seller.city || '',
          state_province: order.seller.state_province || '',
          postal_code: order.seller.postal_code || '',
          country: order.seller.country || 'IT'
        }
      }
    }

    if (!sellerAddress) {
      throw new Error('Indirizzo del venditore non trovato. Configura un indirizzo nel tuo profilo prima di creare etichette di spedizione.')
    }

    // Prepara i dati del venditore per Shippo
    const seller = {
      id: order.seller_id,
      name: order.seller?.name || 'Venditore',
      address: {
        street1: sellerAddress.address_line_1 || sellerAddress.address || '',
        street2: sellerAddress.address_line_2 || '',
        city: sellerAddress.city || '',
        state: sellerAddress.state_province || sellerAddress.state || '',
        zip: sellerAddress.postal_code || sellerAddress.zip || '',
        country: sellerAddress.country || 'IT',
        phone: sellerAddress.phone || order.seller?.phone || ''
      }
    }

    // Calcola le tariffe
    const ratesResponse = await fetch('/api/shipping/calculate-rates', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        sellers: [seller],
        shipping_address: {
          name: `${shippingAddress.first_name} ${shippingAddress.last_name}`,
          street1: shippingAddress.address_line_1,
          city: shippingAddress.city,
          state: shippingAddress.state_province || shippingAddress.region,
          zip: shippingAddress.postal_code,
          country: shippingAddress.country
        }
      })
    })

    if (!ratesResponse.ok) {
      throw new Error('Errore nel calcolo delle tariffe')
    }

    const ratesData = await ratesResponse.json()
    const sellerRates = ratesData.data?.[order.seller_id]?.rates

    if (!sellerRates || sellerRates.length === 0) {
      throw new Error('Nessuna tariffa disponibile per questo ordine')
    }

    // Usa la prima tariffa disponibile (o quella più economica)
    const selectedRate = sellerRates[0]

    // Acquista l'etichetta
    const labelResponse = await fetch('/api/shipping/purchase-label', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        rate_object_id: selectedRate.object_id,
        order_id: order.id,
        seller_id: order.seller_id
      })
    })

    if (!labelResponse.ok) {
      const errorData = await labelResponse.json()
      throw new Error(errorData.message || 'Errore nella creazione dell\'etichetta')
    }

    const labelData = await labelResponse.json()

    if (labelData.success) {
      // Aggiorna l'ordine con il tracking number
      await fetch(`/api/orders/${order.id}/status`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`,
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          status: 'shipped',
          tracking_number: labelData.data.tracking_number
        })
      })

      alert(`Etichetta creata con successo!\nTracking: ${labelData.data.tracking_number}\n\nPuoi scaricare l'etichetta dal link fornito.`)
      loadOrders()
    } else {
      throw new Error(labelData.message || 'Errore nella creazione dell\'etichetta')
    }
  } catch (err) {
    console.error('Errore creazione etichetta Shippo:', err)
    error.value = err.message
    alert(`Errore: ${err.message}`)
  } finally {
    creatingLabel.value = null
  }
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