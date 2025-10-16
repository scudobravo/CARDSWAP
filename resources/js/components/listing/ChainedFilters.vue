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
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
          @input="searchPlayers"
          @focus="onPlayerFocus"
          @blur="onPlayerBlur"
        />
        <div v-if="filteredPlayers.length > 0 && showPlayerDropdown" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
          <div v-for="player in filteredPlayers" :key="player.id" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" @click="selectPlayer(player)">
            <div class="flex flex-col">
              <span class="font-normal block truncate">{{ player.display_name || player.name }}</span>
              <span v-if="player.team" class="text-xs text-gray-500">{{ player.team.name }}</span>
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
      
      <!-- Carte del giocatore selezionato -->
      <div v-if="selectedPlayer && selectedPlayer.cards && selectedPlayer.cards.length > 0" class="mt-4">
        <div class="flex justify-between items-center mb-2">
          <label class="block text-sm font-medium text-gray-700">
            Seleziona Carta ({{ selectedPlayer.cards.length }} disponibili)
          </label>
          <select 
            v-model="cardSetFilter" 
            class="text-xs border border-gray-300 rounded px-2 py-1"
            @change="filterCardsBySet"
          >
            <option value="">Tutti i set</option>
            <option v-for="set in uniqueCardSets" :key="set.id" :value="set.id">
              {{ set.name }} ({{ set.count }})
            </option>
          </select>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-80 overflow-y-auto">
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
              <div class="text-sm font-medium text-gray-900 truncate flex-1 mr-2">{{ card.name }}</div>
              <div v-if="card.card_number_in_set" class="text-xs font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded">
                #{{ card.card_number_in_set }}
              </div>
            </div>
            
            <!-- Set e Anno -->
            <div class="text-xs text-gray-600 mb-1">
              <div class="font-medium">{{ card.card_set?.name || 'N/A' }}</div>
              <div class="text-gray-500">{{ card.year || 'N/A' }}</div>
            </div>
            
            <!-- Rarity e Brand -->
            <div class="flex justify-between items-center text-xs">
              <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded-full">
                {{ card.rarity || 'N/A' }}
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

    <!-- Prima riga: Team e Set -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
      <!-- Team Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Team</label>
        <div class="relative">
          <input 
            v-model="localFilters.teamSearch"
            type="text" 
            placeholder="Cerca team..."
            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
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
            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
            @input="searchCardSets"
            @focus="onSetFocus"
            @blur="onSetBlur"
          />
          <div v-if="filteredCardSets.length > 0 && showSetDropdown" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
            <div v-for="set in filteredCardSets" :key="set.id" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" @click="selectCardSet(set)">
              <span class="font-normal block truncate">{{ set.name }} ({{ set.year }})</span>
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
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
      <!-- Brand Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
        <select 
          v-model="localFilters.brand"
          @change="onFiltersChanged"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6"
        >
          <option value="">Seleziona Brand</option>
          <option v-for="brand in availableBrands" :key="brand" :value="brand">{{ brand }}</option>
        </select>
      </div>

      <!-- Rarity Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Rarity ({{ availableRarities.length }} opzioni)
        </label>
        <select 
          v-model="localFilters.rarity"
          @change="onFiltersChanged"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6"
        >
          <option value="">Seleziona Rarity</option>
          <option v-for="rarity in availableRarities" :key="rarity" :value="rarity">{{ rarity }}</option>
        </select>
      </div>

      <!-- Year Selection -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Year ({{ availableYears.length }} opzioni)
        </label>
        <select 
          v-model="localFilters.year"
          @change="onFiltersChanged"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6"
        >
          <option value="">Seleziona Year</option>
          <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
        </select>
      </div>
    </div>

    <!-- Terza riga: Number e Price (Solo per Single Card) -->
    <div v-if="showNumber || showPrice" class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
      <!-- Number Input (Solo per Single Card) -->
      <div v-if="showNumber">
        <label class="block text-sm font-medium text-gray-700 mb-2">Number *</label>
        <input 
          v-model="localFilters.number"
          type="text" 
          placeholder="Inserisci numero carta (es. 30, RF-18, BA-ZI)"
          class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
          @input="onFiltersChanged"
        />
      </div>

      <!-- Price Input (Solo per Single Card) -->
      <div v-if="showPrice">
        <label class="block text-sm font-medium text-gray-700 mb-2">Price (€) *</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <span class="text-gray-500 text-sm">€</span>
          </div>
          <input 
            v-model="localFilters.price"
            type="number" 
            step="0.01"
            min="0"
            placeholder="0.00"
            class="block w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none"
            @input="onFiltersChanged"
          />
        </div>
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
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

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
  initialFilters: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['filters-changed', 'search-cards'])

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
  year: '',
  number: '',
  price: '',
  ...props.initialFilters
})

const selectedPlayer = ref(null)
const selectedCard = ref(null)
const selectedTeam = ref(null)
const selectedCardSet = ref(null)
const cardSetFilter = ref('')
const filteredCards = ref([])
const uniqueCardSets = ref([])
const filteredPlayers = ref([])
const filteredTeams = ref([])
const filteredCardSets = ref([])

// Available options (populated by chained filters)
const availableBrands = ref([])
const availableRarities = ref([])
const availableYears = ref([])

// Dropdown visibility state
const showPlayerDropdown = ref(false)
const showTeamDropdown = ref(false)
const showSetDropdown = ref(false)

// Computed
const canSearch = computed(() => {
  return localFilters.value.team || localFilters.value.set || localFilters.value.brand || localFilters.value.rarity || localFilters.value.year
})

// Methods
let searchTimeout = null

const searchPlayers = async () => {
  // Clear previous timeout
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  // Set new timeout for debounced search
  searchTimeout = setTimeout(async () => {
    const query = localFilters.value.playerSearch || ''
    
    console.log('Ricerca giocatori per:', query)
    console.log('Categoria:', props.category)
    
    try {
      // Costruisci i parametri con i filtri correnti per interdipendenza
      const params = new URLSearchParams({ q: query })
      if (localFilters.value.team) params.append('team_id', localFilters.value.team)
      if (localFilters.value.set) params.append('set_id', localFilters.value.set)
      if (localFilters.value.year) params.append('year', localFilters.value.year)
      if (localFilters.value.brand) params.append('brand', localFilters.value.brand)
      
      const url = `/api/${props.category}/filters/players/search?${params.toString()}`
      console.log('URL:', url)
      
      const response = await fetch(url)
      console.log('Response status:', response.status)
      
      const data = await response.json()
      console.log('Response data:', data)
      
      filteredPlayers.value = data.players || []
      console.log('Filtered players:', filteredPlayers.value)
    } catch (error) {
      console.error('Errore nella ricerca giocatori:', error)
      filteredPlayers.value = []
    }
  }, 300) // 300ms debounce
}

const onPlayerFocus = async () => {
  showPlayerDropdown.value = true
  // Carica tutti i giocatori disponibili quando si fa focus
  await searchPlayers()
}

const onPlayerBlur = () => {
  // Ritarda la chiusura per permettere il click su un elemento
  setTimeout(() => {
    showPlayerDropdown.value = false
  }, 200)
}

const searchTeams = async () => {
  const query = localFilters.value.teamSearch || ''
  
  console.log('Ricerca team per:', query)
  
  try {
    // Costruisci i parametri con i filtri correnti per interdipendenza
    const params = new URLSearchParams({ q: query })
    if (localFilters.value.player) params.append('player_id', localFilters.value.player)
    if (localFilters.value.set) params.append('set_id', localFilters.value.set)
    if (localFilters.value.year) params.append('year', localFilters.value.year)
    if (localFilters.value.brand) params.append('brand', localFilters.value.brand)
    
    const url = `/api/${props.category}/filters/teams/search?${params.toString()}`
    console.log('URL team:', url)
    
    const response = await fetch(url)
    console.log('Response status team:', response.status)
    
    const data = await response.json()
    console.log('Response data team:', data)
    
    filteredTeams.value = data.teams || []
    console.log('Filtered teams:', filteredTeams.value)
  } catch (error) {
    console.error('Errore nella ricerca squadre:', error)
    filteredTeams.value = []
  }
}

const onTeamFocus = async () => {
  showTeamDropdown.value = true
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
  
  console.log('Ricerca set per:', query)
  
  try {
    // Costruisci i parametri con i filtri correnti per interdipendenza
    const params = new URLSearchParams({ q: query })
    if (localFilters.value.player) params.append('player_id', localFilters.value.player)
    if (localFilters.value.team) params.append('team_id', localFilters.value.team)
    if (localFilters.value.year) params.append('year', localFilters.value.year)
    if (localFilters.value.brand) params.append('brand', localFilters.value.brand)
    
    const url = `/api/${props.category}/filters/card-sets/search?${params.toString()}`
    console.log('URL set:', url)
    
    const response = await fetch(url)
    console.log('Response status set:', response.status)
    
    const data = await response.json()
    console.log('Response data set:', data)
    
    filteredCardSets.value = data.card_sets || []
    console.log('Filtered sets:', filteredCardSets.value)
  } catch (error) {
    console.error('Errore nella ricerca set:', error)
    filteredCardSets.value = []
  }
}

const onSetFocus = async () => {
  showSetDropdown.value = true
  // Carica tutti i set disponibili quando si fa focus
  await searchCardSets()
}

const onSetBlur = () => {
  // Ritarda la chiusura per permettere il click su un elemento
  setTimeout(() => {
    showSetDropdown.value = false
  }, 200)
}

const selectPlayer = (player) => {
  selectedPlayer.value = player
  selectedCard.value = null // Reset carta selezionata
  cardSetFilter.value = '' // Reset filtro set
  localFilters.value.player = player.id
  localFilters.value.playerSearch = ''
  filteredPlayers.value = []
  showPlayerDropdown.value = false
  
  // Popola automaticamente il campo Team
  if (player.team) {
    localFilters.value.team = player.team.id
    selectedTeam.value = player.team
    console.log('✅ Campo Team popolato con:', player.team.name)
  }
  
  // Popola il campo Numbered con il primo card_number disponibile
  if (player.card_numbers && player.card_numbers.length > 0) {
    localFilters.value.number = player.card_numbers[0]
    console.log('✅ Campo Numbered popolato con:', player.card_numbers[0])
  } else if (player.cards && player.cards.length > 0) {
    // Fallback: usa card_number_in_set se card_numbers non è disponibile
    const firstCard = player.cards[0]
    const cardNumber = firstCard.card_number || firstCard.card_number_in_set
    if (cardNumber) {
      localFilters.value.number = cardNumber
      console.log('✅ Campo Numbered popolato con (da cards):', cardNumber)
    }
  }
  
  // Inizializza le carte filtrate e i set unici
  initializeCardFiltering(player)
  
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
  }
}

const filterCardsBySet = () => {
  if (!selectedPlayer.value || !selectedPlayer.value.cards) return
  
  if (cardSetFilter.value === '') {
    filteredCards.value = selectedPlayer.value.cards
  } else {
    filteredCards.value = selectedPlayer.value.cards.filter(card => 
      card.card_set && card.card_set.id == cardSetFilter.value
    )
  }
}

const selectCard = (card) => {
  selectedCard.value = card
  
  // Aggiorna i filtri con i dati della carta selezionata
  if (card.card_set) {
    localFilters.value.set = card.card_set.id
    selectedCardSet.value = card.card_set
    console.log('✅ Campo Set aggiornato con:', card.card_set.name)
  }
  
  if (card.year) {
    localFilters.value.year = card.year
    console.log('✅ Campo Year aggiornato con:', card.year)
  }
  
  if (card.rarity) {
    localFilters.value.rarity = card.rarity
    console.log('✅ Campo Rarity aggiornato con:', card.rarity)
  }
  
  // Aggiorna il campo Numbered con il numero della carta selezionata
  const cardNumber = card.card_number || card.card_number_in_set
  if (cardNumber) {
    localFilters.value.number = cardNumber
    console.log('✅ Campo Numbered aggiornato con:', cardNumber)
  }
  
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
  onFiltersChanged()
}

const removeTeam = () => {
  selectedTeam.value = null
  localFilters.value.team = null
  onFiltersChanged()
}

const selectCardSet = (set) => {
  selectedCardSet.value = set
  localFilters.value.set = set.id
  localFilters.value.setSearch = ''
  filteredCardSets.value = []
  showSetDropdown.value = false
  onFiltersChanged()
}

const removeCardSet = () => {
  selectedCardSet.value = null
  localFilters.value.set = null
  onFiltersChanged()
}

const onFiltersChanged = () => {
  emit('filters-changed', localFilters.value)
  loadChainedData()
}

const searchCards = () => {
  // Convert to the format expected by the API
  const searchFilters = {
    player_id: localFilters.value.player,
    team_id: localFilters.value.team,
    set_id: localFilters.value.set,
    brand: localFilters.value.brand,
    rarity: localFilters.value.rarity,
    year: localFilters.value.year,
    number: localFilters.value.number,
    price: localFilters.value.price
  }
  emit('search-cards', searchFilters)
}

const loadChainedData = async () => {
  try {
    const params = new URLSearchParams()
    if (localFilters.value.player) params.append('player_id', localFilters.value.player)
    if (localFilters.value.team) params.append('team_id', localFilters.value.team)
    if (localFilters.value.set) params.append('set_id', localFilters.value.set)
    if (localFilters.value.brand) params.append('brand', localFilters.value.brand)
    if (localFilters.value.rarity) params.append('rarity', localFilters.value.rarity)
    if (localFilters.value.year) params.append('year', localFilters.value.year)

    const response = await fetch(`/api/${props.category}/filters/chained?${params.toString()}`)
    const data = await response.json()
    
    console.log('Dati filtri a catena:', data)
    
    // Update available options based on current selections
    // SOLO se i dati esistono, altrimenti mantieni quelli iniziali
    if (data.rarities && data.rarities.length > 0) {
      availableRarities.value = data.rarities
      console.log('✅ Rarities aggiornate da filtri a catena:', data.rarities)
    } else {
      console.log('⚠️ Nessuna rarity nei filtri a catena, mantengo quelle iniziali')
    }
    
    if (data.years && data.years.length > 0) {
      availableYears.value = data.years
      console.log('✅ Years aggiornati da filtri a catena:', data.years)
    } else {
      console.log('⚠️ Nessun year nei filtri a catena, mantengo quelli iniziali')
    }
    
    // Extract brands from sets if available
    if (data.sets && data.sets.length > 0) {
      const brands = [...new Set(data.sets.map(set => set.brand))].filter(Boolean)
      availableBrands.value = brands
      console.log('✅ Brands aggiornati da filtri a catena:', brands)
    } else {
      console.log('⚠️ Nessun set nei filtri a catena, mantengo brands iniziali')
    }
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
      availableYears.value = data.years
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
      const response = await fetch(`/api/${props.category}/filters/players/${localFilters.value.player}`)
      if (response.ok) {
        const data = await response.json()
        selectedPlayer.value = data.player
        console.log('✅ Player ripristinato:', selectedPlayer.value)
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
})

onUnmounted(() => {
  window.removeEventListener('filters-populated', handleFiltersPopulated)
  window.removeEventListener('card-selected', handleCardSelected)
})

// Watch for external filter changes
watch(() => props.initialFilters, async (newFilters) => {
  console.log('🔄 initialFilters cambiati:', newFilters)
  localFilters.value = { ...localFilters.value, ...newFilters }
  await restoreSelectedEntities()
  loadChainedData()
}, { deep: true })

// Gestisce l'evento di popolamento filtri
const handleFiltersPopulated = async (event) => {
  const data = event.detail
  
  // Popola Player (importante per la sezione "Seleziona Carta")
  if (data.player) {
    selectedPlayer.value = data.player
    localFilters.value.player = data.player.id
    localFilters.value.playerSearch = data.player.display_name || data.player.name
    
    // Inizializza le carte del giocatore se disponibili
    if (data.player.cards && data.player.cards.length > 0) {
      initializeCardFiltering(data.player)
    }
  }
  
  // Popola Team
  if (data.team) {
    selectedTeam.value = data.team
    localFilters.value.team = data.team.id
  }
  
  // Popola Set
  if (data.card_set) {
    selectedCardSet.value = data.card_set
    localFilters.value.set = data.card_set.id
  }
  
  // Popola altri filtri
  if (data.rarity) {
    localFilters.value.rarity = data.rarity
  }
  if (data.year) {
    localFilters.value.year = data.year
  }
  if (data.brand) {
    localFilters.value.brand = data.brand
  }
  if (data.number) {
    localFilters.value.number = data.number
  }
  
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
