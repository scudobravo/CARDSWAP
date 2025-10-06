<template>
  <div class="availability-status">
    <!-- Stato Disponibile -->
    <div v-if="status === 'available'" class="flex items-center space-x-2 text-green-600">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
      </svg>
      <span class="text-sm font-medium">Disponibile</span>
      <span class="text-xs text-gray-500">({{ quantity }} disponibili)</span>
    </div>

    <!-- Stato Bloccato -->
    <div v-else-if="status === 'locked'" class="flex items-center space-x-2 text-yellow-600">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
      </svg>
      <span class="text-sm font-medium">Bloccata</span>
      <span class="text-xs text-gray-500">({{ timeRemaining }})</span>
    </div>

    <!-- Stato Prenotato -->
    <div v-else-if="status === 'reserved'" class="flex items-center space-x-2 text-blue-600">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
      </svg>
      <span class="text-sm font-medium">Prenotata</span>
      <span class="text-xs text-gray-500">({{ timeRemaining }})</span>
    </div>

    <!-- Stato Venduto -->
    <div v-else-if="status === 'sold'" class="flex items-center space-x-2 text-gray-500">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
      </svg>
      <span class="text-sm font-medium">Venduta</span>
    </div>

    <!-- Stato Non Disponibile -->
    <div v-else class="flex items-center space-x-2 text-red-600">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
      </svg>
      <span class="text-sm font-medium">Non disponibile</span>
    </div>

    <!-- Pulsanti azione -->
    <div v-if="showActions && status === 'available'" class="mt-2">
      <button 
        @click="$emit('reserve')"
        :disabled="loading"
        class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg v-if="loading" class="w-3 h-3 mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <svg v-else class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        Prenota
      </button>
    </div>

    <div v-if="showActions && (status === 'locked' || status === 'reserved')" class="mt-2 space-x-2">
      <button 
        @click="$emit('extend')"
        :disabled="loading"
        class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-yellow-600 rounded hover:bg-yellow-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Estendi
      </button>
      <button 
        @click="$emit('release')"
        :disabled="loading"
        class="inline-flex items-center px-3 py-1 text-xs font-medium text-white bg-red-600 rounded hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        Rilascia
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import availabilityService from '../../services/AvailabilityService.js'

// Props
const props = defineProps({
  listingId: {
    type: Number,
    required: true
  },
  quantity: {
    type: Number,
    default: 1
  },
  showActions: {
    type: Boolean,
    default: false
  },
  autoRefresh: {
    type: Boolean,
    default: true
  },
  refreshInterval: {
    type: Number,
    default: 30000 // 30 secondi
  }
})

// Emits
const emit = defineEmits(['reserve', 'extend', 'release', 'status-changed'])

// State
const loading = ref(false)
const availability = ref(null)
const refreshTimer = ref(null)

// Computed
const status = computed(() => availability.value?.status || 'unavailable')
const timeRemaining = computed(() => {
  if (!availability.value) return ''
  
  const until = availability.value.locked_until || availability.value.reserved_until
  if (!until) return ''
  
  return availabilityService.formatTimeRemaining(until)
})

// Methods
const checkAvailability = async () => {
  if (loading.value) return
  
  loading.value = true
  try {
    const result = await availabilityService.checkSingle(props.listingId, props.quantity)
    if (result.success) {
      availability.value = result.data
      emit('status-changed', result.data)
    }
  } catch (error) {
    console.error('Errore nel controllo disponibilità:', error)
  } finally {
    loading.value = false
  }
}

const startAutoRefresh = () => {
  if (props.autoRefresh && !refreshTimer.value) {
    refreshTimer.value = setInterval(checkAvailability, props.refreshInterval)
  }
}

const stopAutoRefresh = () => {
  if (refreshTimer.value) {
    clearInterval(refreshTimer.value)
    refreshTimer.value = null
  }
}

// Lifecycle
onMounted(() => {
  checkAvailability()
  startAutoRefresh()
})

onUnmounted(() => {
  stopAutoRefresh()
})

// Watch per cambiamenti nelle props
watch(() => [props.listingId, props.quantity], () => {
  checkAvailability()
}, { immediate: false })
</script>
