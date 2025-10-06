<template>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Form Section -->
    <div class="space-y-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Card Details</h3>
        
        <!-- Player Selection -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Player</label>
          <div class="relative">
            <input 
              v-model="localFilters.playerSearch"
              type="text" 
              placeholder="Type Player"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
              @input="searchPlayers"
            />
            <div v-if="filteredPlayers.length > 0 && localFilters.playerSearch" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
              <div v-for="player in filteredPlayers" :key="player.id" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" @click="selectPlayer(player)">
                <span class="font-normal block truncate">{{ player.name }}</span>
              </div>
            </div>
          </div>
          <div v-if="selectedPlayer" class="flex flex-wrap gap-2 mt-2">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary text-white">
              {{ selectedPlayer.name }}
              <button type="button" @click="removePlayer" class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-primary-dark">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
              </button>
            </span>
          </div>
        </div>

        <!-- Set Selection -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Set</label>
          <div class="relative">
            <input 
              v-model="localFilters.setSearch"
              type="text" 
              placeholder="Type Set"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
              @input="searchCardSets"
            />
            <div v-if="filteredCardSets.length > 0 && localFilters.setSearch" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
              <div v-for="set in filteredCardSets" :key="set.id" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" @click="selectCardSet(set)">
                <span class="font-normal block truncate">{{ set.name }}</span>
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

        <!-- Team Selection -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Team</label>
          <div class="relative">
            <input 
              v-model="localFilters.teamSearch"
              type="text" 
              placeholder="Type Team"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
              @input="searchTeams"
            />
            <div v-if="filteredTeams.length > 0 && localFilters.teamSearch" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
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

        <!-- Brand, Year, Rarity -->
        <div class="grid grid-cols-3 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Brand</label>
            <select v-model="localFilters.brand" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6">
              <option value="">Select Brand</option>
              <option v-for="brand in availableBrands" :key="brand" :value="brand">{{ brand }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
            <select v-model="localFilters.year" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6">
              <option value="">Select Year</option>
              <option v-for="year in availableYears" :key="year" :value="year">{{ year }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Rarity</label>
            <select v-model="localFilters.rarity" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6">
              <option value="">Select Rarity</option>
              <option v-for="rarity in availableRarities" :key="rarity" :value="rarity">{{ rarity }}</option>
            </select>
          </div>
        </div>

        <!-- Number -->
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Number</label>
          <div class="flex items-center space-x-2">
            <input 
              v-model="localFilters.numberedMin"
              type="number" 
              placeholder="Type Number"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
            />
            <span class="text-gray-500">/</span>
            <input 
              v-model="localFilters.numberedMax"
              type="number" 
              placeholder="Type Number"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
            />
          </div>
        </div>

        <!-- Card Attributes -->
        <div class="mb-6">
          <label class="block text-sm font-medium text-gray-700 mb-3">Card Attributes</label>
          <div class="grid grid-cols-2 gap-3">
            <button 
              @click="toggleAttribute('autograph')"
              :class="[
                'flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium transition-colors',
                localFilters.autograph ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
              Autograph
            </button>
            
            <button 
              @click="toggleAttribute('relic')"
              :class="[
                'flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium transition-colors',
                localFilters.relic ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              Relic
            </button>
            
            <button 
              @click="toggleAttribute('rookie')"
              :class="[
                'flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium transition-colors',
                localFilters.rookie ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
            >
              <span class="w-4 h-4 mr-2 text-xs font-bold">RC</span>
              RC Rookie
            </button>
            
            <button 
              @click="toggleAttribute('multiAutograph')"
              :class="[
                'flex items-center justify-center px-4 py-2 rounded-md border text-sm font-medium transition-colors',
                localFilters.multiAutograph ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'
              ]"
            >
              <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
              </svg>
              Multi Autograph
            </button>
          </div>
        </div>

      </div>
    </div>

    <!-- Preview Section -->
    <div class="space-y-6">
      <div>
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Card Preview</h3>
        
        <!-- Main Image and Thumbnails -->
        <div class="flex gap-4 mb-6">
          <!-- Main Image -->
          <div class="flex-1">
            <div 
              class="aspect-[3/4] bg-gray-100 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300 cursor-pointer hover:border-gray-400 transition-colors relative"
              @click="triggerMainImageUpload"
            >
              <img v-if="selectedImage" :src="selectedImage" alt="Card preview" class="max-w-full max-h-full object-cover rounded-lg" />
              <svg v-else class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
              </svg>
              
              <!-- Edit icon for main image -->
              <div v-if="selectedImage" class="absolute top-2 right-2">
                <button 
                  @click.stop="triggerMainImageUpload"
                  class="w-8 h-8 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full flex items-center justify-center text-white transition-all"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
              </div>
              
              <input 
                data-ref="mainImageInput"
                type="file" 
                accept="image/*" 
                class="hidden"
                @change="handleMainImageUpload"
              />
            </div>
          </div>
          
          <!-- Thumbnails Column - Fixed height with proper spacing -->
          <div class="w-20 space-y-2.5">
            <div 
              v-for="(image, index) in images" 
              :key="index"
              class="relative aspect-[3/4] max-h-24 border-2 rounded-lg cursor-pointer hover:border-gray-400 transition-colors"
              :class="selectedImageIndex === index ? 'border-primary' : 'border-gray-300'"
              @click="selectImage(index)"
            >
              <div v-if="image" class="w-full h-full bg-gray-100 rounded-lg flex items-center justify-center relative">
                <img :src="image" :alt="`Card image ${index + 1}`" class="max-w-full max-h-full object-cover rounded-lg" />
                
                <!-- Edit icon for thumbnail -->
                <div class="absolute top-1 right-1">
                  <button 
                    @click.stop="triggerFileInput(index)"
                    class="w-5 h-5 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full flex items-center justify-center text-white transition-all"
                  >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                  </button>
                </div>
              </div>
              <div v-else class="w-full h-full bg-gray-100 rounded-lg flex items-center justify-center" @click="triggerFileInput(index)">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
              </div>
              <input 
                :data-ref="`fileInput${index}`"
                type="file" 
                accept="image/*" 
                class="hidden"
                @change="handleFileUpload($event, index)"
              />
            </div>
          </div>
        </div>

        <!-- Price Section -->
        <div class="mt-6">
          <label class="block text-sm font-medium text-gray-700 mb-2">Price*</label>
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
            />
          </div>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'

// Props
const props = defineProps({
  category: {
    type: String,
    default: 'football'
  },
  initialFilters: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['filters-changed', 'card-selected'])

// State
const localFilters = ref({
  playerSearch: '',
  setSearch: '',
  teamSearch: '',
  brand: '',
  year: '',
  rarity: '',
  numberedMin: '',
  numberedMax: '',
  autograph: false,
  relic: false,
  rookie: false,
  multiAutograph: false,
  gradingCompany: '',
  gradingScore: '',
  condition: '',
  price: '',
  ...props.initialFilters
})

// Forza multiAutograph a false per evitare che sia spuntato di default
localFilters.value.multiAutograph = false

// Debug per verificare i valori iniziali
console.log('Initial filters:', props.initialFilters)
console.log('Local filters multiAutograph:', localFilters.value.multiAutograph)

const selectedPlayer = ref(null)
const selectedTeam = ref(null)
const selectedCardSet = ref(null)
const filteredPlayers = ref([])
const filteredTeams = ref([])
const filteredCardSets = ref([])
const images = ref([null, null, null, null])
const selectedImageIndex = ref(-1)

// Available options
const availableBrands = ref([])
const availableYears = ref([])
const availableRarities = ref([])
const availableGradingCompanies = ref([])
const availableGradingScores = ref([])
const availableConditions = ref([])

// Computed
const selectedImage = computed(() => {
  if (selectedImageIndex.value >= 0 && images.value[selectedImageIndex.value]) {
    return images.value[selectedImageIndex.value]
  }
  return images.value.find(img => img !== null) || null
})

// Methods
const searchPlayers = async () => {
  if (localFilters.value.playerSearch.length < 2) {
    filteredPlayers.value = []
    return
  }
  
  try {
    const response = await fetch(`/api/${props.category}/filters/players/search?q=${encodeURIComponent(localFilters.value.playerSearch)}`)
    const data = await response.json()
    filteredPlayers.value = data.players || []
  } catch (error) {
    console.error('Errore nella ricerca giocatori:', error)
    filteredPlayers.value = []
  }
}

const searchTeams = async () => {
  if (localFilters.value.teamSearch.length < 2) {
    filteredTeams.value = []
    return
  }
  
  try {
    const response = await fetch(`/api/${props.category}/filters/teams/search?q=${encodeURIComponent(localFilters.value.teamSearch)}`)
    const data = await response.json()
    filteredTeams.value = data.teams || []
  } catch (error) {
    console.error('Errore nella ricerca squadre:', error)
    filteredTeams.value = []
  }
}

const searchCardSets = async () => {
  if (localFilters.value.setSearch.length < 2) {
    filteredCardSets.value = []
    return
  }
  
  try {
    const response = await fetch(`/api/${props.category}/filters/card-sets/search?q=${encodeURIComponent(localFilters.value.setSearch)}`)
    const data = await response.json()
    filteredCardSets.value = data.card_sets || []
  } catch (error) {
    console.error('Errore nella ricerca set:', error)
    filteredCardSets.value = []
  }
}

const selectPlayer = (player) => {
  selectedPlayer.value = player
  localFilters.value.playerSearch = ''
  filteredPlayers.value = []
  emit('filters-changed', localFilters.value)
}

const removePlayer = () => {
  selectedPlayer.value = null
  emit('filters-changed', localFilters.value)
}

const selectTeam = (team) => {
  selectedTeam.value = team
  localFilters.value.teamSearch = ''
  filteredTeams.value = []
  emit('filters-changed', localFilters.value)
}

const removeTeam = () => {
  selectedTeam.value = null
  emit('filters-changed', localFilters.value)
}

const selectCardSet = (set) => {
  selectedCardSet.value = set
  localFilters.value.setSearch = ''
  filteredCardSets.value = []
  emit('filters-changed', localFilters.value)
}

const removeCardSet = () => {
  selectedCardSet.value = null
  emit('filters-changed', localFilters.value)
}

const toggleAttribute = (attribute) => {
  localFilters.value[attribute] = !localFilters.value[attribute]
  emit('filters-changed', localFilters.value)
}

const triggerFileInput = (index) => {
  const input = document.querySelector(`input[data-ref="fileInput${index}"]`)
  if (input) input.click()
}

const handleFileUpload = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    const reader = new FileReader()
    reader.onload = (e) => {
      images.value[index] = e.target.result
      // Se è la prima immagine caricata, selezionala automaticamente
      if (selectedImageIndex.value === -1) {
        selectedImageIndex.value = index
      }
    }
    reader.readAsDataURL(file)
  }
}

const selectImage = (index) => {
  if (images.value[index]) {
    selectedImageIndex.value = index
  }
}

const triggerMainImageUpload = () => {
  const input = document.querySelector('input[data-ref="mainImageInput"]')
  if (input) input.click()
}

const handleMainImageUpload = (event) => {
  // Trova il primo slot vuoto o usa il primo slot
  let targetIndex = 0
  for (let i = 0; i < images.value.length; i++) {
    if (!images.value[i]) {
      targetIndex = i
      break
    }
  }
  
  // Se tutti gli slot sono pieni, usa il primo
  if (images.value[targetIndex]) {
    targetIndex = 0
  }
  
  handleFileUpload(event, targetIndex)
}

const confirmSelection = () => {
  const cardData = {
    player: selectedPlayer.value,
    team: selectedTeam.value,
    set: selectedCardSet.value,
    filters: localFilters.value,
    images: images.value.filter(img => img !== null)
  }
  emit('card-selected', cardData)
}

const loadFilterData = async () => {
  try {
    const response = await fetch(`/api/${props.category}/filters/chained`)
    const data = await response.json()
    
    availableBrands.value = data.brands || []
    availableYears.value = data.years || []
    availableRarities.value = data.rarities || []
    availableGradingCompanies.value = data.grading_companies || []
    availableGradingScores.value = data.grading_scores || []
    availableConditions.value = data.conditions || []
  } catch (error) {
    console.error('Errore nel caricamento dati filtri:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadFilterData()
})
</script>
