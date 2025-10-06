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
      <svg 
        class="w-full h-full" 
        viewBox="0 0 400 160" 
        preserveAspectRatio="xMidYMid meet"
      >
        <!-- Grid lines -->
        <defs>
          <pattern id="grid" width="40" height="32" patternUnits="userSpaceOnUse">
            <path d="M 40 0 L 0 0 0 32" fill="none" stroke="#f3f4f6" stroke-width="1"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#grid)" />
        
        <!-- Y-axis labels -->
        <text v-for="(label, index) in yLabels" :key="index" 
              :x="10" :y="20 + index * 32" 
              class="text-xs fill-gray-500" 
              text-anchor="end">
          €{{ label }}
        </text>
        
        <!-- Line path -->
        <path 
          :d="linePath" 
          fill="none" 
          stroke="#1e40af" 
          stroke-width="3" 
          stroke-linecap="round" 
          stroke-linejoin="round"
        />
        
        <!-- Area under curve -->
        <path 
          :d="areaPath" 
          fill="rgba(30, 64, 175, 0.1)" 
          stroke="none"
        />
        
        <!-- Data points -->
        <circle 
          v-for="(point, index) in chartPoints" 
          :key="index"
          :cx="point.x" 
          :cy="point.y" 
          r="4" 
          fill="#1e40af" 
          stroke="#ffffff" 
          stroke-width="2"
          class="hover:r-6 transition-all duration-200 cursor-pointer"
        >
          <title>€{{ chartData[index] }}</title>
        </circle>
        
        <!-- X-axis labels -->
        <text 
          v-for="(label, index) in labels" 
          :key="index"
          :x="40 + index * 25" 
          :y="150" 
          class="text-xs fill-gray-600" 
          text-anchor="middle"
          transform="rotate(-45, 40, 150)"
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

const chartPoints = computed(() => {
  if (!chartData.value.length) return []
  
  const points = []
  const range = maxValue.value - minValue.value
  const chartHeight = 120
  const chartWidth = 350
  const startX = 40
  const startY = 20
  
  chartData.value.forEach((value, index) => {
    const x = startX + (index * chartWidth / (chartData.value.length - 1))
    const normalizedValue = (value - minValue.value) / range
    const y = startY + chartHeight - (normalizedValue * chartHeight)
    
    points.push({ x, y })
  })
  
  return points
})

const linePath = computed(() => {
  if (chartPoints.value.length < 2) return ''
  
  let path = `M ${chartPoints.value[0].x} ${chartPoints.value[0].y}`
  
  for (let i = 1; i < chartPoints.value.length; i++) {
    const point = chartPoints.value[i]
    path += ` L ${point.x} ${point.y}`
  }
  
  return path
})

const areaPath = computed(() => {
  if (chartPoints.value.length < 2) return ''
  
  let path = `M ${chartPoints.value[0].x} ${chartPoints.value[0].y}`
  
  for (let i = 1; i < chartPoints.value.length; i++) {
    const point = chartPoints.value[i]
    path += ` L ${point.x} ${point.y}`
  }
  
  // Close the area
  const lastPoint = chartPoints.value[chartPoints.value.length - 1]
  const firstPoint = chartPoints.value[0]
  path += ` L ${lastPoint.x} 140 L ${firstPoint.x} 140 Z`
  
  return path
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
  
  console.log('Line chart data generated:', { 
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
