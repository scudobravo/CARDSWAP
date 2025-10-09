<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Statistiche Vendite
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Monitora le performance delle tue vendite
      </p>
    </div>

    <!-- Filtri Periodo -->
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
      <div class="flex flex-wrap items-center gap-4">
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
            Periodo
          </label>
          <select 
            v-model="selectedPeriod" 
            @change="loadStatistics"
            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <option value="7">Ultimi 7 giorni</option>
            <option value="30">Ultimi 30 giorni</option>
            <option value="90">Ultimi 3 mesi</option>
            <option value="365">Ultimo anno</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
            Categoria
          </label>
          <select 
            v-model="selectedCategory" 
            @change="loadStatistics"
            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          >
            <option value="">Tutte le categorie</option>
            <option value="football">Calcio</option>
            <option value="basketball">Basketball</option>
            <option value="pokemon">Pokemon</option>
          </select>
        </div>

        <div class="flex items-end">
          <button 
            @click="loadStatistics"
            class="px-4 py-2 text-sm font-gill-sans-semibold text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary"
          >
            Aggiorna
          </button>
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
        Caricamento statistiche...
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
            @click="loadStatistics"
            class="mt-2 px-3 py-1 text-sm font-gill-sans-semibold text-red-800 bg-red-100 border border-red-300 rounded-md hover:bg-red-200"
          >
            Riprova
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else-if="statistics" class="space-y-6">
      <!-- Statistiche Principali -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatisticsCard
          label="Vendite Totali"
          :value="statistics.total_sales"
          :icon="CurrencyEuroIcon"
          icon-color="text-green-500"
          :change="statistics.sales_change"
          format="currency"
        />
        
        <StatisticsCard
          label="Ordini Totali"
          :value="statistics.total_orders"
          :icon="ShoppingBagIcon"
          icon-color="text-blue-500"
          :change="statistics.orders_change"
        />
        
        <StatisticsCard
          label="Valore Medio Ordine"
          :value="statistics.average_order_value"
          :icon="ChartBarIcon"
          icon-color="text-purple-500"
          :change="statistics.aov_change"
          format="currency"
        />
        
        <StatisticsCard
          label="Rating Medio"
          :value="statistics.average_rating"
          :icon="StarIcon"
          icon-color="text-yellow-500"
          :change-label="`${statistics.total_feedbacks} recensioni`"
        />
      </div>

      <!-- Grafici -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <SalesChart
          title="Vendite nel Tempo"
          :data="statistics.daily_sales"
          type="line"
        />
        
        <SalesChart
          title="Vendite per Categoria"
          :data="Object.entries(statistics.category_sales || {})"
          type="pie"
        />
      </div>

      <!-- Statistiche Dettagliate -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Prodotti -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Top Prodotti Venduti</h3>
          <div v-if="statistics.top_products?.length > 0" class="space-y-3">
            <div 
              v-for="(product, index) in statistics.top_products" 
              :key="product.id"
              class="flex items-center justify-between p-3 bg-gray-50 rounded-lg"
            >
              <div class="flex items-center space-x-3">
                <span class="flex-shrink-0 w-6 h-6 bg-primary text-white text-xs font-gill-sans-semibold rounded-full flex items-center justify-center">
                  {{ index + 1 }}
                </span>
                <div>
                  <p class="text-sm font-gill-sans-semibold text-gray-900">{{ product.name }}</p>
                  <p class="text-xs text-gray-500">{{ product.category }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm font-gill-sans-semibold text-gray-900">{{ product.quantity_sold }} venduti</p>
                <p class="text-xs text-gray-500">€{{ product.total_revenue.toFixed(2) }}</p>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8">
            <p class="text-sm text-gray-500">Nessun prodotto venduto in questo periodo</p>
          </div>
        </div>

        <!-- Statistiche Ordini -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Stato Ordini</h3>
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">In attesa</span>
              <span class="text-sm font-gill-sans-semibold text-gray-900">{{ statistics.orders_by_status?.pending || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Confermati</span>
              <span class="text-sm font-gill-sans-semibold text-gray-900">{{ statistics.orders_by_status?.confirmed || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Spediti</span>
              <span class="text-sm font-gill-sans-semibold text-gray-900">{{ statistics.orders_by_status?.shipped || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Consegnati</span>
              <span class="text-sm font-gill-sans-semibold text-gray-900">{{ statistics.orders_by_status?.delivered || 0 }}</span>
            </div>
            <div class="flex items-center justify-between">
              <span class="text-sm text-gray-600">Cancellati</span>
              <span class="text-sm font-gill-sans-semibold text-gray-900">{{ statistics.orders_by_status?.cancelled || 0 }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Trend Mensili -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Trend Mensili</h3>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-gill-sans-semibold text-gray-500 uppercase tracking-wider">Mese</th>
                <th class="px-6 py-3 text-left text-xs font-gill-sans-semibold text-gray-500 uppercase tracking-wider">Ordini</th>
                <th class="px-6 py-3 text-left text-xs font-gill-sans-semibold text-gray-500 uppercase tracking-wider">Vendite</th>
                <th class="px-6 py-3 text-left text-xs font-gill-sans-semibold text-gray-500 uppercase tracking-wider">Valore Medio</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="month in statistics.monthly_trend" :key="month.month">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-gill-sans-semibold text-gray-900">
                  {{ formatMonth(month.month) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  {{ month.orders }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  €{{ month.sales.toFixed(2) }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                  €{{ month.average_order_value.toFixed(2) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="text-center py-12">
        <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Nessuna statistica</h3>
        <p class="mt-1 text-sm text-gray-500">Le tue statistiche di vendita appariranno qui.</p>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import StatisticsCard from '@/components/statistics/StatisticsCard.vue'
import SalesChart from '@/components/statistics/SalesChart.vue'
import { 
  ChartBarIcon, 
  CurrencyEuroIcon, 
  ShoppingBagIcon, 
  StarIcon,
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline'

// Reactive data
const statistics = ref(null)
const loading = ref(false)
const error = ref(null)
const selectedPeriod = ref('30')
const selectedCategory = ref('')

// Metodi
const loadStatistics = async () => {
  loading.value = true
  error.value = null
  
  try {
    const params = new URLSearchParams({
      period: selectedPeriod.value,
      category: selectedCategory.value
    })
    
    const response = await fetch(`/api/sales/statistics?${params}`, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      statistics.value = data.data
    } else {
      throw new Error(`Errore HTTP: ${response.status}`)
    }
  } catch (err) {
    console.error('Errore nel caricamento statistiche:', err)
    error.value = err.message
    statistics.value = null
  } finally {
    loading.value = false
  }
}

const formatMonth = (monthString) => {
  const date = new Date(monthString)
  return date.toLocaleDateString('it-IT', { 
    year: 'numeric', 
    month: 'long' 
  })
}

// Lifecycle
onMounted(() => {
  loadStatistics()
})
</script>