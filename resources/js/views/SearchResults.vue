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

        <!-- Results Grid - 2 per riga su mobile/tablet, 3-4 su desktop -->
        <div v-else class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 items-stretch">
          <ProductCard 
            v-for="card in cards" 
            :key="card.id"
            :product="transformCardToProduct(card)"
            class="cursor-pointer"
            @click="goToCardDetail(card)"
          />
        </div>

        <!-- Load More Button -->
        <div v-if="hasMoreResults && !isLoading && !isLoadingMore && cards.length > 0" class="text-center mt-8">
          <button 
            @click="loadMore"
            class="bg-primary text-white px-6 py-3 rounded-lg font-futura-bold text-sm hover:bg-opacity-90 transition-colors"
          >
            Carica Altri Risultati
          </button>
        </div>
        
        <!-- Loading indicator per caricamento di più risultati -->
        <div v-if="isLoadingMore" class="text-center mt-8 py-4">
          <div class="flex items-center justify-center space-x-3">
            <svg class="animate-spin h-6 w-6 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-600 font-gill-sans">Caricamento risultati...</span>
          </div>
        </div>
        
        <!-- Debug info (rimuovere in produzione) -->
        <div v-if="false" class="text-xs text-gray-400 mt-4 p-2 bg-gray-100 rounded">
          Debug: hasMoreResults={{ hasMoreResults }}, isLoading={{ isLoading }}, isLoadingMore={{ isLoadingMore }}, 
          cards.length={{ cards.length }}, currentPage={{ currentPage }}, totalResults={{ totalResults }}
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
import ProductCard from '../components/ProductCard.vue'
import { formatCondition } from '../utils/conditionFormatter'

const route = useRoute()
const router = useRouter()

// Refs
const searchQuery = ref('')
const cards = ref([])
const isLoading = ref(false)
const isLoadingMore = ref(false)
const totalResults = ref(0)
const currentPage = ref(1)
const hasMoreResults = ref(false)

// Funzione per cercare le carte
const searchCards = async (page = 1) => {
  if (!searchQuery.value.trim()) return
  
  // Se stiamo caricando più risultati, usa isLoadingMore invece di isLoading
  if (page === 1) {
    isLoading.value = true
    isLoadingMore.value = false
  } else {
    isLoadingMore.value = true
  }
  
  try {
    const url = `/api/cards/search?search=${encodeURIComponent(searchQuery.value)}&page=${page}`
    const response = await fetch(url, {
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
      
      // Gestisci la paginazione - supporta sia il formato diretto che quello annidato
      const currentPageNum = data.current_page || data.pagination?.current_page || page
      const lastPageNum = data.last_page || data.pagination?.last_page || 1
      
      currentPage.value = currentPageNum
      hasMoreResults.value = currentPageNum < lastPageNum
      
      // Debug log per verificare la paginazione
      console.log('Paginazione:', {
        current_page: currentPageNum,
        last_page: lastPageNum,
        total: totalResults.value,
        hasMore: hasMoreResults.value,
        cardsCount: cards.value.length
      })
    } else {
      // Prova a leggere il messaggio di errore dalla risposta
      let errorMessage = `Errore HTTP ${response.status}`
      try {
        const errorData = await response.json()
        errorMessage = errorData.message || errorMessage
      } catch (e) {
        // Ignora se non riesce a parsare la risposta
      }
      console.error('Errore durante la ricerca:', errorMessage, 'URL:', url)
      if (page === 1) {
        cards.value = []
        totalResults.value = 0
      }
    }
  } catch (error) {
    console.error('Errore durante la ricerca:', error)
    if (page === 1) {
      cards.value = []
      totalResults.value = 0
    }
  } finally {
    isLoading.value = false
    isLoadingMore.value = false
  }
}

// Funzione per caricare più risultati
const loadMore = () => {
  if (hasMoreResults.value && !isLoading.value && !isLoadingMore.value) {
    searchCards(currentPage.value + 1)
  }
}

// Funzione per andare al dettaglio della carta
const goToCardDetail = (card) => {
  router.push(`/product/${card.id}`)
}

// Funzione per trasformare i dati della carta nel formato ProductCard
const transformCardToProduct = (card) => {
  // Se i dati della listing sono già presenti direttamente nell'oggetto (ricerca testuale),
  // usali direttamente, altrimenti prendili dal primo listing
  let listingData = {
    condition: card.condition || null,
    grading_company_id: card.grading_company_id || null,
    grading_company: card.grading_company || null,
    card_condition_score: card.card_condition_score || null,
    autograph_condition_score: card.autograph_condition_score || null
  }
  
  // Se i dati non sono presenti direttamente, prova a prenderli dal primo listing
  if (!listingData.condition && card.card_listings && card.card_listings.length > 0) {
    const firstListing = card.card_listings[0]
    listingData = {
      condition: firstListing.condition,
      grading_company_id: firstListing.grading_company_id,
      grading_company: firstListing.grading_company,
      card_condition_score: firstListing.card_condition_score,
      autograph_condition_score: firstListing.autograph_condition_score
    }
  }
  
  // Se ancora non ci sono dati, usa valori null
  if (!listingData.condition) {
    listingData = {
      condition: null,
      grading_company_id: null,
      grading_company: null,
      card_condition_score: null,
      autograph_condition_score: null
    }
  }
  
  return {
    id: card.id,
    name: card.name,
    imageUrl: card.imageUrl || card.image_url,
    team: card.team?.name || card.team || 'N/A',
    set: card.set_name || card.cardSet?.name || 'N/A',
    year: card.year,
    rarity: card.rarity,
    rarity_variation: card.rarity_variation,
    condition: formatCondition(listingData),
    // Passa anche i dati raw per eventuali usi futuri
    ...listingData,
    price: card.price || 0, // Il prezzo è già presente direttamente nell'oggetto quando viene da searchListings
    card_number: card.card_number,
    card_number_in_set: card.card_number_in_set,
    is_autograph: card.is_autograph || false,
    is_relic: card.is_relic || false,
    is_rookie: card.is_rookie || false,
    is_star: card.is_star || false,
    is_legend: card.is_legend || false,
  }
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
