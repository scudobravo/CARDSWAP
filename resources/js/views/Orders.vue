<template>
  <div class="orders-container">
    <div class="max-w-6xl mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">I Miei Ordini</h1>
        <p class="text-gray-600 mt-2">Gestisci i tuoi ordini e traccia le spedizioni</p>
      </div>

      <!-- Filtri -->
      <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Stato</label>
            <select v-model="filters.status" @change="loadOrders"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
              <option value="">Tutti gli stati</option>
              <option value="pending">In attesa</option>
              <option value="confirmed">Confermato</option>
              <option value="shipped">Spedito</option>
              <option value="delivered">Consegnato</option>
              <option value="cancelled">Cancellato</option>
              <option value="refunded">Rimborsato</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data da</label>
            <input v-model="filters.date_from" type="date" @change="loadOrders"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Data a</label>
            <input v-model="filters.date_to" type="date" @change="loadOrders"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
          </div>
          
          <div class="flex items-end">
            <button @click="resetFilters"
                    class="w-full px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
              Reset filtri
            </button>
          </div>
        </div>
      </div>

      <!-- Lista ordini -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        <p class="text-gray-500 mt-2">Caricamento ordini...</p>
      </div>

      <div v-else-if="orders.length === 0" class="text-center py-12">
        <div class="text-gray-400 mb-4">
          <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Nessun ordine trovato</h3>
        <p class="text-gray-500 mb-6">Non hai ancora effettuato nessun ordine</p>
        <router-link to="/" 
                     class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          Inizia a comprare
        </router-link>
      </div>

      <div v-else class="space-y-6">
        <div v-for="order in orders" :key="order.id" 
             class="bg-white border border-gray-200 rounded-lg overflow-hidden">
          <!-- Header ordine -->
          <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-semibold text-gray-900">
                  Ordine #{{ order.order_number }}
                </h3>
                <p class="text-sm text-gray-500">
                  {{ formatDate(order.created_at) }}
                </p>
              </div>
              <div class="text-right">
                <span :class="getStatusBadgeClass(order.status)" 
                      class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                  {{ getStatusLabel(order.status) }}
                </span>
                <p class="text-lg font-semibold text-gray-900 mt-1">
                  €{{ order.total_amount.toFixed(2) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Contenuto ordine -->
          <div class="p-6">
            <!-- Articoli -->
            <div class="space-y-4 mb-6">
              <h4 class="font-medium text-gray-900">Articoli ordinati</h4>
              <div class="space-y-3">
                <div v-for="item in order.order_items" :key="item.id" 
                     class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                  <div class="w-12 h-12 bg-gray-200 rounded-lg flex-shrink-0">
                    <img v-if="item.card_listing?.images && item.card_listing.images[0]" 
                         :src="item.card_listing.images[0]" 
                         :alt="item.card_listing.card_model?.name || 'Prodotto'"
                         class="w-full h-full object-cover rounded-lg">
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                    </div>
                  </div>
                  
                  <div class="flex-1">
                    <h5 class="font-medium text-gray-900">
                      {{ item.card_listing?.card_model?.name || 'Prodotto' }}
                    </h5>
                    <p class="text-sm text-gray-500">
                      Condizione: {{ getConditionLabel(item.condition) }}
                    </p>
                    <p class="text-sm text-gray-500">
                      Venditore: {{ item.card_listing?.seller?.name || 'N/A' }}
                    </p>
                  </div>
                  
                  <div class="text-right">
                    <p class="font-medium text-gray-900">
                      €{{ item.total_price.toFixed(2) }}
                    </p>
                    <p class="text-sm text-gray-500">
                      {{ item.quantity }}x €{{ item.unit_price.toFixed(2) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Informazioni spedizione -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
              <div>
                <h4 class="font-medium text-gray-900 mb-2">Indirizzo di spedizione</h4>
                <div class="text-sm text-gray-600">
                  <p>{{ order.shipping_address.first_name }} {{ order.shipping_address.last_name }}</p>
                  <p>{{ order.shipping_address.address_line_1 }}</p>
                  <p v-if="order.shipping_address.address_line_2">{{ order.shipping_address.address_line_2 }}</p>
                  <p>{{ order.shipping_address.postal_code }} {{ order.shipping_address.city }}</p>
                  <p>{{ order.shipping_address.country }}</p>
                </div>
              </div>
              
              <div v-if="order.tracking_number">
                <h4 class="font-medium text-gray-900 mb-2">Tracking</h4>
                <div class="text-sm text-gray-600">
                  <p class="font-medium">{{ order.tracking_number }}</p>
                  <p v-if="order.shipped_at">Spedito il {{ formatDate(order.shipped_at) }}</p>
                  <p v-if="order.delivered_at">Consegnato il {{ formatDate(order.delivered_at) }}</p>
                </div>
              </div>
            </div>

            <!-- Riepilogo costi -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6">
              <div class="space-y-2">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Subtotale</span>
                  <span class="text-gray-900">€{{ order.subtotal.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Spedizione</span>
                  <span class="text-gray-900">€{{ order.shipping_cost.toFixed(2) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                  <span class="text-gray-600">Tasse</span>
                  <span class="text-gray-900">€{{ order.tax_amount.toFixed(2) }}</span>
                </div>
                <div class="border-t border-gray-200 pt-2">
                  <div class="flex justify-between font-semibold">
                    <span class="text-gray-900">Totale</span>
                    <span class="text-gray-900">€{{ order.total_amount.toFixed(2) }}</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Azioni -->
            <div class="flex items-center justify-between">
              <div class="flex space-x-3">
                <button @click="viewOrderDetails(order)"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                  Dettagli
                </button>
                
                <button v-if="order.can_be_cancelled" @click="cancelOrder(order)"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                  Cancella
                </button>
                
                <button v-if="order.can_be_refunded" @click="requestRefund(order)"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                  Richiedi rimborso
                </button>
              </div>
              
              <div class="text-sm text-gray-500">
                <p>Pagato il {{ formatDate(order.paid_at) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginazione -->
      <div v-if="pagination && pagination.last_page > 1" class="mt-8">
        <nav class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Mostrando {{ pagination.from }} a {{ pagination.to }} di {{ pagination.total }} risultati
          </div>
          
          <div class="flex space-x-2">
            <button v-if="pagination.current_page > 1" @click="loadOrders(pagination.current_page - 1)"
                    class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
              Precedente
            </button>
            
            <button v-for="page in getPageNumbers()" :key="page"
                    @click="loadOrders(page)"
                    :class="page === pagination.current_page ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'"
                    class="px-3 py-2 border border-gray-300 rounded-lg transition-colors">
              {{ page }}
            </button>
            
            <button v-if="pagination.current_page < pagination.last_page" @click="loadOrders(pagination.current_page + 1)"
                    class="px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
              Successiva
            </button>
          </div>
        </nav>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

// Stato reattivo
const orders = ref([])
const loading = ref(false)
const pagination = ref(null)
const filters = ref({
  status: '',
  date_from: '',
  date_to: ''
})

// Metodi
const loadOrders = async (page = 1) => {
  loading.value = true
  
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      ...filters.value
    })
    
    const response = await fetch(`/api/orders?${params}`, {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      orders.value = data.data.data
      pagination.value = data.data
    }
  } catch (error) {
    console.error('Errore nel caricamento ordini:', error)
  } finally {
    loading.value = false
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

const viewOrderDetails = (order) => {
  // Implementa visualizzazione dettagli ordine
  console.log('Visualizza dettagli ordine:', order)
}

const cancelOrder = async (order) => {
  if (!confirm('Sei sicuro di voler cancellare questo ordine?')) return
  
  try {
    const response = await fetch(`/api/orders/${order.id}/cancel`, {
      method: 'PATCH',
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      await loadOrders()
    }
  } catch (error) {
    console.error('Errore nella cancellazione ordine:', error)
  }
}

const requestRefund = (order) => {
  // Implementa richiesta rimborso
  console.log('Richiedi rimborso per ordine:', order)
}

// Lifecycle
onMounted(() => {
  loadOrders()
})
</script>

<style scoped>
.orders-container {
  min-height: 100vh;
  background-color: #f9fafb;
}
</style>
