<template>
  <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden h-full flex flex-col">
    <!-- Image Area -->
    <div class="relative aspect-square w-full bg-gray-100 overflow-hidden flex-shrink-0">
      <!-- Real Image -->
      <img 
        v-if="product.imageUrl" 
        :src="product.imageUrl" 
        :alt="product.name || 'Carta'"
        class="w-full h-full object-cover"
        @error="handleImageError"
      />
      <!-- Placeholder Image - sempre presente ma nascosto se c'è immagine -->
      <div :class="['w-full h-full flex items-center justify-center bg-gray-200', product.imageUrl ? 'hidden' : '']">
        <svg class="w-16 h-16 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
        </svg>
      </div>
      
      <!-- Camera Icon Overlay -->
      <!-- <div class="absolute bottom-2 left-2 w-8 h-8 bg-blue-600 rounded flex items-center justify-center">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
        </svg>
      </div> -->
    </div>

    <!-- Player Name / Product Title -->
    <div class="px-2 sm:px-4 pt-2 sm:pt-4 pb-1 sm:pb-2 flex-shrink-0">
      <h3 class="text-sm sm:text-lg font-bold text-gray-900 text-center line-clamp-2">
        {{ product.listing_type && ['sealed-pack', 'sealed-box', 'lot'].includes(product.listing_type) 
          ? (product.team || product.set || product.name || 'Carta')
          : (product.name || 'Player') }}
      </h3>
    </div>

    <!-- Informational Badges -->
    <div class="px-2 sm:px-4 pb-2 sm:pb-3 flex flex-wrap gap-1.5 sm:gap-3 justify-center flex-shrink-0">
      <!-- Numbered - Mostra solo se presente e valido (solo card_number_in_set) -->
      <div v-if="isValidNumbered" class="relative group">
        <div class="bg-gray-100 p-1.5 sm:p-3 rounded-lg flex items-center justify-center min-w-[36px] min-h-[36px] sm:min-w-[48px] sm:min-h-[48px]">
          <span class="text-primary font-futura-bold text-xs sm:text-lg">{{ product.card_number_in_set }}</span>
        </div>
        <!-- Tooltip -->
        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
          NUMBERED
          <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
        </div>
      </div>

      <!-- Autograph - Mostra solo se is_autograph è true -->
      <div v-if="product.is_autograph" class="relative group">
        <div class="bg-gray-100 p-1.5 sm:p-3 rounded-lg flex items-center justify-center min-w-[36px] min-h-[36px] sm:min-w-[48px] sm:min-h-[48px]">
          <svg class="w-4 h-4 sm:w-6 sm:h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
          </svg>
        </div>
        <!-- Tooltip -->
        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
          AUTOGRAPH
          <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
        </div>
      </div>

      <!-- Relic - Mostra solo se is_relic è true -->
      <div v-if="product.is_relic" class="relative group">
        <div class="bg-gray-100 p-1.5 sm:p-3 rounded-lg flex items-center justify-center min-w-[36px] min-h-[36px] sm:min-w-[48px] sm:min-h-[48px]">
          <svg class="w-4 h-4 sm:w-6 sm:h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.429 3.658L9.3 16.573z"></path>
          </svg>
        </div>
        <!-- Tooltip -->
        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
          RELIC
          <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
        </div>
      </div>

      <!-- Rookie - Mostra solo se is_rookie è true -->
      <div v-if="product.is_rookie" class="relative group">
        <div class="bg-gray-100 p-1.5 sm:p-3 rounded-lg flex items-center justify-center min-w-[36px] min-h-[36px] sm:min-w-[48px] sm:min-h-[48px]">
          <span class="text-primary font-futura-bold text-xs sm:text-lg">RC</span>
        </div>
        <!-- Tooltip -->
        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
          ROOKIE
          <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
        </div>
      </div>

      <!-- Star - Mostra solo se is_star è true -->
      <div v-if="product.is_star" class="relative group">
        <div class="bg-gray-100 p-1.5 sm:p-3 rounded-lg flex items-center justify-center min-w-[36px] min-h-[36px] sm:min-w-[48px] sm:min-h-[48px]">
          <svg class="w-4 h-4 sm:w-6 sm:h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
          </svg>
        </div>
        <!-- Tooltip -->
        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
          STAR
          <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
        </div>
      </div>

      <!-- Legend - Mostra solo se is_legend è true -->
      <div v-if="product.is_legend" class="relative group">
        <div class="bg-gray-100 p-1.5 sm:p-3 rounded-lg flex items-center justify-center min-w-[36px] min-h-[36px] sm:min-w-[48px] sm:min-h-[48px]">
          <svg class="w-4 h-4 sm:w-6 sm:h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
        </div>
        <!-- Tooltip -->
        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
          LEGEND
          <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
        </div>
      </div>
    </div>

    <!-- Card Details -->
    <div class="px-2 sm:px-4 pb-2 sm:pb-3 space-y-0.5 sm:space-y-1 flex-grow">
      <div class="flex justify-between text-xs sm:text-sm text-gray-600">
        <span class="truncate">Team:</span>
        <span class="font-medium truncate ml-2">{{ product.team || 'Team' }}</span>
      </div>
      <div class="flex justify-between text-xs sm:text-sm text-gray-600">
        <span class="truncate">Set:</span>
        <span class="font-medium truncate ml-2 line-clamp-1">{{ product.set || 'Set' }}</span>
      </div>
      <div class="flex justify-between text-xs sm:text-sm text-gray-600">
        <span class="truncate">Rarity:</span>
        <span class="font-medium truncate ml-2 line-clamp-1">{{ product.rarity || 'Rarity' }}{{ product.rarity_variation ? ` (${product.rarity_variation})` : '' }}</span>
      </div>
    </div>

    <!-- Condition Indicator -->
    <div class="px-2 sm:px-4 pb-2 sm:pb-4 flex justify-center flex-shrink-0">
      <div class="bg-white border border-gray-200 rounded-md px-2 sm:px-3 py-0.5 sm:py-1">
        <span class="text-xs sm:text-sm font-medium text-gray-700">{{ displayCondition }}</span>
      </div>
    </div>

    <!-- Price and Add to Cart -->
    <div class="px-2 sm:px-4 pb-2 sm:pb-4 flex items-center justify-between flex-shrink-0">
      <div class="text-base sm:text-lg font-bold text-gray-900">
        €{{ formatPrice(product.price) }}
      </div>
      <button 
        @click.stop="addToCart"
        class="w-10 h-10 bg-blue-600 rounded-md flex items-center justify-center hover:bg-blue-700 transition-colors"
        :disabled="loading"
        aria-label="Aggiungi al carrello"
      >
        <svg v-if="!loading" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
        </svg>
        <div v-else class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { formatPriceItaliana } from '../utils/priceFormatter'
import { formatCondition } from '../utils/conditionFormatter'

// Props
const props = defineProps({
  product: {
    type: Object,
    required: true,
    default: () => ({
      id: null,
      name: 'Player',
      team: 'Team',
      set: 'Set',
      year: 'Year',
      rarity: 'Rarity',
      condition: 'NEAR MINT',
      price: 'Price',
      card_number_in_set: null,
      card_number: null,
      is_autograph: false,
      is_relic: false,
      is_rookie: false,
      is_star: false,
      is_legend: false,
      imageUrl: null
    })
  }
})

// Verifica se card_number_in_set è valido (non è un identificatore errato)
const isValidNumbered = computed(() => {
  if (!props.product.card_number_in_set) return false
  
  const numbered = props.product.card_number_in_set.toString().trim()
  
  // Se è uguale a card_number, è un errore di importazione
  if (props.product.card_number && numbered === props.product.card_number.toString().trim()) {
    return false
  }
  
  // Se contiene solo lettere e trattini (senza numeri), probabilmente è un identificatore errato
  if (!/\d/.test(numbered)) {
    return false
  }
  
  return true
})

// Emits
const emit = defineEmits(['add-to-cart'])

// Reactive data
const loading = ref(false)

// Methods
const formatPrice = (price) => {
  return formatPriceItaliana(price, false)
}

// Computed per la condizione formattata
const displayCondition = computed(() => {
  return formatCondition(props.product)
})

const handleImageError = (event) => {
  // Gestisci errori di caricamento immagini in modo sicuro
  try {
    if (!event || !event.target) {
      return
    }
    
    const img = event.target
    if (!img) {
      return
    }
    
    // Nascondi l'immagine che ha fallito usando classi CSS
    img.classList.add('hidden')
    
    // Cerca il placeholder nel parent
    const parent = img.parentElement
    if (!parent) {
      return
    }
    
    // Cerca un div placeholder nel parent
    const placeholder = Array.from(parent.children).find(
      el => el !== img && el && el.classList && el.classList.contains('bg-gray-200')
    )
    
    // Mostra il placeholder se esiste, usando solo classi CSS
    if (placeholder && placeholder.classList) {
      placeholder.classList.remove('hidden')
    }
  } catch (error) {
    // Silenziosamente ignora errori per evitare di bloccare l'app
    console.warn('Errore nella gestione del fallimento immagine:', error)
  }
}

const addToCart = async () => {
  loading.value = true
  
  try {
    // Simula chiamata API
    await new Promise(resolve => setTimeout(resolve, 1000))
    
    // Emetti evento
    emit('add-to-cart', props.product)
    
    // Feedback visivo
    console.log('Aggiunto al carrello:', props.product.name)
    
  } catch (error) {
    console.error('Errore aggiunta al carrello:', error)
  } finally {
    loading.value = false
  }
}
</script>
