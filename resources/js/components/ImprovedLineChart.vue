<template>
  <div class="bg-gray-50 p-6 rounded-lg">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-futura-bold text-primary">Price Trend</h3>
      <div class="bg-primary text-white px-3 py-1 rounded-lg text-sm font-futura-bold">
        {{ priceChange }}% AVERAGE PRICE
      </div>
    </div>
    
    <!-- Chart Container -->
    <div class="h-80 bg-white rounded-lg p-6">
      <svg 
        class="w-full h-full" 
        viewBox="0 0 600 250" 
        preserveAspectRatio="xMidYMid meet"
      >
        <!-- Grid lines -->
        <defs>
          <pattern id="grid" width="50" height="40" patternUnits="userSpaceOnUse">
            <path d="M 50 0 L 0 0 0 40" fill="none" stroke="#f3f4f6" stroke-width="1"/>
          </pattern>
        </defs>
        <rect width="100%" height="100%" fill="url(#grid)" />
        
        <!-- Y-axis labels -->
        <text v-for="(label, index) in yLabels" :key="index" 
              :x="20" :y="35 + index * 45" 
              class="text-base fill-gray-800 font-bold" 
              text-anchor="end">
          €{{ label }}
        </text>
        
        <!-- Area under curve -->
        <path 
          :d="areaPath" 
          fill="rgba(30, 64, 175, 0.1)" 
          stroke="none"
        />
        
        <!-- Smooth line path -->
        <path 
          :d="smoothLinePath" 
          fill="none" 
          stroke="#1e40af" 
          stroke-width="3" 
          stroke-linecap="round" 
          stroke-linejoin="round"
        />
        
        <!-- Data points -->
        <circle 
          v-for="(point, index) in chartPoints" 
          :key="index"
          :cx="point.x" 
          :cy="point.y" 
          r="5" 
          fill="#1e40af" 
          stroke="#ffffff" 
          stroke-width="2"
          class="hover:r-7 transition-all duration-200 cursor-pointer"
        >
          <title>€{{ chartData[index] }}</title>
        </circle>
        
        <!-- X-axis labels -->
        <text 
          v-for="(label, index) in visibleLabels" 
          :key="index"
          :x="80 + index * 60" 
          :y="235" 
          class="text-base fill-gray-800 font-bold" 
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
  // Show only every 3rd label to avoid overlap and improve readability
  return labels.value.filter((_, index) => index % 3 === 0)
})

const chartPoints = computed(() => {
  if (!chartData.value.length) return []
  
  const points = []
  const range = maxValue.value - minValue.value
  const chartHeight = 180
  const chartWidth = 500
  const startX = 80
  const startY = 40
  
  chartData.value.forEach((value, index) => {
    const x = startX + (index * chartWidth / (chartData.value.length - 1))
    const normalizedValue = (value - minValue.value) / range
    const y = startY + chartHeight - (normalizedValue * chartHeight)
    
    points.push({ x, y })
  })
  
  return points
})

// Smooth curve using quadratic Bézier curves
const smoothLinePath = computed(() => {
  if (chartPoints.value.length < 2) return ''
  
  const points = chartPoints.value
  let path = `M ${points[0].x} ${points[0].y}`
  
  for (let i = 1; i < points.length; i++) {
    const prevPoint = points[i - 1]
    const currentPoint = points[i]
    
    // Calculate control point for smooth curve
    const controlX = (prevPoint.x + currentPoint.x) / 2
    const controlY = (prevPoint.y + currentPoint.y) / 2
    
    path += ` Q ${controlX} ${controlY} ${currentPoint.x} ${currentPoint.y}`
  }
  
  return path
})

const areaPath = computed(() => {
  if (chartPoints.value.length < 2) return ''
  
  let path = `M ${chartPoints.value[0].x} ${chartPoints.value[0].y}`
  
  // Use the same smooth curve for the area
  for (let i = 1; i < chartPoints.value.length; i++) {
    const prevPoint = chartPoints.value[i - 1]
    const currentPoint = chartPoints.value[i]
    const controlX = (prevPoint.x + currentPoint.x) / 2
    const controlY = (prevPoint.y + currentPoint.y) / 2
    
    path += ` Q ${controlX} ${controlY} ${currentPoint.x} ${currentPoint.y}`
  }
  
  // Close the area
  const lastPoint = chartPoints.value[chartPoints.value.length - 1]
  const firstPoint = chartPoints.value[0]
  path += ` L ${lastPoint.x} 220 L ${firstPoint.x} 220 Z`
  
  return path
})

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
  
  console.log('Improved line chart data generated:', { 
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
