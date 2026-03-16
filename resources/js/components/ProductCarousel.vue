<template>
  <div class="relative">
    <!-- Section header -->
    <div class="mb-4">
      <h2 class="text-xl md:text-3xl font-futura-bold text-primary">{{ title }}</h2>
      <div v-if="!shouldHideSeeAll" class="mt-0 text-right">
        <a :href="seeAllUrl || (category ? `/category/${category}` : '#')" class="text-primary hover:text-secondary transition-colors font-gill-sans-semibold text-sm">
          {{ seeAllText }}
          <svg class="w-4 h-4 inline ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </a>
      </div>
    </div>

    <!-- Carousel container -->
    <div class="relative group">
      <!-- Left arrow -->
      <button 
        @click="scrollLeft"
        :disabled="!canScrollLeft"
        class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-primary hover:bg-primary/90 text-white p-3 rounded-full shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 group-hover:opacity-100 opacity-0"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
      </button>

      <!-- Right arrow -->
      <button 
        @click="scrollRight"
        :disabled="!canScrollRight"
        class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-primary hover:bg-primary/90 text-white p-3 rounded-full shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 group-hover:opacity-100 opacity-0"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>

      <!-- Products container -->
      <div 
        ref="container"
        class="flex space-x-6 overflow-x-auto scrollbar-hide px-4 py-4"
        @scroll="handleScroll"
        style="scroll-behavior: smooth; -webkit-overflow-scrolling: touch;"
      >
                    <div 
                      v-for="(product, index) in displayProducts" 
                      :key="product.id + '-' + index"
                      class="flex-shrink-0 w-40 sm:w-60 lg:w-72 group cursor-pointer"
                      @click="goToProduct(product)"
                    >
                      <!-- Product Card -->
                      <div class="h-60 sm:h-80 lg:h-96 rounded-lg shadow-sm hover:shadow-lg transition-all duration-300 relative">
                        <!-- NEW tag -->
                        <div v-if="index < 2" class="absolute top-3 right-3 z-10 bg-primary text-white text-xs font-futura-bold px-2 py-1 rounded">
                          NEW
                        </div>
                        
                        <!-- Player/Character Icon (for top_players section) -->
                        <div v-if="shouldShowPlayerIcon" 
                             class="w-full h-full rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center relative">
                          <img 
                            v-if="product.icon_path"
                            :src="product.icon_path" 
                            :alt="product.player_name || product.name"
                            class="w-full h-full object-contain p-4"
                            @error="(e) => { 
                              e.target.style.display = 'none' 
                            }"
                          />
                          <div v-else class="text-center text-gray-500 p-4">
                            <p class="text-sm">Icona non disponibile</p>
                            <p class="text-xs">{{ product.player_name || product.name }}</p>
                          </div>
                        </div>
                        
                        <!-- Product background image (for other sections) -->
                        <div v-else
                             class="w-full h-full rounded-lg overflow-hidden bg-cover bg-center bg-no-repeat relative" 
                             :class="(product.image_url || product.imageUrl) ? 'bg-gray-300' : 'bg-gray-300'"
                             :style="(product.image_url || product.imageUrl) ? { backgroundImage: `url(${product.image_url || product.imageUrl})` } : {}">
                          <!-- Fallback content when no image -->
                          <div v-if="!product.image_url && !product.imageUrl" class="absolute inset-0 flex items-center justify-center bg-gray-300">
                            <div class="text-center text-gray-500">
                              <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                              </svg>
                              <p class="text-sm font-gill-sans">Immagine non disponibile</p>
                            </div>
                          </div>
                          <!-- Overlay for better visibility when image exists -->
                          <div v-if="product.image_url || product.imageUrl" class="absolute inset-0 bg-black/20 group-hover:bg-black/10 transition-colors duration-300"></div>
                        </div>
                      </div>
                      
                      <!-- Player Name Below Card -->
                      <div class="mt-3 text-center">
                        <h4 class="text-lg font-futura-bold text-primary group-hover:text-secondary transition-colors duration-300">
                          {{ shouldShowPlayerIcon && product.player_name ? product.player_name : product.name }}
                        </h4>
                        <!-- <p class="text-sm text-gray-600 font-gill-sans">{{ product.team || product.type || 'Carta da collezione' }}</p> -->
                      </div>
                    </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import cardService from '../services/cardService.js'

// Props
const props = defineProps({
  title: {
    type: String,
    required: true
  },
  products: {
    type: Array,
    required: true
  },
  category: {
    type: String,
    default: null
  },
  section: {
    type: String,
    default: null
  },
  useDynamicData: {
    type: Boolean,
    default: false
  },
  hideSeeAll: {
    type: Boolean,
    default: false
  },
  seeAllUrl: {
    type: String,
    default: null
  },
  limit: {
    type: Number,
    default: 8
  }
})

// Router
const router = useRouter()
const route = useRoute()

// Reactive data
const dynamicProducts = ref([])
const loading = ref(false)
const error = ref(null)

// Refs
const container = ref(null)
const scrollPosition = ref(0)
const containerWidth = ref(0)
const scrollWidth = ref(0)

// Computed
const canScrollLeft = ref(false)
const canScrollRight = ref(true)

// Computed property to check if we should show player icon
const shouldShowPlayerIcon = computed(() => {
  return (props.section === 'top_players' || props.section === 'top_characters') && (props.category === 'football' || props.category === 'basketball' || props.category === 'disney')
})

// Computed property for products (dynamic or static)
const displayProducts = computed(() => {
  if (props.useDynamicData && props.category && props.section) {
    return dynamicProducts.value.length > 0 ? dynamicProducts.value : props.products
  }
  return props.products
})

// Computed property to check if we're already on the category page
const isOnCategoryPage = computed(() => {
  if (!props.category) return false
  const currentPath = route.path
  const categoryPath = `/category/${props.category}`
  return currentPath === categoryPath
})

// Computed property to determine if we should hide the "See All" button
const shouldHideSeeAll = computed(() => {
  // Hide if explicitly set via prop
  if (props.hideSeeAll) return true
  // Hide if we're already on the category page
  if (isOnCategoryPage.value) return true
  return false
})

// Computed property for "VEDI TUTTO" text based on category
const seeAllText = computed(() => {
  switch (props.category) {
    case 'football':
      return 'VEDI TUTTO CALCIO'
    case 'basketball':
      return 'VEDI TUTTO BASKETBALL'
    case 'pokemon':
      return 'VEDI TUTTO POKEMON'
    default:
      return 'VEDI TUTTO'
  }
})

// Methods
const loadDynamicData = async () => {
  if (!props.useDynamicData || !props.category || !props.section) {
    return
  }

  loading.value = true
  error.value = null

  try {
    const response = await cardService.getCardsByCategory(
      props.category, 
      props.section, 
      props.limit
    )

    console.log(`API Response for ${props.category}/${props.section}:`, response)

    if (response.success) {
      dynamicProducts.value = response.data
      console.log(`Loaded ${dynamicProducts.value.length} items for ${props.category}`)
      // Attendi che Vue finisca il rendering del DOM
      setTimeout(() => {
        updateScrollButtons()
      }, 300)
    } else {
      // Use fallback data if API fails
      dynamicProducts.value = cardService.getFallbackData(props.category, props.section)
      error.value = response.error
    }
  } catch (err) {
    console.error('Error loading dynamic data:', err)
    // Use fallback data
    dynamicProducts.value = cardService.getFallbackData(props.category, props.section)
    error.value = 'Errore di connessione'
  } finally {
    loading.value = false
  }
}

const handleScroll = () => {
  if (container.value) {
    scrollPosition.value = container.value.scrollLeft
    updateScrollButtons()
  }
}

const updateScrollButtons = () => {
  if (container.value) {
    const currentScroll = container.value.scrollLeft
    const maxScroll = container.value.scrollWidth - container.value.offsetWidth
    
    canScrollLeft.value = currentScroll > 5
    canScrollRight.value = currentScroll < (maxScroll - 5)
    
    scrollPosition.value = currentScroll
    scrollWidth.value = container.value.scrollWidth
    containerWidth.value = container.value.offsetWidth
    
    console.log(`Carousel status (${props.title}):`, {
      scrollWidth: scrollWidth.value,
      containerWidth: containerWidth.value,
      canScrollRight: canScrollRight.value,
      productsCount: displayProducts.value.length
    })
  }
}

const scrollLeft = () => {
  if (container.value) {
    container.value.scrollBy({ left: -300, behavior: 'smooth' })
  }
}

const scrollRight = () => {
  if (container.value) {
    container.value.scrollBy({ left: 300, behavior: 'smooth' })
  }
}

const goToProduct = (product) => {
  // Se il prodotto ha listing_id, usa il formato /category/:listingId/:slug
  if (product.listing_id) {
    // Usa category_slug se disponibile, altrimenti determina dalla categoria
    let category = product.category_slug || props.category || 'football'
    
    // Se category_slug non è disponibile, mappa il tipo
    if (!product.category_slug) {
      const typeMap = {
        'Calcio': 'football',
        'calcio': 'football',
        'Basketball': 'basketball',
        'basketball': 'basketball',
        'Pokemon': 'pokemon',
        'pokemon': 'pokemon',
        'Disney': 'disney',
        'disney': 'disney',
        'Spongebob': 'spongebob',
        'spongebob': 'spongebob'
      }
      
      if (product.type && typeMap[product.type]) {
        category = typeMap[product.type]
      } else if (props.category) {
        category = props.category
      }
    }
    
    // Usa slug se disponibile, altrimenti genera dal nome
    let slug = product.slug
    if (!slug) {
      slug = product.name
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '') // Rimuove caratteri speciali
        .replace(/\s+/g, '-') // Sostituisce spazi con trattini
        .replace(/-+/g, '-') // Rimuove trattini multipli
        .replace(/^-+|-+$/g, '') // Rimuove trattini all'inizio e alla fine
    }
    
    const url = `/${category}/${product.listing_id}/${slug}`
    console.log('Navigating to listing:', url)
    window.location.href = url
    return
  }
  
  // Per Disney/Spongebob senza listing_id, naviga a /category/:slug
  if (product.category_slug && ['disney', 'spongebob'].includes(product.category_slug)) {
    let slug = product.slug
    if (!slug) {
      slug = product.name
        .toLowerCase()
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-+|-+$/g, '')
    }
    const url = `/${product.category_slug}/${slug}`
    console.log('Navigating to card:', url)
    router.push(url)
    return
  }
  
  // Per top players/characters (senza listing_id), naviga alla pagina /top/:category/:name
  // Questo è il caso per le sezioni "Top Player" e "Top Characters"
  const typeMap = {
    'Calcio': 'football',
    'Basketball': 'basketball', 
    'Pokemon': 'pokemon',
    'Disney': 'disney'
  }
  
  // Determina la categoria - usa category_slug se disponibile, altrimenti props.category
  let category = product.category_slug || props.category || 'football'
  if (!category && product.type && typeMap[product.type]) {
    category = typeMap[product.type]
  }
  
  // Genera lo slug dal nome del giocatore/character (usa player_name se disponibile)
  const displayName = product.player_name || product.name
  const playerSlug = displayName
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '') // Rimuove caratteri speciali
    .replace(/\s+/g, '-') // Sostituisce spazi con trattini
    .replace(/-+/g, '-') // Rimuove trattini multipli
    .replace(/^-+|-+$/g, '') // Rimuove trattini all'inizio e alla fine
  
  // Naviga alla pagina dei top players/characters
  const url = `/top/${category}/${playerSlug}`
  console.log('Navigating to top player/character page:', url)
  window.location.href = url
}

const updateDimensions = () => {
  if (container.value) {
    containerWidth.value = container.value.clientWidth
    scrollWidth.value = container.value.scrollWidth
    updateScrollButtons()
  }
}

// Lifecycle
// Watchers
watch(() => [props.category, props.section, props.useDynamicData], () => {
  if (props.useDynamicData) {
    loadDynamicData()
  }
}, { immediate: true })

// Watch for changes in displayProducts to update scroll buttons
watch(displayProducts, () => {
  setTimeout(updateScrollButtons, 300)
}, { deep: true })

onMounted(() => {
  updateDimensions()
  window.addEventListener('resize', updateDimensions)
  
  // Load dynamic data if needed
  if (props.useDynamicData) {
    loadDynamicData()
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', updateDimensions)
})
</script>

<style scoped>
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>

