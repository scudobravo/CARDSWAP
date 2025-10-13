<template>
  <div class="space-y-6">
    <!-- Intestazione -->
    <div class="text-center">
      <h3 class="text-lg font-medium text-gray-900 mb-2">Preview e Dettagli</h3>
      <p class="text-sm text-gray-600">
        Imposta valori comuni per {{ selectedCards.length }} carte selezionate
      </p>
    </div>

    <!-- Layout a 2 colonne (stesso stile di Single) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Left Side: Upload Immagini Bulk -->
      <div class="space-y-4">
        <h5 class="text-lg font-semibold text-gray-900">Carica Immagini</h5>
        
        <!-- Istruzioni -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800">Istruzioni</h3>
              <div class="mt-2 text-sm text-blue-700">
                <ul class="list-disc list-inside space-y-1">
                  <li>Seleziona tutte le immagini delle carte</li>
                  <li>Le immagini verranno associate alle carte nell'ordine</li>
                  <li>Formati: PNG, JPG (max 10MB)</li>
                  <li>Massimo {{ cardsCount }} immagini</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Drag & Drop Upload Area -->
        <div 
          ref="bulkDropZone"
          class="relative border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-primary transition-colors cursor-pointer"
          :class="{ 'border-primary bg-primary/5': isDragOver }"
          @dragover.prevent="handleDragOver"
          @dragleave.prevent="handleDragLeave"
          @drop.prevent="handleBulkDrop"
          @click="() => $refs.bulkImageInput.click()"
        >
          <input
            ref="bulkImageInput"
            type="file"
            accept="image/*"
            multiple
            @change="handleBulkImageUpload"
            class="hidden"
          />
          
          <div class="space-y-4">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
              <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            
            <div>
              <p class="text-lg font-medium text-gray-900">
                Trascina le immagini qui o clicca per selezionare
              </p>
              <p class="text-sm text-gray-500 mt-1">
                Carica fino a {{ cardsCount }} immagini (PNG, JPG - max 10MB)
              </p>
            </div>
            
            <div v-if="bulkImages.length > 0" class="mt-4">
              <p class="text-sm text-primary font-medium">
                {{ bulkImages.length }} immagini caricate
              </p>
            </div>
          </div>
        </div>

        <!-- Image Preview Grid -->
        <div v-if="bulkImages.length > 0" class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div v-for="(image, index) in bulkImages" :key="index" class="relative">
            <img :src="image.preview" :alt="`Card ${index + 1}`" class="w-full h-24 object-cover rounded-lg border border-gray-200">
            <div class="absolute top-1 right-1 bg-white rounded-full p-1">
              <span class="text-xs font-medium text-gray-600">{{ index + 1 }}</span>
            </div>
            <button 
              @click.stop="removeBulkImage(index)" 
              class="absolute top-1 left-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600"
            >
              ×
            </button>
          </div>
        </div>

        <!-- Carte Selezionate (sotto le immagini) -->
        <div class="mt-6">
          <h5 class="text-md font-semibold text-gray-900 mb-3">Carte Selezionate ({{ cardsCount }})</h5>
          <div class="max-h-[300px] overflow-y-auto space-y-2">
            <div 
              v-for="card in selectedCards" 
              :key="card.id"
              class="border rounded-lg p-3 bg-white shadow-sm"
            >
              <h6 class="font-semibold text-gray-900 text-sm">{{ card.name }}</h6>
              <p class="text-xs text-gray-600">{{ card.card_set?.name }} {{ card.year }}</p>
              <p class="text-xs text-gray-500">{{ card.rarity }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Side: Dettagli Aggiuntivi (stesso stile di Single) -->
      <div class="space-y-6">
        <div class="bg-gray-50 rounded-lg p-6">
          <h4 class="text-md font-medium text-gray-900 mb-4">Dettagli Aggiuntivi</h4>
          <div class="space-y-4">
            <!-- Prezzo e Quantità -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Prezzo (€) *</label>
                <input 
                  v-model="bulkData.price"
                  type="number" 
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm"
                />
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Quantità *</label>
                <input 
                  v-model="bulkData.quantity"
                  type="number" 
                  min="1"
                  placeholder="1"
                  class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 placeholder:text-gray-400 focus:border-primary focus:outline-none sm:text-sm"
                />
              </div>
            </div>

            <!-- Condizione -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Condizione *</label>
              <select 
                v-model="bulkData.condition"
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
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
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
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

            <!-- Grading Company -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Grading Company</label>
              <select 
                v-model="bulkData.grading_company"
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              >
                <option value="">Seleziona grading company</option>
                <option v-for="company in availableGradingCompanies" :key="company.id" :value="company.id">{{ company.name }}</option>
              </select>
            </div>

            <!-- Grading Score -->
            <div v-if="bulkData.grading_company">
              <label class="block text-sm font-medium text-gray-700 mb-2">Grading Score</label>
              <input
                v-model="bulkData.grading_score"
                type="text"
                placeholder="Es. 10, 9.5, 9"
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              />
            </div>

            <!-- Separatore Caratteristiche Speciali -->
            <div class="border-t border-gray-200 pt-4 mt-4">
              <h5 class="text-sm font-semibold text-gray-900 mb-3">Caratteristiche Speciali</h5>
            </div>

            <!-- Autograph -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Autograph</label>
              <select 
                v-model="bulkData.autograph"
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              >
                <option value="">Non specificato</option>
                <option value="yes">Sì</option>
                <option value="no">No</option>
              </select>
            </div>

            <!-- Relic -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Relic</label>
              <select 
                v-model="bulkData.relic"
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              >
                <option value="">Non specificato</option>
                <option value="yes">Sì</option>
                <option value="no">No</option>
              </select>
            </div>

            <!-- On Card Auto -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">On Card Auto</label>
              <select 
                v-model="bulkData.onCardAuto"
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              >
                <option value="">Non specificato</option>
                <option value="yes">Sì</option>
                <option value="no">No</option>
              </select>
            </div>

            <!-- Rookie -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Rookie</label>
              <select 
                v-model="bulkData.rookie"
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              >
                <option value="">Non specificato</option>
                <option value="yes">Sì</option>
                <option value="no">No</option>
              </select>
            </div>

            <!-- Jewel -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Jewel</label>
              <select 
                v-model="bulkData.jewel"
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              >
                <option value="">Non specificato</option>
                <option value="yes">Sì</option>
                <option value="no">No</option>
              </select>
            </div>

            <!-- Multi-Autograph -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Multi-Autograph</label>
              <select 
                v-model="bulkData.multiAutograph"
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              >
                <option value="">Non specificato</option>
                <option value="dual">Dual</option>
                <option value="triple">Triple</option>
                <option value="quad">Quad</option>
                <option value="booklet">Booklet</option>
              </select>
            </div>

            <!-- Description -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
              <textarea 
                v-model="bulkData.description"
                rows="4"
                placeholder="Descrizione comune per tutte le carte..."
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              ></textarea>
            </div>

            <!-- Notes -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">Note</label>
              <textarea 
                v-model="bulkData.notes"
                rows="3"
                placeholder="Note aggiuntive..."
                class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              ></textarea>
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
const emit = defineEmits(['go-back', 'apply-bulk-edit', 'next-step', 'bulk-images-uploaded'])

// Refs
const bulkImageInput = ref(null)
const bulkDropZone = ref(null)

// State
const bulkImages = ref([])
const isDragOver = ref(false)

const bulkData = ref({
  price: '1.00',
  quantity: 1,
  condition: 'mint',
  language: 'italian',
  // Grading
  grading_company: '',
  grading_score: '',
  // Filtri Extra
  autograph: '',
  relic: '',
  onCardAuto: '',
  jewel: '',
  rookie: '',
  multiAutograph: '',
  // Caratteristiche booleane
  is_foil: false,
  is_signed: false,
  is_altered: false,
  is_first_edition: false,
  is_negotiable: false,
  description: '',
  notes: ''
})

const availableGradingCompanies = ref([])
const availableGradingScores = ref([])

// Computed
const cardsCount = computed(() => {
  return props.selectedCards?.length || 0
})

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
  
  // Emetti sia i listings che le immagini
  emit('apply-bulk-edit', listings)
  emit('bulk-images-uploaded', bulkImages.value)
  emit('next-step')
}

// Gestione immagini bulk
const handleBulkImageUpload = (event) => {
  const files = Array.from(event.target.files)
  processBulkImages(files)
}

const handleDragOver = (event) => {
  event.preventDefault()
  isDragOver.value = true
}

const handleDragLeave = (event) => {
  event.preventDefault()
  isDragOver.value = false
}

const handleBulkDrop = (event) => {
  event.preventDefault()
  isDragOver.value = false
  
  const files = Array.from(event.dataTransfer.files)
  if (files.length > 0) {
    processBulkImages(files)
  }
}

const processBulkImages = (files) => {
  console.log('📸 Processing bulk images:', files)
  console.log('📸 Selected cards:', props.selectedCards)
  console.log('📸 Selected cards length:', props.selectedCards?.length)
  
  // Verifica che ci siano carte selezionate
  if (!props.selectedCards || props.selectedCards.length === 0) {
    alert('Errore: Nessuna carta selezionata. Torna allo step precedente e seleziona le carte.')
    return
  }
  
  // Validate files
  const validFiles = files.filter(file => {
    if (file.size > 10 * 1024 * 1024) {
      alert(`File ${file.name} è troppo grande. Dimensione massima: 10MB`)
      return false
    }
    if (!file.type.startsWith('image/')) {
      alert(`File ${file.name} non è un'immagine valida`)
      return false
    }
    return true
  })

  // Limit to selected cards count
  const maxFiles = props.selectedCards.length
  if (validFiles.length > maxFiles) {
    alert(`Puoi caricare massimo ${maxFiles} immagini per ${maxFiles} carte selezionate`)
    validFiles.splice(maxFiles)
  }

  // Create previews
  bulkImages.value = validFiles.map((file, index) => ({
    file,
    preview: URL.createObjectURL(file),
    cardIndex: index
  }))
  
  console.log('✅ Bulk images created:', bulkImages.value)
}

const removeBulkImage = (index) => {
  if (bulkImages.value[index]) {
    URL.revokeObjectURL(bulkImages.value[index].preview)
  }
  bulkImages.value.splice(index, 1)
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
  console.log('🔧 BulkEditForm mounted')
  console.log('🔧 Selected cards on mount:', props.selectedCards)
  console.log('🔧 Selected cards length:', props.selectedCards?.length)
  loadGradingData()
})
</script>
