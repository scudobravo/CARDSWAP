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
      <svg 
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
        <rect 
          v-for="(value, index) in chartData" 
          :key="index"
          :x="60 + index * barWidth + index * 5" 
          :y="getBarY(value)" 
          :width="barWidth" 
          :height="getBarHeight(value)"
          fill="#1e40af"
          class="hover:fill-blue-600 transition-colors duration-200 cursor-pointer"
          :opacity="0.8"
        >
          <title>€{{ value }} - {{ labels[index] }}</title>
        </rect>
        
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
      <p class="text-sm text-gray-600 font-gill-sans">
        Ultimi {{ chartData.length }} giorni
      </p>
      <p class="text-xs text-gray-500 mt-1">
        Min: €{{ Math.min(...chartData).toFixed(0) }} | Max: €{{ Math.max(...chartData).toFixed(0) }}
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

const minValue = computed(() => {
  return Math.min(...chartData.value) || 50
})

const yLabels = computed(() => {
  const range = maxValue.value - minValue.value
  const step = range / 4
  return [
    Math.round(maxValue.value),
    Math.round(maxValue.value - step),
    Math.round(maxValue.value - step * 2),
    Math.round(maxValue.value - step * 3),
    Math.round(minValue.value)
  ]
})

const visibleLabels = computed(() => {
  // Show only every 2nd label to avoid overlap
  return labels.value.filter((_, index) => index % 2 === 0)
})

const barWidth = computed(() => {
  return Math.max(20, (600 - (chartData.value.length - 1) * 5) / chartData.value.length)
})

const getBarHeight = (value) => {
  const range = maxValue.value - minValue.value
  const normalizedValue = (value - minValue.value) / range
  return Math.max(2, normalizedValue * 200) // Min height of 2px
}

const getBarY = (value) => {
  const range = maxValue.value - minValue.value
  const normalizedValue = (value - minValue.value) / range
  return 60 + 200 - (normalizedValue * 200) // 60 is top margin, 200 is chart height
}

// Methods
const generateFakeData = () => {
  const days = 14
  const basePrice = parseFloat(props.currentPrice) || 95
  const data = []
  const labelData = []
  
  // Generate last 14 days with more realistic variation
  for (let i = days - 1; i >= 0; i--) {
    const date = new Date()
    date.setDate(date.getDate() - i)
    labelData.push(date.toLocaleDateString('it-IT', { 
      weekday: 'short', 
      day: '2-digit' 
    }))
    
    // Generate more realistic price fluctuation with trend
    const trend = Math.sin(i * 0.5) * 0.1 // Add some trend
    const variation = (Math.random() - 0.5) * 0.3 + trend
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
  
  console.log('Bar chart data generated:', { 
    data, 
    labels: labelData, 
    change,
    maxValue: Math.max(...data),
    minValue: Math.min(...data),
    barWidth: barWidth.value
  })
}

// Lifecycle
onMounted(() => {
  generateFakeData()
})
</script>