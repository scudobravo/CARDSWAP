<template>
  <div class="shipping-zone-selector">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-900">Destinazioni</h2>
      <button 
        @click="handleDone"
        class="text-blue-600 font-medium hover:text-blue-700 transition-colors"
      >
        Fine
      </button>
    </div>

    <!-- Description -->
    <p class="text-gray-600 mb-6">
      Allarga la tua clientela rendendo il tuo oggetto disponibile agli acquirenti di tutto il mondo.
    </p>

    <!-- Shipping Options -->
    <div class="space-y-4">
      <!-- Worldwide Option -->
      <div class="flex items-start space-x-3">
        <input
          type="radio"
          id="worldwide"
          v-model="selectedOption"
          value="worldwide"
          class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
        />
        <label for="worldwide" class="text-sm font-medium text-gray-900 cursor-pointer">
          Tutto il mondo
        </label>
      </div>

      <!-- Custom Locations Option -->
      <div class="flex items-start space-x-3">
        <input
          type="radio"
          id="custom"
          v-model="selectedOption"
          value="custom"
          class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
        />
        <label for="custom" class="text-sm font-medium text-gray-900 cursor-pointer">
          Scegli località personalizzate
        </label>
      </div>

      <!-- Custom Locations List -->
      <div v-if="selectedOption === 'custom'" class="ml-7 space-y-3">
        <div 
          v-for="zone in shippingZones" 
          :key="zone.id"
          class="flex items-center space-x-3"
        >
          <input
            type="checkbox"
            :id="`zone-${zone.id}`"
            v-model="selectedZones"
            :value="zone.id"
            class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
          />
          <label 
            :for="`zone-${zone.id}`" 
            class="text-sm text-gray-900 cursor-pointer flex items-center justify-between w-full"
          >
            <span>{{ zone.name }}</span>
            <span v-if="zone.shippo_price" class="text-xs text-gray-500">
              €{{ zone.shippo_price }}
            </span>
          </label>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="mt-6 text-center">
      <div class="inline-flex items-center px-4 py-2 text-sm text-gray-600">
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Calcolo prezzi in corso...
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-md">
      <div class="flex">
        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Errore</h3>
          <div class="mt-2 text-sm text-red-700">{{ error }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, watch } from 'vue'
import axios from 'axios'

export default {
  name: 'ShippingZoneSelector',
  props: {
    modelValue: {
      type: Object,
      default: () => ({ option: 'worldwide', zones: [] })
    },
    listingId: {
      type: [String, Number],
      default: null
    }
  },
  emits: ['update:modelValue', 'done'],
  setup(props, { emit }) {
    const selectedOption = ref(props.modelValue.option || 'worldwide')
    const selectedZones = ref([...props.modelValue.zones])
    const shippingZones = ref([])
    const loading = ref(false)
    const error = ref(null)

    // Carica le zone di spedizione
    const loadShippingZones = async () => {
      try {
        loading.value = true
        error.value = null
        
        const response = await axios.get('/api/shipping-zones')
        shippingZones.value = response.data.data || []
        
        // Calcola i prezzi SHIPPO per ogni zona
        await calculateShippoPrices()
        
      } catch (err) {
        console.error('Errore caricamento zone spedizione:', err)
        error.value = 'Impossibile caricare le zone di spedizione'
      } finally {
        loading.value = false
      }
    }

    // Calcola i prezzi SHIPPO per le zone
    const calculateShippoPrices = async () => {
      if (!props.listingId) return

      try {
        for (const zone of shippingZones.value) {
          if (zone.use_shippo_pricing) {
            try {
              const response = await axios.post('/api/shipping-zones/calculate-price', {
                zone_id: zone.id,
                listing_id: props.listingId
              })
              zone.shippo_price = response.data.price
            } catch (err) {
              console.warn(`Errore calcolo prezzo per zona ${zone.id}:`, err)
              zone.shippo_price = null
            }
          }
        }
      } catch (err) {
        console.error('Errore calcolo prezzi SHIPPO:', err)
      }
    }

    // Gestisce il cambio di opzione
    const handleOptionChange = () => {
      if (selectedOption.value === 'worldwide') {
        selectedZones.value = []
      }
      updateModelValue()
    }

    // Aggiorna il valore del modello
    const updateModelValue = () => {
      const value = {
        option: selectedOption.value,
        zones: selectedOption.value === 'custom' ? selectedZones.value : []
      }
      emit('update:modelValue', value)
    }

    // Gestisce il click su "Fine"
    const handleDone = () => {
      updateModelValue()
      emit('done', {
        option: selectedOption.value,
        zones: selectedOption.value === 'custom' ? selectedZones.value : []
      })
    }

    // Watchers
    watch(selectedOption, handleOptionChange)
    watch(selectedZones, updateModelValue, { deep: true })

    // Lifecycle
    onMounted(() => {
      loadShippingZones()
    })

    return {
      selectedOption,
      selectedZones,
      shippingZones,
      loading,
      error,
      handleDone
    }
  }
}
</script>

<style scoped>
.shipping-zone-selector {
  @apply max-w-md mx-auto p-6 bg-white rounded-lg shadow-sm;
}

/* Stili per radio buttons personalizzati */
input[type="radio"] {
  @apply appearance-none w-4 h-4 border-2 border-gray-300 rounded-full;
}

input[type="radio"]:checked {
  @apply border-blue-600 bg-blue-600;
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
}

/* Stili per checkbox personalizzati */
input[type="checkbox"] {
  @apply appearance-none w-4 h-4 border-2 border-gray-300 rounded;
}

input[type="checkbox"]:checked {
  @apply border-blue-600 bg-blue-600;
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='m13.854 3.646-7.5 7.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6 10.293l7.146-7.147a.5.5 0 0 1 .708.708z'/%3e%3c/svg%3e");
}
</style>
