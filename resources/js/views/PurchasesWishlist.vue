<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Wishlist
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Le tue carte preferite salvate per acquisti futuri
      </p>
    </div>

    <!-- Filtri e Controlli -->
    <div class="mb-6 bg-white rounded-lg border border-gray-200 p-4">
      <div class="flex flex-col sm:flex-row gap-4">
        <!-- Filtro Condizioni -->
        <div class="relative flex-1">
          <select
            v-model="filters.condition"
            @change="loadWishlist"
            class="w-full appearance-none bg-white border border-gray-300 rounded-lg px-4 py-3 pr-10 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary cursor-pointer"
          >
            <option value="">Tutte le condizioni</option>
            <option value="any">Qualsiasi</option>
            <option value="mint">Mint</option>
            <option value="near_mint">Near Mint</option>
            <option value="excellent">Excellent</option>
            <option value="good">Good</option>
          </select>
          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
        
        <!-- Filtro Ordinamento -->
        <div class="relative flex-1">
          <select
            v-model="filters.sortBy"
            @change="loadWishlist"
            class="w-full appearance-none bg-white border border-gray-300 rounded-lg px-4 py-3 pr-10 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary cursor-pointer"
          >
            <option value="created_at">Data aggiunta</option>
            <option value="max_price">Prezzo massimo</option>
            <option value="card_model_id">Nome carta</option>
          </select>
          <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      <p class="mt-2 text-sm text-gray-500">Caricamento wishlist...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
      <div class="flex">
        <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
        <div class="ml-3">
          <h3 class="text-sm font-gill-sans-semibold text-red-800">Errore</h3>
          <p class="mt-1 text-sm text-red-700">{{ error }}</p>
        </div>
      </div>
    </div>

    <!-- Wishlist vuota -->
    <div v-else-if="wishlistItems.length === 0" class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="text-center py-12">
        <HeartIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Wishlist vuota</h3>
        <p class="mt-1 text-sm text-gray-500">Aggiungi carte alla tua wishlist per tenerle d'occhio.</p>
        <div class="mt-6">
          <router-link
            to="/"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-gill-sans-semibold rounded-md text-white bg-primary hover:bg-primary/90"
          >
            Esplora Carte
          </router-link>
        </div>
      </div>
    </div>

    <!-- Lista Wishlist -->
    <div v-else class="space-y-4">
      <div
        v-for="item in wishlistItems"
        :key="item.id"
        class="bg-white rounded-lg border border-gray-200 p-4 sm:p-6 hover:shadow-md transition-shadow"
      >
        <div class="flex flex-col sm:flex-row gap-4 sm:gap-6">
          <!-- Immagine carta -->
          <div class="flex-shrink-0">
            <div v-if="(item.cardModel || item.card_model)?.image_url && (item.cardModel || item.card_model).image_url.trim() !== ''" 
                 class="w-24 h-32 sm:w-28 sm:h-40 rounded-lg overflow-hidden bg-gray-100">
              <img
                :src="(item.cardModel || item.card_model).image_url"
                :alt="(item.cardModel || item.card_model)?.name"
                class="w-full h-full object-cover"
              />
            </div>
            <div v-else class="w-24 h-32 sm:w-28 sm:h-40 rounded-lg bg-gray-200 flex items-center justify-center">
              <div class="text-center text-gray-400">
                <svg class="w-10 h-10 sm:w-12 sm:h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-xs font-gill-sans text-gray-500">N/A</p>
              </div>
            </div>
          </div>

          <!-- Dettagli carta -->
          <div class="flex-1 min-w-0 flex flex-col justify-between">
            <div>
              <h3 class="text-lg sm:text-xl font-futura-bold text-gray-900 mb-1">
                {{ (item.cardModel || item.card_model)?.name || 'Carta' }}
              </h3>
              <p class="text-sm text-gray-500 mb-3">
                {{ getCardSetName(item) }} - {{ (item.cardModel || item.card_model)?.year || '' }}
              </p>
              
              <!-- Attributi carta -->
              <div class="space-y-1 text-sm text-gray-600">
                <div v-if="(item.cardModel || item.card_model)?.player">
                  <span class="font-medium">{{ (item.cardModel || item.card_model).player.name || (item.cardModel || item.card_model).player.display_name }}</span>
                </div>
                <div v-if="(item.cardModel || item.card_model)?.team">
                  <span>{{ (item.cardModel || item.card_model).team.name }}</span>
                </div>
                <div v-if="(item.cardModel || item.card_model)?.card_set?.name">
                  <span>{{ (item.cardModel || item.card_model).card_set.name }}</span>
                </div>
                <div v-if="(item.cardModel || item.card_model)?.rarity">
                  <span class="capitalize">{{ (item.cardModel || item.card_model).rarity }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Prezzo e azioni -->
          <div class="flex flex-col items-start sm:items-end justify-between gap-4 sm:gap-2 w-full sm:min-w-[140px]">
            <div class="w-full sm:text-right">
              <p class="text-xs text-gray-500 mb-1">Prezzo più basso</p>
              <p class="text-2xl sm:text-3xl font-futura-bold text-green-600">
                {{ formatPrice(item.lowest_price) }}
              </p>
            </div>

            <!-- Pulsanti azioni -->
            <div class="flex flex-row gap-2 w-full sm:w-auto">
              <button
                @click="viewCard(item)"
                class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-gill-sans-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors"
              >
                Visualizza
              </button>
              <button
                @click="removeFromWishlist(item.card_model_id)"
                class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2 bg-red-600 rounded-lg text-sm font-gill-sans-semibold text-white hover:bg-red-700 transition-colors"
              >
                Rimuovi
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginazione -->
      <div v-if="pagination && pagination.last_page > 1" class="mt-6">
        <nav class="flex items-center justify-between">
          <div class="flex-1 flex justify-between sm:hidden">
            <button
              @click="loadWishlist(pagination.current_page - 1)"
              :disabled="!pagination.prev_page_url"
              class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-gill-sans-semibold rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              Precedente
            </button>
            <button
              @click="loadWishlist(pagination.current_page + 1)"
              :disabled="!pagination.next_page_url"
              class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-gill-sans-semibold rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
            >
              Successivo
            </button>
          </div>
          <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
              <p class="text-sm text-gray-700">
                Mostrando
                <span class="font-gill-sans-semibold">{{ pagination.from }}</span>
                a
                <span class="font-gill-sans-semibold">{{ pagination.to }}</span>
                di
                <span class="font-gill-sans-semibold">{{ pagination.total }}</span>
                risultati
              </p>
            </div>
            <div>
              <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                <button
                  v-for="page in getPageNumbers()"
                  :key="page"
                  @click="loadWishlist(page)"
                  :class="[
                    page === pagination.current_page
                      ? 'z-10 bg-primary border-primary text-white'
                      : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                    'relative inline-flex items-center px-4 py-2 border text-sm font-gill-sans-semibold'
                  ]"
                >
                  {{ page }}
                </button>
              </nav>
            </div>
          </div>
        </nav>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useWishlistStore } from '@/stores/wishlist.js'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { 
  HeartIcon, 
  ExclamationTriangleIcon 
} from '@heroicons/vue/24/outline'

const router = useRouter()
const wishlistStore = useWishlistStore()

// Refs
const loading = ref(false)
const error = ref(null)
const pagination = ref(null)
const filters = ref({
  condition: '',
  sortBy: 'created_at'
})

// Computed per gli articoli della wishlist
const wishlistItems = computed(() => wishlistStore.wishlistItems)

// Metodi
const loadWishlist = async (page = 1) => {
  loading.value = true
  error.value = null
  
  try {
    // Usa il store per caricare la wishlist
    await wishlistStore.loadWishlist()
  } catch (err) {
    console.error('Errore nel caricamento wishlist:', err)
    error.value = err.message
  } finally {
    loading.value = false
  }
}

const removeFromWishlist = async (cardModelId) => {
  if (!confirm('Sei sicuro di voler rimuovere questa carta dalla wishlist?')) {
    return
  }
  
  try {
    // Usa il store per rimuovere dalla wishlist
    const result = await wishlistStore.removeFromWishlist(cardModelId)
    if (!result.success) {
      throw new Error(result.message || 'Errore nella rimozione dalla wishlist')
    }
  } catch (err) {
    console.error('Errore nella rimozione:', err)
    error.value = err.message
  }
}

const viewCard = (item) => {
  // Accedi ai dati correttamente
  const cardModel = item.cardModel || item.card_model
  
  // Usa l'URL SEO-friendly se disponibile
  if (cardModel?.category?.slug && cardModel?.slug) {
    const categoryMap = {
      'calcio': 'football',
      'basketball': 'basketball', 
      'pokemon': 'pokemon'
    }
    const categorySlug = categoryMap[cardModel.category.slug] || cardModel.category.slug
    const seoUrl = `/${categorySlug}/${cardModel.slug}`
    router.push(seoUrl)
  } else {
    // Fallback all'ID se non ci sono slug
    const fallbackUrl = `/cards/${item.card_model_id}`
    router.push(fallbackUrl)
  }
}


const formatPrice = (price) => {
  if (!price && price !== 0) return 'N/A'
  // Formatta il prezzo come nell'immagine: 6.500,00 (senza simbolo €)
  return new Intl.NumberFormat('it-IT', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

const getCardSetName = (item) => {
  const cardModel = item.cardModel || item.card_model
  if (!cardModel) return ''
  
  // Prova diversi campi per il nome del set
  return cardModel.card_set?.name || 
         cardModel.set_name || 
         cardModel.cardSet?.name || 
         ''
}

const getPageNumbers = () => {
  if (!pagination.value) return []
  
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const pages = []
  
  for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
    pages.push(i)
  }
  
  return pages
}

// Lifecycle
onMounted(() => {
  loadWishlist()
})
</script>