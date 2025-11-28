<template>
  <div class="space-y-4">
      <!-- Player Selection (Solo per Single Card) -->
    <div v-if="showPlayer" class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Player *</label>
      <div class="relative">
        <input 
          v-model="localFilters.playerSearch"
          type="text" 
          placeholder="Cerca giocatore..."
          class="block w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
          @input="searchPlayers"
          @focus="onPlayerFocus"
          @blur="onPlayerBlur"
          @change="onPlayerInputChange"
        />
        <div v-if="filteredPlayers.length > 0 && showPlayerDropdown" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
          <div v-for="player in filteredPlayers" :key="player.id" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" @click="selectPlayer(player)">
            <div class="flex flex-col">
              <span class="font-normal block truncate">{{ player.display_name || player.name }}</span>
            </div>
          </div>
        </div>
      </div>
      <div v-if="selectedPlayer" class="flex flex-wrap gap-2 mt-2">
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary text-white">
          {{ selectedPlayer.display_name || selectedPlayer.name }}
          <button type="button" @click="removePlayer" class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-primary-dark">
            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </span>
      </div>
      
    </div>

    <!-- Prima riga: Team e Set -->
    <div :class="['grid gap-4 mb-4', showTeam ? 'grid-cols-1 md:grid-cols-2' : 'grid-cols-1']">
      <!-- Team Selection -->
      <div v-if="showTeam">
        <label class="block text-sm font-medium text-gray-700 mb-2">Team</label>
        <div class="relative">
          <input 
            v-model="localFilters.teamSearch"
            type="text" 
            placeholder="Cerca team..."
            class="block w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
            @input="searchTeams"
            @focus="onTeamFocus"
            @blur="onTeamBlur"
          />
          <div v-if="filteredTeams.length > 0 && showTeamDropdown" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
            <div v-for="team in filteredTeams" :key="team.id" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" @click="selectTeam(team)">
              <span class="font-normal block truncate">{{ team.name }}</span>
            </div>
          </div>
        </div>
        <div v-if="selectedTeam" class="flex flex-wrap gap-2 mt-2">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary text-white">
            {{ selectedTeam.name }}
            <button type="button" @click="removeTeam" class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-primary-dark">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </span>
        </div>
      </div>

      <!-- Set Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Set</label>
        <div class="relative">
          <input 
            v-model="localFilters.setSearch"
            type="text" 
            placeholder="Cerca set..."
            class="block w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
            @input="searchCardSets"
            @focus="onSetFocus"
            @blur="onSetBlur"
          />
          <div v-if="filteredCardSets.length > 0 && showSetDropdown" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-sm md:text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
            <div v-for="set in filteredCardSets" :key="set.id" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" @click="selectCardSet(set)">
              <span class="font-normal block truncate text-xs md:text-sm">{{ set.name }} ({{ set.year }})</span>
            </div>
          </div>
        </div>
        <div v-if="selectedCardSet" class="flex flex-wrap gap-2 mt-2">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary text-white">
            {{ selectedCardSet.name }}
            <button type="button" @click="removeCardSet" class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-primary-dark">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </span>
        </div>
      </div>
    </div>

    <!-- Seconda riga: Brand, Rarity, Year -->
    <div :class="['grid gap-4 mb-4', showRarity ? 'grid-cols-1 md:grid-cols-3' : 'grid-cols-1 md:grid-cols-2']">
      <!-- Brand Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
        <select 
          v-model="localFilters.brand"
          @change="onFiltersChanged"
          class="block w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6"
        >
          <option value="">Seleziona Brand</option>
          <option v-for="brand in availableBrands" :key="brand" :value="brand">{{ brand }}</option>
        </select>
      </div>

      <!-- Rarity Selection -->
      <div v-if="showRarity">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Rarity
        </label>
        <div class="relative">
          <input 
            v-model="localFilters.raritySearch"
            type="text" 
            placeholder="Cerca rarity..."
            class="block w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
            @input="searchRarities"
            @focus="onRarityFocus"
            @blur="onRarityBlur"
          />
          <div v-if="filteredRarities.length > 0 && showRarityDropdown" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
            <div v-for="rarity in filteredRarities" :key="rarity" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" @click="selectRarity(rarity)">
              <span class="font-normal block truncate">{{ rarity }}</span>
            </div>
          </div>
        </div>
        <div v-if="selectedRarity" class="flex flex-wrap gap-2 mt-2">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary text-white">
            {{ selectedRarity }}
            <button type="button" @click="removeRarity" class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-primary-dark">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </span>
        </div>
      </div>

      <!-- Year Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Year ({{ availableYears.length }} opzioni)
        </label>
        <select 
          v-model="localFilters.year"
          @change="onFiltersChanged"
          class="block w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6"
        >
          <option value="">Seleziona Year</option>
          <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
        </select>
      </div>

      
    </div>

    <!-- Terza riga: Number (Solo per Single Card) -->
    <div v-if="showNumber" class="mb-4">
      <!-- Number Input (Solo per Single Card) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Numbered *</label>
        <input 
          v-model="localFilters.number"
          type="text" 
          placeholder="Inserisci numero carta (es. 30, RF-18, BA-ZI)"
          class="block w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
          @input="onFiltersChanged"
        />
      </div>
    </div>

    <!-- Search Button (Solo per Bulk Cards) -->
    <div v-if="showSearchButton" class="mb-4">
      <button 
        @click="searchCards"
        :disabled="!canSearch"
        class="w-full px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Cerca Carte
      </button>
    </div>

    <!-- Carte del giocatore selezionato (solo per Single Card) -->
    <div v-if="showPlayer && selectedPlayer && selectedPlayer.cards && selectedPlayer.cards.length > 0" class="mt-6">
      <!-- Info empty state ABOVE header -->
      <div v-if="filteredCards.length === 0" class="mb-3 p-3 bg-blue-50 border border-blue-200 rounded-md text-sm text-blue-800">
        Nessuna carta disponibile con questi criteri
      </div>
      <div class="mb-4">
        <label class="block text-lg font-medium text-gray-700">
          Seleziona Carta ({{ filteredCards.length }} disponibili)
        </label>
        <p class="text-sm text-gray-600 mt-1">
          Usa i filtri Team e Set sopra per filtrare le carte disponibili
        </p>
      </div>
      <!-- Cards grid -->
      <div v-if="filteredCards.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-80 overflow-y-auto">
        <div 
          v-for="card in filteredCards" 
          :key="card.id"
          @click="selectCard(card)"
          :class="[
            'p-4 border rounded-lg cursor-pointer transition-all duration-200 hover:shadow-md',
            selectedCard && selectedCard.id === card.id 
              ? 'border-primary bg-primary/5 shadow-md ring-2 ring-primary/20' 
              : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'
          ]"
        >
          <!-- Header con nome e numero -->
          <div class="flex justify-between items-start mb-2">
            <div class="text-sm font-medium text-gray-900 truncate flex-1 mr-2">{{ formatCardName(card.name) }}</div>
          <div v-if="card.card_number" class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded">
            /{{ card.card_number }}
            </div>
          </div>
          
          <!-- Numbered field -->
          <div v-if="card.card_number" class="text-xs text-gray-600 mb-1">
            <span class="font-medium text-gray-700">Numbered:</span> {{ card.card_number }}
          </div>
          
          <!-- Squadra -->
          <div v-if="card.team" class="text-xs text-gray-600 mb-1">
            <div class="font-medium text-blue-600">{{ card.team.name }}</div>
          </div>
          
          <!-- Set e Anno -->
          <div class="text-xs text-gray-600 mb-1">
            <div class="font-medium">{{ card.card_set?.name || 'N/A' }}</div>
            <!-- Mostra l'anno solo se il set non contiene già l'anno nel nome -->
            <div v-if="card.year && !card.card_set?.name?.includes(card.year)" class="text-gray-500">{{ card.year }}</div>
          </div>
          
          <!-- Rarity e Brand -->
          <div class="flex justify-between items-center text-xs">
            <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full">
              {{ card.rarity || 'N/A' }}{{ card.rarity_variation ? ` (${card.rarity_variation})` : '' }}
            </span>
            <span v-if="card.card_set?.brand" class="text-gray-500">
              {{ card.card_set.brand }}
            </span>
          </div>
          
          <!-- Indicatore di selezione -->
          <div v-if="selectedCard && selectedCard.id === card.id" class="mt-2 text-xs text-primary font-medium">
            ✓ Selezionata
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'

// Props
const props = defineProps({
  category: {
    type: String,
    default: 'football'
  },
  showPlayer: {
    type: Boolean,
    default: true
  },
  showNumber: {
    type: Boolean,
    default: true
  },
  showPrice: {
    type: Boolean,
    default: true
  },
  showSearchButton: {
    type: Boolean,
    default: false
  },
  showTeam: {
    type: Boolean,
    default: true
  },
  showRarity: {
    type: Boolean,
    default: true
  },
  initialFilters: {
    type: Object,
    default: () => ({})
  },
  priceError: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['filters-changed', 'search-cards', 'card-picked'])

// State
const localFilters = ref({
  player: null,
  playerSearch: '',
  team: null,
  teamSearch: '',
  set: null,
  setSearch: '',
  brand: '',
  rarity: '',
  raritySearch: '',
  year: '',
  number: '',
  price: '',
  ...props.initialFilters
})

const selectedPlayer = ref(null)
const selectedCard = ref(null)
const selectedTeam = ref(null)
const selectedCardSet = ref(null)
const selectedRarity = ref(null)
const filteredCards = ref([])
const uniqueCardSets = ref([])
const uniqueTeams = ref([])
const filteredPlayers = ref([])
const filteredTeams = ref([])
const filteredCardSets = ref([])
const filteredRarities = ref([])

// Available options (populated by chained filters)
const availableBrands = ref([])
const availableRarities = ref([])
const availableYears = ref([])

// Dropdown visibility state
const showPlayerDropdown = ref(false)

// Format card name: replace # with /
const formatCardName = (name) => {
  if (!name) return ''
  return name.replace(/#/g, '/')
}
const showTeamDropdown = ref(false)
const showSetDropdown = ref(false)
const showRarityDropdown = ref(false)

// Computed
const canSearch = computed(() => {
  return localFilters.value.team || localFilters.value.set || localFilters.value.brand || localFilters.value.rarity || localFilters.value.year
})

// Methods
let searchTimeout = null
let currentSearchId = 0
let activeRequests = new Set()
let chainedDataTimeout = null

// Fallback: calcola brand/rarity/year dai dati locali del giocatore selezionato
const computeOptionsFromLocalCards = () => {
  const fallback = { brands: [], rarities: [], years: [] }
  if (!selectedPlayer.value || !Array.isArray(selectedPlayer.value.cards)) {
    return fallback
  }
  const teamId = localFilters.value.team
  const setId = localFilters.value.set
  const brandSel = localFilters.value.brand

  const cards = selectedPlayer.value.cards.filter(card => {
    const teamOk = !teamId || (card.team && card.team.id == teamId)
    const setOk = !setId || (card.card_set && card.card_set.id == setId)
    const brandOk = !brandSel || (card.card_set && card.card_set.brand === brandSel)
    return teamOk && setOk && brandOk
  })

  const brands = Array.from(new Set(cards.map(c => c.card_set?.brand).filter(Boolean)))
  const rarities = Array.from(new Set(cards.map(c => c.rarity).filter(Boolean)))
  const years = Array.from(new Set(cards.map(c => c.year).filter(Boolean)))

  return { brands, rarities, years }
}

// Helper function to manage API calls safely
const makeApiCall = async (url, requestId) => {
  // Cancel previous request if it exists
  if (activeRequests.has(requestId)) {
    console.log(`Cancelling previous request: ${requestId}`)
    activeRequests.delete(requestId)
  }
  
  // Add current request to active set
  activeRequests.add(requestId)
  
  try {
    const response = await fetch(url)
    
    // Check if request is still active
    if (!activeRequests.has(requestId)) {
      console.log(`Request ${requestId} was cancelled`)
      return null
    }
    
    return response
  } catch (error) {
    console.error(`Error in API call ${requestId}:`, error)
    return null
  } finally {
    // Remove request from active set
    activeRequests.delete(requestId)
  }
}

const searchRarities = async () => {
  // Clear previous timeout
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  // Increment search ID to cancel previous requests
  const searchId = ++currentSearchId
  
  // Set new timeout for debounced search
  searchTimeout = setTimeout(async () => {
    const query = localFilters.value.raritySearch || ''
    
    // Skip search if query is too short
    if (query.length < 2) {
      filteredRarities.value = []
      return
    }
    
    console.log('Ricerca rarità per:', query)
    console.log('Categoria:', props.category)
    console.log('Search ID:', searchId)
    
    try {
      // Costruisci i parametri con i filtri correnti per interdipendenza
      const params = new URLSearchParams({ q: query })
      if (localFilters.value.player) params.append('player_id', localFilters.value.player)
      if (localFilters.value.team) params.append('team_id', localFilters.value.team)
      if (localFilters.value.set) params.append('set_id', localFilters.value.set)
      if (localFilters.value.year) params.append('year', localFilters.value.year)
      if (localFilters.value.brand) params.append('brand', localFilters.value.brand)
      
      const url = `/api/${props.category}/filters/rarities/search?${params.toString()}`
      console.log('URL:', url)
      
      const requestId = `rarities-${searchId}`
      const response = await makeApiCall(url, requestId)
      
      // Check if this is still the current search and response is valid
      if (searchId !== currentSearchId || !response) {
        console.log('Search cancelled or no response')
        return
      }
      
      console.log('Response status:', response.status)
      
      const data = await response.json()
      console.log('Response data:', data)
      
      // Only update if this is still the current search
      if (searchId === currentSearchId) {
        filteredRarities.value = data.rarities || []
        console.log('Filtered rarities:', filteredRarities.value)
      }
    } catch (error) {
      console.error('Errore nella ricerca rarità:', error)
      // Only update if this is still the current search
      if (searchId === currentSearchId) {
        filteredRarities.value = []
      }
    }
  }, 500) // Debounce di 500ms
}

const onRarityFocus = async () => {
  showRarityDropdown.value = true
  
  // Se c'è già una rarity selezionata, mostra i suoi dati
  if (selectedRarity.value) {
    console.log('✅ Rarity già selezionata, mostro i suoi dati:', selectedRarity.value)
    // Non fare nulla, la rarity è già selezionata
    return
  }
  
  // Se c'è una query, esegui la ricerca
  if (localFilters.value.raritySearch && localFilters.value.raritySearch.length >= 2) {
    await searchRarities()
  }
}

const onRarityBlur = () => {
  // Ritarda la chiusura per permettere il click su un elemento
  setTimeout(() => {
    showRarityDropdown.value = false
  }, 200)
}

const selectRarity = (rarity) => {
  selectedRarity.value = rarity
  localFilters.value.rarity = rarity
  localFilters.value.raritySearch = rarity
  filteredRarities.value = []
  showRarityDropdown.value = false
  
  console.log('✅ Rarity selezionata:', rarity)
  
  onFiltersChanged()
}

const removeRarity = () => {
  selectedRarity.value = null
  localFilters.value.rarity = ''
  localFilters.value.raritySearch = ''
  onFiltersChanged()
}

const searchPlayers = async () => {
  // Clear previous timeout
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  // Increment search ID to cancel previous requests
  const searchId = ++currentSearchId
  
  // Set new timeout for debounced search
  searchTimeout = setTimeout(async () => {
    const query = localFilters.value.playerSearch || ''
    
    // Skip search if query is too short
    if (query.length < 2) {
      filteredPlayers.value = []
      return
    }
    
    console.log('Ricerca giocatori per:', query)
    console.log('Categoria:', props.category)
    console.log('Search ID:', searchId)
    
    try {
      // Costruisci i parametri con i filtri correnti per interdipendenza
      const params = new URLSearchParams({ q: query })
      if (localFilters.value.team) params.append('team_id', localFilters.value.team)
      if (localFilters.value.set) params.append('set_id', localFilters.value.set)
      if (localFilters.value.year) params.append('year', localFilters.value.year)
      if (localFilters.value.brand) params.append('brand', localFilters.value.brand)
      
      const url = `/api/${props.category}/filters/players/search?${params.toString()}`
      console.log('URL:', url)
      
      const requestId = `players-${searchId}`
      const response = await makeApiCall(url, requestId)
      
      // Check if this is still the current search and response is valid
      if (searchId !== currentSearchId || !response) {
        console.log('Search cancelled or no response')
        return
      }
      
      console.log('Response status:', response.status)
      
      const data = await response.json()
      console.log('Response data:', data)
      
      // Only update if this is still the current search
      if (searchId === currentSearchId) {
      filteredPlayers.value = data.players || []
      console.log('Filtered players:', filteredPlayers.value)
      }
    } catch (error) {
      console.error('Errore nella ricerca giocatori:', error)
      // Only update if this is still the current search
      if (searchId === currentSearchId) {
      filteredPlayers.value = []
    }
    }
  }, 500) // Increased debounce to 500ms
}

const onPlayerFocus = async () => {
  showPlayerDropdown.value = true
  
  // Se c'è già un giocatore selezionato, mostra i suoi dati
  if (selectedPlayer.value) {
    console.log('✅ Giocatore già selezionato, mostro i suoi dati:', selectedPlayer.value.name)
    // Non fare nulla, il giocatore è già selezionato
    return
  }
  
  // Carica tutti i giocatori disponibili quando si fa focus
  await searchPlayers()
}

const onPlayerBlur = () => {
  // Ritarda la chiusura per permettere il click su un elemento
  setTimeout(() => {
    showPlayerDropdown.value = false
  }, 200)
}

const onPlayerInputChange = () => {
  console.log('🔄 Campo playerSearch cambiato a:', localFilters.value.playerSearch)
  console.log('🔄 selectedPlayer.value:', selectedPlayer.value?.name)
  
  // Se l'utente modifica manualmente il campo e c'è un giocatore selezionato,
  // verifica se il testo corrisponde al giocatore selezionato
  if (selectedPlayer.value && localFilters.value.playerSearch !== (selectedPlayer.value.display_name || selectedPlayer.value.name)) {
    console.log('⚠️ L\'utente ha modificato il campo, potrebbe aver deselezionato il giocatore')
    // Non fare nulla automaticamente, lascia che l'utente gestisca la selezione
  }
}

const searchTeams = async () => {
  const query = localFilters.value.teamSearch || ''
  
  console.log('🔍 searchTeams chiamata con query:', query)
  console.log('🔍 Player ID:', localFilters.value.player)
  
  // Se non c'è query ma c'è un giocatore selezionato, carica le squadre del giocatore
  if (query.length < 2 && localFilters.value.player) {
    console.log('🔍 Nessuna query, carico squadre per giocatore selezionato')
    await loadTeamsForPlayer()
    return
  }
  
  // Skip search if query is too short and no player selected
  if (query.length < 2) {
    console.log('🔍 Query troppo corta e nessun giocatore selezionato')
    filteredTeams.value = []
    return
  }
  
  console.log('🔍 Ricerca team per:', query)
  
  try {
    // Costruisci i parametri - SOLO player_id per evitare filtri che limitano i risultati
    const params = new URLSearchParams({ q: query })
    if (localFilters.value.player) params.append('player_id', localFilters.value.player)
    // RIMOSSO: set, year, brand per evitare di limitare i risultati delle squadre
    
    const url = `/api/${props.category}/filters/teams/search?${params.toString()}`
    console.log('🔍 URL team:', url)
    
    const response = await fetch(url)
    console.log('🔍 Response status team:', response.status)
    
    const data = await response.json()
    console.log('🔍 Response data team:', data)
    
    filteredTeams.value = data.teams || []
    console.log('🔍 Filtered teams:', filteredTeams.value)
    console.log('🔍 Numero squadre trovate:', filteredTeams.value.length)
  } catch (error) {
    console.error('❌ Errore nella ricerca squadre:', error)
    filteredTeams.value = []
  }
}

const onTeamFocus = async () => {
  console.log('🔍 Focus su campo Team')
  console.log('🔍 selectedPlayer.value:', selectedPlayer.value?.name)
  console.log('🔍 filteredTeams.value.length:', filteredTeams.value.length)
  console.log('🔍 localFilters.value.player:', localFilters.value.player)
  
  showTeamDropdown.value = true
  
  // Se ci sono già squadre caricate per il giocatore selezionato, non fare ricerche inutili
  if (selectedPlayer.value && filteredTeams.value.length > 0) {
    console.log('✅ Squadre già caricate per questo giocatore, le mostro')
    return
  }
  
  // Carica tutte le squadre disponibili quando si fa focus
  await searchTeams()
}

const onTeamBlur = () => {
  // Ritarda la chiusura per permettere il click su un elemento
  setTimeout(() => {
    showTeamDropdown.value = false
  }, 200)
}

const searchCardSets = async () => {
  const query = localFilters.value.setSearch || ''
  
  // Skip search if query is too short
  if (query.length < 2) {
    filteredCardSets.value = []
    return
  }
  
  console.log('🔍 Ricerca set per:', query)
  
  try {
    // Includi team_id quando presente per filtrare correttamente i set
    const params = new URLSearchParams({ q: query })
    if (localFilters.value.player) params.append('player_id', localFilters.value.player)
    if (localFilters.value.team) params.append('team_id', localFilters.value.team)
    
    const url = `/api/${props.category}/filters/card-sets/search?${params.toString()}`
    console.log('🔍 URL set:', url)
    
    const response = await fetch(url)
    console.log('🔍 Response status set:', response.status)
    
    if (!response.ok) {
      console.error('❌ Errore HTTP nella ricerca set:', response.status, response.statusText)
      return
    }
    
    const data = await response.json()
    console.log('🔍 Response data set:', data)
    
    if (data.card_sets && data.card_sets.length > 0) {
      // Mantieni i set così come arrivano dall'API (set diversi per anno restano distinti)
      filteredCardSets.value = data.card_sets
      console.log('✅ Set filtrati:', filteredCardSets.value.length)
    } else {
      filteredCardSets.value = []
      console.log('⚠️ Nessun set trovato per questa ricerca')
    }
  } catch (error) {
    console.error('❌ Errore nella ricerca set:', error)
    filteredCardSets.value = []
  }
}

const loadAllCardSets = async () => {
  console.log('🔍 Caricamento tutti i set disponibili')
  
  try {
    // Includi team_id quando presente per filtrare correttamente i set
    const params = new URLSearchParams()
    if (localFilters.value.player) params.append('player_id', localFilters.value.player)
    if (localFilters.value.team) params.append('team_id', localFilters.value.team)
    
    const url = `/api/${props.category}/filters/card-sets/search?${params.toString()}`
    console.log('🔍 URL set (all):', url)
    
    const response = await fetch(url)
    console.log('🔍 Response status set (all):', response.status)
    
    if (!response.ok) {
      console.error('❌ Errore HTTP nel caricamento set:', response.status, response.statusText)
      return
    }
    
    const data = await response.json()
    console.log('🔍 Response data set (all):', data)
    
    if (data.card_sets && data.card_sets.length > 0) {
      // Mantieni i set così come arrivano dall'API (set diversi per anno restano distinti)
      filteredCardSets.value = data.card_sets
      console.log('✅ Set filtrati per giocatore:', filteredCardSets.value.length)
    } else {
      filteredCardSets.value = []
      console.log('⚠️ Nessun set trovato per questo giocatore')
    }
  } catch (error) {
    console.error('❌ Errore nel caricamento di tutti i set:', error)
    filteredCardSets.value = []
  }
}

const onSetFocus = async () => {
  console.log('🔍 Focus su campo Set')
  console.log('🔍 selectedPlayer.value:', selectedPlayer.value?.name)
  console.log('🔍 filteredCardSets.value.length:', filteredCardSets.value.length)
  console.log('🔍 localFilters.value.player:', localFilters.value.player)
  console.log('🔍 localFilters.value.team:', localFilters.value.team)
  
  showSetDropdown.value = true
  
  // Ricarica sempre i set quando si fa focus per assicurarsi che siano aggiornati con i filtri correnti (player + team)
  // Questo è importante perché quando viene selezionato un team, i set devono essere filtrati correttamente
  console.log('🔄 Ricaricamento set con filtri correnti (player + team)')
  
  // Carica tutti i set disponibili quando si fa focus
  // Se non c'è query, mostra tutti i set disponibili
  if (!localFilters.value.setSearch || localFilters.value.setSearch.length < 2) {
    await loadAllCardSets()
  } else {
  await searchCardSets()
  }
}

const onSetBlur = () => {
  // Ritarda la chiusura per permettere il click su un elemento
  setTimeout(() => {
    showSetDropdown.value = false
  }, 200)
}

const selectPlayer = (player) => {
  console.log('🎯 Selezionando giocatore:', player)
  console.log('🎯 Dati giocatore completi:', {
    id: player.id,
    name: player.name,
    display_name: player.display_name,
    all_teams: player.all_teams,
    cards: player.cards,
    card_numbers: player.card_numbers
  })
  
  selectedPlayer.value = player
  selectedCard.value = null // Reset carta selezionata
  localFilters.value.player = player.id
  localFilters.value.playerSearch = '' // Lascia il campo vuoto, il tag mostrerà il nome
  filteredPlayers.value = []
  showPlayerDropdown.value = false
  
  // ✅ LOGICA A CASCATA: NON resettare i filtri esistenti
  // I filtri si accumulano progressivamente per creare query più specifiche
  console.log('✅ Mantenendo filtri esistenti per logica a cascata')
  
  console.log('✅ Giocatore selezionato:', player.name, 'ID:', player.id)
  console.log('✅ Campo playerSearch impostato a:', localFilters.value.playerSearch)
  
  // Gestione team del giocatore
  if (player.all_teams && player.all_teams.length > 0) {
    console.log('✅ Giocatore ha squadre disponibili:', player.all_teams.map(t => t.name))
    
    // ✅ LOGICA A CASCATA: Mantieni il team corrente se è valido per il giocatore
    if (player.all_teams.length > 1) {
    if (localFilters.value.team) {
      const currentTeamIsValid = player.all_teams.some(t => t.id === localFilters.value.team)
      if (currentTeamIsValid) {
          console.log('✅ Team corrente è valido per questo giocatore, lo manteniamo per logica a cascata')
        // Mantieni il team corrente
      } else {
        console.log('⚠️ Team corrente non è valido per questo giocatore, lo resettiamo')
        selectedTeam.value = null
        localFilters.value.team = null
      }
    }
    } else {
    // Il giocatore ha giocato in una sola squadra, impostala automaticamente
    const singleTeam = player.all_teams[0]
    selectedTeam.value = singleTeam
    localFilters.value.team = singleTeam.id
    console.log('✅ Giocatore ha una sola squadra, impostata automaticamente:', singleTeam.name)
    }
  } else {
    console.log('⚠️ Giocatore non ha squadre associate, resetto team')
    selectedTeam.value = null
    localFilters.value.team = null
  }

  // Non autopopolare il campo Numbered alla selezione del player; reset per evitare residui
  localFilters.value.number = ''
  
  // Inizializza le carte filtrate e i set unici
  initializeCardFiltering(player)
  
  // Carica automaticamente le squadre disponibili per questo giocatore
    loadTeamsForPlayer()
  
  // Carica i set disponibili per questo giocatore
  loadCardSetsForPlayer()
  
  // Verifica che la selezione sia stata applicata correttamente
  setTimeout(() => {
    console.log('🔍 Verifica selezione giocatore dopo 100ms:')
    console.log('  - selectedPlayer.value:', selectedPlayer.value?.name)
    console.log('  - localFilters.value.player:', localFilters.value.player)
    console.log('  - localFilters.value.playerSearch:', localFilters.value.playerSearch)
    console.log('  - selectedTeam.value:', selectedTeam.value?.name)
    console.log('  - filteredTeams.value.length:', filteredTeams.value.length)
    console.log('  - filteredCardSets.value.length:', filteredCardSets.value.length)
  }, 100)
  
  onFiltersChanged()
}

const initializeCardFiltering = (player) => {
  if (player.cards && player.cards.length > 0) {
    filteredCards.value = player.cards
    
    // Crea lista dei set unici con conteggio
    const setMap = new Map()
    player.cards.forEach(card => {
      if (card.card_set) {
        const setId = card.card_set.id
        if (setMap.has(setId)) {
          setMap.get(setId).count++
        } else {
          setMap.set(setId, {
            id: setId,
            name: card.card_set.name,
            count: 1
          })
        }
      }
    })
    uniqueCardSets.value = Array.from(setMap.values())
    
    // Crea lista delle squadre uniche con conteggio
    const teamMap = new Map()
    player.cards.forEach(card => {
      if (card.team) {
        const teamId = card.team.id
        if (teamMap.has(teamId)) {
          teamMap.get(teamId).count++
        } else {
          teamMap.set(teamId, {
            id: teamId,
            name: card.team.name,
            count: 1
          })
        }
      }
    })
    uniqueTeams.value = Array.from(teamMap.values())
  }
}

// Ricalcola le carte filtrate in base a tutti i filtri slegati attivi
const recomputeFilteredCards = () => {
  if (!selectedPlayer.value || !selectedPlayer.value.cards) return
  
  let cards = selectedPlayer.value.cards
  
  if (localFilters.value.team) {
    cards = cards.filter(card => card.team && card.team.id == localFilters.value.team)
  }
  if (localFilters.value.set) {
    cards = cards.filter(card => card.card_set && card.card_set.id == localFilters.value.set)
  }
  if (localFilters.value.brand) {
    cards = cards.filter(card => card.card_set && card.card_set.brand === localFilters.value.brand)
  }
  if (localFilters.value.year) {
    cards = cards.filter(card => {
      const cy = card?.year != null ? String(card.year) : null
      const sy = card?.card_set?.year != null ? String(card.card_set.year) : null
      const fy = String(localFilters.value.year)
      
      // Match esatto
      if (cy === fy || sy === fy) return true
      
      // Se l'anno selezionato è solo un numero (es. "2024"), matcha anche anni con formato "2024/25"
      if (/^\d{4}$/.test(fy)) {
        return (cy && cy.startsWith(fy + '/')) || (sy && sy.startsWith(fy + '/'))
      }
      
      return false
    })
  }
  if (localFilters.value.rarity) {
    cards = cards.filter(card => {
      // Usa SOLO rarity, NON rarity_variation
      return card.rarity === localFilters.value.rarity
    })
  }
  // Assicurati che la carta selezionata resti visibile anche se i filtri correnti la escluderebbero
  if (selectedCard.value && !cards.some(c => c.id === selectedCard.value.id)) {
    cards = [selectedCard.value, ...cards]
  }

  filteredCards.value = cards
}

// Legacy helper (manteniamo per compatibilità, ora delega al calcolo generale)
const filterCardsBySet = () => {
  if (!selectedPlayer.value || !selectedPlayer.value.cards) return
  
  recomputeFilteredCards()
}

const filterCardsByTeam = () => {
  if (!selectedPlayer.value || !selectedPlayer.value.cards) return
  recomputeFilteredCards()
}

const selectCard = (card) => {
  selectedCard.value = card
  // Mostra subito la carta selezionata nella lista
  filteredCards.value = [card]
  
  // Aggiorna i filtri con i dati della carta selezionata
  if (card.card_set) {
    localFilters.value.set = card.card_set.id
    selectedCardSet.value = card.card_set
    console.log('✅ Campo Set aggiornato con:', card.card_set.name)
    // Popola anche Brand se disponibile
    if (card.card_set.brand) {
      localFilters.value.brand = card.card_set.brand
      console.log('✅ Campo Brand aggiornato con:', card.card_set.brand)
    }
  }
  
  if (card.year) {
    localFilters.value.year = card.year
    console.log('✅ Campo Year aggiornato con:', card.year)
  } else if (card.card_set && card.card_set.year) {
    localFilters.value.year = card.card_set.year
    console.log('✅ Campo Year aggiornato dal set con:', card.card_set.year)
  }
  
  if (card.rarity) {
    localFilters.value.rarity = card.rarity
    selectedRarity.value = card.rarity
    localFilters.value.raritySearch = card.rarity
    console.log('✅ Campo Rarity aggiornato con:', card.rarity)
  }
  
  // Aggiorna il campo Numbered con il numero della carta selezionata
  // Usa SOLO card_number (NUMBERED /) - NON usare card_number_in_set come fallback
  // Se card_number è vuoto, il campo Numbered deve rimanere vuoto
  if (card.card_number) {
    localFilters.value.number = card.card_number
    console.log('✅ Campo Numbered aggiornato con:', card.card_number)
  } else {
    // Se card_number è vuoto/null, resetta il campo Numbered
    localFilters.value.number = null
    console.log('✅ Campo Numbered resettato (card_number vuoto)')
  }
  
  // Notifica al parent la carta selezionata
  emit('card-picked', card)

  onFiltersChanged()
}

const removePlayer = () => {
  selectedPlayer.value = null
  selectedCard.value = null
  localFilters.value.player = null
  onFiltersChanged()
}

const selectTeam = (team) => {
  selectedTeam.value = team
  localFilters.value.team = team.id
  localFilters.value.teamSearch = ''
  filteredTeams.value = []
  showTeamDropdown.value = false
  
  // ✅ LOGICA A CASCATA: NON resettare i filtri esistenti
  // I filtri si accumulano progressivamente per creare query più specifiche
  console.log('✅ Team selezionato per logica a cascata:', team.name)
  
  // Filtri slegati: non aggiornare opzioni a cascata
  
  onFiltersChanged()
}

const removeTeam = () => {
  selectedTeam.value = null
  localFilters.value.team = null
  onFiltersChanged()
}

const selectCardSet = async (set) => {
  // IMPORTANTE: Preserva l'anno selezionato manualmente dall'utente PRIMA di qualsiasi operazione
  // Questo deve essere fatto PRIMA di impostare selectedCardSet per evitare che venga sovrascritto
  const userSelectedYear = localFilters.value.year
  
  selectedCardSet.value = set
  localFilters.value.set = set.id
  localFilters.value.setSearch = ''
  filteredCardSets.value = []
  showSetDropdown.value = false
  
  // ✅ LOGICA A CASCATA: NON resettare i filtri esistenti
  // I filtri si accumulano progressivamente per creare query più specifiche
  console.log('✅ Set selezionato per logica a cascata:', set.name)
  console.log('✅ Team mantenuto:', selectedTeam.value?.name)
  console.log('✅ Anno corrente prima della selezione set:', userSelectedYear)
  console.log('✅ Anno del set selezionato:', set.year)
  
  // IMPORTANTE: Se il set ha un anno e l'utente NON ha ancora selezionato un anno manualmente,
  // popola automaticamente l'anno dal set
  // Questo permette di selezionare "STADIUM CLUB CHROME UCC (2022/23)" e avere l'anno 2022/23 popolato automaticamente
  if (set.year && (!userSelectedYear || userSelectedYear === '')) {
    localFilters.value.year = set.year
    console.log('✅ Anno popolato automaticamente dal set:', set.year)
  } else if (userSelectedYear && userSelectedYear !== '') {
    // Se l'utente ha già selezionato un anno manualmente, preservalo
    console.log('✅ Anno selezionato manualmente preservato:', userSelectedYear)
  }
  
  // IMPORTANTE: Carica le opzioni disponibili per l'anno quando viene selezionato un set
  // Questo aggiorna il dropdown Year con le annate disponibili per il set selezionato
  await loadChainedData()
  
  // Se il set ha un anno ma non è nelle opzioni disponibili, aggiungilo comunque
  if (set.year && !availableYears.value.includes(set.year)) {
    availableYears.value.push(set.year)
    availableYears.value.sort((a, b) => String(b).localeCompare(String(a), undefined, { numeric: true }))
    console.log('✅ Anno del set aggiunto alle opzioni disponibili:', set.year)
  }
  
  // Assicurati che l'anno selezionato venga preservato anche dopo il caricamento delle opzioni
  if (localFilters.value.year) {
    const yearToPreserve = localFilters.value.year
    await nextTick()
    // Se l'anno non è nelle opzioni disponibili ma è quello del set, aggiungilo
    if (!availableYears.value.includes(yearToPreserve) && set.year === yearToPreserve) {
      availableYears.value.push(yearToPreserve)
      availableYears.value.sort((a, b) => String(b).localeCompare(String(a), undefined, { numeric: true }))
    }
    // Verifica che l'anno sia ancora valido tra le opzioni disponibili
    if (availableYears.value.includes(yearToPreserve)) {
      localFilters.value.year = yearToPreserve
      console.log('✅ Anno preservato dopo caricamento opzioni:', yearToPreserve)
    }
  }
  
  onFiltersChanged()
}

const removeCardSet = () => {
  selectedCardSet.value = null
  localFilters.value.set = null
  onFiltersChanged()
}

const onFiltersChanged = () => {
  // Filtri slegati: emetti solo i filtri correnti, senza aggiornare dropdown a cascata
  emit('filters-changed', localFilters.value)
}

const loadChainedDataDebounced = () => {
  // Clear previous timeout
  if (chainedDataTimeout) {
    clearTimeout(chainedDataTimeout)
  }
  
  // Set new timeout for debounced call
  chainedDataTimeout = setTimeout(() => {
    // Controlla se ci sono troppi filtri selezionati per evitare errori 500
    const activeFiltersCount = [
      localFilters.value.player,
      localFilters.value.team,
      localFilters.value.set,
      localFilters.value.brand,
      localFilters.value.rarity,
      localFilters.value.year
    ].filter(Boolean).length
    
    console.log('🔍 Numero di filtri attivi:', activeFiltersCount)
    
    // ✅ LOGICA MIGLIORATA: Chiama i filtri a catena anche con 1 filtro per aggiornare i dropdown
    // ma gestisci correttamente i risultati per non sovrascrivere le squadre
    console.log('🔄 Aggiornamento filtri a catena con', activeFiltersCount, 'filtri attivi')
    
    console.log('🔄 Aggiornamento filtri a catena con', activeFiltersCount, 'filtri attivi')
    
  loadChainedData()
  }, 1000) // 1 secondo di debounce
}

// ✅ LOGICA A CASCATA: Funzione per aggiornare le opzioni disponibili in base ai filtri selezionati
let updateOptionsTimeout = null
let lastUpdateParams = ''

const updateAvailableOptions = async () => {
  try {
    // Costruisci i parametri per la query a cascata
    const params = new URLSearchParams()
    if (localFilters.value.player) params.append('player_id', localFilters.value.player)
    if (localFilters.value.team) params.append('team_id', localFilters.value.team)
    if (localFilters.value.set) params.append('set_id', localFilters.value.set)
    if (localFilters.value.brand) params.append('brand', localFilters.value.brand)
    if (localFilters.value.rarity) params.append('rarity', localFilters.value.rarity)
    if (localFilters.value.year) params.append('year', localFilters.value.year)
    
    const currentParams = params.toString()
    
    // ✅ Evita chiamate ridondanti con gli stessi parametri
    if (currentParams === lastUpdateParams) {
      console.log('⚠️ Parametri identici, salto l\'aggiornamento per evitare chiamate ridondanti')
      return
    }
    
    // ✅ Evita chiamate se ci sono troppi filtri attivi
    const activeFiltersCount = [
      localFilters.value.player,
      localFilters.value.team,
      localFilters.value.set,
      localFilters.value.brand,
      localFilters.value.rarity,
      localFilters.value.year
    ].filter(Boolean).length
    
    // ✅ LOGICA MIGLIORATA: Chiama i filtri a catena anche con 1 filtro per aggiornare i dropdown
    // ma gestisci correttamente i risultati per non sovrascrivere le squadre
    console.log('🔄 Aggiornamento opzioni disponibili con', activeFiltersCount, 'filtri attivi')
    
    console.log('🔄 Aggiornamento opzioni disponibili con', activeFiltersCount, 'filtri attivi')
    
    // ✅ Debounce per evitare troppe chiamate
    if (updateOptionsTimeout) {
      clearTimeout(updateOptionsTimeout)
    }
    
    updateOptionsTimeout = setTimeout(async () => {
      try {
        console.log('🔄 Aggiornando opzioni disponibili per logica a cascata')
        
        const url = `/api/${props.category}/filters/chained?${currentParams}`
        console.log('🔍 URL per aggiornamento opzioni a cascata:', url)
        
        const response = await fetch(url)
        
        if (!response.ok) {
          console.error('❌ Errore HTTP nell\'aggiornamento opzioni a cascata:', response.status, response.statusText)
          console.error('❌ URL che ha causato l\'errore:', url)
          return
        }
        
        const data = await response.json()
        console.log('🔍 Dati per aggiornamento opzioni a cascata:', data)
        
        // ✅ Aggiorna le opzioni disponibili in base ai dati restituiti
        // ✅ LOGICA MIGLIORATA: Aggiorna solo i dropdown che necessitano di aggiornamento
        if (data.teams && data.teams.length > 0) {
          // Aggiorna le squadre solo se i filtri a catena restituiscono più squadre
          // o se non ci sono squadre già caricate
          if (filteredTeams.value.length === 0 || data.teams.length >= filteredTeams.value.length) {
            filteredTeams.value = data.teams
            console.log('✅ Squadre aggiornate per logica a cascata:', data.teams.length)
          } else {
            console.log('⚠️ Filtri a catena restituiscono meno squadre, mantengo quelle esistenti')
            console.log('⚠️ Squadre esistenti:', filteredTeams.value.length, 'vs filtri a catena:', data.teams.length)
          }
        } else {
          // Se non ci sono squadre nei filtri a catena, mantieni quelle esistenti
          console.log('⚠️ Nessuna squadra nei filtri a catena, mantengo quelle esistenti')
          console.log('⚠️ Squadre esistenti:', filteredTeams.value.length)
        }
        
        if (data.card_sets && data.card_sets.length > 0) {
          // Aggiorna i set disponibili
          filteredCardSets.value = data.card_sets
          console.log('✅ Set aggiornati per logica a cascata:', data.card_sets.length)
        } else {
          // Se non ci sono set, mantieni quelli esistenti
          console.log('⚠️ Nessun set nei filtri a catena, mantengo quelli esistenti')
        }
        
        // Aggiorna brand/rarity/year; se vuoti, usa fallback dai dati locali
        if (data.brands && data.brands.length) {
          availableBrands.value = data.brands
        }
        if (data.rarities && data.rarities.length) {
          availableRarities.value = data.rarities
        }
        if (data.years && data.years.length) {
          availableYears.value = data.years
        }

        if ((!data.brands || data.brands.length === 0) || (!data.rarities || data.rarities.length === 0) || (!data.years || data.years.length === 0)) {
          const fb = computeOptionsFromLocalCards()
          if ((!data.brands || data.brands.length === 0) && fb.brands.length) availableBrands.value = fb.brands
          if ((!data.rarities || data.rarities.length === 0) && fb.rarities.length) availableRarities.value = fb.rarities
          if ((!data.years || data.years.length === 0) && fb.years.length) availableYears.value = fb.years
        }

        console.log('✅ Brand finali:', availableBrands.value.length)
        console.log('✅ Rarities finali:', availableRarities.value.length)
        console.log('✅ Years finali:', availableYears.value.length)
        
        // Aggiorna i parametri dell'ultima chiamata riuscita
        lastUpdateParams = currentParams
        
      } catch (error) {
        console.error('❌ Errore nell\'aggiornamento opzioni a cascata:', error)
      }
    }, 500) // Debounce di 500ms
    
  } catch (error) {
    console.error('❌ Errore nella preparazione aggiornamento opzioni a cascata:', error)
  }
}

// Filtri slegati: rimuovi aggiornamenti a cascata su brand/rarity/year

// (nessuna azione)

// (nessuna azione)

const searchCards = () => {
  // Convert to the format expected by the card-models/search API
  const searchFilters = {}
  
  // Convert player_id to selectedPlayers array (API expects array)
  if (localFilters.value.player) {
    searchFilters.selectedPlayers = [localFilters.value.player]
  }
  
  // Convert team_id to team (API expects 'team', not 'team_id')
  if (localFilters.value.team) {
    searchFilters.team = localFilters.value.team
  }
  
  // Convert set_id to set (API expects 'set', not 'set_id')
  if (localFilters.value.set) {
    searchFilters.set = localFilters.value.set
  }
  
  // Brand, rarity, year are already in the correct format
  if (localFilters.value.brand) {
    searchFilters.brand = localFilters.value.brand
  }
  
  if (localFilters.value.rarity) {
    searchFilters.rarity = localFilters.value.rarity
  }
  
  if (localFilters.value.year) {
    searchFilters.year = localFilters.value.year
  }
  
  // Numbered filter (if exists)
  if (localFilters.value.numbered !== undefined && localFilters.value.numbered !== null && localFilters.value.numbered !== '') {
    searchFilters.numbered = localFilters.value.numbered
  }
  
  // Note: number and price are not used by card-models/search API
  // They are only relevant for single card listings
  
  emit('search-cards', searchFilters)
}

const loadTeamsForPlayer = async () => {
  if (!localFilters.value.player) {
    console.log('⚠️ Nessun player ID per caricare squadre')
    return
  }
  
  try {
    console.log('🔍 Caricamento squadre per giocatore:', localFilters.value.player)
    
    const params = new URLSearchParams()
    params.append('player_id', localFilters.value.player)
    
    const url = `/api/${props.category}/filters/teams/search?${params.toString()}`
    console.log('🔍 URL squadre:', url)
    
    const response = await fetch(url)
    console.log('🔍 Response status squadre:', response.status)
    
    if (!response.ok) {
      console.error('❌ Errore HTTP nel caricamento squadre:', response.status, response.statusText)
      return
    }
    
    const data = await response.json()
    console.log('🔍 Squadre caricate:', data.teams)
    console.log('🔍 Dettagli squadre:', data.teams?.map(t => ({ id: t.id, name: t.name })))
    
    filteredTeams.value = data.teams || []
    console.log('🔍 filteredTeams.value dopo assegnazione:', filteredTeams.value)
    console.log('🔍 filteredTeams.value.length:', filteredTeams.value.length)
    
    // Se il giocatore ha solo una squadra, selezionala automaticamente
    if (data.teams && data.teams.length === 1) {
      const singleTeam = data.teams[0]
      selectedTeam.value = singleTeam
      localFilters.value.team = singleTeam.id
      console.log('✅ Squadra unica selezionata automaticamente:', singleTeam.name)
    } else if (data.teams && data.teams.length > 1) {
      console.log('✅ Giocatore ha più squadre disponibili:', data.teams.map(t => t.name))
      console.log('✅ Squadre disponibili per selezione manuale')
    } else {
      console.log('⚠️ Nessuna squadra trovata per questo giocatore')
    }
    
  } catch (error) {
    console.error('❌ Errore nel caricamento squadre:', error)
    filteredTeams.value = []
  }
}

const loadAllTeamsForPlayer = async (player) => {
  if (!player || !player.all_teams) return
  
  try {
    console.log('🔍 Caricamento tutte le squadre per giocatore:', player.name)
    
    // Usa le squadre già disponibili nel player object
    filteredTeams.value = player.all_teams.map(team => ({
      id: team.id,
      name: team.name,
      slug: team.slug
    }))
    
    console.log('🔍 Squadre caricate da player object:', filteredTeams.value)
    
  } catch (error) {
    console.error('❌ Errore nel caricamento squadre:', error)
    filteredTeams.value = []
  }
}

const loadCardSetsForPlayer = async () => {
  if (!localFilters.value.player) {
    console.log('⚠️ Nessun player ID per caricare set')
    return
  }
  
  try {
    console.log('🔍 Caricamento set per giocatore:', localFilters.value.player)
    
    const params = new URLSearchParams()
    params.append('player_id', localFilters.value.player)
    // Includi team_id quando presente per filtrare correttamente i set
    if (localFilters.value.team) params.append('team_id', localFilters.value.team)
    
    const url = `/api/${props.category}/filters/card-sets/search?${params.toString()}`
    console.log('🔍 URL set per giocatore:', url)
    
    const response = await fetch(url)
    console.log('🔍 Response status set:', response.status)
    
    if (!response.ok) {
      console.error('❌ Errore HTTP nel caricamento set:', response.status, response.statusText)
      return
    }
    
    const data = await response.json()
    console.log('🔍 Set caricati per giocatore:', data.card_sets)
    
    // ✅ LOGICA MIGLIORATA: Rimuovi i set duplicati e gestisci correttamente gli anni
    if (data.card_sets && data.card_sets.length > 0) {
      // Raggruppa i set per nome per rimuovere i duplicati
      const uniqueSets = new Map()
      
      data.card_sets.forEach(set => {
        const setName = set.name
        if (!uniqueSets.has(setName)) {
          uniqueSets.set(setName, set)
        } else {
          // Se esiste già un set con lo stesso nome, mantieni quello con l'anno più recente
          const existingSet = uniqueSets.get(setName)
          if (set.year && existingSet.year && set.year > existingSet.year) {
            uniqueSets.set(setName, set)
          }
        }
      })
      
      filteredCardSets.value = Array.from(uniqueSets.values())
      console.log('✅ Set unici disponibili per giocatore:', filteredCardSets.value.map(s => s.name))
      console.log('✅ Set duplicati rimossi:', data.card_sets.length - filteredCardSets.value.length)
    } else {
      filteredCardSets.value = []
      console.log('⚠️ Nessun set trovato per questo giocatore')
    }
    
  } catch (error) {
    console.error('❌ Errore nel caricamento set per giocatore:', error)
    filteredCardSets.value = []
  }
}

const loadChainedData = async () => {
  try {
    // Controlla se ci sono troppi filtri selezionati per evitare errori 500
    const activeFiltersCount = [
      localFilters.value.player,
      localFilters.value.team,
      localFilters.value.set,
      localFilters.value.brand,
      localFilters.value.rarity,
      localFilters.value.year
    ].filter(Boolean).length
    
    // ✅ LOGICA MIGLIORATA: Chiama i filtri a catena anche con 1 filtro per aggiornare i dropdown
    // ma gestisci correttamente i risultati per non sovrascrivere le squadre
    console.log('🔄 Caricamento filtri a catena con', activeFiltersCount, 'filtri attivi')
    
    console.log('🔄 Caricamento filtri a catena con', activeFiltersCount, 'filtri attivi')
    
    const params = new URLSearchParams()
    if (localFilters.value.player) params.append('player_id', localFilters.value.player)
    if (localFilters.value.team) params.append('team_id', localFilters.value.team)
    if (localFilters.value.set) params.append('set_id', localFilters.value.set)
    if (localFilters.value.brand) params.append('brand', localFilters.value.brand)
    if (localFilters.value.rarity) params.append('rarity', localFilters.value.rarity)
    if (localFilters.value.year) params.append('year', localFilters.value.year)

    const url = `/api/${props.category}/filters/chained?${params.toString()}`
    console.log('🔍 URL filtri a catena:', url)

    const response = await fetch(url)
    
    if (!response.ok) {
      console.error('❌ Errore HTTP nei filtri a catena:', response.status, response.statusText)
      console.error('❌ URL che ha causato l\'errore:', url)
      return
    }
    
    const data = await response.json()
    console.log('🔍 Dati filtri a catena:', data)
    
    // ✅ Aggiorna le opzioni disponibili in base ai dati restituiti
    // ✅ LOGICA MIGLIORATA: Aggiorna solo i dropdown che necessitano di aggiornamento
    if (data.teams && data.teams.length > 0) {
      // Aggiorna le squadre solo se i filtri a catena restituiscono più squadre
      // o se non ci sono squadre già caricate
      if (filteredTeams.value.length === 0 || data.teams.length >= filteredTeams.value.length) {
        filteredTeams.value = data.teams
        console.log('✅ Squadre aggiornate da filtri a catena:', data.teams.length)
    } else {
        console.log('⚠️ Filtri a catena restituiscono meno squadre, mantengo quelle esistenti')
        console.log('⚠️ Squadre esistenti:', filteredTeams.value.length, 'vs filtri a catena:', data.teams.length)
      }
    } else {
      // Se non ci sono squadre nei filtri a catena, mantieni quelle esistenti
      console.log('⚠️ Nessuna squadra nei filtri a catena, mantengo quelle esistenti')
      console.log('⚠️ Squadre esistenti:', filteredTeams.value.length)
    }
    
    if (data.card_sets && data.card_sets.length > 0) {
      // Aggiorna i set disponibili
      filteredCardSets.value = data.card_sets
      console.log('✅ Set aggiornati da filtri a catena:', data.card_sets.length)
    } else {
      console.log('⚠️ Nessun set nei filtri a catena, mantengo quelli esistenti')
    }
    
    // Aggiorna brand/rarity/year; se vuoti, usa fallback dai dati locali
    if (data.brands && data.brands.length) {
      availableBrands.value = data.brands
    }
    if (data.rarities && data.rarities.length) {
      availableRarities.value = data.rarities
    }
    if (data.years && data.years.length) {
      // IMPORTANTE: Aggiorna solo le opzioni disponibili, NON il valore selezionato
      // Preserva l'anno selezionato manualmente dall'utente
      const currentYear = localFilters.value.year
      availableYears.value = data.years
      // Ripristina l'anno selezionato manualmente se è ancora valido
      if (currentYear && data.years.includes(currentYear)) {
        localFilters.value.year = currentYear
        console.log('✅ Anno selezionato manualmente preservato dopo aggiornamento opzioni:', currentYear)
      }
    }

    if ((!data.brands || data.brands.length === 0) || (!data.rarities || data.rarities.length === 0) || (!data.years || data.years.length === 0)) {
      const fb = computeOptionsFromLocalCards()
      if ((!data.brands || data.brands.length === 0) && fb.brands.length) availableBrands.value = fb.brands
      if ((!data.rarities || data.rarities.length === 0) && fb.rarities.length) availableRarities.value = fb.rarities
      if ((!data.years || data.years.length === 0) && fb.years.length) availableYears.value = fb.years
    }

    console.log('✅ Brand finali:', availableBrands.value.length)
    console.log('✅ Rarities finali:', availableRarities.value.length)
    console.log('✅ Years finali:', availableYears.value.length)
  } catch (error) {
    console.error('❌ Errore nel caricamento dati filtri a catena:', error)
    console.error('❌ Dettagli errore:', error.message)
  }
}

const loadChainedDataWithBrand = async (selectedBrand) => {
  try {
    const params = new URLSearchParams()
    if (localFilters.value.player) params.append('player_id', localFilters.value.player)
    if (localFilters.value.team) params.append('team_id', localFilters.value.team)
    if (localFilters.value.set) params.append('set_id', localFilters.value.set)
    if (selectedBrand) params.append('brand', selectedBrand)
    if (localFilters.value.rarity) params.append('rarity', localFilters.value.rarity)
    if (localFilters.value.year) params.append('year', localFilters.value.year)

    const response = await fetch(`/api/${props.category}/filters/chained?${params.toString()}`)
    const data = await response.json()
    
    console.log('Dati filtri a catena con brand:', data)
    
    // Update available options based on current selections
    if (data.rarities && data.rarities.length > 0) {
      availableRarities.value = data.rarities
      console.log('✅ Rarities aggiornate da filtri a catena:', data.rarities)
    } else {
      console.log('⚠️ Nessuna rarity per il brand selezionato:', selectedBrand)
    }
    
    if (data.years && data.years.length > 0) {
      availableYears.value = data.years
      console.log('✅ Years aggiornati da filtri a catena:', data.years)
    } else {
      console.log('⚠️ Nessun year per il brand selezionato:', selectedBrand)
    }
    
    // Aggiorna i brand disponibili dai set
    if (data.sets && data.sets.length > 0) {
      const brands = [...new Set(data.sets.map(set => set.brand))].filter(Boolean)
      availableBrands.value = brands
      console.log('✅ Brands aggiornati da filtri a catena:', brands)
    } else {
      console.log('⚠️ Nessun set per il brand selezionato:', selectedBrand)
    }
    
    console.log('✅ Brand mantenuto dall\'utente:', selectedBrand)
    
  } catch (error) {
    console.error('Errore nel caricamento dati filtri a catena:', error)
  }
}

const loadInitialData = async () => {
  try {
    console.log('🔄 Caricamento dati iniziali per categoria:', props.category)
    
    const response = await fetch(`/api/${props.category}/filters/options`)
    const data = await response.json()
    
    console.log('📊 Dati iniziali caricati:', data)
    console.log('📊 Rarities raw:', data.rarities)
    console.log('📊 Years raw:', data.years)
    
    // Load initial options
    if (data.rarities) {
      availableRarities.value = data.rarities
      console.log('✅ Rarities assegnate:', availableRarities.value)
    } else {
      console.log('❌ Nessuna rarity trovata nei dati')
    }
    
    if (data.years) {
      // Ordina in ordine decrescente lato frontend per sicurezza
      availableYears.value = [...data.years].sort((a, b) => String(b).localeCompare(String(a), undefined, { numeric: true }))
      console.log('✅ Years assegnati:', availableYears.value)
    } else {
      console.log('❌ Nessun year trovato nei dati')
    }
    
    // Extract brands from card_sets
    if (data.card_sets) {
      console.log('📊 Card sets raw:', data.card_sets)
      const brands = [...new Set(data.card_sets.map(set => set.brand))].filter(Boolean)
      availableBrands.value = brands
      console.log('✅ Brands estratti da card_sets:', brands)
      console.log('✅ availableBrands.value dopo assegnazione:', availableBrands.value)
    } else {
      console.log('❌ Nessun card_sets trovato nei dati')
    }
    
    console.log('🎯 Stato finale:')
    console.log('  - Available brands:', availableBrands.value)
    console.log('  - Available rarities:', availableRarities.value)
    console.log('  - Available years:', availableYears.value)
  } catch (error) {
    console.error('❌ Errore nel caricamento dati iniziali:', error)
  }
}

// Ripristina gli oggetti completi quando abbiamo solo gli ID
const restoreSelectedEntities = async () => {
  console.log('🔄 Ripristino entità selezionate da initialFilters:', props.initialFilters)
  
  // Ripristina player se abbiamo un ID ma non l'oggetto
  if (localFilters.value.player && !selectedPlayer.value) {
    try {
      // Controlla se abbiamo già un playerSearch (nome) ma non l'oggetto completo
      if (localFilters.value.playerSearch && !selectedPlayer.value) {
        // Il player potrebbe essere già stato popolato tramite l'evento filters-populated
        // Aspetta un po' prima di fare la chiamata API
        await new Promise(resolve => setTimeout(resolve, 200))
        if (selectedPlayer.value) {
          console.log('✅ Player già popolato tramite evento')
          return
        }
        
        // Se abbiamo playerSearch ma non selectedPlayer, crea un oggetto temporaneo per mostrare il tag
        // Nota: playerSearch viene usato solo per creare l'oggetto temporaneo, poi viene svuotato
        if (localFilters.value.playerSearch && localFilters.value.player) {
          console.log('🔄 Creando oggetto player temporaneo per mostrare il tag')
          selectedPlayer.value = {
            id: localFilters.value.player,
            name: localFilters.value.playerSearch,
            display_name: localFilters.value.playerSearch
          }
          // Svuota il campo di ricerca dopo aver creato l'oggetto temporaneo
          localFilters.value.playerSearch = ''
        }
      }
      
      // Se abbiamo ancora solo l'ID ma non l'oggetto completo, carica dall'API
      if (!selectedPlayer.value || !selectedPlayer.value.id) {
        const response = await fetch(`/api/${props.category}/filters/players/${localFilters.value.player}`, {
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        if (response.ok) {
          const data = await response.json()
          if (data.player) {
            selectedPlayer.value = data.player
            console.log('✅ Player ripristinato dall\'API:', selectedPlayer.value)
          } else {
            console.warn('⚠️ Player non trovato nella risposta')
          }
        } else {
          console.warn('⚠️ Errore HTTP nel ripristino player:', response.status)
        }
      }
    } catch (error) {
      console.error('❌ Errore nel ripristino player:', error)
    }
  }
  
  // Ripristina team se abbiamo un ID ma non l'oggetto
  if (localFilters.value.team && !selectedTeam.value) {
    try {
      const response = await fetch(`/api/${props.category}/filters/teams/${localFilters.value.team}`)
      if (response.ok) {
        const data = await response.json()
        selectedTeam.value = data.team
        console.log('✅ Team ripristinato:', selectedTeam.value)
      }
    } catch (error) {
      console.error('❌ Errore nel ripristino team:', error)
    }
  }
  
  // Ripristina set se abbiamo un ID ma non l'oggetto
  if (localFilters.value.set && !selectedCardSet.value) {
    try {
      const response = await fetch(`/api/${props.category}/filters/card-sets/${localFilters.value.set}`)
      if (response.ok) {
        const data = await response.json()
        selectedCardSet.value = data.card_set
        console.log('✅ Set ripristinato:', selectedCardSet.value)
      }
    } catch (error) {
      console.error('❌ Errore nel ripristino set:', error)
    }
  }
}

// Lifecycle
onMounted(async () => {
  await loadInitialData()
  await restoreSelectedEntities()
  
  // Ascolta l'evento per popolare i filtri quando viene selezionata una carta
  window.addEventListener('filters-populated', handleFiltersPopulated)
  
  // Ascolta l'evento per aggiornare i filtri quando viene selezionata una carta in modalità edit
  window.addEventListener('card-selected', handleCardSelected)
  
  // Se ci sono filtri iniziali con playerSearch ma selectedPlayer non è ancora popolato,
  // aspetta un po' per permettere agli eventi di essere emessi
  if (localFilters.value.playerSearch && !selectedPlayer.value) {
    setTimeout(async () => {
      if (!selectedPlayer.value && localFilters.value.player) {
        await restoreSelectedEntities()
      }
    }, 500)
  }
})

onUnmounted(() => {
  window.removeEventListener('filters-populated', handleFiltersPopulated)
  window.removeEventListener('card-selected', handleCardSelected)
})

// Watch for external filter changes
watch(() => props.initialFilters, async (newFilters) => {
  console.log('🔄 initialFilters cambiati:', newFilters)
  
  // Salva il brand selezionato dall'utente prima di aggiornare i filtri
  const userSelectedBrand = localFilters.value.brand
  console.log('🔄 Brand selezionato dall\'utente da preservare:', userSelectedBrand)
  
  // Aggiorna i filtri ma preserva il brand selezionato dall'utente
  localFilters.value = { ...localFilters.value, ...newFilters }
  
  // Se l'utente aveva selezionato un brand, ripristinalo
  if (userSelectedBrand && userSelectedBrand !== '') {
    localFilters.value.brand = userSelectedBrand
    console.log('✅ Brand dell\'utente ripristinato:', userSelectedBrand)
  }
  
  // Se abbiamo playerSearch ma non selectedPlayer, imposta selectedPlayer basandosi sui dati disponibili
  if (localFilters.value.playerSearch && !selectedPlayer.value && localFilters.value.player) {
    console.log('🔄 Trovato playerSearch ma selectedPlayer non popolato, ripristino...')
    await restoreSelectedEntities()
  }
  
  // Sincronizza rarity con selectedRarity e raritySearch
  if (localFilters.value.rarity) {
    selectedRarity.value = localFilters.value.rarity
    localFilters.value.raritySearch = localFilters.value.rarity
  } else {
    selectedRarity.value = null
    localFilters.value.raritySearch = ''
  }
  
  await restoreSelectedEntities()
  // Non caricare sempre i filtri a catena per evitare errori 500
  // loadChainedData()
}, { deep: true })

// Watch for team filter changes to update cards
watch(() => localFilters.value.team, () => {
  console.log('🔄 Team filter cambiato:', localFilters.value.team)
  filterCardsByTeam()
  // Ricarica i set in base al nuovo team
  loadCardSetsForPlayer()
  // Filtri slegati: non aggiornare opzioni a cascata
})

// Watch for set filter changes to update cards
watch(() => localFilters.value.set, () => {
  console.log('🔄 Set filter cambiato:', localFilters.value.set)
  filterCardsBySet()
  // IMPORTANTE: Carica le opzioni disponibili per l'anno quando viene selezionato un set
  // Questo aggiorna il dropdown Year con le annate disponibili per il set selezionato
  if (localFilters.value.set) {
    loadChainedData()
  }
})

// Aggiorna lista carte quando cambiano brand/year/rarity
watch(() => localFilters.value.brand, () => {
  recomputeFilteredCards()
})
watch(() => localFilters.value.year, () => {
  recomputeFilteredCards()
})
watch(() => localFilters.value.rarity, () => {
  console.log('🔄 Rarity filter cambiato:', localFilters.value.rarity)
  recomputeFilteredCards()
})
// Watch per popolare selectedPlayer quando playerSearch cambia ma selectedPlayer è vuoto
watch(() => localFilters.value.playerSearch, async (newSearch) => {
  // Se abbiamo playerSearch ma non selectedPlayer e abbiamo un player ID, ripristina il player
  if (newSearch && newSearch !== '' && !selectedPlayer.value && localFilters.value.player) {
    console.log('🔄 playerSearch cambiato ma selectedPlayer vuoto, ripristino player...')
    await restoreSelectedEntities()
  }
}, { immediate: false })

// Watch per popolare selectedPlayer quando player ID cambia
watch(() => localFilters.value.player, async (newPlayerId) => {
  // Se abbiamo un player ID ma non selectedPlayer, ripristina il player
  if (newPlayerId && newPlayerId !== '' && !selectedPlayer.value) {
    console.log('🔄 Player ID cambiato ma selectedPlayer vuoto, ripristino player...')
    await restoreSelectedEntities()
  }
}, { immediate: false })

// Gestisce l'evento di popolamento filtri
const handleFiltersPopulated = async (event) => {
  const data = event.detail
  console.log('🎯 handleFiltersPopulated ricevuto con dati:', data)
  
  // Popola Player (importante per la sezione "Seleziona Carta")
  if (data.player) {
    // Assicurati che il player sia un oggetto completo
    selectedPlayer.value = data.player
    localFilters.value.player = data.player.id
    localFilters.value.playerSearch = '' // Lascia il campo vuoto, il tag mostrerà il nome
    console.log('✅ Player popolato tramite filters-populated:', selectedPlayer.value?.name || selectedPlayer.value?.display_name)
    
    // Inizializza le carte del giocatore se disponibili
    if (data.player.cards && data.player.cards.length > 0) {
      initializeCardFiltering(data.player)
    }
  } else {
    console.warn('⚠️ Nessun player nei dati di filters-populated')
  }
  
  // Popola Team
  if (data.team) {
    selectedTeam.value = data.team
    localFilters.value.team = data.team.id || data.team
    localFilters.value.teamSearch = '' // Svuota il campo di ricerca, il tag mostrerà il nome
    console.log('✅ Team popolato tramite filters-populated:', selectedTeam.value?.name)
  }
  
  // Popola Set
  if (data.card_set) {
    // Preserva l'anno selezionato manualmente dall'utente prima di popolare il set
    const userSelectedYear = localFilters.value.year
    
    selectedCardSet.value = data.card_set
    localFilters.value.set = data.card_set.id || data.card_set
    localFilters.value.setSearch = '' // Svuota il campo di ricerca, il tag mostrerà il nome
    console.log('✅ Set popolato tramite filters-populated:', selectedCardSet.value?.name)
    
    // IMPORTANTE: NON popolare l'anno automaticamente dal set se l'utente ha già selezionato un anno
    // Ripristina l'anno selezionato manualmente dall'utente (se presente)
    if (userSelectedYear) {
      localFilters.value.year = userSelectedYear
      console.log('✅ Anno selezionato manualmente preservato dopo popolamento set:', userSelectedYear)
    }
  }
  
  // Popola Brand
  if (data.brand) {
    localFilters.value.brand = data.brand
    console.log('✅ Brand popolato tramite filters-populated:', data.brand)
  }
  
  // Popola altri filtri
  if (data.rarity) {
    localFilters.value.rarity = data.rarity
    selectedRarity.value = data.rarity
    localFilters.value.raritySearch = data.rarity
    console.log('✅ Rarity popolato tramite filters-populated:', data.rarity)
  }
  // IMPORTANTE: Popola l'anno SOLO se esplicitamente fornito nei dati, NON dal set
  // Questo evita che l'anno venga popolato automaticamente quando viene selezionato un set
  if (data.year && !data.card_set) {
    // Popola l'anno solo se NON stiamo popolando anche un set (per evitare conflitti)
    localFilters.value.year = data.year
    console.log('✅ Year popolato tramite filters-populated:', data.year)
  } else if (data.year && data.card_set && !localFilters.value.year) {
    // Popola l'anno solo se non c'è già un anno selezionato manualmente
    localFilters.value.year = data.year
    console.log('✅ Year popolato tramite filters-populated (nessun anno esistente):', data.year)
  }
  if (data.number) {
    localFilters.value.number = data.number
    console.log('✅ Number popolato tramite filters-populated:', data.number)
  }
  
  // Forza un re-render per assicurarsi che i tag vengano mostrati
  await nextTick()
  
  // Aggiorna i filtri
  onFiltersChanged()
}

// Gestisce l'evento di carta selezionata in modalità edit
const handleCardSelected = (event) => {
  const card = event.detail.card
  
  if (card) {
    // Popola Team se disponibile
    if (card.team) {
      selectedTeam.value = card.team
      localFilters.value.team = card.team.id
    }
    
    // Popola Set se disponibile
    if (card.card_set) {
      selectedCardSet.value = card.card_set
      localFilters.value.set = card.card_set.id
    }
    
    // Popola Player se disponibile
    if (card.player) {
      selectedPlayer.value = card.player
      localFilters.value.player = card.player.id
    }
    
    // Popola altri campi se disponibili
    if (card.rarity) {
      localFilters.value.rarity = card.rarity
      selectedRarity.value = card.rarity
      localFilters.value.raritySearch = card.rarity
    }
    if (card.year) {
      localFilters.value.year = card.year
    }
    if (card.brand) {
      localFilters.value.brand = card.brand
    }
    if (card.number) {
      localFilters.value.number = card.number
    }
    
    // Aggiorna i filtri
    onFiltersChanged()
  }
}
</script>
