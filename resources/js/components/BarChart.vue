<template>
  <div class="bg-gray-200 p-6 rounded-lg">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-futura-bold text-primary">Price Trend</h3>
      <div class="bg-primary text-white px-3 py-1 rounded-lg text-sm font-futura-bold">
        {{ priceChange }}% AVERAGE PRICE
      </div>
    </div>
    
    <!-- Chart Container -->
    <div class="h-96">
      <div v-if="loading" class="h-full flex items-center justify-center">
        <div class="text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-2"></div>
          <p class="text-sm text-gray-600">Caricamento dati...</p>
        </div>
      </div>
      <div v-else-if="chartData.length === 0" class="h-full flex items-center justify-center">
        <div class="text-center">
          <p class="text-gray-600 font-gill-sans">Nessun dato storico disponibile</p>
          <p class="text-xs text-gray-500 mt-2">Il prezzo corrente è €{{ currentPrice }}</p>
        </div>
      </div>
      <svg 
        v-else
        class="w-full h-full" 
        viewBox="0 0 700 300" 
        preserveAspectRatio="xMidYMid meet"
      >
        <!-- Grid lines -->
        <defs>
          <pattern id="grid" width="50" height="30" patternUnits="userSpaceOnUse">
            <path d="M 50 0 L 0 0 0 30" fill="none" stroke="#f3f4f6" stroke-width="1"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#grid)" />
        
        <!-- Y-axis labels -->
        <text v-for="(label, index) in yLabels" :key="index" 
              :x="25" :y="40 + index * 50" 
              class="text-base fill-gray-800 font-bold" 
              text-anchor="end">
          €{{ label }}
        </text>
        
        <!-- Bars -->
        <template v-for="(value, index) in chartData" :key="index">
          <rect 
            v-if="!isNaN(value) && value > 0 && !isNaN(getBarY(value)) && !isNaN(getBarHeight(value))"
            :x="60 + index * (barWidth + 5)" 
            :y="getBarY(value)" 
            :width="barWidth" 
            :height="getBarHeight(value)"
            fill="#1e40af"
            class="hover:fill-blue-600 transition-colors duration-200 cursor-pointer"
            :opacity="0.8"
          >
            <title>€{{ value.toFixed(2) }} - {{ labels[index] }}</title>
          </rect>
        </template>
        
        <!-- X-axis labels -->
        <text 
          v-for="(label, index) in visibleLabels" 
          :key="index"
          :x="60 + index * (barWidth + 5) + barWidth/2" 
          :y="280" 
          class="text-sm fill-gray-700 font-medium" 
          text-anchor="middle"
        >
          {{ label }}
        </text>
      </svg>
    </div>
    
    <!-- Chart Info -->
    <div class="mt-4 text-center">
      <p v-if="chartData.length > 0" class="text-sm text-gray-600 font-gill-sans">
        Ultimi {{ chartData.length }} punti dati
      </p>
      <p v-if="chartData.length > 0 && Math.min(...chartData) !== Math.max(...chartData)" class="text-xs text-gray-500 mt-1">
        Min: €{{ Math.min(...chartData).toFixed(0) }} | Max: €{{ Math.max(...chartData).toFixed(0) }}
      </p>
      <p v-else-if="chartData.length > 0" class="text-xs text-gray-500 mt-1">
        Prezzo: €{{ chartData[0].toFixed(0) }}
      </p>
      <p v-else class="text-xs text-gray-500">
        Prezzo corrente: €{{ parseFloat(currentPrice).toFixed(2) }}
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  productId: {
    type: [String, Number],
    default: null
  },
  currentPrice: {
    type: [String, Number],
    default: 95
  },
  listingId: {
    type: [String, Number],
    default: null
  }
})

// Reactive data
const chartData = ref([])
const labels = ref([])
const priceChange = ref(0)
const loading = ref(true)
const error = ref(null)

// Computed
const maxValue = computed(() => {
  if (chartData.value.length === 0) return parseFloat(props.currentPrice) || 100
  return Math.max(...chartData.value) || parseFloat(props.currentPrice) || 100
})

const minValue = computed(() => {
  if (chartData.value.length === 0) return parseFloat(props.currentPrice) || 50
  return Math.min(...chartData.value) || parseFloat(props.currentPrice) || 50
})

const yLabels = computed(() => {
  const range = maxValue.value - minValue.value
  // Se range è 0 (tutti i valori sono uguali), mostra solo un label
  if (range === 0) {
    return [Math.round(maxValue.value)]
  }
  
  const step = range / 4
  const labels = [
    Math.round(maxValue.value),
    Math.round(maxValue.value - step),
    Math.round(maxValue.value - step * 2),
    Math.round(maxValue.value - step * 3),
    Math.round(minValue.value)
  ]
  
  // Filtra valori NaN
  return labels.filter(label => !isNaN(label))
})

const visibleLabels = computed(() => {
  // Show only every 2nd label to avoid overlap
  return labels.value.filter((_, index) => index % 2 === 0)
})

const barWidth = computed(() => {
  if (chartData.value.length === 0) return 20
  const calculatedWidth = (600 - (chartData.value.length - 1) * 5) / chartData.value.length
  // Assicurati che sia un numero valido
  return isNaN(calculatedWidth) || calculatedWidth <= 0 ? 20 : Math.max(20, calculatedWidth)
})

const getBarHeight = (value) => {
  // Valida il valore
  if (isNaN(value) || value <= 0) return 2
  
  const range = maxValue.value - minValue.value
  // Se range è 0 (tutti i valori sono uguali), usa un'altezza minima
  if (range === 0) {
    return 50 // Altezza fissa quando tutti i valori sono uguali
  }
  
  const normalizedValue = (value - minValue.value) / range
  const height = Math.max(2, normalizedValue * 200) // Min height of 2px
  
  // Assicurati che sia un numero valido
  return isNaN(height) ? 2 : height
}

const getBarY = (value) => {
  // Valida il valore
  if (isNaN(value) || value <= 0) return 260 // Posizione in basso per valori non validi
  
  const range = maxValue.value - minValue.value
  // Se range è 0 (tutti i valori sono uguali), usa una posizione centrale
  if (range === 0) {
    return 160 // Posizione centrale quando tutti i valori sono uguali
  }
  
  const normalizedValue = (value - minValue.value) / range
  const y = 60 + 200 - (normalizedValue * 200) // 60 is top margin, 200 is chart height
  
  // Assicurati che sia un numero valido
  return isNaN(y) ? 260 : y
}

// Methods
const loadPriceHistory = async () => {
  if (!props.listingId) {
    // Se non c'è listingId, mostra solo il prezzo corrente
    chartData.value = [parseFloat(props.currentPrice) || 95]
    labels.value = ['Prezzo corrente']
    priceChange.value = 0
    loading.value = false
    return
  }

  loading.value = true
  error.value = null

  try {
    const response = await axios.get(`/api/listings/${props.listingId}/price-history`)
    
    if (response.data.success && response.data.data && response.data.data.length > 0) {
      const history = response.data.data
      
      // Ordina per data (più vecchia prima)
      history.sort((a, b) => new Date(a.date) - new Date(b.date))
      
      // Estrai dati e label, filtra valori non validi
      const validData = history
        .map(item => {
          const price = parseFloat(item.price)
          return isNaN(price) || price <= 0 ? null : { price, date: item.date }
        })
        .filter(item => item !== null)
      
      chartData.value = validData.map(item => item.price)
      labels.value = validData.map(item => {
        const date = new Date(item.date)
        return date.toLocaleDateString('it-IT', { 
          weekday: 'short', 
          day: '2-digit',
          month: 'short'
        })
      })
      
      // Calcola variazione prezzo se ci sono almeno 2 punti
      if (chartData.value.length >= 2) {
        const firstPrice = chartData.value[0]
        const lastPrice = chartData.value[chartData.value.length - 1]
        const change = ((lastPrice - firstPrice) / firstPrice) * 100
        priceChange.value = Math.round(change * 10) / 10 // Arrotonda a 1 decimale
      } else {
        priceChange.value = 0
      }
      
      console.log('Price history loaded:', { 
        data: chartData.value, 
        labels: labels.value, 
        change: priceChange.value,
        hasHistory: response.data.has_history
      })
    } else {
      // Nessun dato storico, mostra solo prezzo corrente
      chartData.value = [parseFloat(props.currentPrice) || 95]
      labels.value = ['Prezzo corrente']
      priceChange.value = 0
    }
  } catch (err) {
    console.error('Error loading price history:', err)
    error.value = err.response?.data?.error || 'Errore nel caricamento dati'
    
    // Fallback: mostra solo prezzo corrente
    chartData.value = [parseFloat(props.currentPrice) || 95]
    labels.value = ['Prezzo corrente']
    priceChange.value = 0
  } finally {
    loading.value = false
  }
}

// Watch per ricaricare quando cambia il listingId
watch(() => props.listingId, () => {
  if (props.listingId) {
    loadPriceHistory()
  }
})

// Lifecycle
onMounted(() => {
  loadPriceHistory()
})
</script>