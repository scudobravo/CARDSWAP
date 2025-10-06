<template>
  <div class="bg-gray-50 p-6 rounded-lg">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-futura-bold text-primary">Price Trend</h3>
      <div class="bg-primary text-white px-3 py-1 rounded-lg text-sm font-futura-bold">
        {{ priceChange }}% AVERAGE PRICE
      </div>
    </div>
    
    <!-- Chart Container -->
    <div class="h-48 bg-white rounded-lg p-4 relative">
      <canvas ref="chartCanvas" class="w-full h-full"></canvas>
      <!-- Fallback text if chart fails -->
      <div v-if="!chartInstance" class="absolute inset-0 flex items-center justify-center text-gray-500">
        <div class="text-center">
          <div class="text-2xl mb-2">📊</div>
          <p class="text-sm">Caricamento grafico...</p>
        </div>
      </div>
    </div>
    
    <!-- Chart Info -->
    <div class="mt-4 text-center">
      <p class="text-sm text-gray-600 font-gill-sans">
        Ultimi {{ chartData.labels.length }} giorni
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { Chart, registerables } from 'chart.js'

// Register all Chart.js components
Chart.register(...registerables)

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

// Refs
const chartCanvas = ref(null)
let chartInstance = null

// Chart data
const chartData = ref({
  labels: [],
  datasets: [{
    label: 'Prezzo (€)',
    data: [],
    borderColor: '#1e40af',
    backgroundColor: 'rgba(30, 64, 175, 0.1)',
    borderWidth: 2,
    fill: true,
    tension: 0.4,
    pointBackgroundColor: '#1e40af',
    pointBorderColor: '#ffffff',
    pointBorderWidth: 2,
    pointRadius: 4,
    pointHoverRadius: 6
  }]
})

// Computed
const priceChange = ref(-20)

// Methods
const generateFakeData = () => {
  const days = 14
  const basePrice = parseFloat(props.currentPrice) || 95
  const labels = []
  const data = []
  
  // Generate last 14 days with more visible variation
  for (let i = days - 1; i >= 0; i--) {
    const date = new Date()
    date.setDate(date.getDate() - i)
    labels.push(date.toLocaleDateString('it-IT', { 
      weekday: 'short', 
      day: '2-digit' 
    }))
    
    // Generate more visible price fluctuation for testing
    const variation = (Math.random() - 0.5) * 0.4 // ±20% variation
    const price = basePrice * (1 + variation)
    data.push(Math.round(price * 100) / 100)
  }
  
  // Ensure we have visible data
  console.log('Chart data generated:', { labels, data })
  
  chartData.value.labels = labels
  chartData.value.datasets[0].data = data
  
  // Calculate price change
  const firstPrice = data[0]
  const lastPrice = data[data.length - 1]
  const change = ((lastPrice - firstPrice) / firstPrice) * 100
  priceChange.value = Math.round(change)
  
  console.log('Price change calculated:', priceChange.value)
}

const createChart = () => {
  if (!chartCanvas.value) {
    console.error('Canvas element not found')
    return
  }
  
  console.log('Creating chart with canvas:', chartCanvas.value)
  const ctx = chartCanvas.value.getContext('2d')
  
  chartInstance = new Chart(ctx, {
    type: 'line',
    data: chartData.value,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: 'rgba(0, 0, 0, 0.8)',
          titleColor: '#ffffff',
          bodyColor: '#ffffff',
          borderColor: '#1e40af',
          borderWidth: 1,
          callbacks: {
            label: function(context) {
              return `Prezzo: €${context.parsed.y}`
            }
          }
        }
      },
      scales: {
        x: {
          display: true,
          grid: {
            display: false
          },
          ticks: {
            color: '#6b7280',
            font: {
              size: 11
            }
          }
        },
        y: {
          display: true,
          beginAtZero: false,
          grid: {
            color: 'rgba(107, 114, 128, 0.1)',
            drawBorder: false
          },
          ticks: {
            color: '#6b7280',
            font: {
              size: 11
            },
            callback: function(value) {
              return '€' + value
            }
          }
        }
      },
      elements: {
        point: {
          hoverBackgroundColor: '#1e40af'
        }
      },
      interaction: {
        intersect: false,
        mode: 'index'
      }
    }
  })
}

const destroyChart = () => {
  if (chartInstance) {
    chartInstance.destroy()
    chartInstance = null
  }
}

// Lifecycle
onMounted(async () => {
  generateFakeData()
  await nextTick()
  createChart()
})

onUnmounted(() => {
  destroyChart()
})

// Watch for prop changes
watch(() => props.currentPrice, async () => {
  generateFakeData()
  if (chartInstance) {
    chartInstance.data = chartData.value
    await nextTick()
    chartInstance.update()
  }
})
</script>
