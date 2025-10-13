<template>
  <div class="space-y-6">
    <!-- Selezione Modello Carta -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Modello Carta</label>
      <div v-if="listing.cardModel" class="flex items-center space-x-3 p-3 border rounded-lg bg-gray-50">
        <img 
          :src="listing.cardModel.image_url || '/images/placeholder-card.jpg'" 
          :alt="listing.cardModel.name"
          class="w-12 h-16 object-cover rounded"
        />
        <div class="flex-1">
          <h6 class="font-semibold text-gray-900">{{ listing.cardModel.name }}</h6>
          <p class="text-sm text-gray-600">{{ listing.cardModel.set_name }} {{ listing.cardModel.year }}</p>
          <p class="text-sm text-gray-500">{{ listing.cardModel.rarity }}</p>
        </div>
        <button 
          @click="clearCardModel"
          class="text-red-500 hover:text-red-700"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <button 
        v-else
        @click="showCardModelSelector = true"
        class="w-full p-4 border-2 border-dashed border-gray-300 rounded-lg text-center hover:border-primary hover:bg-primary/5 transition-colors"
      >
        <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        <p class="text-sm text-gray-600">Seleziona Modello Carta</p>
      </button>
    </div>

    <!-- Dettagli Aggiuntivi (stesso stile di ImagePreviewStep) -->
    <div class="bg-gray-50 rounded-lg p-6">
      <h4 class="text-md font-medium text-gray-900 mb-4">Dettagli Aggiuntivi</h4>
      <div class="space-y-4">
        <!-- Prezzo e Quantità -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Prezzo (€)</label>
            <input 
              v-model="formData.price"
              type="number"
              step="0.01"
              min="0"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              placeholder="0.00"
            />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Quantità</label>
            <input 
              v-model="formData.quantity"
              type="number"
              min="1"
              class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
              placeholder="1"
            />
          </div>
        </div>
        
        <!-- Condition -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Condizione</label>
          <select 
            v-model="formData.condition"
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
            v-model="formData.language"
            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
          >
            <option value="">Seleziona lingua</option>
            <option value="italiano">Italiano</option>
            <option value="inglese">Inglese</option>
            <option value="spagnolo">Spagnolo</option>
            <option value="francese">Francese</option>
            <option value="tedesco">Tedesco</option>
            <option value="portoghese">Portoghese</option>
          </select>
        </div>

        <!-- Grading Company -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Grading Company</label>
          <select 
            v-model="formData.gradingCompany"
            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
          >
            <option value="">Seleziona grading company</option>
            <option v-for="company in gradingCompanies" :key="company.id" :value="company.id">
              {{ company.name }}
            </option>
          </select>
        </div>
        
        <!-- Grading Score -->
        <div v-if="formData.gradingCompany">
          <label class="block text-sm font-medium text-gray-700 mb-2">Grading Score</label>
          <input 
            v-model="formData.gradingScore"
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
            v-model="formData.autograph"
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
            v-model="formData.relic"
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
            v-model="formData.onCardAuto"
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
            v-model="formData.rookie"
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
            v-model="formData.jewel"
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
            v-model="formData.multiAutograph"
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
            v-model="formData.description"
            rows="4"
            placeholder="Descrizione della carta..."
            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
          ></textarea>
        </div>

        <!-- Notes -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Note</label>
          <textarea
            v-model="formData.notes"
            rows="3"
            placeholder="Note aggiuntive sulla carta..."
            class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
          ></textarea>
        </div>
      </div>
    </div>

    <!-- Caratteristiche Speciali Booleane (Foil, Firmata, etc.) -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-3">Caratteristiche Speciali</label>
      <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
        <label class="flex items-center space-x-3 cursor-pointer">
          <input 
            v-model="formData.is_foil"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span class="text-sm font-medium text-gray-700">Foil</span>
        </label>
        
        <label class="flex items-center space-x-3 cursor-pointer">
          <input 
            v-model="formData.is_signed"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span class="text-sm font-medium text-gray-700">Firmata</span>
        </label>
        
        <label class="flex items-center space-x-3 cursor-pointer">
          <input 
            v-model="formData.is_altered"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span class="text-sm font-medium text-gray-700">Alterata</span>
        </label>
        
        <label class="flex items-center space-x-3 cursor-pointer">
          <input 
            v-model="formData.is_first_edition"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span class="text-sm font-medium text-gray-700">Prima Edizione</span>
        </label>
        
        <label class="flex items-center space-x-3 cursor-pointer">
          <input 
            v-model="formData.is_negotiable"
            type="checkbox"
            class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
          />
          <span class="text-sm font-medium text-gray-700">Prezzo Negoziabile</span>
        </label>
      </div>
    </div>

    <!-- Upload Immagini -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Immagini</label>
      <div 
        ref="dropZone"
        class="border-2 border-dashed rounded-lg p-6 text-center transition-colors duration-200"
        :class="{
          'border-primary bg-primary/5': isDragOver,
          'border-gray-300 hover:border-gray-400': !isDragOver
        }"
        @drop="handleDrop"
        @dragover.prevent="handleDragOver"
        @dragenter.prevent="handleDragEnter"
        @dragleave="handleDragLeave"
      >
        <input 
          ref="imageInput"
          type="file"
          multiple
          accept="image/*"
          @change="handleImageUpload"
          class="hidden"
        />
        
        <!-- Icona e testo -->
        <div class="space-y-2">
          <svg 
            class="mx-auto h-8 w-8 text-gray-400" 
            :class="{ 'text-primary': isDragOver }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path 
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="2" 
              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" 
            />
          </svg>
          
          <div class="text-sm text-gray-600">
            <button 
              @click="$refs.imageInput.click()"
              class="text-primary hover:text-primary-dark font-medium"
            >
              Clicca per caricare
            </button>
            <span class="text-gray-500"> o trascina le immagini qui</span>
          </div>
          
          <p class="text-xs text-gray-500">
            PNG, JPG, JPEG fino a 1MB ciascuna (max 4 immagini)
          </p>
        </div>
      </div>
      
      <!-- Anteprima Immagini -->
      <div v-if="formData.images.length > 0" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div 
          v-for="(image, index) in formData.images" 
          :key="index"
          class="relative group"
        >
          <img 
            :src="image.preview || image" 
            :alt="`Immagine ${index + 1}`"
            class="w-full h-24 object-cover rounded"
          />
          <button 
            @click="removeImage(index)"
            class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
          >
            ×
          </button>
        </div>
      </div>
    </div>

    <!-- Descrizione -->
    <div>
      <label class="block text-sm font-medium text-gray-700 mb-2">Descrizione</label>
      <textarea 
        v-model="formData.description"
        rows="3"
        class="w-full rounded-md border border-gray-300 px-3 py-2 focus:border-primary focus:outline-none"
        placeholder="Descrivi la condizione, eventuali difetti, storia della carta..."
      ></textarea>
    </div>

    <!-- Azioni -->
    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
      <button 
        @click="$emit('cancel')"
        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
      >
        Annulla
      </button>
      <button 
        @click="save"
        class="px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
      >
        Salva
      </button>
    </div>

    <!-- Modal Selezione Modello Carta -->
    <div v-if="showCardModelSelector" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="showCardModelSelector = false"></div>
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-4xl transform overflow-hidden rounded-lg bg-white shadow-xl">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Seleziona Modello Carta</h3>
          </div>
          <div class="px-6 py-6">
            <CardModelSelector 
              @selected="selectCardModel"
              @close="showCardModelSelector = false"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import CardModelSelector from './CardModelSelector.vue'

// Props
const props = defineProps({
  listing: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['save', 'cancel'])

// State
const showCardModelSelector = ref(false)
const isDragOver = ref(false) // For drag & drop
const gradingCompanies = ref([])

const formData = ref({
  cardModel: props.listing.cardModel,
  price: props.listing.price || '',
  quantity: props.listing.quantity || 1,
  condition: props.listing.condition || '',
  language: props.listing.language || '',
  // Grading
  gradingCompany: props.listing.gradingCompany || '',
  gradingScore: props.listing.gradingScore || '',
  // Filtri Extra
  autograph: props.listing.autograph || '',
  relic: props.listing.relic || '',
  onCardAuto: props.listing.onCardAuto || '',
  rookie: props.listing.rookie || '',
  jewel: props.listing.jewel || '',
  multiAutograph: props.listing.multiAutograph || '',
  // Caratteristiche booleane
  is_foil: props.listing.is_foil || false,
  is_signed: props.listing.is_signed || false,
  is_altered: props.listing.is_altered || false,
  is_first_edition: props.listing.is_first_edition || false,
  is_negotiable: props.listing.is_negotiable || false,
  description: props.listing.description || '',
  notes: props.listing.notes || '',
  images: props.listing.images || []
})

// Methods
const selectCardModel = (cardModel) => {
  formData.value.cardModel = cardModel
  showCardModelSelector.value = false
}

const clearCardModel = () => {
  formData.value.cardModel = null
}

// Drag & Drop handlers
const handleDragOver = (event) => {
  event.preventDefault()
  isDragOver.value = true
}

const handleDragEnter = (event) => {
  event.preventDefault()
  isDragOver.value = true
}

const handleDragLeave = (event) => {
  if (!event.currentTarget.contains(event.relatedTarget)) {
    isDragOver.value = false
  }
}

const handleDrop = (event) => {
  event.preventDefault()
  isDragOver.value = false
  
  const files = Array.from(event.dataTransfer.files)
  processImageFiles(files)
}

const handleImageUpload = (event) => {
  const files = Array.from(event.target.files)
  processImageFiles(files)
}

// Processa i file immagine
const processImageFiles = (files) => {
  const maxFiles = 4
  const maxSize = 1 * 1024 * 1024 // 1MB
  
  if (formData.value.images.length + files.length > maxFiles) {
    alert(`Massimo ${maxFiles} immagini per inserzione. Hai già ${formData.value.images.length} immagini.`)
    return
  }
  
  files.forEach(file => {
    if (file.type.startsWith('image/')) {
      if (file.size > maxSize) {
        alert(`L'immagine "${file.name}" è troppo grande. Dimensione massima: 1MB`)
        return
      }
      
      if (formData.value.images.length >= maxFiles) {
        alert(`Massimo ${maxFiles} immagini per inserzione`)
        return
      }
      
      const reader = new FileReader()
      reader.onload = (e) => {
        formData.value.images.push({
          file: file,
          preview: e.target.result
        })
      }
      reader.readAsDataURL(file)
    } else {
      alert(`Il file "${file.name}" non è un'immagine valida`)
    }
  })
}

const removeImage = (index) => {
  formData.value.images.splice(index, 1)
}

const save = () => {
  const updatedListing = {
    ...props.listing,
    ...formData.value,
    card_model_id: formData.value.cardModel?.id || null
  }
  emit('save', updatedListing)
}

// Carica le grading companies
const loadGradingCompanies = async () => {
  try {
    const response = await fetch('/api/grading-companies')
    if (response.ok) {
      gradingCompanies.value = await response.json()
    }
  } catch (error) {
    console.error('Errore nel caricamento grading companies:', error)
  }
}

// Lifecycle
onMounted(() => {
  loadGradingCompanies()
})

// Watch for changes in props
watch(() => props.listing, (newListing) => {
  formData.value = {
    cardModel: newListing.cardModel,
    price: newListing.price || '',
    quantity: newListing.quantity || 1,
    condition: newListing.condition || '',
    language: newListing.language || '',
    // Grading
    gradingCompany: newListing.gradingCompany || '',
    gradingScore: newListing.gradingScore || '',
    // Filtri Extra
    autograph: newListing.autograph || '',
    relic: newListing.relic || '',
    onCardAuto: newListing.onCardAuto || '',
    rookie: newListing.rookie || '',
    jewel: newListing.jewel || '',
    multiAutograph: newListing.multiAutograph || '',
    // Caratteristiche booleane
    is_foil: newListing.is_foil || false,
    is_signed: newListing.is_signed || false,
    is_altered: newListing.is_altered || false,
    is_first_edition: newListing.is_first_edition || false,
    is_negotiable: newListing.is_negotiable || false,
    description: newListing.description || '',
    notes: newListing.notes || '',
    images: newListing.images || []
  }
}, { deep: true })
</script>
