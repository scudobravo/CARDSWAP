<template>
  <div class="bg-gray-50 min-h-screen">
    <div class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
      <!-- Header di successo -->
      <div class="text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
          <CheckCircleIcon class="h-8 w-8 text-green-600" />
        </div>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900">
          Ordine confermato!
        </h1>
        <p class="mt-2 text-lg text-gray-600">
          Grazie per il tuo acquisto. Riceverai una conferma via email a breve.
        </p>
      </div>

      <!-- Dettagli ordine -->
      <div v-if="order" class="mt-8">
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
          <!-- Header ordine -->
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-medium text-gray-900">
                  Ordine #{{ order.order_number }}
                </h2>
                <p class="text-sm text-gray-500">
                  Effettuato il {{ formatDate(order.created_at) }}
                </p>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium text-gray-900">
                  Totale: €{{ parseFloat(order.total_amount).toFixed(2) }}
                </p>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getStatusClass(order.status)">
                  {{ getStatusText(order.status) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Articoli ordinati -->
          <div class="px-6 py-4">
            <h3 class="text-sm font-medium text-gray-900 mb-4">Articoli ordinati</h3>
            <div class="space-y-4">
              <div v-for="item in orderItems" :key="item.id" 
                   class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                <img :src="item.image || '/images/placeholder-card.jpg'" 
                     :alt="item.name" 
                     class="h-16 w-16 object-cover rounded-md" />
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-medium text-gray-900 truncate">
                    {{ item.name }}
                  </h4>
                  <p class="text-sm text-gray-500">
                    Condizione: {{ item.condition }}
                  </p>
                  <p class="text-sm text-gray-500">
                    Venditore: {{ item.seller_name }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium text-gray-900">
                    €{{ (parseFloat(item.price) * item.quantity).toFixed(2) }}
                  </p>
                  <p class="text-sm text-gray-500">
                    {{ item.quantity }}x €{{ parseFloat(item.price).toFixed(2) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Indirizzo di spedizione -->
          <div v-if="order.shipping_address" class="px-6 py-4 border-t border-gray-200">
            <h3 class="text-sm font-medium text-gray-900 mb-2">Indirizzo di spedizione</h3>
            <div class="text-sm text-gray-600">
              <p>{{ order.shipping_address.first_name }} {{ order.shipping_address.last_name }}</p>
              <p>{{ order.shipping_address.address_line_1 }}</p>
              <p v-if="order.shipping_address.address_line_2">{{ order.shipping_address.address_line_2 }}</p>
              <p>{{ order.shipping_address.postal_code }} {{ order.shipping_address.city }}</p>
              <p>{{ order.shipping_address.country }}</p>
              <p v-if="order.shipping_address.phone">Tel: {{ order.shipping_address.phone }}</p>
            </div>
          </div>

          <!-- Riepilogo costi -->
          <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <dl class="space-y-2">
              <div class="flex justify-between text-sm">
                <dt class="text-gray-600">Subtotale</dt>
                <dd class="font-medium text-gray-900">€{{ parseFloat(order.subtotal).toFixed(2) }}</dd>
              </div>
              <div class="flex justify-between text-sm">
                <dt class="text-gray-600">Spedizione</dt>
                <dd class="font-medium text-gray-900">€{{ parseFloat(order.shipping_cost).toFixed(2) }}</dd>
              </div>
              <div class="flex justify-between text-sm">
                <dt class="text-gray-600">IVA</dt>
                <dd class="font-medium text-gray-900">€{{ parseFloat(order.tax_amount).toFixed(2) }}</dd>
              </div>
              <div class="flex justify-between text-lg font-medium border-t border-gray-300 pt-2">
                <dt class="text-gray-900">Totale</dt>
                <dd class="text-gray-900">€{{ parseFloat(order.total_amount).toFixed(2) }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Azioni -->
        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
          <button @click="goToOrders" 
                  class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            Visualizza i miei ordini
          </button>
          <button @click="goToHome" 
                  class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Torna alla home
          </button>
        </div>
      </div>

      <!-- Stato di caricamento -->
      <div v-else-if="loading" class="mt-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Caricamento dettagli ordine...</p>
      </div>

      <!-- Errore -->
      <div v-else-if="error" class="mt-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
          <ExclamationTriangleIcon class="h-8 w-8 text-red-600" />
        </div>
        <h2 class="mt-4 text-xl font-medium text-gray-900">Errore</h2>
        <p class="mt-2 text-gray-600">{{ error }}</p>
        <button @click="goToHome" 
                class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
          Torna alla home
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const route = useRoute()
const router = useRouter()

const order = ref(null)
const orderItems = ref([])
const loading = ref(true)
const error = ref(null)

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('it-IT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusClass = (status) => {
  switch (status) {
    case 'paid':
      return 'bg-green-100 text-green-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'shipped':
      return 'bg-blue-100 text-blue-800'
    case 'delivered':
      return 'bg-green-100 text-green-800'
    case 'cancelled':
      return 'bg-red-100 text-red-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const getStatusText = (status) => {
  switch (status) {
    case 'paid':
      return 'Pagato'
    case 'pending':
      return 'In attesa'
    case 'shipped':
      return 'Spedito'
    case 'delivered':
      return 'Consegnato'
    case 'cancelled':
      return 'Annullato'
    default:
      return status
  }
}

const loadOrderDetails = async () => {
  try {
    const orderId = route.params.id
    if (!orderId) {
      throw new Error('ID ordine non fornito')
    }

    const response = await axios.get(`/api/orders/${orderId}`)
    if (response.data.success) {
      order.value = response.data.order
      orderItems.value = response.data.order_items || []
    } else {
      throw new Error(response.data.message || 'Errore nel caricamento dell\'ordine')
    }
  } catch (err) {
    console.error('Errore nel caricamento ordine:', err)
    error.value = err.response?.data?.message || err.message || 'Errore nel caricamento dell\'ordine'
  } finally {
    loading.value = false
  }
}

const goToOrders = () => {
  router.push('/orders')
}

const goToHome = () => {
  router.push('/')
}

onMounted(() => {
  loadOrderDetails()
})
</script>
