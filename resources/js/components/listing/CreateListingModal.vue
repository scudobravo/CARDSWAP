<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <!-- Overlay -->
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="closeModal"></div>
    
    <!-- Modal -->
    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative w-full max-w-4xl transform overflow-hidden rounded-lg bg-white shadow-xl transition-all">
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
          <div class="flex items-center space-x-3">
            <h3 class="text-lg font-semibold text-gray-900">
              {{ isEdit ? 'Modifica Inserzione' : 'Crea Inserzione' }}
            </h3>
            <span class="text-sm text-gray-500">Passo {{ currentStep }} di {{ totalSteps }}</span>
          </div>
          <button @click="closeModal" class="text-gray-400 hover:text-gray-600">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>


        <!-- Content -->
        <div class="px-6 py-6">
          <!-- Step 0: Controllo Zone di Spedizione -->
          <div v-if="currentStep === 0" class="space-y-6">
            <!-- Messaggio se non ci sono zone -->
            <div v-if="!hasShippingZones" class="text-center">
              <div class="w-20 h-20 mx-auto mb-4 bg-red-100 rounded-full flex items-center justify-center">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
              </div>
              <h3 class="text-xl font-semibold text-gray-900 mb-2">Configurazione Richiesta</h3>
              <p class="text-gray-600 mb-6">
                Prima di creare inserzioni, devi configurare le tue zone di spedizione.<br>
                Crea almeno una zona per definire dove puoi spedire le tue carte.
              </p>
              <div class="flex gap-3 justify-center">
                <button
                  @click="closeModal"
                  class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 transition-colors"
                >
                  Chiudi
                </button>
                <button
                  @click="goToShippingZones"
                  class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-primary-dark transition-colors"
                >
                  Configura Zone
                </button>
              </div>
            </div>
            
            <!-- Selezione modalità se ci sono zone -->
            <div v-else>
              <div class="text-center">
                <h4 class="text-xl font-semibold text-gray-900 mb-2">Come vuoi aggiungere le tue carte?</h4>
                <p class="text-gray-600">Scegli la modalità che preferisci per creare le tue inserzioni</p>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-8">
                <!-- Singola Carta -->
                <div 
                  class="relative border-2 rounded-lg p-6 cursor-pointer transition-all duration-200 hover:border-primary hover:shadow-lg"
                  :class="{ 'border-primary bg-primary/5': selectedMode === 'single' }"
                  @click="selectMode('single')"
                >
                  <div class="text-center">
                    <h5 class="text-2xl font-black text-gray-900 mb-3">Single Card</h5>
                    <p class="text-gray-600 text-sm">
                      Perfect for selling unique or special cards. Upload images, apply detailed filters, preview, and in just a click
                    </p>
                  </div>
                  <div v-if="selectedMode === 'single'" class="absolute top-2 right-2">
                    <div class="w-6 h-6 bg-primary rounded-full flex items-center justify-center">
                      <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                </div>

                <!-- Bulk Cards -->
                <div 
                  class="relative border-2 rounded-lg p-6 cursor-pointer transition-all duration-200 hover:border-primary hover:shadow-lg"
                  :class="{ 'border-primary bg-primary/5': selectedMode === 'bulk' }"
                  @click="selectMode('bulk')"
                >
                  <div class="text-center">
                    <h5 class="text-2xl font-black text-gray-900 mb-3">Bulk Cards</h5>
                    <p class="text-gray-600 text-sm">
                      Ideal for card lots. Apply filters, adjust prices and quantities, and stay in full control with an editable card table.
                    </p>
                  </div>
                  <div v-if="selectedMode === 'bulk'" class="absolute top-2 right-2">
                    <div class="w-6 h-6 bg-primary rounded-full flex items-center justify-center">
                      <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 1: Selezione Modello Carta (Singola) -->
          <!-- Mantieni lo stato del componente quando si cambia step: usa v-show per nascondere/mostrare senza smontare -->
          <div v-if="selectedMode === 'single'" v-show="currentStep === 1" class="space-y-6">
            <!-- Selezione Categoria -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Categoria Carta</label>
              <select v-model="selectedCategory" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6">
                <option value="football">Calcio</option>
                <option value="basketball">Basketball</option>
                <option value="pokemon">Pokemon</option>
              </select>
            </div>

            <!-- Chained Filters per Single Card -->
            <ChainedFilters 
              :category="selectedCategory"
              :show-player="true"
              :show-number="true"
              :show-price="true"
              :show-search-button="false"
              :initial-filters="filters"
              :price-error="priceError"
              @filters-changed="handleFiltersChanged"
              @card-picked="selectCardModel"
            />

          </div>

          <!-- Step 1: Selezione Modelli Carta (Bulk) -->
          <div v-if="currentStep === 1 && selectedMode === 'bulk'" class="space-y-6">
            <div class="text-center">
              <h4 class="text-xl font-semibold text-gray-900 mb-2">Seleziona Modelli Carta</h4>
              <p class="text-gray-600">Usa i filtri per trovare i modelli di carte che vuoi vendere</p>
            </div>
            
            <!-- Selezione Categoria -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2">Categoria Carta</label>
              <select v-model="selectedCategory" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm/6">
                <option value="football">Calcio</option>
                <option value="basketball">Basketball</option>
                <option value="pokemon">Pokemon</option>
              </select>
            </div>

            <!-- Chained Filters per Bulk Cards -->
            <ChainedFilters 
              :category="selectedCategory"
              :show-player="false"
              :show-number="false"
              :show-price="false"
              :show-search-button="true"
              :initial-filters="filters"
              @filters-changed="handleFiltersChanged"
              @search-cards="handleSearchCards"
            />
            
            <!-- Tabella di selezione carte -->
            <BulkCardSelectionTable 
              :cards="filteredCardModels"
              :has-searched="hasSearched"
              :category="selectedCategory"
              @cards-selected="handleCardsSelected"
              @proceed-to-bulk-edit="handleProceedToBulkEdit"
            />
            
            <!-- Pulsante per caricare più risultati -->
            <div v-if="paginationInfo && currentPage < paginationInfo.last_page" class="mt-4 text-center">
              <button 
                @click="loadMoreCards"
                :disabled="isLoadingMore"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ isLoadingMore ? 'Caricamento...' : `Carica più risultati (${paginationInfo.total - filteredCardModels.length} rimanenti)` }}
              </button>
            </div>
          </div>

          <!-- Step 2: Preview e Immagini (Singola) -->
          <div v-if="currentStep === 2 && selectedMode === 'single'" class="space-y-6">
            <ImagePreviewStep
              :is-bulk-mode="false"
              :card-data="getSingleCardData"
              :grading-companies="gradingCompanies"
              @image-uploaded="handleImageUploaded"
              @additional-details-changed="handleAdditionalDetailsChanged"
            />
          </div>


          <!-- Step 2: Bulk Edit (con immagini integrate) -->
          <div v-if="currentStep === 2 && selectedMode === 'bulk'" class="space-y-6">
            <BulkEditForm 
              :selected-cards="selectedCardsForBulkEdit"
              @go-back="handleBulkEditGoBack"
              @apply-bulk-edit="handleApplyBulkEdit"
              @bulk-images-uploaded="handleBulkImagesUploaded"
              @next-step="nextStep"
            />
          </div>




          <!-- Step 3: Zone di Spedizione (Bulk) - era step 4, ora step 3 -->
          <div v-if="currentStep === 3 && selectedMode === 'bulk'" class="space-y-6">
            <div class="text-center">
              <h4 class="text-xl font-semibold text-gray-900 mb-2">Zone di Spedizione</h4>
              <p class="text-gray-600">Seleziona le zone dove vuoi spedire</p>
              <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800">
                  <strong>⚠️ Obbligatorio:</strong> Devi selezionare almeno una zona di spedizione per pubblicare l'inserzione
                </p>
              </div>
            </div>
            
            <div class="space-y-4">
              <div 
                v-for="zone in shippingZones" 
                :key="zone.id"
                class="border rounded-lg p-4 transition-all duration-200 hover:shadow-md"
                :class="{
                  'border-primary bg-primary/5': selectedShippingZones.includes(zone.id),
                  'border-gray-300': !selectedShippingZones.includes(zone.id)
                }"
              >
                <label class="flex items-start space-x-3 cursor-pointer">
                  <input 
                    v-model="selectedShippingZones"
                    :value="zone.id"
                    type="checkbox"
                    class="h-5 w-5 text-primary focus:ring-primary border-gray-300 rounded mt-1"
                  />
                  <div class="flex-1">
                    <div class="flex items-center justify-between">
                      <h6 class="font-medium text-gray-900">{{ zone.name }}</h6>
                      <span v-if="zone.delivery_days_min || zone.delivery_days_max" class="text-sm text-gray-500">
                        {{ zone.delivery_days_min ?? '?' }}-{{ zone.delivery_days_max ?? '?' }} giorni
                      </span>
                      <span v-else class="text-sm text-gray-500">Tempi variabili</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">{{ zone.description }}</p>
                  </div>
                </label>
              </div>
            </div>
            
            <!-- Validazione zone di spedizione -->
            <div v-if="selectedShippingZones.length === 0" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
              <p class="text-sm text-red-800">
                <strong>⚠️ Attenzione:</strong> Devi selezionare almeno una zona di spedizione per procedere
              </p>
            </div>
            
            <!-- Pulsanti per bulk -->
            <div class="mt-6 flex items-center justify-between">
              <button 
                @click="previousStep"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
              >
                Indietro
              </button>
              <button
                @click="createListing"
                :disabled="selectedShippingZones.length === 0 || isSubmitting"
                class="px-6 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ isSubmitting ? 'Salvataggio in corso...' : 'Crea Inserzioni Bulk' }}
              </button>
            </div>
          </div>

          <!-- Step 3: Zone di Spedizione (Single) -->
          <div v-if="currentStep === 3 && selectedMode === 'single'" class="space-y-6">
            <div class="text-center">
              <h4 class="text-xl font-semibold text-gray-900 mb-2">Zone di Spedizione</h4>
              <p class="text-gray-600">Seleziona le zone dove vuoi spedire</p>
              <div class="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <p class="text-sm text-yellow-800">
                  <strong>⚠️ Obbligatorio:</strong> Devi selezionare almeno una zona di spedizione per pubblicare l'inserzione
                </p>
              </div>
            </div>
            
            <div class="space-y-4">
              <div 
                v-for="zone in shippingZones" 
                :key="zone.id"
                class="border rounded-lg p-4 transition-all duration-200 hover:shadow-md"
                :class="{
                  'border-primary bg-primary/5': selectedShippingZones.includes(zone.id),
                  'border-gray-300': !selectedShippingZones.includes(zone.id)
                }"
              >
                <label class="flex items-start space-x-3 cursor-pointer">
                  <input 
                    v-model="selectedShippingZones"
                    :value="zone.id"
                    type="checkbox"
                    class="h-5 w-5 text-primary focus:ring-primary border-gray-300 rounded mt-1"
                  />
                  <div class="flex-1">
                    <div class="flex items-center justify-between">
                      <h6 class="font-medium text-gray-900">{{ zone.name }}</h6>
                      <span v-if="zone.delivery_days_min || zone.delivery_days_max" class="text-sm text-gray-500">
                        {{ zone.delivery_days_min ?? '?' }}-{{ zone.delivery_days_max ?? '?' }} giorni
                      </span>
                      <span v-else class="text-sm text-gray-500">Tempi variabili</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">{{ zone.description }}</p>
                  </div>
                </label>
              </div>
            </div>
            
            <!-- Validazione zone di spedizione -->
          </div>

          <!-- Step 4: Anteprima e Conferma (Single) -->
          <div v-if="currentStep === 4 && selectedMode === 'single'" class="space-y-6">
            <div class="text-center">
              <h4 class="text-xl font-semibold text-gray-900 mb-2">Anteprima Inserzione</h4>
              <p class="text-gray-600">Controlla i dettagli prima di pubblicare</p>
            </div>
            
            <!-- Anteprima Card -->
            <div class="max-w-md mx-auto">
              <div class="border rounded-lg p-6 bg-white shadow-lg">
                <div class="flex items-start space-x-4">
                  <div class="relative w-20 h-28 rounded overflow-hidden">
                    <img 
                      v-if="previewImageSrc"
                      :src="previewImageSrc" 
                      :alt="selectedCardModel?.name"
                      class="w-20 h-28 object-cover rounded"
                    />
                    <div v-else class="absolute inset-0 flex items-center justify-center bg-gray-300">
                      <div class="text-center text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-sm font-gill-sans">Immagine non disponibile</p>
                      </div>
                    </div>
                  </div>
                  <div class="flex-1">
                    <h5 class="font-semibold text-gray-900">{{ selectedCardModel?.name }}</h5>
                    <p class="text-sm text-gray-600">{{ selectedCardModel?.set_name }} {{ selectedCardModel?.year }}</p>
                    <p class="text-sm text-gray-500">{{ additionalDetails.condition || 'mint' }}</p>
                    <div class="mt-2">
                      <span class="text-lg font-bold text-primary">€ {{ filters.price || '0.00' }}</span>
                      <span class="text-sm text-gray-500 ml-2">x1</span>
                    </div>
                    <div v-if="additionalDetails.notes" class="mt-2 text-sm text-gray-600">
                      {{ additionalDetails.notes }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div v-if="!(currentStep === 3 && selectedMode === 'bulk') && !(currentStep === 2 && selectedMode === 'bulk') && !(currentStep === 1 && selectedMode === 'bulk')" class="flex items-center justify-between border-t border-gray-200 px-6 py-4">
          <button 
            v-if="currentStep > 0"
            @click="previousStep"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
          >
            Indietro
          </button>
          <div v-else></div>
          
          <button 
            v-if="currentStep === 0"
            @click="nextStep"
            :disabled="!canProceed"
            class="px-6 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Avanti
          </button>
          <button 
            v-else-if="currentStep < totalSteps - 1 && !(currentStep === 1 && selectedMode === 'bulk')"
            @click="nextStep"
            :disabled="!canProceed"
            class="px-6 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Avanti
          </button>
          <button 
            v-else-if="selectedMode === 'single'"
            @click="isEdit ? updateSingleListing() : createListing()"
            :disabled="!canProceed || isSubmitting"
            class="px-6 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {{ isSubmitting ? 'Salvataggio in corso...' : (isEdit ? 'Aggiorna' : 'Crea') }}
          </button>
        </div>
      </div>
      
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import ChainedFilters from './ChainedFilters.vue'
import BulkCardSelectionTable from './BulkCardSelectionTable.vue'
import BulkEditForm from './BulkEditForm.vue'
import ImagePreviewStep from './ImagePreviewStep.vue'

// Props
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  isEdit: {
    type: Boolean,
    default: false
  },
  editingListing: {
    type: Object,
    default: null
  },
  preselectedCardModel: {
    type: Object,
    default: null
  }
})

// Emits
const emit = defineEmits(['close', 'created', 'updated'])

// State
const currentStep = ref(0)
const selectedMode = ref('single')
const isSubmitting = ref(false) // For form submission state
const selectedCardModel = ref(null)
const selectedCardModels = ref([]) // For bulk mode
const selectedCardsForBulkEdit = ref([]) // Cards selected for bulk edit
const hasSearched = ref(false)
const filteredCardModels = ref([])
const selectedShippingZones = ref([])
const gradingCompanies = ref([])
const paginationInfo = ref(null)
const currentPage = ref(1)
const isLoadingMore = ref(false)
const currentFilters = ref({})
const shippingZones = ref([])
const bulkListings = ref([]) // For bulk mode
const isDragOver = ref(false) // For drag & drop
const selectedCategory = ref('football') // Categoria selezionata
const hasShippingZones = ref(false) // Controllo esistenza zone di spedizione
const priceError = ref(false) // Stato errore validazione prezzo

// Listing data
const listingData = ref({
  card_model_id: null,
  price: '',
  quantity: 1,
  condition: '',
  language: '',
  is_foil: false,
  is_signed: false,
  is_altered: false,
  is_first_edition: false,
  is_negotiable: false,
  description: '',
  images: []
})

// Image and preview data
const cardImage = ref(null)
const cardImagePreview = ref(null)
const bulkImages = ref([])
const additionalDetails = ref({
  condition: '',
  gradingCompany: '',
  gradingScore: '',
  notes: ''
})

// 4 Images for Single Card
const cardImages = ref([null, null, null, null]) // Array of 4 image objects
const bulkRepresentativeImage = ref(null)

// Filters for card model selection
const filters = ref({
  playerSearch: '',
  selectedPlayers: [],
  team: '',
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
  conditions: []
})

// Computed
const totalSteps = computed(() => {
  if (selectedMode.value === 'single') {
    return 5 // Step 0 (selezione modalità), step 1 (selezione carta), step 2 (immagini + dettagli), step 3 (zone spedizione), step 4 (anteprima)
  } else {
    return 4 // Step 0 (selezione modalità), step 1 (selezione carte), step 2 (dettagli + immagini), step 3 (zone spedizione)
  }
})

// Anteprima immagine per lo step finale: usa immagine caricata, altrimenti immagine del modello, altrimenti null
const previewImageSrc = computed(() => {
  const uploaded = getFirstUploadedImage()
  if (uploaded) return uploaded
  return selectedCardModel.value?.image_url || null
})

const canProceed = computed(() => {
  switch (currentStep.value) {
    case 0:
      return selectedMode.value !== null
    case 1:
      if (selectedMode.value === 'single') {
        // Deve essere selezionata una carta (accetta anche l'ID già memorizzato)
        return !!(selectedCardModel.value?.id || listingData.value.card_model_id)
      } else {
        // In bulk consenti di passare se ci sono risultati o già selezioni
        return filteredCardModels.value.length > 0 || selectedCardModels.value.length > 0
      }
    case 2:
      if (selectedMode.value === 'single') {
        return !!(listingData.value && (filters.value.price || listingData.value.price))
      } else {
        return bulkListings.value.length > 0 && bulkListings.value.every(listing => 
          listing.cardModel && listing.price && listing.condition
        )
      }
    case 3:
      // Step zone di spedizione
      return selectedShippingZones.value.length > 0
    case 4:
      // Anteprima: richiedi comunque la carta selezionata
      return selectedCardModel.value !== null
    default:
      return true
  }
})

// Methods
const selectMode = (mode) => {
  selectedMode.value = mode
}

const nextStep = () => {
  // Validazione prezzo al passo 1 (solo per single mode)
  if (currentStep.value === 1 && selectedMode.value === 'single') {
    const hasPrice = !!(filters.value.price || listingData.value.price)
    if (!hasPrice) {
      priceError.value = true
      return
    }
    // Se il prezzo è presente, rimuovi l'errore
    priceError.value = false
  }
  
  // Validazione specifica per le zone di spedizione
  if ((currentStep.value === 3 && selectedMode.value === 'single') || 
      (currentStep.value === 3 && selectedMode.value === 'bulk')) { // Step delle zone di spedizione
    if (selectedShippingZones.value.length === 0) {
      alert('⚠️ Seleziona almeno una zona di spedizione per continuare')
      return
    }
  }
  
  if (canProceed.value && currentStep.value < totalSteps.value - 1) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
    // Reset errore prezzo quando si torna indietro
    priceError.value = false
  }
}

const closeModal = () => {
  emit('close')
  resetForm()
}

const resetForm = () => {
  currentStep.value = 0
  selectedMode.value = 'single'
  selectedCardModel.value = null
  selectedCardModels.value = []
  selectedCardsForBulkEdit.value = []
  hasSearched.value = false
  filteredCardModels.value = []
  selectedShippingZones.value = []
  priceError.value = false // Reset errore prezzo
  // Reset card images
  cardImages.value = [null, null, null, null]
  filters.value = {
    playerSearch: '',
    selectedPlayers: [],
    team: '',
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
    conditions: []
  }
  additionalDetails.value = {
    condition: '',
    gradingCompany: '',
    gradingScore: '',
    notes: ''
  }
  listingData.value = {
    card_model_id: null,
    price: '',
    quantity: 1,
    condition: '',
    language: '',
    is_foil: false,
    is_signed: false,
    is_altered: false,
    is_first_edition: false,
    is_negotiable: false,
    description: '',
    images: []
  }
}

const handleFiltersChanged = async (newFilters) => {
  filters.value = newFilters
  
  // Cerca automaticamente solo durante lo step di selezione (step 1)
  if (selectedMode.value === 'single' && currentStep.value === 1) {
    // Se l'utente ha già scelto una carta, non ricerchiamo per non perdere la selezione
    if (selectedCardModel.value?.id || listingData.value.card_model_id) {
      return
    }
    await searchSingleCard(newFilters)
  }
}

// Debounce per evitare troppe chiamate API
let searchTimeout = null

const searchSingleCard = async (filters) => {
  // Cancella la ricerca precedente se esiste
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  // Debounce di 300ms
  return new Promise((resolve) => {
    searchTimeout = setTimeout(async () => {
      try {
        console.log('🔍 Ricerca carta singola con filtri:', filters)
        
        // Convert to the format expected by the API
        const searchFilters = {
          player_id: filters.player,
          team_id: filters.team,
          card_set_id: filters.set, // Corretto: card_set_id invece di set_id
          brand: filters.brand,
          rarity: filters.rarity,
          year: filters.year,
          number: filters.number
          // ✅ RIMOSSO: price non è un filtro di ricerca, è un input dell'utente
        }
        
        console.log('🔍 Filtri convertiti per API:', searchFilters)
        
        // Rimuovi parametri vuoti
        const cleanFilters = Object.fromEntries(
          Object.entries(searchFilters).filter(([_, value]) => value !== null && value !== undefined && value !== '')
        )
        
        // Crea query string
        const queryParams = new URLSearchParams(cleanFilters).toString()
        const url = `/api/cards/search?${queryParams}`
        
        console.log('🔍 URL richiesta:', url)
        
        const response = await fetch(url, {
          method: 'GET',
          headers: {
            'Accept': 'application/json',
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        
        console.log('🔍 Response status:', response.status)
        
        const data = await response.json()
        console.log('🔍 Response data:', data)
        
        const cards = data.cards || []
        console.log('🔍 Carte trovate:', cards.length)
        
        // Popola filteredCardModels per la selezione manuale
        filteredCardModels.value = cards
        hasSearched.value = true
        
        // Se troviamo una sola carta che corrisponde ai filtri, selezionala automaticamente
        if (cards.length === 1) {
          console.log('✅ Carta unica trovata, selezionata automaticamente:', cards[0])
          selectCardModel(cards[0]) // Usa la funzione per popolare i dati
        } else if (cards.length > 1) {
          console.log('⚠️ Multiple carte trovate, non seleziono automaticamente')
        } else {
          console.log('❌ Nessuna carta trovata per i filtri specificati')
          selectedCardModel.value = null
          listingData.value.card_model_id = null
        }
      } catch (error) {
        console.error('❌ Errore nella ricerca carta singola:', error)
        selectedCardModel.value = null
        listingData.value.card_model_id = null
        filteredCardModels.value = []
        hasSearched.value = true
      }
      resolve()
    }, 300)
  })
}

const selectCardModel = (card) => {
  console.log('🎯 Carta selezionata manualmente:', card)
  selectedCardModel.value = card
  listingData.value.card_model_id = card.id
  
  // Popola automaticamente SOLO i dati informativi (non i filtri di ricerca)
  if (card) {
    // Popola i dati base della carta
    listingData.value.card_model_id = card.id
    
    // ✅ NON popoliamo più automaticamente il prezzo
    // L'utente deve inserirlo manualmente
    
    // ✅ NON popoliamo più automaticamente i campi del form
    // L'utente deve selezionarli manualmente per evitare filtri troppo specifici
    console.log('✅ Campi del form NON popolati automaticamente - l\'utente deve selezionarli manualmente')
    
    // ✅ NON popoliamo più automaticamente i campi del form
    // L'utente deve selezionarli manualmente per evitare filtri troppo specifici
    
    console.log('✅ Carta selezionata per riferimento:', {
      card_model_id: card.id,
      price: card.price,
      rarity: card.rarity,
      year: card.year,
      brand: card.card_set?.brand,
      number: card.card_number
    })
    console.log('✅ Campi del form NON popolati automaticamente - l\'utente deve selezionarli manualmente')
  }
}

const handleCardSelected = (cardData) => {
  console.log('Card selected:', cardData)
  // Qui puoi gestire i dati della carta selezionata
  // Per ora passiamo al prossimo step
  nextStep()
}

const handleSearchCards = async (filters, page = 1) => {
  try {
    // Salva i filtri per il caricamento di più risultati
    if (page === 1) {
      currentFilters.value = { ...filters }
      currentPage.value = 1
    }
    
    const searchData = {
      ...filters,
      per_page: 50, // Aumentiamo il numero di risultati per pagina
      page: page
    }
    
    const response = await fetch('/api/card-models/search', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      },
      body: JSON.stringify(searchData)
    })
    
    const data = await response.json()
    console.log('🔍 Response data per bulk search:', data)
    
    if (page === 1) {
      // Prima pagina: sostituisci i risultati
      filteredCardModels.value = data.data?.card_models || []
    } else {
      // Pagine successive: aggiungi ai risultati esistenti
      filteredCardModels.value = [...filteredCardModels.value, ...(data.data?.card_models || [])]
    }
    
    // Salva le informazioni di paginazione
    paginationInfo.value = data.data?.pagination || null
    hasSearched.value = true
  } catch (error) {
    console.error('Errore nella ricerca carte:', error)
    filteredCardModels.value = []
    hasSearched.value = true
  }
}

const handleCardsSelected = (cards) => {
  selectedCardModels.value = cards
}

const loadMoreCards = async () => {
  if (isLoadingMore.value || !paginationInfo.value) return
  
  const nextPage = currentPage.value + 1
  if (nextPage > paginationInfo.value.last_page) return
  
  isLoadingMore.value = true
  currentPage.value = nextPage
  
  try {
    // Usa gli stessi filtri della ricerca iniziale
    const currentFilters = getCurrentFilters() // Dobbiamo implementare questa funzione
    await handleSearchCards(currentFilters, nextPage)
  } catch (error) {
    console.error('Errore nel caricamento di più carte:', error)
  } finally {
    isLoadingMore.value = false
  }
}

const getCurrentFilters = () => {
  return currentFilters.value
}

const handleProceedToBulkEdit = (cards) => {
  console.log('🎯 CreateListingModal - handleProceedToBulkEdit called with cards:', cards)
  console.log('🎯 Number of cards received:', cards?.length)
  selectedCardsForBulkEdit.value = cards
  console.log('🎯 selectedCardsForBulkEdit after assignment:', selectedCardsForBulkEdit.value)
  console.log('🎯 selectedCardsForBulkEdit length:', selectedCardsForBulkEdit.value?.length)
  nextStep()
}

const handleBulkEditGoBack = () => {
  previousStep()
}

const handleApplyBulkEdit = (listings) => {
  console.log('🔍 CreateListingModal - Ricevute listings:', listings)
  bulkListings.value = listings
  console.log('✅ Bulk listings aggiornate:', bulkListings.value)
  // Non chiamiamo nextStep() qui, lasciamo che sia il footer a gestire la navigazione
}

const searchCardModels = async () => {
  try {
    // Implement API call to search card models
    const response = await fetch('/api/card-models/search', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      },
      body: JSON.stringify(filters.value)
    })
    
    const data = await response.json()
    filteredCardModels.value = data.data?.card_models || []
  } catch (error) {
    console.error('Errore nella ricerca modelli:', error)
    filteredCardModels.value = []
  }
}


// Bulk mode methods
const isCardModelSelected = (cardModel) => {
  return selectedCardModels.value.some(selected => selected.id === cardModel.id)
}

const toggleCardModelSelection = (cardModel) => {
  const index = selectedCardModels.value.findIndex(selected => selected.id === cardModel.id)
  if (index > -1) {
    selectedCardModels.value.splice(index, 1)
  } else {
    selectedCardModels.value.push(cardModel)
  }
}

const selectAllCardModels = () => {
  selectedCardModels.value = [...filteredCardModels.value]
}

const clearCardModelSelection = () => {
  selectedCardModels.value = []
}

const updateBulkListings = (listings) => {
  bulkListings.value = listings
}

const removeBulkListing = (index) => {
  bulkListings.value.splice(index, 1)
}

const addBulkListing = () => {
  const newListing = {
    id: Date.now(),
    cardModel: null,
    price: '',
    quantity: 1,
    condition: '',
    language: '',
    is_foil: false,
    is_signed: false,
    is_altered: false,
    is_first_edition: false,
    is_negotiable: false,
    description: '',
    images: []
  }
  bulkListings.value.push(newListing)
}

const handleImageUpload = (event) => {
  const files = Array.from(event.target.files)
  processImageFiles(files)
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
  // Solo se lasciamo completamente la zona di drop
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

// Processa i file immagine (usato sia per click che drag & drop)
const processImageFiles = (files) => {
  const maxFiles = 4
  const maxSize = 10 * 1024 * 1024 // 10MB (aumentato per permettere immagini più grandi)
  
  // Controlla se superiamo il limite di file
  if (cardImages.value.filter(img => img).length + files.length > maxFiles) {
    alert(`Massimo ${maxFiles} immagini per inserzione. Hai già ${cardImages.value.filter(img => img).length} immagini.`)
    return
  }
  
  files.forEach(file => {
    if (file.type.startsWith('image/')) {
      // Controllo dimensione
      if (file.size > maxSize) {
        alert(`L'immagine "${file.name}" è troppo grande. Dimensione massima: 10MB`)
        return
      }
      
      // Controllo se abbiamo già raggiunto il limite
      if (cardImages.value.filter(img => img).length >= maxFiles) {
        alert(`Massimo ${maxFiles} immagini per inserzione`)
        return
      }
      
      // Trova il primo slot vuoto
      const emptyIndex = cardImages.value.findIndex(img => !img)
      if (emptyIndex !== -1) {
        cardImages.value[emptyIndex] = {
          file: file,
          preview: URL.createObjectURL(file)
        }
      } else {
        alert(`Massimo ${maxFiles} immagini per inserzione`)
      }
    } else {
      alert(`Il file "${file.name}" non è un'immagine valida`)
    }
  })
}

const removeImage = (index) => {
  if (cardImages.value[index]) {
    URL.revokeObjectURL(cardImages.value[index].preview)
  }
  cardImages.value[index] = null
}


const loadGradingCompanies = async () => {
  try {
    console.log('🔄 Caricamento grading companies...')
    const response = await fetch('/api/grading-companies')
    console.log('📡 Response status:', response.status)
    console.log('📡 Response ok:', response.ok)
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    gradingCompanies.value = data
    console.log('✅ Grading companies caricate:', data)
    console.log('📊 Numero di companies:', data.length)
  } catch (error) {
    console.error('❌ Errore nel caricamento grading companies:', error)
    console.error('❌ Error details:', error.message)
    // Fallback con dati mock se l'API non funziona
    gradingCompanies.value = [
      { id: 1, name: 'PSA' },
      { id: 2, name: 'BGS' },
      { id: 3, name: 'AIGRADING' },
      { id: 4, name: 'GRAAD' },
      { id: 5, name: 'CGC' }
    ]
    console.log('🔄 Usando dati mock:', gradingCompanies.value)
  }
}

const checkShippingZones = async () => {
  try {
    console.log('🔄 Controllo esistenza zone di spedizione...')
    const response = await fetch('/api/shipping-zones/check', {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      console.log('✅ Controllo zone di spedizione:', data)
      hasShippingZones.value = data.has_zones
    } else {
      console.error('❌ Errore nel controllo zone di spedizione:', response.status)
      hasShippingZones.value = false
    }
  } catch (error) {
    console.error('❌ Errore nel controllo zone di spedizione:', error)
    hasShippingZones.value = false
  }
}

const goToShippingZones = () => {
  closeModal()
  window.location.href = '/profile/shipping-zones'
}

const loadShippingZones = async () => {
  try {
    console.log('🔄 Caricamento zone di spedizione...')
    const response = await fetch('/api/shipping-zones', {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      console.log('✅ Zone di spedizione caricate:', data)
      // L'endpoint ritorna { success: true, data: [...] }
      // In alcuni ambienti potremmo ricevere direttamente un array
      const rawZones = Array.isArray(data) ? data : (data && Array.isArray(data.data) ? data.data : [])
      console.log('📦 Raw zones ricevute:', rawZones.length, rawZones)
      
      // Normalizza per garantire sempre id, name e giorni
      const normalized = rawZones.map((z) => {
        const source = z || {}
        const attrs = source.attributes || {}
        const id = source.id ?? attrs.id ?? source.zone_id
        const name = source.name || attrs.name || source.title || source.label || (source.country_code ? `Spedizione ${source.country_code}` : 'Zona')
        // Gestisci null/undefined per i giorni di consegna
        const deliveryMin = source.delivery_days_min ?? attrs.delivery_days_min ?? source.min_days ?? null
        const deliveryMax = source.delivery_days_max ?? attrs.delivery_days_max ?? source.max_days ?? null
        const description = source.description || attrs.description || ''
        
        console.log('📦 Normalizzazione zona:', { id, name, deliveryMin, deliveryMax, description })
        
        return { 
          id, 
          name, 
          delivery_days_min: deliveryMin, 
          delivery_days_max: deliveryMax, 
          description 
        }
      })
      
      console.log('✅ Zone normalizzate:', normalized.length, normalized)
      shippingZones.value = normalized
    } else {
      console.error('❌ Errore nel caricamento zone di spedizione:', response.status)
    }
  } catch (error) {
    console.error('❌ Errore nel caricamento zone di spedizione:', error)
  }
}

// Funzione per comprimere immagini prima dell'upload (per evitare errore 413)
// Migliorata per mobile con compressione più aggressiva
// IMPORTANTE: Su mobile, comprimi SEMPRE se > 500KB per sicurezza
const compressImageForUpload = (file, maxWidth = 1920, maxHeight = 2560, quality = 0.70, maxSizeMB = 1.0) => {
  return new Promise((resolve, reject) => {
    const fileSizeMB = file.size / 1024 / 1024
    console.log(`📦 Compressione immagine: ${file.name}, dimensione originale: ${fileSizeMB.toFixed(2)}MB`)
    
    // Su mobile, comprimi sempre se > 500KB per sicurezza (limite più conservativo)
    const shouldCompress = file.size > 500 * 1024 // 500KB invece di 1.5MB
    
    if (!shouldCompress) {
      console.log('✅ File già piccolo (< 500KB), non comprimere')
      resolve(file)
      return
    }

    const reader = new FileReader()
    reader.readAsDataURL(file)
    reader.onload = (e) => {
      const img = new Image()
      img.src = e.target.result
      img.onload = () => {
        const canvas = document.createElement('canvas')
        let width = img.width
        let height = img.height

        // Calcola le nuove dimensioni mantenendo le proporzioni
        // Riduci sempre le dimensioni se l'immagine è grande
        if (width > height) {
          if (width > maxWidth) {
            height = Math.round((height * maxWidth) / width)
            width = maxWidth
          }
        } else {
          if (height > maxHeight) {
            width = Math.round((width * maxHeight) / height)
            height = maxHeight
          }
        }

        canvas.width = width
        canvas.height = height

        const ctx = canvas.getContext('2d')
        // Migliora la qualità del rendering su mobile
        ctx.imageSmoothingEnabled = true
        ctx.imageSmoothingQuality = 'high'
        ctx.drawImage(img, 0, 0, width, height)

        // Funzione helper per comprimere con qualità specifica
        const compressWithQuality = (targetQuality) => {
          return new Promise((res, rej) => {
            canvas.toBlob(
              (blob) => {
                if (blob) {
                  const sizeMB = blob.size / 1024 / 1024
                  console.log(`📦 Compressione tentativo: qualità ${targetQuality.toFixed(2)}, dimensione: ${sizeMB.toFixed(2)}MB`)
                  res({ blob, size: blob.size, quality: targetQuality })
                } else {
                  rej(new Error('Errore nella creazione blob'))
                }
              },
              'image/jpeg',
              targetQuality
            )
          })
        }

        // Compressione progressiva più aggressiva con limite più basso
        const compressProgressively = async (startQuality) => {
          let currentQuality = startQuality
          const minQuality = 0.4 // Ridotto da 0.5 per compressione più aggressiva
          const maxSizeBytes = maxSizeMB * 1024 * 1024 // 1MB invece di 1.5MB

          while (currentQuality >= minQuality) {
            try {
              const result = await compressWithQuality(currentQuality)
              
              if (result.size <= maxSizeBytes) {
                console.log(`✅ Compressione riuscita: qualità ${result.quality.toFixed(2)}, dimensione: ${(result.size / 1024 / 1024).toFixed(2)}MB`)
                const compressedFile = new File([result.blob], file.name, {
                  type: 'image/jpeg',
                  lastModified: Date.now()
                })
                resolve(compressedFile)
                return
              } else {
                // Se ancora troppo grande, riduci qualità o ridimensiona
                if (currentQuality > minQuality) {
                  currentQuality -= 0.05 // Riduzione più piccola per tentativi più frequenti
                } else {
                  // Ridimensiona ulteriormente se la qualità minima non basta
                  const scaleFactor = Math.sqrt(maxSizeBytes / result.size) * 0.9 // Riduci ulteriormente del 10%
                  const newWidth = Math.round(width * scaleFactor)
                  const newHeight = Math.round(height * scaleFactor)
                  
                  console.log(`📦 Ridimensionamento ulteriore: ${newWidth}x${newHeight} (fattore: ${scaleFactor.toFixed(2)})`)
                  
                  canvas.width = newWidth
                  canvas.height = newHeight
                  ctx.drawImage(img, 0, 0, newWidth, newHeight)
                  
                  const finalResult = await compressWithQuality(0.5)
                  const compressedFile = new File([finalResult.blob], file.name, {
                    type: 'image/jpeg',
                    lastModified: Date.now()
                  })
                  console.log(`✅ Compressione finale riuscita: dimensione: ${(finalResult.size / 1024 / 1024).toFixed(2)}MB`)
                  resolve(compressedFile)
                  return
                }
              }
            } catch (error) {
              console.error('Errore durante compressione progressiva:', error)
              currentQuality -= 0.05
            }
          }
          
          // Se arriviamo qui, la compressione non è riuscita
          reject(new Error('Impossibile comprimere l\'immagine sotto il limite'))
        }

        // Inizia la compressione progressiva
        compressProgressively(quality).catch(reject)
      }
      img.onerror = () => {
        console.error('❌ Errore nel caricamento dell\'immagine')
        reject(new Error('Errore nel caricamento dell\'immagine'))
      }
    }
    reader.onerror = () => {
      console.error('❌ Errore nella lettura del file')
      reject(new Error('Errore nella lettura del file'))
    }
  })
}

const createListing = async () => {
  try {
    isSubmitting.value = true
    if (selectedMode.value === 'single') {
      await createSingleListing()
    } else {
      await createBulkListings()
    }
  } catch (error) {
    console.error('Errore nella creazione inserzioni:', error)
    
    // Gestione errori dettagliata
    let errorMessage = 'Errore nella creazione inserzioni.'
    
    if (error.response) {
      // Errore HTTP con risposta
      const errorData = error.response.data || {}
      if (errorData.errors) {
        const errorDetails = Object.entries(errorData.errors)
          .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
          .join('\n')
        errorMessage = `Errore nella creazione inserzioni:\n\n${errorDetails}`
      } else if (errorData.message) {
        errorMessage = `Errore nella creazione inserzioni:\n\n${errorData.message}`
      } else {
        errorMessage = `Errore HTTP ${error.response.status}: ${error.response.statusText}`
      }
    } else if (error.message) {
      errorMessage = `Errore nella creazione inserzioni:\n\n${error.message}`
    } else if (typeof error === 'string') {
      errorMessage = `Errore nella creazione inserzioni:\n\n${error}`
    }
    
    alert(errorMessage)
  } finally {
    isSubmitting.value = false
  }
}

const createSingleListing = async () => {
  if (props.isEdit && props.editingListing) {
    await updateSingleListing()
  } else {
    await createNewSingleListing()
  }
}

const createNewSingleListing = async () => {
  const formData = new FormData()
  
  // Add card_model_id (required) – usa selezione corrente o ID salvato
  const cmId = selectedCardModel.value?.id || listingData.value.card_model_id
  if (cmId) {
    formData.append('card_model_id', cmId)
  } else {
    console.error('card_model_id is required but not found')
    alert('Errore: Carta non selezionata')
    return
  }
  
  // Add price from filters (required)
  if (filters.value.price) {
    formData.append('price', filters.value.price)
  } else {
    console.error('price is required but not found')
    alert('Errore: Prezzo non inserito')
    return
  }
  
  // Add quantity (default 1)
  formData.append('quantity', '1')
  
  // Add condition (required) - from additionalDetails or default
  const condition = additionalDetails.value.condition || 'mint'
  formData.append('condition', condition)
  
  // Add language (required) - default to italian
  formData.append('language', 'italian')
  
  // Add boolean fields (required) - FormData converts booleans to strings
  formData.append('is_foil', 'false')
  formData.append('is_signed', 'false')
  formData.append('is_altered', 'false')
  formData.append('is_first_edition', 'false')
  formData.append('is_negotiable', 'false')
  
  // Add description
  if (additionalDetails.value.notes) {
    formData.append('description', additionalDetails.value.notes)
  }
  
  // Add number from filters
  if (filters.value.number) {
    formData.append('number', filters.value.number)
  }
  
  // Add grading info
  if (additionalDetails.value.gradingCompany) {
    formData.append('grading_company', additionalDetails.value.gradingCompany)
  }
  if (additionalDetails.value.gradingScore) {
    formData.append('grading_score', additionalDetails.value.gradingScore)
  }
  
  // Add 4 images - comprimi SEMPRE se > 500KB per evitare 413 su mobile
  let totalSize = 0
  for (let index = 0; index < cardImages.value.length; index++) {
    const image = cardImages.value[index]
    if (image && image.file) {
      try {
        const fileSizeMB = image.file.size / 1024 / 1024
        console.log(`🖼️ Immagine ${index + 1}: ${fileSizeMB.toFixed(2)}MB`)
        
        // Comprimi sempre se > 500KB o se la dimensione totale supererebbe 3MB (più conservativo)
        const shouldCompress = image.file.size > 500 * 1024 || totalSize + image.file.size > 3 * 1024 * 1024
        
        if (shouldCompress) {
          console.log(`📦 Compressione immagine ${index + 1}...`)
          const compressedFile = await compressImageForUpload(image.file)
          const compressedSizeMB = compressedFile.size / 1024 / 1024
          console.log(`✅ Immagine ${index + 1} compressa: ${compressedSizeMB.toFixed(2)}MB (riduzione: ${((1 - compressedFile.size / image.file.size) * 100).toFixed(1)}%)`)
          
          // Verifica che la compressione sia stata effettivamente applicata
          if (compressedFile.size >= image.file.size) {
            console.warn(`⚠️ Compressione non efficace: file compresso (${compressedSizeMB.toFixed(2)}MB) è grande quanto o più grande dell'originale (${fileSizeMB.toFixed(2)}MB)`)
            // Usa comunque il file compresso, ma avvisa
          }
          
          formData.append('images[]', compressedFile)
          totalSize += compressedFile.size
          
          // Se dopo la compressione è ancora troppo grande, mostra errore
          if (compressedFile.size > 1.5 * 1024 * 1024) {
            const errorMsg = `⚠️ Attenzione: L'immagine ${index + 1} è ancora troppo grande dopo la compressione (${compressedSizeMB.toFixed(2)}MB). Prova con un'immagine più piccola o di qualità inferiore.`
            console.error(errorMsg)
            alert(errorMsg)
            throw new Error(`Immagine ${index + 1} troppo grande dopo compressione`)
          }
        } else {
          console.log(`✅ Immagine ${index + 1} già piccola (${fileSizeMB.toFixed(2)}MB), non comprimere`)
          formData.append('images[]', image.file)
          totalSize += image.file.size
        }
      } catch (error) {
        console.error(`❌ Errore compressione immagine ${index + 1}:`, error)
        // Se la compressione fallisce, NON inviare il file originale se è troppo grande
        if (image.file.size < 2 * 1024 * 1024) {
          console.log(`✅ Usando file originale per immagine ${index + 1} (${(image.file.size / 1024 / 1024).toFixed(2)}MB)`)
          formData.append('images[]', image.file)
          totalSize += image.file.size
        } else {
          const errorMsg = `❌ L'immagine ${index + 1} è troppo grande (${(image.file.size / 1024 / 1024).toFixed(2)}MB) e non può essere compressa. Per favore, usa un'immagine più piccola.`
          console.error(errorMsg)
          alert(errorMsg)
          throw new Error(`Immagine ${index + 1} troppo grande: ${(image.file.size / 1024 / 1024).toFixed(2)}MB`)
        }
      }
    }
  }
  
  console.log(`📦 Dimensione totale immagini: ${(totalSize / 1024 / 1024).toFixed(2)}MB`)
  
  // Verifica che la dimensione totale non superi 4MB
  if (totalSize > 4 * 1024 * 1024) {
    const errorMsg = `⚠️ Attenzione: La dimensione totale delle immagini (${(totalSize / 1024 / 1024).toFixed(2)}MB) è troppo grande. Per favore, carica meno immagini o immagini più piccole.`
    console.error(errorMsg)
    alert(errorMsg)
    throw new Error('Dimensione totale immagini troppo grande')
  }
  
  // Add shipping zones (required)
  if (selectedShippingZones.value.length === 0) {
    console.error('shipping_zones is required but not found')
    alert('Errore: Seleziona almeno una zona di spedizione')
    return
  }
  selectedShippingZones.value.forEach(zoneId => {
    formData.append('shipping_zones[]', zoneId)
  })
  
  console.log('Creating single listing with data:', {
    card_model_id: selectedCardModel.value.id,
    price: filters.value.price,
    condition,
    language: 'italian',
    images: cardImages.value.filter(img => img).length,
    shipping_zones: selectedShippingZones.value.length
  })
  
  const response = await fetch('/api/listings', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${localStorage.getItem('token')}`
    },
    body: formData
  })
  
  if (response.ok) {
    const data = await response.json()
    console.log('✅ Inserzione creata con successo:', data)
    emit('created', data.data)
    closeModal()
  } else {
    const errorData = await response.json().catch(() => ({ message: 'Errore nella lettura della risposta' }))
    console.error('Error creating listing:', errorData)
    console.error('Response status:', response.status)
    console.error('Response statusText:', response.statusText)
    
    // Gestione specifica per errore KYC
    if (errorData.requires_kyc) {
      alert(`⚠️ Verifica identità richiesta!\n\nPer creare inserzioni devi completare la verifica della tua identità.\n\nClicca OK per essere reindirizzato alla pagina di verifica.`)
      // Reindirizza alla pagina KYC
      window.location.href = '/dashboard/kyc'
      return
    }
    
    // Altri errori con dettagli
    let errorMessage = `Errore nella creazione inserzione (HTTP ${response.status}):\n\n`
    
    if (errorData.errors) {
      const errorDetails = Object.entries(errorData.errors)
        .map(([field, messages]) => `• ${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
        .join('\n')
      errorMessage += errorDetails
    } else if (errorData.message) {
      errorMessage += errorData.message
    } else {
      errorMessage += errorData.error || 'Errore sconosciuto'
    }
    
    alert(errorMessage)
    throw new Error(errorMessage)
  }
}

const createBulkListings = async () => {
  console.log('🔄 Creazione inserzioni bulk...', bulkListings.value)
  
  // Crea un'inserzione per ogni carta selezionata
  const createdListings = []
  
  for (let i = 0; i < bulkListings.value.length; i++) {
    const listing = bulkListings.value[i]
    const formData = new FormData()
    
    // Dati obbligatori
    console.log(`🔍 Listing ${i + 1} data:`, {
      card_model_id: listing.card_model_id,
      price: listing.price,
      condition: listing.condition,
      language: listing.language
    })
    
    formData.append('card_model_id', listing.card_model_id)
    formData.append('price', listing.price)
    formData.append('quantity', listing.quantity || 1)
    formData.append('condition', listing.condition)
    formData.append('language', listing.language || 'italian')
    
    // Dati opzionali
    if (listing.number) formData.append('number', listing.number)
    if (listing.grading_company) formData.append('grading_company', listing.grading_company)
    if (listing.grading_score) formData.append('grading_score', listing.grading_score)
    if (listing.description) formData.append('description', listing.description)
    
    // Boolean fields
    formData.append('is_foil', listing.is_foil ? 'true' : 'false')
    formData.append('is_signed', listing.is_signed ? 'true' : 'false')
    formData.append('is_altered', listing.is_altered ? 'true' : 'false')
    formData.append('is_first_edition', listing.is_first_edition ? 'true' : 'false')
    formData.append('is_negotiable', listing.is_negotiable ? 'true' : 'false')
    
    // Immagini bulk - una immagine per carta (comprimi se necessario)
    if (bulkImages.value && bulkImages.value[i] && bulkImages.value[i].file) {
      console.log(`🖼️ Aggiungendo immagine per carta ${i + 1}:`, bulkImages.value[i])
      try {
        const imageFile = bulkImages.value[i].file
        // Se l'immagine è > 2MB, comprimila prima dell'upload
        if (imageFile.size > 2 * 1024 * 1024) {
          const compressedFile = await compressImageForUpload(imageFile)
          formData.append('images[]', compressedFile)
        } else {
          formData.append('images[]', imageFile)
        }
      } catch (error) {
        console.error(`Errore compressione immagine bulk ${i + 1}:`, error)
        // Usa il file originale se la compressione fallisce
        formData.append('images[]', bulkImages.value[i].file)
      }
    } else {
      console.log(`⚠️ Nessuna immagine per carta ${i + 1}`)
    }
    
    // Zone di spedizione
    selectedShippingZones.value.forEach(zoneId => {
      formData.append('shipping_zones[]', zoneId)
    })
    
      try {
        const response = await axios.post('/api/listings', formData, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'multipart/form-data'
          }
        })
        
        createdListings.push(response.data.data)
        console.log(`✅ Inserzione ${i + 1}/${bulkListings.value.length} creata con successo`)
    } catch (error) {
      console.error(`❌ Errore nella creazione inserzione ${i + 1}:`, error)
      
      // Gestione specifica per errore KYC
      if (error.response && error.response.data && error.response.data.requires_kyc) {
        alert(`⚠️ Verifica identità richiesta!\n\nPer creare inserzioni devi completare la verifica della tua identità.\n\nClicca OK per essere reindirizzato alla pagina di verifica.`)
        // Reindirizza alla pagina KYC
        window.location.href = '/dashboard/kyc'
        return
      }
      
      if (error.response && error.response.data && error.response.data.errors) {
        console.error(`❌ Dettagli errore:`, error.response.data.errors)
        alert(`Errore nella creazione inserzione ${i + 1}: ${JSON.stringify(error.response.data.errors)}`)
      } else {
        alert(`Errore nella creazione inserzione ${i + 1}`)
      }
    }
  }
  
  if (createdListings.length > 0) {
    console.log(`✅ ${createdListings.length} inserzioni create con successo`)
    emit('created', createdListings)
    closeModal()
  }
}

// Image and preview methods
// Usa un computed per evitare che venga chiamato continuamente
const getSingleCardData = computed(() => {
  const baseData = {
    player: selectedCardModel.value?.player,
    team: selectedCardModel.value?.team,
    set: selectedCardModel.value?.card_set,
    brand: filters.value.brand,
    rarity: filters.value.rarity,
    year: filters.value.year,
    number: filters.value.number,
    price: filters.value.price
  }
  
  // In modalità edit, aggiungi i dati dell'inserzione esistente
  // IMPORTANTE: passa tutte le immagini (esistenti + nuove) per mantenere la persistenza tra gli step
  if (props.isEdit && props.editingListing) {
    return {
      ...baseData,
      // Dati dell'inserzione esistente
      condition: listingData.value.condition,
      quantity: listingData.value.quantity,
      language: listingData.value.language,
      description: listingData.value.description,
      is_foil: listingData.value.is_foil,
      is_signed: listingData.value.is_signed,
      is_altered: listingData.value.is_altered,
      is_first_edition: listingData.value.is_first_edition,
      is_negotiable: listingData.value.is_negotiable,
      // Dati aggiuntivi per il componente ImagePreviewStep
      gradingCompany: additionalDetails.value.gradingCompany,
      gradingScore: additionalDetails.value.gradingScore,
      notes: additionalDetails.value.notes,
      // Caratteristiche speciali
      autograph: listingData.value.is_signed ? 'yes' : 'no',
      relic: listingData.value.is_altered ? 'yes' : 'no',
      onCardAuto: listingData.value.is_signed ? 'yes' : 'no',
      rookie: listingData.value.is_first_edition ? 'yes' : 'no',
      jewel: listingData.value.is_foil ? 'yes' : 'no',
      multiAutograph: '',
      // Passa TUTTE le immagini (esistenti + nuove) per mantenere la persistenza
      existingImages: cardImages.value.filter(img => img !== null)
    }
  }
  
  return baseData
})

const handleImageUploaded = (imagesArray) => {
  console.log('📸 handleImageUploaded chiamata con:', imagesArray)
  
  // Assicurati che l'array abbia sempre 4 elementi per mantenere la struttura
  const fullArray = Array(4).fill(null)
  imagesArray.forEach((img, index) => {
    if (index < 4 && img) {
      fullArray[index] = img
    }
  })
  
  // Aggiorna cardImages con l'array completo delle immagini (mantenendo la struttura di 4 elementi)
  cardImages.value = [...fullArray]
  console.log('📸 cardImages.value aggiornato:', cardImages.value)
  
  // Aggiorna anche cardImage per compatibilità (prima immagine valida)
  const firstValidImage = imagesArray.find(img => img && img.file)
  if (firstValidImage) {
    cardImage.value = firstValidImage.file
    cardImagePreview.value = firstValidImage.preview
  }
  
  // Aggiorna listingData.images con tutti i file validi
  listingData.value.images = imagesArray.filter(img => img && img.file).map(img => img.file)
  console.log('📸 listingData.value.images aggiornato:', listingData.value.images)
}

const handleAdditionalDetailsChanged = (details) => {
  additionalDetails.value = details
  // Update listing data with additional details
  listingData.value.condition = details.condition
  listingData.value.grading_company = details.gradingCompany
  listingData.value.grading_score = details.gradingScore
  listingData.value.description = details.notes || details.description
  
  // Update special characteristics
  listingData.value.is_signed = details.autograph === 'yes'
  listingData.value.is_altered = details.relic === 'yes'
  listingData.value.is_first_edition = details.rookie === 'yes'
  listingData.value.is_foil = details.jewel === 'yes'
}

const handleBulkImagesUploaded = (images) => {
  console.log('🖼️ Immagini bulk caricate:', images)
  bulkImages.value = images
  // Update bulk listings with images
  bulkListings.value.forEach((listing, index) => {
    if (images[index]) {
      listing.images = [images[index].file]
    }
  })
  console.log('🖼️ Bulk images aggiornate:', bulkImages.value)
}


// 4 Images methods
const handleCardImageUpload = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      alert('Il file è troppo grande. Dimensione massima: 10MB')
      return
    }

    // Validate file type
    if (!file.type.startsWith('image/')) {
      alert('Seleziona un file immagine valido')
      return
    }

    cardImages.value[index] = {
      file,
      preview: URL.createObjectURL(file)
    }
  }
}

const removeCardImage = (index) => {
  if (cardImages.value[index]) {
    URL.revokeObjectURL(cardImages.value[index].preview)
  }
  cardImages.value[index] = null
}

const handleBulkRepresentativeImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Validate file size (10MB max)
    if (file.size > 10 * 1024 * 1024) {
      alert('Il file è troppo grande. Dimensione massima: 10MB')
      return
    }

    // Validate file type
    if (!file.type.startsWith('image/')) {
      alert('Seleziona un file immagine valido')
      return
    }

    bulkRepresentativeImage.value = {
      file,
      preview: URL.createObjectURL(file)
    }
  }
}

const removeBulkRepresentativeImage = () => {
  if (bulkRepresentativeImage.value) {
    URL.revokeObjectURL(bulkRepresentativeImage.value.preview)
  }
  bulkRepresentativeImage.value = null
}

// Get first uploaded image for preview
const getFirstUploadedImage = () => {
  // Cerca la prima immagine caricata nell'array cardImages
  const firstImage = cardImages.value.find(img => img && img.preview)
  return firstImage ? firstImage.preview : null
}

// Lifecycle
onMounted(async () => {
  await checkShippingZones()
  if (hasShippingZones.value) {
    loadShippingZones()
  }
  loadGradingCompanies()
})

// Watch per modalità edit
watch(() => props.editingListing, (newListing) => {
  if (newListing && props.isEdit) {
    // Delay per permettere al componente di montarsi
    nextTick(() => {
      initializeEditMode(newListing)
      // Forza un secondo nextTick per assicurarsi che tutto sia aggiornato
      nextTick(() => {
        // Forza l'aggiornamento dei componenti figli
        if (selectedCardModel.value) {
          // Dispatches event per aggiornare ChainedFilters con tutti i dati
          window.dispatchEvent(new CustomEvent('card-selected', { 
            detail: { 
              card: selectedCardModel.value,
              filters: filters.value,
              category: selectedCategory.value
            } 
          }))
          
          // Dispatches anche l'evento filters-populated per compatibilità
          console.log('🎯 Dispatching filters-populated con brand:', selectedCardModel.value.brand)
          window.dispatchEvent(new CustomEvent('filters-populated', { 
            detail: {
              team: selectedCardModel.value.team,
              card_set: selectedCardModel.value.card_set,
              player: selectedCardModel.value.player,
              rarity: selectedCardModel.value.rarity,
              year: selectedCardModel.value.year,
              brand: selectedCardModel.value.brand,
              number: selectedCardModel.value.card_number || selectedCardModel.value.card_number_in_set || selectedCardModel.value.number
            }
          }))
        }
      })
    })
  }
}, { immediate: true })

// Watch per controllo zone di spedizione quando il modal si apre
watch(() => props.isOpen, async (isOpen) => {
  if (isOpen) {
    console.log('🔄 Modal aperto, controllo zone di spedizione...')
    await checkShippingZones()
    if (hasShippingZones.value) {
      loadShippingZones()
    }
    
    // Se c'è una carta pre-selezionata (Sell Same Card), pre-popolare il form
    // Aspetta che il componente sia completamente montato
    if (props.preselectedCardModel) {
      await nextTick()
      await nextTick()
      // Usa setTimeout per assicurarsi che ChainedFilters sia montato e ascolti gli eventi
      setTimeout(() => {
        initializePreselectedCard()
      }, 100)
    }
  }
})

// Watch per rimuovere l'errore del prezzo quando viene inserito
watch(() => filters.value.price, (newPrice) => {
  if (priceError.value && newPrice) {
    priceError.value = false
  }
})

// Inizializza carta pre-selezionata per "Sell Same Card"
const initializePreselectedCard = async () => {
  try {
    console.log('🔄 Inizializzazione carta pre-selezionata:', props.preselectedCardModel)
    
    // Imposta la modalità single
    selectedMode.value = 'single'
    
    // Imposta la categoria se disponibile
    if (props.preselectedCardModel.category) {
      selectedCategory.value = props.preselectedCardModel.category
    }
    
    // Carica SEMPRE i dettagli completi dalla API per avere tutte le relazioni
    let cardModelData = null
    const cmId = props.preselectedCardModel.id
    if (cmId) {
      try {
        console.log('📡 Caricamento dati completi carta da API:', cmId)
        const resp = await fetch(`/api/card-models/${cmId}`)
        if (resp.ok) {
          const cmData = await resp.json()
          // L'API restituisce { success: true, data: { card_model: ... } }
          cardModelData = cmData.data?.card_model || cmData.data || cmData.card_model || cmData
          console.log('✅ Dati carta caricati:', {
            id: cardModelData.id,
            hasPlayer: !!cardModelData.player,
            hasTeam: !!cardModelData.team,
            hasCardSet: !!cardModelData.card_set,
            playerName: cardModelData.player?.name,
            teamName: cardModelData.team?.name,
            cardSetName: cardModelData.card_set?.name
          })
        } else {
          console.error('❌ Errore HTTP nel caricamento carta:', resp.status)
          // Fallback ai dati passati
          cardModelData = props.preselectedCardModel
        }
      } catch (e) {
        console.error('❌ Errore caricamento dettagli card model:', e)
        // Fallback ai dati passati
        cardModelData = props.preselectedCardModel
      }
    } else {
      cardModelData = props.preselectedCardModel
    }
    
    // Se ancora non abbiamo player o card_set, usa i dati fallback
    if (!cardModelData.player || !cardModelData.card_set) {
      console.warn('⚠️ Dati incompleti, uso dati passati come fallback')
      cardModelData = {
        ...cardModelData,
        ...props.preselectedCardModel
      }
    }
    
    // Carica le carte del player se necessario (come in edit mode)
    let playerWithCards = cardModelData.player
    if (playerWithCards && (!playerWithCards.cards || playerWithCards.cards.length === 0)) {
      try {
        const playerId = playerWithCards.id
        if (playerId) {
          // Usa lo stesso endpoint di edit mode
          const response = await fetch(`/api/${selectedCategory.value}/filters/players/${playerId}`, {
            headers: {
              'Accept': 'application/json',
              'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
          })
          
          if (response.ok) {
            const contentType = response.headers.get('content-type')
            if (contentType && contentType.includes('application/json')) {
              const data = await response.json()
              if (data.success && data.data?.player) {
                playerWithCards = data.data.player
                console.log('✅ Carte del giocatore caricate per sell same:', playerWithCards.cards?.length || 0)
              } else if (data.player) {
                playerWithCards = data.player
                console.log('✅ Carte del giocatore caricate per sell same (formato alternativo):', playerWithCards.cards?.length || 0)
              }
            } else {
              console.warn('⚠️ La risposta non è JSON, probabilmente non esiste l\'endpoint specifico')
            }
          } else {
            console.warn('⚠️ Errore HTTP nel caricamento carte del giocatore:', response.status)
          }
        }
      } catch (error) {
        console.warn('⚠️ Errore nel caricamento carte del giocatore (non critico):', error.message)
        // Non bloccare il flusso se non si riesce a caricare le carte
      }
    }
    
    // Imposta la carta selezionata
    selectedCardModel.value = cardModelData
    
    // Imposta il card_model_id
    listingData.value.card_model_id = cardModelData.id
    
    // Pre-popolare il prezzo dalla carta corrente se disponibile
    if (props.preselectedCardModel.price) {
      filters.value.price = props.preselectedCardModel.price
    }
    
    // Pre-popolare altri campi se disponibili
    if (props.preselectedCardModel.condition) {
      additionalDetails.value.condition = props.preselectedCardModel.condition
      listingData.value.condition = props.preselectedCardModel.condition
    }
    
    // Popola i filtri con i dati della carta (come in modalità edit)
    // IMPORTANTE: popola sia filters.value che dispatcha l'evento, come in edit mode
    filters.value = {
      ...filters.value,
      brand: cardModelData.card_set?.brand || cardModelData.brand || '',
      rarity: cardModelData.rarity || '',
      year: cardModelData.year || cardModelData.card_set?.year || '',
      number: cardModelData.card_number || cardModelData.card_number_in_set || '',
      player: cardModelData.player?.id || '',
      playerSearch: cardModelData.player?.display_name || cardModelData.player?.name || '',
      selectedPlayers: cardModelData.player ? [cardModelData.player] : [],
      team: cardModelData.team?.id || '',
      set: cardModelData.card_set?.id || ''
    }
    
    // Pre-popolare anche il prezzo se disponibile
    if (props.preselectedCardModel.price) {
      filters.value.price = props.preselectedCardModel.price
    }
    
    // Estrai brand dal card_set
    const brandFromSet = cardModelData.card_set?.brand || cardModelData.brand || ''
    
    // Non saltiamo lo step 1, mostriamo i campi già popolati
    // L'utente può vedere che i filtri sono già compilati e passare allo step successivo
    currentStep.value = 1 // Mostra lo step 1 con i campi già popolati
    
    // Aspetta che il componente sia completamente montato prima di dispatchare gli eventi
    await nextTick()
    await nextTick()
    
    // Usa setTimeout come in edit mode per assicurarsi che ChainedFilters sia completamente montato e ascolti gli eventi
    setTimeout(() => {
      console.log('🎯 Dispatching filters-populated per sell same card con:', {
        player: playerWithCards?.name || playerWithCards?.id || 'MISSING',
        team: cardModelData.team?.name || cardModelData.team?.id || 'MISSING',
        card_set: cardModelData.card_set?.name || cardModelData.card_set?.id || 'MISSING',
        rarity: cardModelData.rarity || 'MISSING',
        year: cardModelData.year || cardModelData.card_set?.year || 'MISSING',
        brand: brandFromSet || 'MISSING',
        number: cardModelData.card_number || cardModelData.card_number_in_set || 'MISSING'
      })
      
      // Verifica che tutti i dati necessari siano presenti
      if (!playerWithCards && !cardModelData.player) {
        console.error('❌ ERRORE: Player mancante nei dati!')
      }
      if (!cardModelData.team) {
        console.error('❌ ERRORE: Team mancante nei dati!')
      }
      if (!cardModelData.card_set) {
        console.error('❌ ERRORE: Card_set mancante nei dati!')
      }
      
      // Dispatches event 'filters-populated' per popolare i filtri in ChainedFilters (come in edit mode)
      // IMPORTANTE: passa il player con le carte caricate, non solo il player base
      // IMPORTANTE: passa gli oggetti completi con ID e proprietà necessarie (id, name)
      const eventData = {
        player: playerWithCards || cardModelData.player, // Usa il player con le carte caricate
        team: cardModelData.team, // Deve essere un oggetto con id e name
        card_set: cardModelData.card_set, // Deve essere un oggetto con id e name
        rarity: cardModelData.rarity,
        year: cardModelData.year || cardModelData.card_set?.year,
        brand: brandFromSet,
        number: cardModelData.card_number || cardModelData.card_number_in_set
      }
      
      console.log('📤 Dispatching event con dati:', JSON.stringify(eventData, null, 2))
      
      window.dispatchEvent(new CustomEvent('filters-populated', { 
        detail: eventData
      }))
      
      // Comunica esplicitamente la carta selezionata
      window.dispatchEvent(new CustomEvent('card-selected', { 
        detail: { 
          card: selectedCardModel.value,
          filters: filters.value,
          category: selectedCategory.value
        } 
      }))
    }, 600) // Timeout aumentato per dare più tempo
  } catch (error) {
    console.error('❌ Errore nell\'inizializzazione carta pre-selezionata:', error)
  }
}

// Inizializza modalità edit
const initializeEditMode = async (listing) => {
  try {
    console.log('🔄 Inizializzazione modalità edit con listing:', listing)
    
    // Imposta i dati dell'inserzione
    listingData.value = {
      card_model_id: listing.card_model_id,
      price: listing.price,
      condition: listing.condition,
      quantity: listing.quantity,
      language: listing.language,
      description: listing.description || '',
      is_foil: listing.is_foil,
      is_signed: listing.is_signed,
      is_altered: listing.is_altered,
      is_first_edition: listing.is_first_edition,
      is_negotiable: listing.is_negotiable
    }
    
    // Imposta la carta selezionata con fallback a fetch dettagli
    selectedCardModel.value = listing.card_model
    if (!selectedCardModel.value || !selectedCardModel.value.player || !selectedCardModel.value.card_set) {
      try {
        const cmId = listing.card_model_id || listing.card_model?.id
        if (cmId) {
          const resp = await fetch(`/api/card-models/${cmId}`)
          if (resp.ok) {
            const cmData = await resp.json()
            selectedCardModel.value = cmData.data || cmData
          }
        }
      } catch (e) {
        console.error('❌ Errore caricamento dettagli card model:', e)
      }
    }
    
    // Imposta la categoria basata sulla carta
    if (listing.card_model?.category?.name) {
      const categoryName = listing.card_model.category.name.toLowerCase()
      if (categoryName.includes('calcio') || categoryName.includes('football')) {
        selectedCategory.value = 'football'
      } else if (categoryName.includes('basketball') || categoryName.includes('basket')) {
        selectedCategory.value = 'basketball'
      } else if (categoryName.includes('pokemon')) {
        selectedCategory.value = 'pokemon'
      }
    }
    
    // Imposta i filtri con i dati della carta per compatibilità
    filters.value = {
      ...filters.value,
      price: listing.price,
      condition: listing.condition,
      brand: selectedCardModel.value?.card_set?.brand || selectedCardModel.value?.brand || '',
      rarity: selectedCardModel.value?.rarity || '',
      year: selectedCardModel.value?.year || selectedCardModel.value?.card_set?.year || '',
      number: selectedCardModel.value?.card_number || selectedCardModel.value?.card_number_in_set || '',
      player: selectedCardModel.value?.player?.id || '',
      playerSearch: selectedCardModel.value?.player?.display_name || selectedCardModel.value?.player?.name || '',
      team: selectedCardModel.value?.team?.id || '',
      set: selectedCardModel.value?.card_set?.id || ''
    }
    
    
    // Imposta additionalDetails con i dati dell'inserzione
    additionalDetails.value = {
      condition: listing.condition,
      gradingCompany: listing.grading_company || '',
      gradingScore: listing.grading_score || '',
      notes: listing.description || '',
      // Caratteristiche speciali
      autograph: listing.is_signed ? 'yes' : 'no',
      relic: listing.is_altered ? 'yes' : 'no',
      onCardAuto: listing.is_signed ? 'yes' : 'no',
      rookie: listing.is_first_edition ? 'yes' : 'no',
      jewel: listing.is_foil ? 'yes' : 'no',
      multiAutograph: '',
      description: listing.description || ''
    }
    
    // Imposta le zone di spedizione
    if (listing.shipping_zones) {
      selectedShippingZones.value = listing.shipping_zones.map(zone => zone.id)
    }
    
    // Imposta le immagini se presenti (sono memorizzate come array JSON)
    if (listing.images && Array.isArray(listing.images) && listing.images.length > 0) {
      // Converti le immagini esistenti nel formato corretto
      cardImages.value = listing.images.map((imageUrl, index) => {
        if (index < 4 && imageUrl) {
          return {
            file: null, // Non abbiamo il file originale
            preview: imageUrl.startsWith('/storage/') ? imageUrl : `/storage/${imageUrl}`, // Assicura il prefisso corretto
            isExisting: true // Flag per identificare le immagini esistenti
          }
        }
        return null
      })
      console.log('📸 Immagini esistenti caricate:', cardImages.value)
    }
    
    // Vai direttamente al primo step (selezione carta)
    currentStep.value = 1
    selectedMode.value = 'single'
    
    
    // Dispatch event per popolare i filtri nel componente ChainedFilters
    if (selectedCardModel.value) {
      const brandFromSet = selectedCardModel.value.card_set?.brand
      
      // Carica le carte del giocatore se non sono già caricate
      let playerWithCards = selectedCardModel.value.player
      if (playerWithCards && (!playerWithCards.cards || playerWithCards.cards.length === 0)) {
        try {
          const response = await fetch(`/api/${selectedCategory.value}/filters/players/${playerWithCards.id}`, {
            headers: {
              'Accept': 'application/json',
              'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
          })
          
          if (response.ok) {
            const contentType = response.headers.get('content-type')
            if (contentType && contentType.includes('application/json')) {
              const data = await response.json()
              if (data.success && data.data?.player) {
                playerWithCards = data.data.player
                console.log('✅ Carte del giocatore caricate per edit:', playerWithCards.cards?.length || 0)
              } else if (data.player) {
                playerWithCards = data.player
                console.log('✅ Carte del giocatore caricate per edit (formato alternativo):', playerWithCards.cards?.length || 0)
              }
            } else {
              console.warn('⚠️ La risposta non è JSON, probabilmente non esiste l\'endpoint specifico')
            }
          } else {
            console.warn('⚠️ Errore HTTP nel caricamento carte del giocatore:', response.status)
          }
        } catch (error) {
          console.warn('⚠️ Errore nel caricamento carte del giocatore (non critico):', error.message)
          // Non bloccare il flusso se non si riesce a caricare le carte
        }
      }
      
      // Usa setTimeout per assicurarsi che il componente ChainedFilters sia montato e i listener attivi
      setTimeout(() => {
        console.log('🎯 Dispatching filters-populated con player:', playerWithCards?.name || playerWithCards?.id)
        window.dispatchEvent(new CustomEvent('filters-populated', { 
          detail: {
            team: selectedCardModel.value.team,
            card_set: selectedCardModel.value.card_set,
            player: playerWithCards, // Usa il giocatore con le carte caricate
            rarity: filters.value.rarity,
            year: filters.value.year,
            brand: brandFromSet,
            number: filters.value.number
          }
        }))
        // Comunica esplicitamente la carta selezionata
        window.dispatchEvent(new CustomEvent('card-selected', { detail: { card: selectedCardModel.value } }))
      }, 300) // Aumentato timeout per assicurarsi che il componente sia montato
    }
  } catch (error) {
    console.error('❌ Errore nell\'inizializzazione modalità edit:', error)
  }
}

// Aggiorna inserzione esistente
const updateSingleListing = async () => {
  try {
    isSubmitting.value = true
    console.log('💾 Aggiornamento inserzione:', props.editingListing.id)
    
    // Usa FormData per supportare le immagini
    const formData = new FormData()
    
    // Aggiungi i dati dell'inserzione
    formData.append('price', listingData.value.price)
    formData.append('condition', listingData.value.condition)
    formData.append('quantity', listingData.value.quantity)
    formData.append('language', listingData.value.language)
    formData.append('description', listingData.value.description || '')
    formData.append('is_foil', listingData.value.is_foil ? 'true' : 'false')
    formData.append('is_signed', listingData.value.is_signed ? 'true' : 'false')
    formData.append('is_altered', listingData.value.is_altered ? 'true' : 'false')
    formData.append('is_first_edition', listingData.value.is_first_edition ? 'true' : 'false')
    formData.append('is_negotiable', listingData.value.is_negotiable ? 'true' : 'false')
    
    // Aggiungi solo le nuove immagini (quelle con file e non esistenti) - comprimi SEMPRE se > 500KB
    const newImages = cardImages.value.filter(image => image && image.file && !image.isExisting)
    
    let totalSize = 0
    for (const image of newImages) {
      try {
        const fileSizeMB = image.file.size / 1024 / 1024
        console.log(`🖼️ Immagine nuova per update: ${fileSizeMB.toFixed(2)}MB`)
        
        // Comprimi sempre se > 500KB o se la dimensione totale supererebbe 3MB
        const shouldCompress = image.file.size > 500 * 1024 || totalSize + image.file.size > 3 * 1024 * 1024
        
        if (shouldCompress) {
          console.log(`📦 Compressione immagine per update...`)
          const compressedFile = await compressImageForUpload(image.file)
          const compressedSizeMB = compressedFile.size / 1024 / 1024
          console.log(`✅ Immagine compressa: ${compressedSizeMB.toFixed(2)}MB`)
          formData.append('images[]', compressedFile)
          totalSize += compressedFile.size
          
          // Verifica che dopo compressione non sia ancora troppo grande
          if (compressedFile.size > 1.5 * 1024 * 1024) {
            throw new Error(`Immagine ancora troppo grande dopo compressione: ${compressedSizeMB.toFixed(2)}MB`)
          }
        } else {
          formData.append('images[]', image.file)
          totalSize += image.file.size
        }
      } catch (error) {
        console.error('❌ Errore compressione immagine:', error)
        // Se la compressione fallisce, NON inviare se è troppo grande
        if (image.file.size < 2 * 1024 * 1024) {
          formData.append('images[]', image.file)
          totalSize += image.file.size
        } else {
          alert(`❌ L'immagine è troppo grande (${(image.file.size / 1024 / 1024).toFixed(2)}MB) e non può essere compressa. Per favore, usa un'immagine più piccola.`)
          throw error
        }
      }
    }
    
    console.log(`📦 Dimensione totale immagini per update: ${(totalSize / 1024 / 1024).toFixed(2)}MB`)
    
    // Verifica dimensione totale
    if (totalSize > 4 * 1024 * 1024) {
      alert(`⚠️ Attenzione: La dimensione totale delle immagini (${(totalSize / 1024 / 1024).toFixed(2)}MB) è troppo grande.`)
      throw new Error('Dimensione totale immagini troppo grande')
    }
    
    // Aggiungi le zone di spedizione
    selectedShippingZones.value.forEach(zoneId => {
      formData.append('shipping_zones[]', zoneId)
    })
    
    // Aggiungi _method=PUT per Laravel
    formData.append('_method', 'PUT')
    
    // Usa POST invece di PUT per FormData (Laravel non processa correttamente PUT con multipart)
    const response = await axios.post(`/api/listings/${props.editingListing.id}`, formData, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'multipart/form-data'
      }
    })
    
    // Axios restituisce direttamente i dati
    const data = response.data
    console.log('✅ Inserzione aggiornata:', data)
    
    if (data.success) {
      emit('updated', data.data)
      closeModal()
    } else {
      throw new Error(data.message || 'Errore nell\'aggiornamento dell\'inserzione')
    }
  } catch (error) {
    console.error('❌ Errore nell\'aggiornamento inserzione:', error)
    
    // Gestione errori dettagliata
    let errorMessage = 'Errore nell\'aggiornamento dell\'inserzione.'
    
    if (error.response) {
      // Errore HTTP con risposta
      const errorData = error.response.data || {}
      if (errorData.errors) {
        const errorDetails = Object.entries(errorData.errors)
          .map(([field, messages]) => `${field}: ${Array.isArray(messages) ? messages.join(', ') : messages}`)
          .join('\n')
        errorMessage = `Errore nell'aggiornamento:\n\n${errorDetails}`
      } else if (errorData.message) {
        errorMessage = `Errore nell'aggiornamento:\n\n${errorData.message}`
      } else {
        errorMessage = `Errore HTTP ${error.response.status}: ${error.response.statusText}`
      }
    } else if (error.message) {
      errorMessage = `Errore nell'aggiornamento:\n\n${error.message}`
    }
    
    alert(errorMessage)
  } finally {
    isSubmitting.value = false
  }
}
</script>
