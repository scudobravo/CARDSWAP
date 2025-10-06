<template>
  <div class="availability-checkout">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg p-6 mb-6">
      <h2 class="text-xl font-semibold text-gray-900 mb-2">Checkout</h2>
      <p class="text-sm text-gray-600">Verifica disponibilità e completa l'acquisto</p>
    </div>

    <!-- Lista prodotti -->
    <div class="space-y-4 mb-6">
      <div 
        v-for="(item, index) in items" 
        :key="item.listing_id"
        class="bg-white border rounded-lg p-4"
        :class="{ 'border-red-300 bg-red-50': !item.available }"
      >
        <div class="flex items-start space-x-4">
          <!-- Immagine -->
          <img 
            :src="item.cardModel?.image_url || '/images/placeholder-card.jpg'" 
            :alt="item.cardModel?.name"
            class="w-16 h-20 object-cover rounded"
          />
          
          <!-- Dettagli -->
          <div class="flex-1 min-w-0">
            <h3 class="text-lg font-medium text-gray-900 truncate">
              {{ item.cardModel?.name }}
            </h3>
            <p class="text-sm text-gray-600">
              {{ item.cardModel?.set_name }} {{ item.cardModel?.year }}
            </p>
            <p class="text-sm text-gray-500">
              Condizione: {{ item.condition }} | Quantità: {{ item.quantity }}
            </p>
            <p class="text-lg font-semibold text-primary">
              €{{ item.price }}
            </p>
          </div>
          
          <!-- Stato disponibilità -->
          <div class="flex-shrink-0">
            <AvailabilityStatus 
              :listing-id="item.listing_id"
              :quantity="item.quantity"
              :show-actions="false"
              :auto-refresh="true"
              @status-changed="updateItemStatus(index, $event)"
            />
          </div>
        </div>
        
        <!-- Messaggio di errore -->
        <div v-if="!item.available" class="mt-3 p-3 bg-red-100 border border-red-300 rounded text-sm text-red-700">
          <div class="flex items-center">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            {{ item.error || 'Non disponibile' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Riepilogo -->
    <div class="bg-white border rounded-lg p-6 mb-6">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Riepilogo</h3>
      
      <div class="space-y-2">
        <div class="flex justify-between text-sm">
          <span>Subtotale:</span>
          <span>€{{ subtotal.toFixed(2) }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span>Spedizione:</span>
          <span>€{{ shipping.toFixed(2) }}</span>
        </div>
        <div class="flex justify-between text-sm">
          <span>Tasse:</span>
          <span>€{{ taxes.toFixed(2) }}</span>
        </div>
        <div class="border-t pt-2">
          <div class="flex justify-between text-lg font-semibold">
            <span>Totale:</span>
            <span>€{{ total.toFixed(2) }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Azioni -->
    <div class="flex items-center justify-between">
      <button 
        @click="$emit('back')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
      >
        Indietro
      </button>
      
      <div class="flex items-center space-x-3">
        <!-- Pulsante blocca per checkout -->
        <button 
          v-if="!isLocked && allAvailable"
          @click="lockItems"
          :disabled="loading"
          class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <svg v-if="loading" class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          Blocca per Checkout (15 min)
        </button>
        
        <!-- Pulsante conferma -->
        <button 
          v-if="isLocked"
          @click="confirmPurchase"
          :disabled="loading"
          class="px-6 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Conferma Acquisto
        </button>
        
        <!-- Pulsante rilascia -->
        <button 
          v-if="isLocked"
          @click="releaseLock"
          :disabled="loading"
          class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Rilascia
        </button>
      </div>
    </div>

    <!-- Timer di lock -->
    <div v-if="isLocked && lockTimeRemaining" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2">
          <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
          </svg>
          <span class="text-sm font-medium text-blue-900">
            Blocco attivo per checkout
          </span>
        </div>
        <div class="text-sm text-blue-700">
          Tempo rimanente: {{ lockTimeRemaining }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import AvailabilityStatus from '../availability/AvailabilityStatus.vue'
import availabilityService from '../../services/AvailabilityService.js'

// Props
const props = defineProps({
  items: {
    type: Array,
    required: true
  },
  shipping: {
    type: Number,
    default: 0
  },
  taxes: {
    type: Number,
    default: 0
  }
})

// Emits
const emit = defineEmits(['back', 'purchase-confirmed', 'lock-created', 'lock-released'])

// State
const loading = ref(false)
const isLocked = ref(false)
const lockId = ref(null)
const lockTimeRemaining = ref('')
const timer = ref(null)

// Computed
const allAvailable = computed(() => {
  return props.items.every(item => item.available)
})

const subtotal = computed(() => {
  return props.items.reduce((total, item) => total + (item.price * item.quantity), 0)
})

const total = computed(() => {
  return subtotal.value + props.shipping + props.taxes
})

// Methods
const updateItemStatus = (index, status) => {
  props.items[index].status = status.status
  props.items[index].available = status.available
  props.items[index].quantity_available = status.quantity_available
}

const lockItems = async () => {
  loading.value = true
  try {
    const items = props.items.map(item => ({
      listing_id: item.listing_id,
      quantity: item.quantity
    }))

    const result = await availabilityService.lock(items, 15)
    
    if (result.success) {
      isLocked.value = true
      lockId.value = 'checkout_lock'
      startLockTimer(result.data.locked_until)
      emit('lock-created', result.data)
    } else {
      alert('Errore nel blocco: ' + result.message)
    }
  } catch (error) {
    console.error('Errore nel blocco:', error)
    alert('Errore nel blocco')
  } finally {
    loading.value = false
  }
}

const releaseLock = async () => {
  loading.value = true
  try {
    // Rilascia tutti i lock
    const promises = props.items.map(item => 
      availabilityService.release(item.listing_id)
    )
    
    const results = await Promise.all(promises)
    const allReleased = results.every(result => result.success)
    
    if (allReleased) {
      isLocked.value = false
      lockId.value = null
      stopLockTimer()
      emit('lock-released')
    } else {
      alert('Errore nel rilascio del lock')
    }
  } catch (error) {
    console.error('Errore nel rilascio:', error)
    alert('Errore nel rilascio del lock')
  } finally {
    loading.value = false
  }
}

const startLockTimer = (until) => {
  stopLockTimer()
  
  const updateTimer = () => {
    const now = new Date()
    const untilDate = new Date(until)
    const diff = untilDate - now

    if (diff <= 0) {
      lockTimeRemaining.value = 'Scaduto'
      isLocked.value = false
      lockId.value = null
      stopLockTimer()
      return
    }

    const minutes = Math.floor(diff / 60000)
    const seconds = Math.floor((diff % 60000) / 1000)
    lockTimeRemaining.value = `${minutes}m ${seconds}s`
  }

  updateTimer()
  timer.value = setInterval(updateTimer, 1000)
}

const stopLockTimer = () => {
  if (timer.value) {
    clearInterval(timer.value)
    timer.value = null
  }
  lockTimeRemaining.value = ''
}

// Lifecycle
onMounted(() => {
  // Avvia il servizio di monitoraggio
  availabilityService.startRealTimeMonitoring()
})

onUnmounted(() => {
  stopLockTimer()
})
</script>
