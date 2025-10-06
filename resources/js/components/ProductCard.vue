<template>
  <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden">
    <!-- Image Area -->
    <div class="relative aspect-square w-full bg-gray-100 overflow-hidden">
      <!-- Placeholder Image -->
      <div class="w-full h-full flex items-center justify-center bg-gray-200">
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

    <!-- Player Name -->
    <div class="px-4 pt-4 pb-2">
      <h3 class="text-lg font-bold text-gray-900 text-center">{{ product.name || 'Player' }}</h3>
    </div>

    <!-- Informational Badges -->
    <div class="px-4 pb-3 flex flex-wrap gap-3 justify-center">
      <!-- Numbered -->
      <div class="relative group">
        <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
          <span class="text-primary font-futura-bold text-lg">{{ product.limitedEdition || '/10' }}</span>
        </div>
        <!-- Tooltip -->
        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
          NUMBERED
          <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
        </div>
      </div>

      <!-- Autograph -->
      <div class="relative group">
        <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
          <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
          </svg>
        </div>
        <!-- Tooltip -->
        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
          AUTOGRAPH
          <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
        </div>
      </div>

      <!-- Relic -->
      <div class="relative group">
        <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
          <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.429 3.658L9.3 16.573z"></path>
          </svg>
        </div>
        <!-- Tooltip -->
        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
          RELIC
          <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
        </div>
      </div>

      <!-- Rookie -->
      <div v-if="product.isRookie" class="relative group">
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

    <!-- Card Details -->
    <div class="px-4 pb-3 space-y-1">
      <div class="flex justify-between text-sm text-gray-600">
        <span>Team:</span>
        <span class="font-medium">{{ product.team || 'Team' }}</span>
      </div>
      <div class="flex justify-between text-sm text-gray-600">
        <span>Set:</span>
        <span class="font-medium">{{ product.set || 'Set' }}</span>
      </div>
      <div class="flex justify-between text-sm text-gray-600">
        <span>Year:</span>
        <span class="font-medium">{{ product.year || 'Year' }}</span>
      </div>
      <div class="flex justify-between text-sm text-gray-600">
        <span>Rarity:</span>
        <span class="font-medium">{{ product.rarity || 'Rarity' }}</span>
      </div>
    </div>

    <!-- Condition Indicator -->
    <div class="px-4 pb-4 flex justify-center">
      <div class="bg-white border border-gray-200 rounded-md px-3 py-1">
        <span class="text-sm font-medium text-gray-700">{{ product.condition || 'NEAR MINT' }}</span>
      </div>
    </div>

    <!-- Price and Add to Cart -->
    <div class="px-4 pb-4 flex items-center justify-between">
      <div class="text-lg font-bold text-gray-900">
        €{{ product.price || 'Price' }}
      </div>
      <button 
        @click="addToCart"
        class="w-10 h-10 bg-blue-600 rounded-md flex items-center justify-center hover:bg-blue-700 transition-colors"
        :disabled="loading"
      >
        <svg v-if="!loading" class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 11-4 0v-6m4 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01" />
        </svg>
        <div v-else class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

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
      limitedEdition: '/10',
      isRookie: false,
      imageUrl: null
    })
  }
})

// Emits
const emit = defineEmits(['add-to-cart'])

// Reactive data
const loading = ref(false)

// Methods
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
