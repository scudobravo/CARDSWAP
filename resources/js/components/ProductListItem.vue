<template>
  <div 
    class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden cursor-pointer"
    @click="$emit('click', product)"
  >
    <div class="flex">
      <!-- Image Area -->
      <div class="w-48 h-screen max-h-64 bg-gray-100 overflow-hidden flex-shrink-0 relative">
        <img 
          v-if="product.imageUrl || product.image_url" 
          :src="product.imageUrl || product.image_url" 
          :alt="product.name || 'Carta'"
          class="w-full h-full object-cover"
          @error="handleImageError"
        />
        <div :class="['w-full h-full flex items-center justify-center bg-gray-200', (product.imageUrl || product.image_url) ? 'hidden' : '']">
          <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
          </svg>
        </div>
      </div>
      
      <!-- Product Details -->
      <div class="flex-1 p-4 flex flex-col justify-between">
        <div>
          <h3 class="text-lg font-bold text-gray-900 hover:text-primary">
            {{ product.name || 'Player' }}
          </h3>
          
          <!-- Badges Row -->
          <div class="flex flex-wrap gap-2 mt-2">
            <!-- Rookie - Mostra solo se is_rookie è true -->
            <div v-if="product.is_rookie" class="relative group">
              <div class="bg-gray-100 p-2 rounded-lg flex items-center justify-center min-w-[40px] min-h-[40px]">
                <span class="text-primary font-futura-bold text-sm">RC</span>
              </div>
              <!-- Tooltip -->
              <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                ROOKIE
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
              </div>
            </div>

            <!-- Condition -->
            <div class="relative group">
              <div class="bg-gray-100 p-2 rounded-lg flex items-center justify-center min-w-[40px] min-h-[40px]">
                <span class="text-primary font-futura-bold text-sm">{{ displayCondition }}</span>
              </div>
              <!-- Tooltip -->
              <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                {{ displayCondition }}
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
              </div>
            </div>
          </div>
          
          <!-- Details -->
          <div class="mt-2 text-sm text-gray-600 space-y-1">
            <div v-if="shouldShowTeam" class="flex justify-between">
              <span>Team:</span>
              <span class="font-medium">{{ product.team && product.team !== 'Team' && product.team !== 'Team Name' && product.team !== 'Unknown Team' ? product.team : '-' }}</span>
            </div>
            <div class="flex justify-between">
              <span>Set:</span>
              <span class="font-medium">{{ (product.set || product.set_name) && (product.set !== 'Set' && product.set !== 'Set Name' && product.set !== 'Unknown Set') ? (product.set || product.set_name) : '-' }}</span>
            </div>
            <div class="flex justify-between">
              <span>Rarity:</span>
              <span class="font-medium">{{ product.rarity ? (product.rarity + (product.rarity_variation ? ` (${product.rarity_variation})` : '')) : '-' }}</span>
            </div>
          </div>
        </div>
        
        <!-- Price and Add to Cart -->
        <div class="mt-4 flex items-center justify-between">
          <p class="text-lg font-bold text-gray-900">€{{ formatPrice(product.price) }}</p>
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
      rarity: 'Rarity',
      condition: 'NEAR MINT',
      price: '0',
      is_rookie: false,
      imageUrl: null,
      image_url: null
    })
  },
  category: {
    type: String,
    default: null
  }
})

// Emits
const emit = defineEmits(['add-to-cart', 'click'])

// Reactive data
const loading = ref(false)

// Computed
const shouldShowTeam = computed(() => {
  const category = props.category || props.product.category_slug || props.product.category
  return category !== 'disney' && category !== 'spongebob'
})

const displayCondition = computed(() => {
  return formatCondition(props.product)
})

// Methods
const formatPrice = (price) => {
  // Se il prezzo è già una stringa formattata (con virgola), usala direttamente
  if (typeof price === 'string' && price.includes(',')) {
    return price
  }
  // Altrimenti formatta il numero
  return formatPriceItaliana(price, false)
}

const handleImageError = (event) => {
  try {
    if (!event || !event.target) {
      return
    }
    
    const img = event.target
    img.classList.add('hidden')
    
    const parent = img.parentElement
    if (!parent) {
      return
    }
    
    const placeholder = Array.from(parent.children).find(
      el => el !== img && el && el.classList && el.classList.contains('bg-gray-200')
    )
    
    if (placeholder && placeholder.classList) {
      placeholder.classList.remove('hidden')
    }
  } catch (error) {
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
