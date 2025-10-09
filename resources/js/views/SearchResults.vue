<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <!-- Search Results Section -->
    <div class="bg-white py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Search Header -->
        <div class="mb-8">
          <h1 class="text-3xl font-futura-bold text-primary mb-2">
            Risultati per "{{ searchQuery }}"
          </h1>
          <p class="text-gray-600 font-gill-sans">
            {{ totalResults }} risultati trovati
          </p>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="flex justify-center items-center py-12">
          <div class="flex items-center space-x-3">
            <svg class="animate-spin h-8 w-8 text-secondary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-600 font-gill-sans">Ricerca in corso...</span>
          </div>
        </div>

        <!-- No Results -->
        <div v-else-if="!isLoading && cards.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <h3 class="text-lg font-gill-sans-semibold text-gray-900 mb-2">Nessun risultato trovato</h3>
          <p class="text-gray-600 font-gill-sans mb-4">
            Prova a modificare i termini di ricerca o esplora le nostre categorie
          </p>
          <router-link to="/categories" class="bg-secondary text-primary px-6 py-3 rounded-lg font-futura-bold text-sm hover:bg-opacity-90 transition-colors">
            Esplora Categorie
          </router-link>
        </div>

        <!-- Results Grid -->
        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          <div 
            v-for="card in cards" 
            :key="card.id"
            class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow cursor-pointer"
            @click="goToCardDetail(card)"
          >
            <!-- Card Image Placeholder -->
            <div class="aspect-square bg-gray-100 rounded-t-lg flex items-center justify-center">
              <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
            </div>
            
            <!-- Card Info -->
            <div class="p-4">
              <h3 class="font-gill-sans-semibold text-gray-900 text-sm mb-1 line-clamp-2">
                {{ card.name }}
              </h3>
              <p class="text-xs text-gray-600 mb-2">
                {{ card.set_name }} ({{ card.year }})
              </p>
              <div class="flex items-center justify-between">
                <span class="text-xs text-gray-500">
                  {{ card.category?.name || 'N/A' }}
                </span>
                <span v-if="card.card_listings && card.card_listings.length > 0" class="text-xs font-gill-sans-semibold text-secondary">
                  {{ card.card_listings.length }} in vendita
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Load More Button -->
        <div v-if="hasMoreResults && !isLoading" class="text-center mt-8">
          <button 
            @click="loadMore"
            class="bg-primary text-white px-6 py-3 rounded-lg font-futura-bold text-sm hover:bg-opacity-90 transition-colors"
          >
            Carica Altri Risultati
          </button>
        </div>
      </div>
    </div>
    
    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'

const route = useRoute()
const router = useRouter()

// Refs
const searchQuery = ref('')
const cards = ref([])
const isLoading = ref(false)
const totalResults = ref(0)
const currentPage = ref(1)
const hasMoreResults = ref(false)

// Funzione per cercare le carte
const searchCards = async (page = 1) => {
  if (!searchQuery.value.trim()) return
  
  isLoading.value = true
  
  try {
    const response = await fetch(`/api/cards/search?search=${encodeURIComponent(searchQuery.value)}&page=${page}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      
      if (page === 1) {
        cards.value = data.data || []
      } else {
        cards.value.push(...(data.data || []))
      }
      
      totalResults.value = data.total || 0
      hasMoreResults.value = data.current_page < data.last_page
      currentPage.value = data.current_page || 1
    } else {
      console.error('Errore durante la ricerca')
      cards.value = []
      totalResults.value = 0
    }
  } catch (error) {
    console.error('Errore durante la ricerca:', error)
    cards.value = []
    totalResults.value = 0
  } finally {
    isLoading.value = false
  }
}

// Funzione per caricare più risultati
const loadMore = () => {
  if (hasMoreResults.value && !isLoading.value) {
    searchCards(currentPage.value + 1)
  }
}

// Funzione per andare al dettaglio della carta
const goToCardDetail = (card) => {
  router.push(`/product/${card.id}`)
}

// Watch per la query di ricerca
watch(() => route.query.q, (newQuery) => {
  if (newQuery) {
    searchQuery.value = newQuery
    searchCards()
  }
}, { immediate: true })

// On mounted
onMounted(() => {
  if (route.query.q) {
    searchQuery.value = route.query.q
    searchCards()
  }
})
</script>
