<template>
  <div class="min-h-screen bg-white">
    <!-- Header -->
    <Header />
    
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-4">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex" aria-label="Breadcrumb">
          <ol class="flex items-center space-x-2">
            <li>
              <router-link to="/" class="text-gray-500 hover:text-primary transition-colors font-gill-sans text-sm">
                All Products
              </router-link>
            </li>
            <li>
              <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </li>
            <li>
              <router-link to="/categories" class="text-gray-500 hover:text-primary transition-colors font-gill-sans text-sm">
                Category
              </router-link>
            </li>
            <li>
              <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </li>
            <li>
              <span class="text-gray-500 font-gill-sans text-sm">Subcategory</span>
            </li>
            <li>
              <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </li>
            <li>
              <span class="text-gray-500 font-gill-sans text-sm">Products</span>
            </li>
          </ol>
        </nav>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        <p class="mt-4 text-gray-600 font-gill-sans">Caricamento dettagli carta...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-500 text-6xl mb-4">⚠️</div>
        <h2 class="text-2xl font-futura-bold text-primary mb-2">Errore nel caricamento</h2>
        <p class="text-gray-600 font-gill-sans mb-4">{{ error }}</p>
        <button @click="loadProductDetails" class="bg-primary text-white px-6 py-2 rounded-lg font-futura-bold hover:bg-primary/90 transition-colors">
          Riprova
        </button>
      </div>

      <!-- Product Content -->
      <div v-else>
      <!-- Top Actions -->
      <div class="flex justify-end mb-6">
        <button class="bg-secondary text-primary px-6 py-2 rounded-lg font-futura-bold text-sm hover:bg-secondary/90 transition-colors">
          SELL SAME CARD
        </button>
      </div>

      <!-- Product Details Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-6 gap-8 mb-12">
        <!-- Product Image (Left) -->
        <div class="lg:col-span-2">
          <div class="space-y-4">
            <!-- Main Image -->
            <div class="aspect-[3/4] bg-gray-200 rounded-lg flex items-center justify-center">
              <div v-if="product.image_url" class="w-full h-full bg-cover bg-center rounded-lg" :style="{ backgroundImage: `url(${product.image_url})` }"></div>
              <div v-else class="text-center text-gray-500">
                <svg class="w-24 h-24 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm font-gill-sans">Immagine non disponibile</p>
              </div>
            </div>
            
            <!-- Thumbnail Images -->
            <div class="grid grid-cols-2 gap-4">
              <div class="aspect-[3/4] bg-gray-200 rounded-lg flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <div class="aspect-[3/4] bg-gray-200 rounded-lg flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Product Info (Center) -->
        <div class="lg:col-span-2">
          <div class="space-y-6">
            <!-- Title -->
            <h1 class="text-2xl font-futura-bold text-primary">{{ product.name || 'Player' }}</h1>
            
            <!-- Card Attributes -->
            <div class="flex flex-wrap gap-3">
              <!-- Numbered -->
              <div v-if="product.is_numbered" class="relative group">
                <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
                  <span class="text-primary font-futura-bold text-lg">/10</span>
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                  NUMBERED
                  <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                </div>
              </div>
              
              <!-- Autograph -->
              <div v-if="product.is_autograph" class="relative group">
                <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
                  <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                  </svg>
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                  AUTOGRAPH
                  <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                </div>
              </div>
              
              <!-- Relic -->
              <div v-if="product.is_relic" class="relative group">
                <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
                  <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.429 3.658L9.3 16.573z"/>
                  </svg>
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                  RELIC
                  <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                </div>
              </div>

              <!-- Rookie -->
              <div v-if="product.is_rookie" class="relative group">
                <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
                  <span class="text-primary font-futura-bold text-lg">RC</span>
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                  ROOKIE
                  <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                </div>
              </div>
            </div>
            
            <!-- Key Information -->
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-gray-600 font-gill-sans">Team:</span>
                <span class="font-futura-bold text-primary">{{ product.team || 'Team Name' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 font-gill-sans">Set:</span>
                <span class="font-futura-bold text-primary">{{ product.set_name || 'Set Name' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 font-gill-sans">Year:</span>
                <span class="font-futura-bold text-primary">{{ product.year || '2024' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 font-gill-sans">Rarity:</span>
                <span class="font-futura-bold text-primary">{{ product.rarity || 'Rare' }}</span>
              </div>
            </div>
            
            <!-- Condition -->
            <div class="bg-gray-100 px-4 py-2 rounded-lg">
              <span class="text-primary font-futura-bold">{{ product.condition || 'LIGHT PLAYED' }}</span>
            </div>
            
            <!-- Price -->
            <div class="text-2xl font-futura-bold text-primary">
              {{ product.price || '95' }}
            </div>
            
            <!-- Add to Cart Button -->
            <button class="w-full bg-primary text-white py-3 px-6 rounded-lg font-futura-bold text-lg hover:bg-primary/90 transition-colors">
              ADD TO CART
            </button>
          </div>
        </div>

        <!-- Price Trend (Right) -->
        <div class="lg:col-span-2">
          <BarChart 
            :product-id="product.id"
            :current-price="product.price"
          />
        </div>
      </div>

      <!-- Seller Details -->
      <div class="bg-gray-50 p-6 rounded-lg mb-8">
        <h3 class="text-xl font-futura-bold text-primary mb-4">Seller Details</h3>
        
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
              <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
              </svg>
              <a href="#" class="text-primary hover:text-secondary transition-colors font-futura-bold">
                Nome Venditore
              </a>
            </div>
            
            <div class="bg-primary text-white px-3 py-1 rounded-lg text-sm font-futura-bold">
              8 Numero di vendite
            </div>
            
            <div class="flex items-center space-x-1">
              <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
          </div>
          
          <div class="flex space-x-3">
            <button class="bg-primary text-white px-4 py-2 rounded-lg font-futura-bold text-sm hover:bg-primary/90 transition-colors">
              Chat
            </button>
            <button 
              @click="showReportPopup = true"
              class="bg-red-500 text-white px-4 py-2 rounded-lg font-futura-bold text-sm hover:bg-red-600 transition-colors"
            >
              REPORT
            </button>
          </div>
        </div>
      </div>

      <!-- Related Products -->
      <div>
        <ProductCarousel 
          :title="'Related Products'"
          :products="relatedProducts"
          :category="product.category"
          :section="'related'"
          :use-dynamic-data="false"
        />
      </div>
      </div>
    </div>

    <!-- Footer -->
    <Footer />

    <!-- Report Popup -->
    <ReportPopup 
      :is-open="showReportPopup"
      :product-id="product.id"
      :seller-name="sellerName"
      @close="showReportPopup = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'
import ProductCarousel from '../components/ProductCarousel.vue'
import ReportPopup from '../components/ReportPopup.vue'
import BarChart from '../components/BarChart.vue'
import cardService from '../services/cardService.js'

const route = useRoute()

// Product data
const product = ref({
  id: route.params.id,
  name: 'Player',
  team: 'Team Name',
  set_name: 'Set Name',
  year: '2024',
  rarity: 'Rare',
  price: '95',
  image_url: null,
  category: 'football',
  condition: 'LIGHT PLAYED',
  is_numbered: true,
  is_autograph: true,
  is_relic: true,
  is_rookie: true,
  serial_number: '10'
})

// Loading and error states
const loading = ref(true)
const error = ref(null)

// Report popup
const showReportPopup = ref(false)
const sellerName = ref('Nome Venditore')

// Related products
const relatedProducts = ref([
  {
    id: 1,
    name: 'Related Player 1',
    team: 'Team A',
    type: 'Calcio',
    price: '€120.00',
    rating: '4.8',
    image_url: null
  },
  {
    id: 2,
    name: 'Related Player 2',
    team: 'Team B',
    type: 'Calcio',
    price: '€85.00',
    rating: '4.6',
    image_url: null
  },
  {
    id: 3,
    name: 'Related Player 3',
    team: 'Team C',
    type: 'Calcio',
    price: '€150.00',
    rating: '4.9',
    image_url: null
  },
  {
    id: 4,
    name: 'Related Player 4',
    team: 'Team D',
    type: 'Calcio',
    price: '€75.00',
    rating: '4.5',
    image_url: null
  }
])

// Methods
const loadProductDetails = async () => {
  loading.value = true
  error.value = null

  try {
    const response = await cardService.getCardDetails(route.params.id)
    
    if (response.success) {
      // Merge database data with fallback data, preserving fallback values for missing attributes
      product.value = {
        ...product.value,
        ...response.data,
        // Force attributes to true for testing
        is_numbered: true,
        is_autograph: true,
        is_relic: true,
        is_rookie: true,
        serial_number: '10'
      }
    } else {
      error.value = response.error
      console.error('Error loading product:', response.error)
    }
  } catch (err) {
    error.value = 'Errore di connessione'
    console.error('Error loading product details:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadProductDetails()
})
</script>
