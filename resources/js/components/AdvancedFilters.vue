<template>
  <div class="space-y-6">
    <!-- Main Filters -->
    <div class="space-y-6">
      <!-- Player Filter -->
      <div class="space-y-2">
        <div class="relative">
          <input 
            v-model="localFilters.playerSearch"
            type="text" 
            placeholder="Cerca giocatore..."
            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
            @input="searchPlayers"
          />
          <div v-if="filteredPlayers.length > 0 && localFilters.playerSearch" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
            <div v-for="player in filteredPlayers" :key="player.id" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" @click="selectPlayer(player)">
              <span class="font-normal block truncate">{{ player.name }}</span>
            </div>
          </div>
        </div>
        <div v-if="selectedPlayers.length > 0" class="flex flex-wrap gap-2 mt-2">
          <span v-for="player in selectedPlayers" :key="player.id" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary text-white">
            {{ player.name }}
            <button type="button" @click="removePlayer(player)" class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-primary-dark">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </span>
        </div>
      </div>

      <!-- Team Filter -->
      <div class="space-y-2">
        <div class="relative">
          <input 
            v-model="localFilters.teamSearch"
            type="text" 
            placeholder="Cerca squadra..."
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

      <!-- Set Filter -->
      <div class="space-y-2">
        <div class="relative">
          <input 
            v-model="localFilters.setSearch"
            type="text" 
            placeholder="Cerca set..."
            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
            @input="searchCardSets"
          />
          <div v-if="filteredCardSets.length > 0 && localFilters.setSearch" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none">
            <div v-for="set in filteredCardSets" :key="set.id" class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" @click="selectCardSet(set)">
              <span class="font-normal block truncate">{{ set.name }} ({{ set.year }})</span>
            </div>
          </div>
        </div>
        <div v-if="selectedCardSet" class="flex flex-wrap gap-2 mt-2">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary text-white">
            {{ selectedCardSet.name }} ({{ selectedCardSet.year }})
            <button type="button" @click="removeCardSet" class="ml-1 inline-flex items-center justify-center w-4 h-4 rounded-full hover:bg-primary-dark">
              <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
              </svg>
            </button>
          </span>
        </div>
      </div>

      <!-- Rarity Filter -->
      <div class="space-y-2">
        <select 
          v-model="localFilters.rarity"
          class="col-start-1 row-start-1 w-full appearance-none rounded-md border border-gray-300 bg-white py-2 pr-8 pl-3 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6"
        >
          <option value="">Scegli una rarità</option>
          <option v-for="rarity in availableRarities" :key="rarity" :value="rarity">{{ rarity }}</option>
        </select>
      </div>

      <!-- Year Filter -->
      <div class="space-y-2">
        <select 
          v-model="localFilters.year"
          class="col-start-1 row-start-1 w-full appearance-none rounded-md border border-gray-300 bg-white py-2 pr-8 pl-3 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6"
        >
          <option value="">Scegli un anno</option>
          <option v-for="year in databaseYears" :key="year" :value="year">{{ year }}</option>
        </select>
      </div>

      <!-- Brand Filter -->
      <div class="space-y-2">
        <select 
          v-model="localFilters.brand"
          class="col-start-1 row-start-1 w-full appearance-none rounded-md border border-gray-300 bg-white py-2 pr-8 pl-3 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6"
        >
          <option value="">Scegli un brand</option>
          <option v-for="brand in databaseBrands" :key="brand" :value="brand">{{ brand }}</option>
        </select>
      </div>

      <!-- Numbered Filter -->
      <div class="space-y-2">
        <div class="space-y-3">
          <div class="flex items-center space-x-4">
            <input 
              v-model.number="localFilters.numberedMin"
              type="number" 
              min="1" 
              placeholder="Min"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
            />
            <span class="text-gray-500">-</span>
            <input 
              v-model.number="localFilters.numberedMax"
              type="number" 
              min="1" 
              placeholder="Max"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
            />
          </div>
          <div class="flex flex-wrap gap-2">
            <button 
              v-for="preset in numberedPresets" 
              :key="preset.label"
              type="button"
              @click="setNumberedRange(preset.min, preset.max)"
              class="px-3 py-1 text-xs rounded-full bg-gray-100 hover:bg-gray-200 transition-colors"
            >
              {{ preset.label }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Extra Filters (Collapsible) -->
    <Disclosure as="div" class="border-t border-gray-200 py-6" v-slot="{ open }">
      <h3 class="-my-3 flow-root">
        <DisclosureButton class="flex w-full items-center justify-between bg-white py-3 text-sm text-gray-400 hover:text-gray-500">
          <span class="font-medium text-gray-900">Filtri Extra</span>
          <span class="ml-6 flex items-center">
            <PlusIcon v-if="!open" class="size-5" aria-hidden="true" />
            <MinusIcon v-else class="size-5" aria-hidden="true" />
          </span>
        </DisclosureButton>
      </h3>
      <DisclosurePanel class="pt-6">
        <div class="space-y-4">
          <!-- Autograph -->
          <div class="space-y-2">
            <select v-model="localFilters.autograph" class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-2 pr-8 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-primary sm:text-sm/6">
              <option value="">Autograph</option>
              <option value="yes">Sì</option>
              <option value="no">No</option>
            </select>
          </div>

          <!-- Relic -->
          <div class="space-y-2">
            <select v-model="localFilters.relic" class="col-start-1 row-start-1 w-full appearance-none rounded-md border border-gray-300 bg-white py-2 pr-8 pl-3 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6">
              <option value="">Relic</option>
              <option value="yes">Sì</option>
              <option value="no">No</option>
            </select>
          </div>

          <!-- On Card Auto -->
          <div class="space-y-2">
            <select v-model="localFilters.onCardAuto" class="col-start-1 row-start-1 w-full appearance-none rounded-md border border-gray-300 bg-white py-2 pr-8 pl-3 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6">
              <option value="">On Card Auto</option>
              <option value="yes">Sì</option>
              <option value="no">No</option>
            </select>
          </div>

          <!-- Jewel -->
          <div class="space-y-2">
            <select v-model="localFilters.jewel" class="col-start-1 row-start-1 w-full appearance-none rounded-md border border-gray-300 bg-white py-2 pr-8 pl-3 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6">
              <option value="">Jewel</option>
              <option value="yes">Sì</option>
              <option value="no">No</option>
            </select>
          </div>

          <!-- Rookie -->
          <div class="space-y-2">
            <select v-model="localFilters.rookie" class="col-start-1 row-start-1 w-full appearance-none rounded-md border border-gray-300 bg-white py-2 pr-8 pl-3 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6">
              <option value="">Rookie (RC)</option>
              <option value="yes">Sì</option>
              <option value="no">No</option>
            </select>
          </div>

          <!-- Multi Player -->
          <div class="space-y-2">
            <div class="flex flex-wrap gap-2">
              <label v-for="option in multiPlayerOptions" :key="option.value" class="flex items-center">
                <input 
                  v-model="localFilters.multiPlayer"
                  type="checkbox" 
                  :value="option.value"
                  class="rounded border-gray-300 text-primary focus:ring-primary"
                />
                <span class="ml-2 text-sm text-gray-600">{{ option.label }}</span>
              </label>
            </div>
          </div>

          <!-- Multi Autograph -->
          <div class="space-y-2">
            <div class="flex flex-wrap gap-2">
              <label v-for="option in multiAutographOptions" :key="option.value" class="flex items-center">
                <input 
                  v-model="localFilters.multiAutograph"
                  type="checkbox" 
                  :value="option.value"
                  class="rounded border-gray-300 text-primary focus:ring-primary"
                />
                <span class="ml-2 text-sm text-gray-600">{{ option.label }}</span>
              </label>
            </div>
          </div>
        </div>
      </DisclosurePanel>
    </Disclosure>

    <!-- Grading Filters (Collapsible) -->
    <Disclosure as="div" class="border-t border-gray-200 py-6" v-slot="{ open }">
      <h3 class="-my-3 flow-root">
        <DisclosureButton class="flex w-full items-center justify-between bg-white py-3 text-sm text-gray-400 hover:text-gray-500">
          <span class="font-medium text-gray-900">Grading</span>
          <span class="ml-6 flex items-center">
            <PlusIcon v-if="!open" class="size-5" aria-hidden="true" />
            <MinusIcon v-else class="size-5" aria-hidden="true" />
          </span>
        </DisclosureButton>
      </h3>
      <DisclosurePanel class="pt-6">
        <div class="space-y-4">
          <!-- Grading Yes/No -->
          <div v-if="gradingAvailable" class="space-y-2">
            <select v-model="localFilters.grading" class="col-start-1 row-start-1 w-full appearance-none rounded-md border border-gray-300 bg-white py-2 pr-8 pl-3 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6" @change="handleGradingChange">
              <option value="">Grading</option>
              <option value="yes">Sì</option>
              <option value="no">No</option>
            </select>
          </div>

          <!-- Vote Grading (only if grading = yes) -->
          <div v-if="localFilters.grading === 'yes' && gradingAvailable" class="space-y-3">
            <div class="space-y-2">
              <div class="flex items-center space-x-4">
                <input 
                  v-model.number="localFilters.gradingScoreMin"
                  type="number" 
                  min="1" 
                  max="10" 
                  step="0.5"
                  placeholder="Min"
                  class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
                />
                <span class="text-gray-500">-</span>
                <input 
                  v-model.number="localFilters.gradingScoreMax"
                  type="number" 
                  min="1" 
                  max="10" 
                  step="0.5"
                  placeholder="Max"
                  class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
                />
              </div>
              <div class="text-xs text-gray-500">
                Range: 1.0 - 10.0 (incrementi di 0.5)
              </div>
            </div>

            <!-- Grading Company -->
            <div class="space-y-2">
              <div class="space-y-2">
                <label v-for="company in gradingCompanies" :key="company.id" class="flex items-center">
                  <input 
                    v-model="localFilters.gradingCompanies"
                    type="checkbox" 
                    :value="company.id"
                    class="rounded border-gray-300 text-primary focus:ring-primary"
                  />
                  <span class="ml-2 text-sm text-gray-600">{{ company.name }}</span>
                </label>
              </div>
            </div>
          </div>

          <!-- Condition (only if grading = no) -->
          <div v-if="localFilters.grading === 'no' && conditionAvailable" class="space-y-2">
            <div class="space-y-2">
              <label v-for="condition in conditionOptions" :key="condition.value" class="flex items-center">
                <input 
                  v-model="localFilters.conditions"
                  type="checkbox" 
                  :value="condition.value"
                  class="rounded border-gray-300 text-primary focus:ring-primary"
                />
                <span class="ml-2 text-sm text-gray-600">{{ condition.label }}</span>
              </label>
            </div>
          </div>
        </div>
      </DisclosurePanel>
    </Disclosure>

    <!-- Apply Filters Button -->
    <div class="pt-6 border-t border-gray-200">
      <button 
        type="button"
        @click="applyFilters"
        class="w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-primary-dark transition-colors"
      >
        Applica Filtri
      </button>
      <button 
        type="button"
        @click="clearFilters"
        class="w-full mt-2 text-gray-600 py-2 px-4 rounded-md hover:text-gray-800 transition-colors"
      >
        Cancella Filtri
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { MinusIcon, PlusIcon } from '@heroicons/vue/20/solid'

// Props
const props = defineProps({
  filters: {
    type: Object,
    required: true
  },
  category: {
    type: String,
    default: 'football'
  },
  gradingCompanies: {
    type: Array,
    default: () => []
  },
  conditionOptions: {
    type: Array,
    default: () => []
  },
  numberedPresets: {
    type: Array,
    default: () => []
  },
  multiPlayerOptions: {
    type: Array,
    default: () => []
  },
  multiAutographOptions: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['update:filters', 'apply-filters', 'clear-filters', 'filter-changed'])

// Local reactive data
const localFilters = ref({ ...props.filters })
const filteredPlayers = ref([])
const filteredTeams = ref([])
const filteredCardSets = ref([])
const selectedPlayers = ref([])
const selectedTeam = ref(null)
const selectedCardSet = ref(null)
const availableRarities = ref([])
const databaseYears = ref([])
const databaseBrands = ref([])
const gradingAvailable = ref(true)
const conditionAvailable = ref(true)

// REMOVED: Watch for external filter changes - this was causing infinite recursion
// watch(() => props.filters, (newFilters) => {
//   localFilters.value = { ...newFilters }
// }, { deep: true })

// Watch for local filter changes and emit updates
watch(localFilters, (newFilters) => {
  emit('update:filters', newFilters)
  emit('filter-changed', newFilters)
}, { deep: true })

// Watch for specific filter changes to trigger chained filters
watch(() => localFilters.value.year, (newYear, oldYear) => {
  if (newYear !== oldYear) {
    // Reset brand e rarity quando cambia year
    localFilters.value.brand = ''
    localFilters.value.rarity = ''
    setTimeout(() => {
      loadChainedFilters()
    }, 100)
  }
})

watch(() => localFilters.value.brand, (newBrand, oldBrand) => {
  if (newBrand !== oldBrand) {
    // Reset rarity quando cambia brand
    localFilters.value.rarity = ''
    setTimeout(() => {
      loadChainedFilters()
    }, 100)
  }
})

watch(() => localFilters.value.rarity, () => {
  setTimeout(() => {
    loadChainedFilters()
  }, 100)
})

// Functions
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

const loadFilterData = async () => {
  try {
    // Carica dati iniziali (senza filtri)
    const response = await fetch(`/api/${props.category}/filters/chained`)
    const data = await response.json()
    
    // Aggiorna i dati disponibili
    databaseYears.value = data.years || []
    databaseBrands.value = data.brands || []
    availableRarities.value = data.rarities || []
    
    // Aggiorna disponibilità grading/condition
    updateGradingConditionAvailability(data)
  } catch (error) {
    console.error('Errore nel caricamento dati filtri:', error)
  }
}

const loadChainedFilters = async () => {
  try {
    // Prepara i filtri attuali per la chiamata API
    const currentFilters = {
      player_id: localFilters.value.selectedPlayers?.length > 0 ? localFilters.value.selectedPlayers[0] : null,
      team_id: localFilters.value.team || null,
      set_id: localFilters.value.set || null,
      year: localFilters.value.year || null,
      brand: localFilters.value.brand || null
    }

    // Rimuovi valori null/vuoti
    Object.keys(currentFilters).forEach(key => {
      if (!currentFilters[key]) {
        delete currentFilters[key]
      }
    })

    // Costruisci query string
    const queryString = new URLSearchParams(currentFilters).toString()
    const url = `/api/${props.category}/filters/chained${queryString ? '?' + queryString : ''}`
    
    const response = await fetch(url)
    const data = await response.json()
    
    // Aggiorna i filtri disponibili in base alle selezioni
    updateAvailableFilters(data)
    
  } catch (error) {
    console.error('Errore nel caricamento filtri a catena:', error)
  }
}

const updateAvailableFilters = (data) => {
  // Aggiorna teams se disponibili
  if (data.teams) {
    // Non aggiorniamo filteredTeams perché è per l'autocomplete
    // Ma potremmo usare questi dati per limitare l'autocomplete
    console.log('Teams disponibili per il giocatore selezionato:', data.teams)
  }
  
  // Aggiorna card sets se disponibili
  if (data.card_sets) {
    // Non aggiorniamo filteredCardSets perché è per l'autocomplete
    // Ma potremmo usare questi dati per limitare l'autocomplete
    console.log('Set disponibili per il giocatore selezionato:', data.card_sets)
  }
  
  // Aggiorna anni, brand, rarità - QUESTI DEVONO ESSERE AGGIORNATI
  if (data.years && Array.isArray(data.years)) {
    databaseYears.value = data.years
    console.log('Anni aggiornati:', data.years)
  }
  
  if (data.brands && Array.isArray(data.brands)) {
    databaseBrands.value = data.brands
    console.log('Brand aggiornati:', data.brands)
  }
  
  if (data.rarities && Array.isArray(data.rarities)) {
    availableRarities.value = data.rarities
    console.log('Rarità aggiornate:', data.rarities)
  }
  
  // Aggiorna range numerato
  if (data.numbered_range) {
    updateNumberedRange(data.numbered_range)
  }
  
  // Aggiorna disponibilità grading/condition
  updateGradingConditionAvailability(data)
}

const updateNumberedRange = (numberedData) => {
  // Aggiorna i preset numerati in base ai valori disponibili
  localFilters.value.numberedMin = numberedData.min
  localFilters.value.numberedMax = numberedData.max
  
  // Aggiorna i preset disponibili
  const newPresets = []
  if (numberedData.available_values) {
    const values = numberedData.available_values
    if (values.includes(1)) newPresets.push({ label: '1', min: 1, max: 1 })
    if (values.includes(10)) newPresets.push({ label: '10', min: 10, max: 10 })
    if (values.includes(25)) newPresets.push({ label: '25', min: 25, max: 25 })
    if (values.includes(50)) newPresets.push({ label: '50', min: 50, max: 50 })
    if (values.includes(100)) newPresets.push({ label: '100', min: 100, max: 100 })
  }
  
  // Aggiorna i preset se ci sono nuovi valori
  if (newPresets.length > 0) {
    // I preset sono passati come prop, quindi non possiamo modificarli direttamente
    // Ma possiamo usare i valori per aggiornare i range
  }
}

const updateGradingConditionAvailability = (data) => {
  // Aggiorna la disponibilità dei filtri grading/condition
  gradingAvailable.value = data.grading_available || false
  conditionAvailable.value = data.condition_available || false
  
  // Se non ci sono carte con grading, resetta il filtro grading
  if (!data.grading_available && localFilters.value.grading === 'yes') {
    localFilters.value.grading = ''
    localFilters.value.gradingScoreMin = null
    localFilters.value.gradingScoreMax = null
    localFilters.value.gradingCompanies = []
  }
  
  // Se non ci sono carte senza grading, resetta il filtro condition
  if (!data.condition_available) {
    localFilters.value.conditions = []
  }
}

const selectPlayer = (player) => {
  if (!selectedPlayers.value.find(p => p.id === player.id)) {
    selectedPlayers.value.push(player)
    // Aggiorna anche i filtri con i giocatori selezionati
    localFilters.value.selectedPlayers = selectedPlayers.value.map(p => p.id)
    
    // Reset filtri dipendenti quando si seleziona un nuovo giocatore
    resetDependentFilters()
    
    // Trigger filtri a catena
    setTimeout(() => {
      loadChainedFilters()
    }, 100)
  }
  localFilters.value.playerSearch = ''
  filteredPlayers.value = []
}

const resetDependentFilters = () => {
  // Reset team
  selectedTeam.value = null
  localFilters.value.team = ''
  localFilters.value.teamSearch = ''
  filteredTeams.value = []
  
  // Reset set
  selectedCardSet.value = null
  localFilters.value.set = ''
  localFilters.value.setSearch = ''
  filteredCardSets.value = []
  
  // Reset year, brand, rarity
  localFilters.value.year = ''
  localFilters.value.brand = ''
  localFilters.value.rarity = ''
}

const removePlayer = (player) => {
  selectedPlayers.value = selectedPlayers.value.filter(p => p.id !== player.id)
  // Aggiorna anche i filtri
  localFilters.value.selectedPlayers = selectedPlayers.value.map(p => p.id)
  
  // Se non ci sono più giocatori selezionati, resetta tutti i filtri dipendenti
  if (selectedPlayers.value.length === 0) {
    resetDependentFilters()
    // Ricarica dati iniziali
    loadFilterData()
  } else {
    // Trigger filtri a catena
    setTimeout(() => {
      loadChainedFilters()
    }, 100)
  }
}

const selectTeam = (team) => {
  selectedTeam.value = team
  localFilters.value.team = team.id
  localFilters.value.teamSearch = ''
  filteredTeams.value = []
  
  // Reset filtri dipendenti (set, year, brand, rarity)
  selectedCardSet.value = null
  localFilters.value.set = ''
  localFilters.value.setSearch = ''
  filteredCardSets.value = []
  localFilters.value.year = ''
  localFilters.value.brand = ''
  localFilters.value.rarity = ''
  
  // Trigger filtri a catena
  setTimeout(() => {
    loadChainedFilters()
  }, 100)
}

const removeTeam = () => {
  selectedTeam.value = null
  localFilters.value.team = ''
  
  // Reset filtri dipendenti
  selectedCardSet.value = null
  localFilters.value.set = ''
  localFilters.value.setSearch = ''
  filteredCardSets.value = []
  localFilters.value.year = ''
  localFilters.value.brand = ''
  localFilters.value.rarity = ''
  
  // Trigger filtri a catena
  setTimeout(() => {
    loadChainedFilters()
  }, 100)
}

const selectCardSet = (set) => {
  selectedCardSet.value = set
  localFilters.value.set = set.id
  localFilters.value.setSearch = ''
  filteredCardSets.value = []
  
  // Reset filtri dipendenti (year, brand, rarity)
  localFilters.value.year = ''
  localFilters.value.brand = ''
  localFilters.value.rarity = ''
  
  // Trigger filtri a catena
  setTimeout(() => {
    loadChainedFilters()
  }, 100)
}

const removeCardSet = () => {
  selectedCardSet.value = null
  localFilters.value.set = ''
  
  // Reset filtri dipendenti
  localFilters.value.year = ''
  localFilters.value.brand = ''
  localFilters.value.rarity = ''
  
  // Trigger filtri a catena
  setTimeout(() => {
    loadChainedFilters()
  }, 100)
}

const setNumberedRange = (min, max) => {
  localFilters.value.numberedMin = min
  localFilters.value.numberedMax = max
}

const handleGradingChange = () => {
  // Reset grading-specific filters when grading changes
  if (localFilters.value.grading !== 'yes') {
    localFilters.value.gradingScoreMin = null
    localFilters.value.gradingScoreMax = null
    localFilters.value.gradingCompanies = []
  }
  if (localFilters.value.grading !== 'no') {
    localFilters.value.conditions = []
  }
}

const applyFilters = () => {
  emit('apply-filters')
}

const clearFilters = () => {
  // Reset all filters
  localFilters.value = {
    playerSearch: '',
    selectedPlayers: [],
    teamSearch: '',
    team: '',
    setSearch: '',
    set: '',
    rarity: '',
    year: '',
    brand: '',
    numberedMin: null,
    numberedMax: null,
    autograph: '',
    relic: '',
    onCardAuto: '',
    jewel: '',
    rookie: '',
    multiPlayer: [],
    multiAutograph: [],
    grading: '',
    gradingScoreMin: null,
    gradingScoreMax: null,
    gradingCompanies: [],
    conditions: []
  }
  selectedPlayers.value = []
  selectedTeam.value = null
  selectedCardSet.value = null
  filteredPlayers.value = []
  filteredTeams.value = []
  filteredCardSets.value = []
  emit('clear-filters')
}

// Carica i dati dei filtri al mount del componente
onMounted(() => {
  loadFilterData()
})
</script>
