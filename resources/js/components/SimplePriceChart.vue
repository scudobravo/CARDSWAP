<template>
  <div class="bg-gray-50 p-6 rounded-lg">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-futura-bold text-primary">Price Trend</h3>
      <div class="bg-primary text-white px-3 py-1 rounded-lg text-sm font-futura-bold">
        {{ priceChange }}% AVERAGE PRICE
      </div>
    </div>
    
    <!-- Chart Container -->
    <div class="h-48 bg-white rounded-lg p-4">
      <div class="h-full flex items-end justify-between space-x-1">
        <div 
          v-for="(value, index) in chartData" 
          :key="index"
          class="flex flex-col items-center space-y-1 flex-1"
        >
          <!-- Bar -->
          <div 
            class="w-full bg-primary rounded-t transition-all duration-300 hover:bg-primary/80"
            :style="{ 
              height: `${Math.max((value / maxValue) * 80, 8)}px`, 
              minHeight: '8px',
              maxHeight: '120px'
            }"
            :title="`€${value}`"
          ></div>
          <!-- Label -->
          <span class="text-xs text-gray-600 transform -rotate-45 origin-top-left whitespace-nowrap">
            {{ labels[index] }}
          </span>
        </div>
      </div>
    </div>
    
    <!-- Chart Info -->
    <div class="mt-4 text-center">
      <p class="text-sm text-gray-600 font-gill-sans">
        Ultimi {{ chartData.length }} giorni
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// Props
const props = defineProps({
  productId: {
    type: [String, Number],
    default: null
  },
  currentPrice: {
    type: [String, Number],
    default: 95
  }
})

// Reactive data
const chartData = ref([])
const labels = ref([])
const priceChange = ref(-20)

// Computed
const maxValue = computed(() => {
  return Math.max(...chartData.value) || 100
})

// Methods
const generateFakeData = () => {
  const days = 14
  const basePrice = parseFloat(props.currentPrice) || 95
  const data = []
  const labelData = []
  
  // Generate last 14 days
  for (let i = days - 1; i >= 0; i--) {
    const date = new Date()
    date.setDate(date.getDate() - i)
    labelData.push(date.toLocaleDateString('it-IT', { 
      weekday: 'short', 
      day: '2-digit' 
    }))
    
    // Generate realistic price fluctuation
    const variation = (Math.random() - 0.5) * 0.4 // ±20% variation
    const price = basePrice * (1 + variation)
    data.push(Math.round(price * 100) / 100)
  }
  
  chartData.value = data
  labels.value = labelData
  
  // Calculate price change
  const firstPrice = data[0]
  const lastPrice = data[data.length - 1]
  const change = ((lastPrice - firstPrice) / firstPrice) * 100
  priceChange.value = Math.round(change)
  
  console.log('Simple chart data generated:', { 
    data, 
    labels: labelData, 
    change,
    maxValue: Math.max(...data),
    minValue: Math.min(...data)
  })
}

// Lifecycle
onMounted(() => {
  generateFakeData()
})
</script>
