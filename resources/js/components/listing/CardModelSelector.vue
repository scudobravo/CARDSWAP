<template>
  <div class="space-y-6">
    <!-- Filtri di Ricerca -->
    <div class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Ricerca per Nome -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Cerca per Nome</label>
          <input 
            v-model="searchQuery"
            type="text"
            placeholder="Nome della carta..."
            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
            @input="searchCardModels"
          />
        </div>
        
        <!-- Filtro per Set -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Set</label>
          <select 
            v-model="filters.set"
            @change="searchCardModels"
            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
          >
            <option value="">Tutti i set</option>
            <option v-for="set in availableSets" :key="set.id" :value="set.id">
              {{ set.name }}
            </option>
          </select>
        </div>
        
        <!-- Filtro per Anno -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Anno</label>
          <select 
            v-model="filters.year"
            @change="searchCardModels"
            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
          >
            <option value="">Tutti gli anni</option>
            <option v-for="year in availableYears" :key="year" :value="year">
              {{ year }}
            </option>
          </select>
        </div>
        
        <!-- Filtro per Rarità -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Rarità</label>
          <select 
            v-model="filters.rarity"
            @change="searchCardModels"
            class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
          >
            <option value="">Tutte le rarità</option>
            <option v-for="rarity in availableRarities" :key="rarity" :value="rarity">
              {{ rarity }}
            </option>
          </select>
        </div>
      </div>
    </div>

    <!-- Risultati -->
    <div v-if="loading" class="text-center py-8">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      <p class="mt-2 text-sm text-gray-500">Caricamento...</p>
    </div>
    
    <div v-else-if="cardModels.length > 0" class="space-y-4">
      <div class="flex items-center justify-between">
        <h5 class="text-lg font-semibold text-gray-900">
          Modelli Trovati ({{ cardModels.length }})
        </h5>
        <div class="flex items-center space-x-2">
          <span class="text-sm text-gray-500">Per pagina:</span>
          <select 
            v-model="perPage"
            @change="searchCardModels"
            class="text-sm border border-gray-300 rounded px-2 py-1"
          >
            <option value="10">10</option>
            <option value="20">20</option>
            <option value="50">50</option>
          </select>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
        <div 
          v-for="cardModel in cardModels" 
          :key="cardModel.id"
          class="border rounded-lg p-4 cursor-pointer hover:border-primary hover:shadow-md transition-all"
          :class="{ 'border-primary bg-primary/5': selectedCardModel?.id === cardModel.id }"
          @click="selectCardModel(cardModel)"
        >
          <div class="flex items-start space-x-3">
            <img 
              :src="cardModel.image_url || '/images/placeholder-card.jpg'" 
              :alt="cardModel.name"
              class="w-16 h-20 object-cover rounded"
            />
            <div class="flex-1 min-w-0">
              <h6 class="font-semibold text-gray-900 truncate">{{ cardModel.name }}</h6>
              <p class="text-sm text-gray-600">{{ cardModel.set_name }} {{ cardModel.year }}</p>
              <p class="text-sm text-gray-500">{{ cardModel.rarity }}</p>
              <div v-if="cardModel.player" class="text-sm text-gray-500">
                {{ cardModel.player.name }}
                <span v-if="cardModel.team"> - {{ cardModel.team.name }}</span>
              </div>
              <div v-if="cardModel.league" class="text-xs text-gray-400">
                {{ cardModel.league.name }}
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Paginazione -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between">
        <div class="text-sm text-gray-500">
          Mostrando {{ pagination.from }} - {{ pagination.to }} di {{ pagination.total }} risultati
        </div>
        <div class="flex items-center space-x-2">
          <button 
            @click="previousPage"
            :disabled="pagination.current_page === 1"
            class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Precedente
          </button>
          <span class="text-sm text-gray-500">
            Pagina {{ pagination.current_page }} di {{ pagination.last_page }}
          </span>
          <button 
            @click="nextPage"
            :disabled="pagination.current_page === pagination.last_page"
            class="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Successiva
          </button>
        </div>
      </div>
    </div>
    
    <div v-else-if="hasSearched" class="text-center py-8">
      <p class="text-gray-500">Nessun modello trovato con i filtri selezionati</p>
    </div>
    
    <div v-else class="text-center py-8">
      <p class="text-gray-500">Inizia a cercare un modello di carta</p>
    </div>

    <!-- Azioni -->
    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
      <button 
        @click="$emit('close')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
      >
        Annulla
      </button>
      <button 
        @click="confirmSelection"
        :disabled="!selectedCardModel"
        class="px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Seleziona
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'

// Emits
const emit = defineEmits(['selected', 'close'])

// State
const searchQuery = ref('')
const selectedCardModel = ref(null)
const cardModels = ref([])
const loading = ref(false)
const hasSearched = ref(false)
const perPage = ref(20)
const pagination = ref({
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0,
  total: 0
})

const filters = ref({
  set: '',
  year: '',
  rarity: ''
})

const availableSets = ref([])
const availableYears = ref([])
const availableRarities = ref([])

// Methods
const searchCardModels = async () => {
  if (searchQuery.value.length < 2 && !filters.value.set && !filters.value.year && !filters.value.rarity) {
    cardModels.value = []
    hasSearched.value = false
    return
  }
  
  loading.value = true
  hasSearched.value = true
  
  try {
    const params = new URLSearchParams({
      search: searchQuery.value,
      set: filters.value.set,
      year: filters.value.year,
      rarity: filters.value.rarity,
      per_page: perPage.value,
      page: pagination.value.current_page
    })
    
    const response = await fetch(`/api/card-models/search?${params}`)
    const data = await response.json()
    
    if (data.success) {
      cardModels.value = data.data.card_models
      pagination.value = data.data.pagination
    } else {
      cardModels.value = []
    }
  } catch (error) {
    console.error('Errore nella ricerca modelli:', error)
    cardModels.value = []
  } finally {
    loading.value = false
  }
}

const selectCardModel = (cardModel) => {
  selectedCardModel.value = cardModel
}

const confirmSelection = () => {
  if (selectedCardModel.value) {
    emit('selected', selectedCardModel.value)
  }
}

const previousPage = () => {
  if (pagination.value.current_page > 1) {
    pagination.value.current_page--
    searchCardModels()
  }
}

const nextPage = () => {
  if (pagination.value.current_page < pagination.value.last_page) {
    pagination.value.current_page++
    searchCardModels()
  }
}

const loadFilterData = async () => {
  try {
    const response = await fetch('/api/card-models/chained-filters')
    const data = await response.json()
    
    if (data.success) {
      availableSets.value = data.data.card_sets || []
      availableYears.value = data.data.years || []
      availableRarities.value = data.data.rarities || []
    }
  } catch (error) {
    console.error('Errore nel caricamento dati filtri:', error)
  }
}

// Watch for search query changes
watch(searchQuery, (newQuery) => {
  if (newQuery.length >= 2) {
    pagination.value.current_page = 1
    searchCardModels()
  } else if (newQuery.length === 0) {
    cardModels.value = []
    hasSearched.value = false
  }
})

// Lifecycle
onMounted(() => {
  loadFilterData()
})
</script>
