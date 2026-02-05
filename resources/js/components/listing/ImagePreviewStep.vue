<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="text-center">
      <h3 class="text-lg font-medium text-gray-900 mb-2">
        {{ isBulkMode ? 'Preview e Immagini - Bulk Cards' : 'Preview e Immagini - Single Card' }}
      </h3>
      <p class="text-sm text-gray-600">
        {{ isBulkMode 
          ? 'Aggiungi immagini e rivedi le carte selezionate prima di pubblicare' 
          : 'Aggiungi immagini e dettagli aggiuntivi per la tua carta' 
        }}
      </p>
    </div>

    <!-- Single Card Mode -->
    <div v-if="!isBulkMode" class="space-y-6">
      <!-- Main Image and Details Layout -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left Side: Image Gallery -->
        <div class="space-y-4">
          <!-- Main Image Display -->
          <div class="aspect-[3/4] bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center relative overflow-hidden">
            <!-- Show first uploaded image if available -->
            <img 
              v-if="getFirstUploadedImage" 
              :src="getFirstUploadedImage" 
              :alt="'Card preview'"
              class="w-full h-full object-cover rounded-lg"
            />
            <!-- Show upload placeholder if no images -->
            <div v-else class="text-center">
              <svg class="mx-auto h-16 w-16 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
              <p class="mt-2 text-sm text-gray-500">Carica immagini</p>
            </div>
          </div>
          
          <!-- Thumbnail Grid -->
          <div class="grid grid-cols-4 gap-2">
            <div 
              v-for="(image, index) in cardImages" 
              :key="index" 
              class="aspect-[3/4] bg-gray-100 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center relative cursor-pointer hover:border-primary transition-colors"
              :class="{ 'border-primary': image }"
              @click="() => $refs[`imageInput${index}`][0].click()"
            >
              <div v-if="!image" class="text-center">
                <svg class="mx-auto h-6 w-6 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                <p class="mt-1 text-xs text-gray-500">Immagine {{ index + 1 }}</p>
              </div>
              <img v-else :src="image.preview" :alt="`Card image ${index + 1}`" class="w-full h-full object-cover rounded-lg">
              
              <!-- Remove button for thumbnails -->
              <button 
                v-if="image" 
                @click.stop="removeCardImage(index)" 
                class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600"
              >
                ×
              </button>
              
              <!-- Hidden file input -->
              <input
                :ref="`imageInput${index}`"
                type="file"
                accept="image/*"
                @change="(e) => handleCardImageUpload(e, index)"
                class="hidden"
              />
            </div>
          </div>
        </div>

        <!-- Right Side: Additional Details -->
        <div class="space-y-6">
          <div class="bg-gray-50 rounded-lg p-6">
            <h4 class="text-md font-medium text-gray-900 mb-4">Dettagli Aggiuntivi</h4>
            <div class="space-y-4">
              <!-- Grading Company -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Grading Company</label>
                <select v-model="additionalDetails.gradingCompany" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
                  <option value="">Seleziona grading company</option>
                  <option v-for="company in gradingCompanies" :key="company.id" :value="company.id">
                    {{ company.name }}
                  </option>
                </select>
              </div>

              <!-- Condizioni testuali (solo quando NON c'è grading company) -->
              <template v-if="!additionalDetails.gradingCompany">
                <!-- Condition (Condizione carta) -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Condizione carta</label>
                  <select v-model="additionalDetails.condition" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
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

                <!-- Autograph Condition (Condizione autografo) -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Condizione autografo</label>
                  <select v-model="additionalDetails.autographCondition" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
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
              </template>

              <!-- Campi numerici (solo quando C'È grading company) -->
              <template v-if="additionalDetails.gradingCompany">
                <!-- Card Condition Score (0-10 con incrementi 0.5) -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Condizione carta (0-10)</label>
                  <select v-model="additionalDetails.cardConditionScore" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
                    <option value="">Seleziona condizione</option>
                    <option v-for="score in cardConditionScores" :key="score" :value="score">{{ score }}</option>
                  </select>
                </div>

                <!-- Autograph Condition Score (0-10 con incrementi 0.5) -->
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Condizione autografo (0-10)</label>
                  <select v-model="additionalDetails.autographConditionScore" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
                    <option value="">Seleziona condizione</option>
                    <option v-for="score in autographConditionScores" :key="score" :value="score">{{ score }}</option>
                  </select>
                </div>
              </template>

              <!-- Separatore Filtri Extra -->
              <div class="border-t border-gray-200 pt-4 mt-4">
                <h5 class="text-sm font-semibold text-gray-900 mb-3">Caratteristiche Speciali</h5>
              </div>

              <!-- Autograph -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Autograph</label>
                <select v-model="additionalDetails.autograph" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
                  <option value="">Non specificato</option>
                  <option value="yes">Sì</option>
                  <option value="no">No</option>
                </select>
              </div>

              <!-- Relic (solo per categorie sportive) -->
              <div v-if="!['disney', 'spongebob'].includes(props.category)">
                <label class="block text-sm font-medium text-gray-700 mb-2">Relic</label>
                <select v-model="additionalDetails.relic" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
                  <option value="">Non specificato</option>
                  <option value="yes">Sì</option>
                  <option value="no">No</option>
                </select>
              </div>

              <!-- On Card Auto -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">On Card Auto</label>
                <select v-model="additionalDetails.onCardAuto" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
                  <option value="">Non specificato</option>
                  <option value="yes">Sì</option>
                  <option value="no">No</option>
                </select>
              </div>

              <!-- Rookie (solo per categorie sportive) -->
              <div v-if="!['disney', 'spongebob'].includes(props.category)">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rookie</label>
                <select v-model="additionalDetails.rookie" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
                  <option value="">Non specificato</option>
                  <option value="yes">Sì</option>
                  <option value="no">No</option>
                </select>
              </div>

              <!-- Sketch (solo per Disney e Spongebob) -->
              <div v-if="['disney', 'spongebob'].includes(props.category)">
                <label class="block text-sm font-medium text-gray-700 mb-2">Sketch</label>
                <select v-model="additionalDetails.sketch" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
                  <option value="">Non specificato</option>
                  <option value="yes">Sì</option>
                  <option value="no">No</option>
                </select>
              </div>

              <!-- Jewel -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Jewel</label>
                <select v-model="additionalDetails.jewel" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
                  <option value="">Non specificato</option>
                  <option value="yes">Sì</option>
                  <option value="no">No</option>
                </select>
              </div>

              <!-- Multi-Autograph -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Multi-Autograph</label>
                <select v-model="additionalDetails.multiAutograph" class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm">
                  <option value="">Non specificato</option>
                  <option value="dual">Dual</option>
                  <option value="triple">Triple</option>
                  <option value="quad">Quad</option>
                  <option value="booklet">Booklet</option>
                </select>
              </div>

              <!-- Notes -->
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Note</label>
                <textarea
                  v-model="additionalDetails.notes"
                  rows="4"
                  placeholder="Note aggiuntive sulla carta..."
                  class="block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-base text-gray-900 focus:border-primary focus:outline-none sm:text-sm"
                ></textarea>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bulk Cards Mode -->
    <div v-else class="space-y-6">
      <!-- Selected Cards Summary -->
      <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="text-md font-medium text-gray-900 mb-4">Carte Selezionate ({{ selectedCards.length }})</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div v-for="(card, index) in selectedCards" :key="index" class="bg-white rounded-lg p-4 border border-gray-200">
            <div class="flex justify-between items-start mb-2">
              <h5 class="text-sm font-medium text-gray-900">{{ card.player?.name || 'N/A' }}</h5>
              <span class="text-xs text-gray-500">/{{ card.number || 'N/A' }}</span>
            </div>
            <div class="text-xs text-gray-600 space-y-1">
              <div>{{ card.team?.name || 'N/A' }}</div>
              <div>{{ card.set?.name || 'N/A' }}</div>
              <div class="font-semibold text-primary">€{{ formatPriceItaliana(card.price || 0) }}</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bulk Image Upload -->
      <div class="bg-gray-50 rounded-lg p-6">
        <h4 class="text-md font-medium text-gray-900 mb-4">Carica Immagini in Blocco</h4>
        <div class="space-y-4">
          <!-- Upload Instructions -->
          <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">Istruzioni per il caricamento</h3>
                <div class="mt-2 text-sm text-blue-700">
                  <ul class="list-disc list-inside space-y-1">
                    <li>Seleziona tutte le immagini delle carte in una volta</li>
                    <li>Le immagini verranno associate alle carte nell'ordine di selezione</li>
                    <li>Formati supportati: PNG, JPG, JPEG (max 10MB per immagine)</li>
                    <li>Puoi caricare fino a {{ selectedCards.length }} immagini</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>

          <!-- Drag & Drop Upload Area -->
          <div 
            ref="bulkDropZone"
            class="relative border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-primary transition-colors"
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
                  Carica fino a {{ selectedCards.length }} immagini (PNG, JPG, JPEG - max 10MB ciascuna)
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
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { formatPriceItaliana } from '../../utils/priceFormatter'

// Props
const props = defineProps({
  isBulkMode: {
    type: Boolean,
    default: false
  },
  cardData: {
    type: Object,
    default: () => ({})
  },
  selectedCards: {
    type: Array,
    default: () => []
  },
  gradingCompanies: {
    type: Array,
    default: () => []
  },
  category: {
    type: String,
    default: 'football'
  }
})



// Emits
const emit = defineEmits(['image-uploaded', 'bulk-images-uploaded', 'additional-details-changed'])

// Refs
const imageInput = ref(null)
const bulkImageInput = ref(null)

// Single Card Image
const cardImage = ref(null)
const cardImagePreview = ref(null)
const cardImages = ref([null, null, null, null]) // Array of 4 image objects

// Bulk Images
const bulkImages = ref([])

// Drag & Drop state
const isDragOver = ref(false)

// Additional Details (Single Card)
const additionalDetails = ref({
  condition: '',
  autographCondition: '',
  gradingCompany: '',
  gradingScore: '',
  cardConditionScore: '',
  autographConditionScore: '',
  // Filtri Extra
  autograph: '',
  relic: '',
  onCardAuto: '',
  rookie: '',
  jewel: '',
  sketch: '',
  multiAutograph: '',
  description: '',
  notes: ''
})

// Genera array di score da 0 a 10 con incrementi di 0.5 + "AUTH"
const cardConditionScores = computed(() => {
  const scores = []
  // Aggiungi i voti numerici da 0 a 10 con incrementi di 0.5
  for (let i = 0; i <= 20; i++) {
    scores.push((i * 0.5).toFixed(1))
  }
  // Aggiungi "AUTH" alla fine
  scores.push('AUTH')
  return scores
})

const autographConditionScores = computed(() => {
  const scores = []
  // Aggiungi i voti numerici da 0 a 10 con incrementi di 0.5
  for (let i = 0; i <= 20; i++) {
    scores.push((i * 0.5).toFixed(1))
  }
  // Aggiungi "AUTH" alla fine
  scores.push('AUTH')
  return scores
})

// Flag per evitare loop infiniti
const isInitializing = ref(false)
const hasInitialized = ref(false) // Flag per tracciare se l'inizializzazione è già stata fatta

// Watch per cardData per popolare i campi esistenti in modalità edit
watch(() => props.cardData, (newCardData) => {
  // Evita loop infiniti
  if (isInitializing.value) {
    return
  }
  
  // Se abbiamo già inizializzato E ci sono nuove immagini caricate (non existing), NON fare nulla
  if (hasInitialized.value) {
    const hasNewImages = cardImages.value.some(img => img && !img.isExisting)
    if (hasNewImages) {
      // Ci sono nuove immagini caricate manualmente, non interferire MAI
      return
    }
  }
  
  // Popola solo se sono immagini esistenti (non nuove immagini caricate)
  if (newCardData && newCardData.existingImages && newCardData.existingImages.length > 0) {
    // Controlla se le immagini esistenti sono già state caricate
    const hasExistingImages = cardImages.value.some(img => img && img.isExisting)
    
    if (!hasExistingImages) {
      isInitializing.value = true
      
      // Popola cardImages con le immagini esistenti
      newCardData.existingImages.forEach((image, index) => {
        if (index < 4 && image) {
          // Se l'immagine è già un oggetto con preview, usalo direttamente
          if (image && typeof image === 'object' && image.preview) {
            // Assicurati che l'URL abbia il prefisso /storage/
            const imageWithCorrectUrl = {
              ...image,
              preview: image.preview.startsWith('/storage/') ? image.preview : `/storage/${image.preview}`
            }
            cardImages.value[index] = imageWithCorrectUrl
          } else if (typeof image === 'string') {
            // Se è una stringa (URL), crea un oggetto immagine
            cardImages.value[index] = {
              file: null, // Non abbiamo il file originale
              preview: image.startsWith('/storage/') ? image : `/storage/${image}`,
              isExisting: true // Flag per identificare le immagini esistenti
            }
          } else if (image instanceof File) {
            // Se è un File, crea l'oggetto immagine
            cardImages.value[index] = {
              file: image,
              preview: URL.createObjectURL(image),
              isExisting: false
            }
          }
        }
      })
      
      hasInitialized.value = true
      isInitializing.value = false
    }
  }
  
  // Popola additionalDetails quando arrivano nuovi dati
  // IMPORTANTE: In modalità edit, popola sempre quando arrivano dati nuovi
  if (newCardData) {
    // Controlla se ci sono valori nuovi da applicare (non vuoti)
    const hasNewValues = newCardData.autograph || newCardData.relic || newCardData.rookie || 
                         newCardData.jewel || newCardData.onCardAuto || newCardData.multiAutograph ||
                         newCardData.sketch || newCardData.condition || newCardData.gradingCompany
    
    // Se ci sono nuovi valori, resetta hasInitialized per forzare il popolamento
    if (hasNewValues && hasInitialized.value) {
      console.log('Reset hasInitialized per popolare con nuovi valori dal CardModel')
      hasInitialized.value = false
    }
    
    // Popola se:
    // 1. Non è ancora stato inizializzato, OPPURE
    // 2. Ci sono nuovi valori da applicare (per supportare il ricaricamento in edit mode)
    if (!hasInitialized.value || hasNewValues) {
      // Popola sempre se ci sono nuovi valori, altrimenti solo se i campi sono vuoti
      const shouldPopulate = !hasInitialized.value || hasNewValues
    
    if (shouldPopulate) {
        // Se ci sono nuovi valori, usa quelli, altrimenti mantieni quelli esistenti
      additionalDetails.value = {
          condition: newCardData.condition !== undefined ? newCardData.condition : (additionalDetails.value.condition || ''),
        // NON usare condition come fallback per autographCondition
          autographCondition: newCardData.autographCondition !== undefined ? newCardData.autographCondition : (additionalDetails.value.autographCondition || ''),
          gradingCompany: newCardData.gradingCompany !== undefined ? newCardData.gradingCompany : (additionalDetails.value.gradingCompany || ''),
          gradingScore: newCardData.gradingScore !== undefined ? newCardData.gradingScore : (additionalDetails.value.gradingScore || ''),
          cardConditionScore: newCardData.cardConditionScore !== undefined ? newCardData.cardConditionScore : (additionalDetails.value.cardConditionScore || ''),
          autographConditionScore: newCardData.autographConditionScore !== undefined ? newCardData.autographConditionScore : (additionalDetails.value.autographConditionScore || ''),
          // Filtri Extra - usa i nuovi valori se presenti (anche se vuoti), altrimenti mantieni quelli esistenti
          autograph: newCardData.autograph !== undefined ? newCardData.autograph : (additionalDetails.value.autograph || ''),
          relic: newCardData.relic !== undefined ? newCardData.relic : (additionalDetails.value.relic || ''),
          onCardAuto: newCardData.onCardAuto !== undefined ? newCardData.onCardAuto : (additionalDetails.value.onCardAuto || ''),
          rookie: newCardData.rookie !== undefined ? newCardData.rookie : (additionalDetails.value.rookie || ''),
          jewel: newCardData.jewel !== undefined ? newCardData.jewel : (additionalDetails.value.jewel || ''),
          sketch: newCardData.sketch !== undefined ? newCardData.sketch : (additionalDetails.value.sketch || ''),
          multiAutograph: newCardData.multiAutograph !== undefined ? newCardData.multiAutograph : (additionalDetails.value.multiAutograph || ''),
          description: newCardData.description !== undefined ? newCardData.description : (additionalDetails.value.description || ''),
          notes: newCardData.notes !== undefined ? newCardData.notes : (additionalDetails.value.notes || '')
        }
        
        console.log('ImagePreviewStep - additionalDetails popolato:', {
          autograph: additionalDetails.value.autograph,
          rookie: additionalDetails.value.rookie,
          relic: additionalDetails.value.relic,
          jewel: additionalDetails.value.jewel,
          sketch: additionalDetails.value.sketch,
          fromCardData: {
            autograph: newCardData.autograph,
            rookie: newCardData.rookie,
            relic: newCardData.relic,
            jewel: newCardData.jewel,
            sketch: newCardData.sketch
          },
          hasNewValues,
          wasInitialized: hasInitialized.value
        })
        
        // Marca come inizializzato dopo il popolamento
      hasInitialized.value = true
      }
    }
  }
  
  // Aggiorna sempre le note se presenti nei dati (per supportare la modifica di listing esistenti)
  if (newCardData && newCardData.notes !== undefined && newCardData.notes !== null) {
    additionalDetails.value.notes = newCardData.notes
  }
}, { immediate: true, deep: false, flush: 'post' }) // Aggiunto flush: 'post' per eseguire dopo il rendering


// Methods
const handleImageUpload = (event) => {
  const file = event.target.files[0]
  if (file) {
    // Validate file size (10MB max - il backend comprimerà automaticamente)
    if (file.size > 10 * 1024 * 1024) {
      alert('Il file è troppo grande. Dimensione massima: 10MB')
      return
    }

    // Validate file type
    if (!file.type.startsWith('image/')) {
      alert('Seleziona un file immagine valido')
      return
    }

    cardImage.value = file
    cardImagePreview.value = URL.createObjectURL(file)
    
    emit('image-uploaded', {
      file,
      preview: cardImagePreview.value
    })
  }
}

const handleCardImageUpload = (event, index) => {
  const file = event.target.files[0]
  if (file) {
    // Validate file size (10MB max - il backend comprimerà automaticamente)
    if (file.size > 10 * 1024 * 1024) {
      alert('Il file è troppo grande. Dimensione massima: 10MB')
      return
    }

    // Validate file type
    if (!file.type.startsWith('image/')) {
      alert('Seleziona un file immagine valido')
      return
    }

    // Imposta flag per evitare che il watch interferisca
    isInitializing.value = true

    // Create image object
    const imageData = {
      file,
      preview: URL.createObjectURL(file),
      isExisting: false // Nuova immagine
    }
    
    // Update the specific image slot
    cardImages.value[index] = imageData
    
    // Reset flag dopo un breve delay per permettere al watch di completare
    setTimeout(() => {
      isInitializing.value = false
    }, 100)
    
    // Emit the updated images array
    emit('image-uploaded', cardImages.value)
    
    // Reset input per permettere di caricare lo stesso file di nuovo
    event.target.value = ''
  }
}

const removeCardImage = (index) => {
  if (cardImages.value[index]) {
    // Revoke the object URL to free memory
    URL.revokeObjectURL(cardImages.value[index].preview)
  }
  cardImages.value[index] = null
  
  // Emit the updated images array
  emit('image-uploaded', cardImages.value)
}

const handleBulkImageUpload = (event) => {
  const files = Array.from(event.target.files)
  
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

  emit('bulk-images-uploaded', bulkImages.value)
}

// Get first uploaded image for main display - convertito in computed per evitare chiamate continue
const getFirstUploadedImage = computed(() => {
  // Cerca la prima immagine caricata nell'array cardImages
  const firstImage = cardImages.value.find(img => img && img.preview)
  return firstImage ? firstImage.preview : null
})

// Drag & Drop methods
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

  emit('bulk-images-uploaded', bulkImages.value)
}

// Watch for changes
watch(additionalDetails, (newDetails) => {
  emit('additional-details-changed', newDetails)
}, { deep: true })

</script>
