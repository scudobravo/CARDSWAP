<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Breadcrumbs -->
      <nav class="mb-6">
        <ol class="flex items-center space-x-2 text-sm font-gill-sans">
          <li><a href="/" class="text-primary hover:text-secondary">Home</a></li>
          <li class="text-gray-500">></li>
          <li class="text-gray-500">{{ categoryLabel }}</li>
          <li class="text-gray-500">></li>
          <li class="text-gray-700 font-semibold">{{ playerDisplayName }}</li>
        </ol>
      </nav>

      <!-- Player Banner -->
      <div class="bg-white rounded-lg shadow-md p-8 mb-8">
        <h1 class="text-4xl font-futura-bold text-primary mb-2">
          Carte in vendita: {{ playerDisplayName }}
        </h1>
        <p class="text-lg text-gray-600 font-gill-sans">
          {{ categoryLabel }} - Tutte le carte disponibili
        </p>
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
      </div>

      <!-- Error state -->
      <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
        <p class="text-red-800">{{ error }}</p>
      </div>

      <!-- Products Grid -->
      <div v-else-if="products.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 mb-8">
        <ProductCard 
          v-for="product in displayedProducts" 
          :key="product.id || product.listing_id" 
          :product="product"
          class="cursor-pointer"
          @click="goToProduct(product)"
        />
      </div>

      <!-- Empty state -->
      <div v-else class="bg-white rounded-lg shadow-md p-12 text-center">
        <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        <h3 class="text-2xl font-futura-bold text-gray-900 mb-2">
          Nessuna carta trovata
        </h3>
        <p class="text-gray-600 mb-6">
          Non ci sono carte in vendita per {{ playerDisplayName }} al momento.
        </p>
        <a 
          href="/" 
          class="inline-block bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary/90 transition-colors font-gill-sans-semibold"
        >
          Torna alla Home
        </a>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex justify-center items-center space-x-2 mt-8">
        <button 
          @click="loadPage(currentPage - 1)"
          :disabled="currentPage === 1"
          class="px-4 py-2 bg-white border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
        >
          Precedente
        </button>
        <span class="px-4 py-2 text-gray-700">
          Pagina {{ currentPage }} di {{ totalPages }}
        </span>
        <button 
          @click="loadPage(currentPage + 1)"
          :disabled="currentPage === totalPages"
          class="px-4 py-2 bg-white border border-gray-300 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-50 transition-colors"
        >
          Successiva
        </button>
      </div>
    </div>
    
    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'
import ProductCard from '../components/ProductCard.vue'

const route = useRoute()

// Reactive data
const loading = ref(false)
const error = ref(null)
const products = ref([])
const currentPage = ref(1)
const totalPages = ref(1)
const total = ref(0)

// Computed
const category = computed(() => route.params.category || 'football')
const playerName = computed(() => route.params.name || '')

const categoryLabel = computed(() => {
  const labels = {
    'football': 'Calcio',
    'basketball': 'Basket',
    'pokemon': 'Pokemon'
  }
  return labels[category.value] || category.value
})

const playerDisplayName = computed(() => {
  if (!playerName.value) return ''
  // Converti lo slug in nome leggibile
  return playerName.value
    .split('-')
    .map(word => word.charAt(0).toUpperCase() + word.slice(1))
    .join(' ')
})

const displayedProducts = computed(() => {
  return products.value
})

// Methods
const loadProducts = async (page = 1) => {
  loading.value = true
  error.value = null
  
  try {
    const apiUrl = `/api/top/${category.value}/${playerName.value}?page=${page}&per_page=20`
    const response = await fetch(apiUrl)
    
    if (!response.ok) {
      throw new Error('Errore nel caricamento delle carte')
    }
    
    const data = await response.json()
    
    if (data.data) {
      products.value = data.data
      currentPage.value = data.current_page || page
      totalPages.value = data.last_page || 1
      total.value = data.total || 0
    } else {
      products.value = []
    }
  } catch (err) {
    console.error('Errore nel caricamento prodotti:', err)
    error.value = 'Errore nel caricamento delle carte. Riprova più tardi.'
    products.value = []
  } finally {
    loading.value = false
  }
}

const loadPage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    loadProducts(page)
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }
}

const goToProduct = (product) => {
  if (product.listing_id) {
    // Usa category_slug se disponibile
    let categorySlug = product.category_slug || category.value
    
    // Genera slug dal nome se non disponibile
    let slug = product.slug
    if (!slug) {
      slug = (product.name || 'card')
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '')
    }
    
    const url = `/${categorySlug}/${product.listing_id}/${slug}`
    window.location.href = url
  } else {
    // Fallback per carte senza listing_id
    const categorySlug = product.category_slug || category.value
    const slug = (product.name || 'card')
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-+|-+$/g, '')
    
    const url = `/${categorySlug}/${slug}`
    window.location.href = url
  }
}

// Lifecycle
onMounted(() => {
  loadProducts(1)
})
</script>

