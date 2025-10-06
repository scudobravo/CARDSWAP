<template>
  <div class="space-y-6">
    <!-- Header con controlli bulk -->
    <div class="flex items-center justify-between">
      <div class="flex items-center space-x-4">
        <h4 class="text-lg font-semibold text-gray-900">
          Inserzioni Selezionate ({{ selectedListings.length }})
        </h4>
        <button 
          @click="selectAll"
          class="text-sm text-primary hover:text-primary-dark font-medium"
        >
          Seleziona Tutto
        </button>
        <button 
          @click="clearSelection"
          class="text-sm text-gray-500 hover:text-gray-700 font-medium"
        >
          Deseleziona Tutto
        </button>
      </div>
      
      <div class="flex items-center space-x-2">
        <button 
          @click="bulkEdit"
          :disabled="selectedListings.length === 0"
          class="px-3 py-1 text-sm bg-primary text-white rounded hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Modifica Bulk
        </button>
        <button 
          @click="removeSelected"
          :disabled="selectedListings.length === 0"
          class="px-3 py-1 text-sm bg-red-500 text-white rounded hover:bg-red-600 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Rimuovi Selezionate
        </button>
      </div>
    </div>

    <!-- Tabella modificabile -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-3 py-3 text-left">
              <input 
                type="checkbox" 
                :checked="allSelected"
                @change="toggleAllSelection"
                class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
              />
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Carta
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Prezzo (€)
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Quantità
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Condizione
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Lingua
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Caratteristiche
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Azioni
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr 
            v-for="(listing, index) in listings" 
            :key="listing.id || index"
            class="hover:bg-gray-50"
          >
            <!-- Checkbox -->
            <td class="px-3 py-4">
              <input 
                type="checkbox" 
                :checked="isSelected(listing)"
                @change="toggleSelection(listing)"
                class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
              />
            </td>
            
            <!-- Carta -->
            <td class="px-6 py-4">
              <div class="flex items-center space-x-3">
                <img 
                  :src="listing.cardModel?.image_url || '/images/placeholder-card.jpg'" 
                  :alt="listing.cardModel?.name"
                  class="w-12 h-16 object-cover rounded"
                />
                <div class="min-w-0 flex-1">
                  <p class="text-sm font-medium text-gray-900 truncate">
                    {{ listing.cardModel?.name || 'Modello non selezionato' }}
                  </p>
                  <p class="text-sm text-gray-500">
                    {{ listing.cardModel?.set_name }} {{ listing.cardModel?.year }}
                  </p>
                  <p class="text-xs text-gray-400">
                    {{ listing.cardModel?.rarity }}
                  </p>
                </div>
              </div>
            </td>
            
            <!-- Prezzo -->
            <td class="px-6 py-4">
              <input 
                v-model="listing.price"
                type="number"
                step="0.01"
                min="0"
                class="w-20 text-sm border border-gray-300 rounded px-2 py-1 focus:border-primary focus:outline-none"
                @blur="validatePrice(listing)"
              />
            </td>
            
            <!-- Quantità -->
            <td class="px-6 py-4">
              <input 
                v-model="listing.quantity"
                type="number"
                min="1"
                class="w-16 text-sm border border-gray-300 rounded px-2 py-1 focus:border-primary focus:outline-none"
                @blur="validateQuantity(listing)"
              />
            </td>
            
            <!-- Condizione -->
            <td class="px-6 py-4">
              <select 
                v-model="listing.condition"
                class="text-sm border border-gray-300 rounded px-2 py-1 focus:border-primary focus:outline-none"
              >
                <option value="">Seleziona</option>
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
            </td>
            
            <!-- Lingua -->
            <td class="px-6 py-4">
              <select 
                v-model="listing.language"
                class="text-sm border border-gray-300 rounded px-2 py-1 focus:border-primary focus:outline-none"
              >
                <option value="">Seleziona</option>
                <option value="italiano">Italiano</option>
                <option value="inglese">Inglese</option>
                <option value="spagnolo">Spagnolo</option>
                <option value="francese">Francese</option>
                <option value="tedesco">Tedesco</option>
                <option value="portoghese">Portoghese</option>
              </select>
            </td>
            
            <!-- Caratteristiche -->
            <td class="px-6 py-4">
              <div class="flex flex-wrap gap-1">
                <span 
                  v-if="listing.is_foil" 
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                >
                  Foil
                </span>
                <span 
                  v-if="listing.is_signed" 
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800"
                >
                  Firmata
                </span>
                <span 
                  v-if="listing.is_altered" 
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                >
                  Alterata
                </span>
                <span 
                  v-if="listing.is_first_edition" 
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                >
                  1° Ed.
                </span>
                <span 
                  v-if="listing.is_negotiable" 
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                >
                  Negoziabile
                </span>
              </div>
            </td>
            
            <!-- Azioni -->
            <td class="px-6 py-4">
              <div class="flex items-center space-x-2">
                <button 
                  @click="editListing(listing, index)"
                  class="text-primary hover:text-primary-dark text-sm font-medium"
                >
                  Modifica
                </button>
                <button 
                  @click="removeListing(index)"
                  class="text-red-500 hover:text-red-700 text-sm font-medium"
                >
                  Rimuovi
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pulsante Aggiungi -->
    <div class="text-center">
      <button 
        @click="addNewListing"
        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
        </svg>
        Aggiungi Inserzione
      </button>
    </div>

    <!-- Modal per modifica singola -->
    <div v-if="editingListing" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeEditModal"></div>
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl transform overflow-hidden rounded-lg bg-white shadow-xl">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Modifica Inserzione</h3>
          </div>
          <div class="px-6 py-6">
            <EditListingForm 
              :listing="editingListing"
              @save="saveListing"
              @cancel="closeEditModal"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Modal per modifica bulk -->
    <div v-if="bulkEditMode" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="fixed inset-0 bg-black bg-opacity-50" @click="closeBulkEdit"></div>
      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative w-full max-w-2xl transform overflow-hidden rounded-lg bg-white shadow-xl">
          <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Modifica Bulk ({{ selectedListings.length }} inserzioni)</h3>
          </div>
          <div class="px-6 py-6">
            <BulkEditForm 
              :listings="selectedListings"
              @apply="applyBulkEdit"
              @cancel="closeBulkEdit"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import EditListingForm from './EditListingForm.vue'
import BulkEditForm from './BulkEditForm.vue'

// Props
const props = defineProps({
  listings: {
    type: Array,
    default: () => []
  }
})

// Emits
const emit = defineEmits(['update:listings', 'remove', 'add'])

// State
const selectedListings = ref([])
const editingListing = ref(null)
const editingIndex = ref(-1)
const bulkEditMode = ref(false)

// Computed
const allSelected = computed(() => {
  return props.listings.length > 0 && selectedListings.value.length === props.listings.length
})

// Methods
const isSelected = (listing) => {
  return selectedListings.value.some(selected => selected.id === listing.id || selected === listing)
}

const toggleSelection = (listing) => {
  const index = selectedListings.value.findIndex(selected => selected.id === listing.id || selected === listing)
  if (index > -1) {
    selectedListings.value.splice(index, 1)
  } else {
    selectedListings.value.push(listing)
  }
}

const selectAll = () => {
  selectedListings.value = [...props.listings]
}

const clearSelection = () => {
  selectedListings.value = []
}

const toggleAllSelection = () => {
  if (allSelected.value) {
    clearSelection()
  } else {
    selectAll()
  }
}

const addNewListing = () => {
  const newListing = {
    id: Date.now(), // Temporary ID
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
  
  const updatedListings = [...props.listings, newListing]
  emit('update:listings', updatedListings)
}

const removeListing = (index) => {
  const updatedListings = props.listings.filter((_, i) => i !== index)
  emit('update:listings', updatedListings)
  
  // Remove from selection if selected
  const listing = props.listings[index]
  const selectedIndex = selectedListings.value.findIndex(selected => selected.id === listing.id || selected === listing)
  if (selectedIndex > -1) {
    selectedListings.value.splice(selectedIndex, 1)
  }
}

const removeSelected = () => {
  const updatedListings = props.listings.filter(listing => 
    !selectedListings.value.some(selected => selected.id === listing.id || selected === listing)
  )
  emit('update:listings', updatedListings)
  selectedListings.value = []
}

const editListing = (listing, index) => {
  editingListing.value = { ...listing }
  editingIndex.value = index
}

const closeEditModal = () => {
  editingListing.value = null
  editingIndex.value = -1
}

const saveListing = (updatedListing) => {
  const updatedListings = [...props.listings]
  updatedListings[editingIndex.value] = updatedListing
  emit('update:listings', updatedListings)
  closeEditModal()
}

const bulkEdit = () => {
  bulkEditMode.value = true
}

const closeBulkEdit = () => {
  bulkEditMode.value = false
}

const applyBulkEdit = (updates) => {
  const updatedListings = props.listings.map(listing => {
    if (isSelected(listing)) {
      return { ...listing, ...updates }
    }
    return listing
  })
  emit('update:listings', updatedListings)
  closeBulkEdit()
}

const validatePrice = (listing) => {
  if (listing.price < 0) {
    listing.price = 0
  }
}

const validateQuantity = (listing) => {
  if (listing.quantity < 1) {
    listing.quantity = 1
  }
}
</script>
