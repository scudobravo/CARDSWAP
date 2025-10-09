<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Feedback Ricevuti
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Visualizza i feedback ricevuti dai tuoi acquirenti
      </p>
    </div>

    <!-- Filtri -->
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
      <div class="flex flex-wrap items-center gap-4">
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
            Rating
          </label>
          <select 
            v-model="filters.rating" 
            @change="loadFeedbacks"
            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <option value="">Tutti i rating</option>
            <option value="5">5 stelle</option>
            <option value="4">4 stelle</option>
            <option value="3">3 stelle</option>
            <option value="2">2 stelle</option>
            <option value="1">1 stella</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
            Periodo
          </label>
          <select 
            v-model="filters.period" 
            @change="loadFeedbacks"
            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <option value="">Tutti i periodi</option>
            <option value="7">Ultimi 7 giorni</option>
            <option value="30">Ultimi 30 giorni</option>
            <option value="90">Ultimi 3 mesi</option>
            <option value="365">Ultimo anno</option>
          </select>
        </div>

        <div class="flex items-end">
          <button 
            @click="resetFilters"
            class="px-4 py-2 text-sm font-gill-sans-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
          >
            Reset
          </button>
        </div>
      </div>
    </div>

    <!-- Statistiche Feedback -->
    <div v-if="sellerStats" class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <StarIcon class="h-8 w-8 text-yellow-500" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-gill-sans text-gray-500">Rating Medio</p>
            <p class="text-2xl font-futura-bold text-gray-900">{{ sellerStats.average_rating.toFixed(1) }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ChatBubbleLeftRightIcon class="h-8 w-8 text-blue-500" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-gill-sans text-gray-500">Feedback Totali</p>
            <p class="text-2xl font-futura-bold text-gray-900">{{ sellerStats.total_feedbacks }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <StarIcon class="h-8 w-8 text-green-500" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-gill-sans text-gray-500">5 Stelle</p>
            <p class="text-2xl font-futura-bold text-gray-900">{{ sellerStats.rating_breakdown[5] || 0 }}</p>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex items-center">
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="h-8 w-8 text-red-500" />
          </div>
          <div class="ml-3">
            <p class="text-sm font-gill-sans text-gray-500">1-2 Stelle</p>
            <p class="text-2xl font-futura-bold text-gray-900">{{ (sellerStats.rating_breakdown[1] || 0) + (sellerStats.rating_breakdown[2] || 0) }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-flex items-center">
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Caricamento feedback...
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
      <div class="flex">
        <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
        <div class="ml-3">
          <h3 class="text-sm font-gill-sans-semibold text-red-800">Errore nel caricamento</h3>
          <p class="mt-1 text-sm text-red-700">{{ error }}</p>
          <button 
            @click="loadFeedbacks"
            class="mt-2 px-3 py-1 text-sm font-gill-sans-semibold text-red-800 bg-red-100 border border-red-300 rounded-md hover:bg-red-200"
          >
            Riprova
          </button>
        </div>
      </div>
    </div>

    <!-- Lista Feedback -->
    <div v-else-if="feedbacks.length > 0" class="space-y-4">
      <div 
        v-for="feedback in feedbacks" 
        :key="feedback.id"
        class="bg-white rounded-lg border border-gray-200 p-6"
      >
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <!-- Header Feedback -->
            <div class="flex items-center space-x-4 mb-3">
              <div class="flex items-center space-x-1">
                <div v-for="i in 5" :key="i" class="flex-shrink-0">
                  <StarIcon 
                    :class="[
                      'h-5 w-5',
                      i <= feedback.rating ? 'text-yellow-400' : 'text-gray-300'
                    ]" 
                    fill="currentColor"
                  />
                </div>
              </div>
              <span class="text-sm font-gill-sans-semibold text-gray-900">
                {{ feedback.buyer?.name || 'Cliente' }}
              </span>
              <span class="text-sm text-gray-500">
                {{ formatDate(feedback.created_at) }}
              </span>
            </div>

            <!-- Commento -->
            <p v-if="feedback.comment" class="text-gray-700 mb-4">
              "{{ feedback.comment }}"
            </p>

            <!-- Info Ordine -->
            <div class="bg-gray-50 rounded-lg p-3 mb-4">
              <div class="flex items-center justify-between text-sm">
                <span class="font-gill-sans-semibold text-gray-900">
                  Ordine #{{ feedback.order?.order_number }}
                </span>
                <span class="text-gray-500">
                  {{ formatDate(feedback.order?.created_at) }}
                </span>
              </div>
              <div v-if="feedback.order?.orderItems" class="mt-2 text-sm text-gray-600">
                <p>{{ feedback.order.orderItems.length }} articoli • €{{ feedback.order.total_amount }}</p>
              </div>
            </div>

            <!-- Risposta Venditore -->
            <div v-if="feedback.seller_response" class="bg-blue-50 rounded-lg p-3 mb-4">
              <div class="flex items-start space-x-2">
                <div class="flex-shrink-0">
                  <ChatBubbleLeftRightIcon class="h-5 w-5 text-blue-500 mt-0.5" />
                </div>
                <div>
                  <p class="text-sm font-gill-sans-semibold text-blue-900">La tua risposta:</p>
                  <p class="text-sm text-blue-800">{{ feedback.seller_response }}</p>
                  <p class="text-xs text-blue-600 mt-1">
                    {{ formatDate(feedback.seller_response_at) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Azioni -->
            <div class="flex items-center space-x-3">
              <button 
                v-if="!feedback.seller_response"
                @click="openResponseModal(feedback)"
                class="px-3 py-1 text-sm font-gill-sans-semibold text-primary bg-primary-light border border-primary rounded-md hover:bg-primary hover:text-white focus:outline-none focus:ring-2 focus:ring-primary"
              >
                Rispondi
              </button>
              
              <button 
                v-else
                @click="openResponseModal(feedback)"
                class="px-3 py-1 text-sm font-gill-sans-semibold text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500"
              >
                Modifica Risposta
              </button>

              <button 
                @click="viewOrderDetails(feedback.order)"
                class="px-3 py-1 text-sm font-gill-sans-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500"
              >
                Visualizza Ordine
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginazione -->
      <div v-if="pagination && pagination.last_page > 1" class="mt-6">
        <div class="flex items-center justify-between">
          <div class="text-sm text-gray-700">
            Mostrando {{ pagination.from }} a {{ pagination.to }} di {{ pagination.total }} risultati
          </div>
          <div class="flex space-x-2">
            <button 
              @click="loadFeedbacks(pagination.current_page - 1)"
              :disabled="pagination.current_page <= 1"
              class="px-3 py-1 text-sm font-gill-sans-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Precedente
            </button>
            <button 
              v-for="page in getPageNumbers()" 
              :key="page"
              @click="loadFeedbacks(page)"
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
              @click="loadFeedbacks(pagination.current_page + 1)"
              :disabled="pagination.current_page >= pagination.last_page"
              class="px-3 py-1 text-sm font-gill-sans-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Successiva
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="text-center py-12">
        <StarIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Nessun feedback</h3>
        <p class="mt-1 text-sm text-gray-500">I feedback dei tuoi acquirenti appariranno qui.</p>
      </div>
    </div>

    <!-- Modal Risposta Feedback -->
    <FeedbackResponseModal 
      v-if="showResponseModal" 
      :feedback="selectedFeedback"
      @close="closeResponseModal"
      @response-saved="handleResponseSaved"
    />

    <!-- Modal Dettagli Ordine -->
    <OrderDetailsModal 
      v-if="showOrderModal" 
      :order="selectedOrder"
      @close="closeOrderModal"
    />
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import FeedbackResponseModal from '@/components/feedback/FeedbackResponseModal.vue'
import OrderDetailsModal from '@/components/orders/OrderDetailsModal.vue'
import { 
  StarIcon, 
  ChatBubbleLeftRightIcon,
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline'

// Reactive data
const feedbacks = ref([])
const sellerStats = ref(null)
const loading = ref(false)
const error = ref(null)
const pagination = ref(null)
const showResponseModal = ref(false)
const showOrderModal = ref(false)
const selectedFeedback = ref(null)
const selectedOrder = ref(null)

// Filtri
const filters = ref({
  rating: '',
  period: ''
})

// Metodi
const loadFeedbacks = async (page = 1) => {
  loading.value = true
  error.value = null
  
  try {
    const params = new URLSearchParams({
      page: page.toString(),
      ...filters.value
    })
    
    const response = await fetch(`/api/sales/feedback?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      feedbacks.value = data.data.data || data.data || []
      pagination.value = data.data
      sellerStats.value = data.seller
    } else {
      throw new Error(`Errore HTTP: ${response.status}`)
    }
  } catch (err) {
    console.error('Errore nel caricamento feedback:', err)
    error.value = err.message
    feedbacks.value = []
  } finally {
    loading.value = false
  }
}

const resetFilters = () => {
  filters.value = {
    rating: '',
    period: ''
  }
  loadFeedbacks()
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('it-IT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  })
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

const openResponseModal = (feedback) => {
  selectedFeedback.value = feedback
  showResponseModal.value = true
}

const closeResponseModal = () => {
  showResponseModal.value = false
  selectedFeedback.value = null
}

const viewOrderDetails = (order) => {
  selectedOrder.value = order
  showOrderModal.value = true
}

const closeOrderModal = () => {
  showOrderModal.value = false
  selectedOrder.value = null
}

const handleResponseSaved = () => {
  loadFeedbacks()
  closeResponseModal()
}

// Lifecycle
onMounted(() => {
  loadFeedbacks()
})
</script>