<template>
  <div class="space-y-6">
    <div class="text-center">
      <h4 class="text-xl font-semibold text-gray-900 mb-2">Bulk Edit</h4>
      <p class="text-gray-600">Imposta valori comuni per {{ selectedCards.length }} carte selezionate</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Prezzo -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Prezzo (€) *</label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <span class="text-gray-500 text-sm">€</span>
          </div>
          <input 
            v-model="bulkData.price"
            type="number" 
            step="0.01"
            min="0"
            placeholder="0.00"
            class="block w-full pl-8 pr-3 py-2 border border-gray-300 rounded-md text-sm text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none"
          />
        </div>
      </div>

      <!-- Quantità -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Quantità *</label>
        <input 
          v-model="bulkData.quantity"
          type="number" 
          min="1"
          placeholder="1"
          class="block w-full rounded-md border border-gray-300 px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
        />
      </div>

      <!-- Condizione -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Condizione *</label>
        <select 
          v-model="bulkData.condition"
          class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
        >
          <option value="">Seleziona condizione</option>
          <option value="mint">Mint</option>
          <option value="near_mint">Near Mint</option>
          <option value="excellent">Excellent</option>
          <option value="very_good">Very Good</option>
          <option value="good">Good</option>
          <option value="fair">Fair</option>
          <option value="light_played">Light Played</option>
          <option value="played">Played</option>
          <option value="poor">Poor</option>
        </select>
      </div>

      <!-- Lingua -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Lingua</label>
        <select 
          v-model="bulkData.language"
          class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
        >
          <option value="">Seleziona lingua</option>
          <option value="italian">Italiano</option>
          <option value="english">Inglese</option>
          <option value="spanish">Spagnolo</option>
          <option value="french">Francese</option>
          <option value="german">Tedesco</option>
          <option value="portuguese">Portoghese</option>
        </select>
      </div>

      <!-- Number -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Number</label>
        <input 
          v-model="bulkData.number"
          type="number" 
          placeholder="Numero carta"
          class="block w-full rounded-md border border-gray-300 px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm/6"
        />
      </div>

      <!-- Grading Company -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Grading Company</label>
        <select 
          v-model="bulkData.grading_company"
          class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
        >
          <option value="">Seleziona grading company</option>
          <option v-for="company in availableGradingCompanies" :key="company.id" :value="company.id">{{ company.name }}</option>
        </select>
      </div>

      <!-- Grading Score -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Grading Score</label>
        <select 
          v-model="bulkData.grading_score"
          class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
        >
          <option value="">Seleziona grading score</option>
          <option v-for="score in availableGradingScores" :key="score.id" :value="score.id">{{ score.score }} - {{ score.description }}</option>
        </select>
      </div>
    </div>

    <!-- Caratteristiche Speciali -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-3">Caratteristiche Speciali</label>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <label class="flex items-center space-x-3 cursor-pointer">
          <input 
            v-model="bulkData.is_foil"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span class="text-sm font-medium text-gray-700">Foil</span>
        </label>
        
        <label class="flex items-center space-x-3 cursor-pointer">
          <input 
            v-model="bulkData.is_signed"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span class="text-sm font-medium text-gray-700">Firmata</span>
        </label>
        
        <label class="flex items-center space-x-3 cursor-pointer">
          <input 
            v-model="bulkData.is_altered"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span class="text-sm font-medium text-gray-700">Alterata</span>
        </label>
        
        <label class="flex items-center space-x-3 cursor-pointer">
          <input 
            v-model="bulkData.is_first_edition"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span class="text-sm font-medium text-gray-700">Prima Edizione</span>
        </label>
        
        <label class="flex items-center space-x-3 cursor-pointer">
          <input 
            v-model="bulkData.is_negotiable"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span class="text-sm font-medium text-gray-700">Prezzo Negoziabile</span>
        </label>
      </div>
    </div>

    <!-- Descrizione -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
      <textarea 
        v-model="bulkData.description"
        rows="3"
        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
        placeholder="Descrizione comune per tutte le carte..."
      ></textarea>
    </div>

    <!-- Anteprima Carte Selezionate -->
    <div>
      <h5 class="text-lg font-semibold text-gray-900 mb-4">Carte Selezionate</h5>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto">
        <div 
          v-for="card in selectedCards" 
          :key="card.id"
          class="border rounded-lg p-4 bg-white shadow-sm"
        >
          <div class="space-y-2">
            <h6 class="font-semibold text-gray-900">{{ card.name }}</h6>
            <p class="text-sm text-gray-600">{{ card.card_set?.name }} {{ card.year }}</p>
            <p class="text-sm text-gray-500">Rarità: {{ card.rarity }}</p>
            <div v-if="card.player" class="text-sm text-gray-500">
              <p><strong>Giocatore:</strong> {{ card.player.name }}</p>
              <p><strong>Squadra:</strong> {{ card.team?.name }}</p>
            </div>
            <div class="text-xs text-gray-400">
              ID: {{ card.id }}
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
      <button 
        @click="goBack"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
      >
        Indietro
      </button>
      <button 
        @click="applyBulkEdit"
        :disabled="!canApply"
        class="px-6 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Avanti
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'

// Props
const props = defineProps({
  selectedCards: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['go-back', 'apply-bulk-edit', 'next-step'])

// State
const bulkData = ref({
  price: '1.00',
  quantity: 1,
  condition: 'mint',
  language: 'italian',
  number: '',
  grading_company: '',
  grading_score: '',
  is_foil: false,
  is_signed: false,
  is_altered: false,
  is_first_edition: false,
  is_negotiable: false,
  description: ''
})

const availableGradingCompanies = ref([])
const availableGradingScores = ref([])

// Computed
const canApply = computed(() => {
  return bulkData.value.price && bulkData.value.quantity && bulkData.value.condition
})

// Methods
const goBack = () => {
  emit('go-back')
}

const applyBulkEdit = () => {
  console.log('🔍 BulkEditForm - selectedCards:', props.selectedCards)
  console.log('🔍 BulkEditForm - bulkData:', bulkData.value)
  
  const listings = props.selectedCards.map(card => ({
    card_model_id: card.id,
    ...bulkData.value
  }))
  
  console.log('🔍 BulkEditForm - listings create:', listings)
  emit('apply-bulk-edit', listings)
  emit('next-step')
}

const loadGradingData = async () => {
  try {
    // Carica le aziende di grading
    const companiesResponse = await fetch('/api/grading-companies')
    const companiesData = await companiesResponse.json()
    availableGradingCompanies.value = companiesData || []
    
    // Carica i punteggi di grading (usando il primo controller disponibile)
    const scoresResponse = await fetch('/api/football/filters/options')
    const scoresData = await scoresResponse.json()
    availableGradingScores.value = scoresData.grading_scores || []
  } catch (error) {
    console.error('Errore nel caricamento dati grading:', error)
    // Inizializza con array vuoti in caso di errore
    availableGradingCompanies.value = []
    availableGradingScores.value = []
  }
}

// Lifecycle
onMounted(() => {
  loadGradingData()
})
</script>