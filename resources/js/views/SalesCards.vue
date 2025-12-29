<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Le mie Carte
          </h2>
          <p class="mt-1 text-sm text-gray-500 font-gill-sans">
            Gestisci le tue carte in vendita
          </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
          <router-link
            to="/sales/create"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-gill-sans-semibold text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Vendi Carta
          </router-link>
        </div>
      </div>
    </div>

    <!-- KYC Warning -->
    <div v-if="!kycCompleted" class="mb-8">
      <div class="rounded-md bg-yellow-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-gill-sans-semibold text-yellow-800">
              Verifica KYC Richiesta
            </h3>
            <div class="mt-2 text-sm text-yellow-700">
              <p>Per iniziare a vendere carte, devi completare la verifica KYC. Questo processo è necessario per garantire la sicurezza della piattaforma.</p>
            </div>
            <div class="mt-4">
              <router-link
                to="/dashboard/kyc"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-gill-sans-semibold rounded-md text-yellow-800 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
              >
                Inizia Verifica KYC
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="text-center py-12">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto"></div>
        <p class="mt-2 text-sm text-gray-500">Caricamento carte...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="text-center py-12">
        <ExclamationTriangleIcon class="mx-auto h-12 w-12 text-red-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Errore nel caricamento</h3>
        <p class="mt-1 text-sm text-gray-500">{{ error }}</p>
        <div class="mt-6">
          <button
            @click="loadListings"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-gill-sans-semibold rounded-md text-white bg-primary hover:bg-primary/90"
          >
            Riprova
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!listings.length" class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="text-center py-12">
        <FolderIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Nessuna carta in vendita</h3>
        <p class="mt-1 text-sm text-gray-500">Inizia vendendo la tua prima carta.</p>
        <div class="mt-6">
          <router-link
            to="/sales/create"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-gill-sans-semibold rounded-md text-white bg-primary hover:bg-primary/90"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Vendi Carta
          </router-link>
        </div>
      </div>
    </div>

    <!-- Listings Grid -->
    <div v-else class="bg-white rounded-lg border border-gray-200">
      <!-- Header con ricerca e filtri -->
      <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
          <h3 class="text-lg font-gill-sans-semibold text-gray-900">
            Le tue carte in vendita ({{ totalListings }})
          </h3>
          
          <!-- View Toggle -->
          <div class="flex rounded-md shadow-sm">
            <button 
              type="button" 
              :class="[viewMode === 'grid' ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:text-gray-900', 'px-3 py-2 text-sm font-medium border border-gray-300 rounded-l-md focus:z-10 focus:ring-1 focus:ring-primary focus:border-primary']"
              @click="viewMode = 'grid'"
            >
              <Squares2X2Icon class="size-4" />
            </button>
            <button 
              type="button" 
              :class="[viewMode === 'list' ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:text-gray-900', 'px-3 py-2 text-sm font-medium border border-gray-300 rounded-r-md focus:z-10 focus:ring-1 focus:ring-primary focus:border-primary']"
              @click="viewMode = 'list'"
            >
              <svg class="size-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>

        <!-- Barra di ricerca e filtri -->
        <div class="flex flex-col md:flex-row gap-4">
          <!-- Campo di ricerca -->
          <div class="flex-1 relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cerca per giocatore, squadra, set o nome carta..."
              class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary focus:border-primary sm:text-sm font-gill-sans"
              @input="handleSearchInput"
            />
            <button
              v-if="searchQuery"
              @click="clearSearch"
              class="absolute inset-y-0 right-0 pr-3 flex items-center"
            >
              <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <!-- Filtro Status -->
          <div class="md:w-48">
            <select
              v-model="statusFilter"
              @change="handleFilterChange"
              class="block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-primary focus:border-primary rounded-md sm:text-sm font-gill-sans"
            >
              <option value="all">Tutte le carte</option>
              <option value="active">Solo attive</option>
              <option value="draft">Bozze</option>
              <option value="sold">Vendute</option>
            </select>
          </div>
        </div>
      </div>
      
      <div class="p-6">
        <!-- Messaggio nessun risultato -->
        <div v-if="!loading && listings.length === 0" class="text-center py-12">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
          <h3 class="text-lg font-gill-sans-semibold text-gray-900 mb-2">Nessun risultato trovato</h3>
          <p class="text-gray-600 font-gill-sans mb-4">
            {{ searchQuery ? 'Prova a modificare i termini di ricerca' : 'Non ci sono carte che corrispondono ai filtri selezionati' }}
          </p>
          <button
            v-if="searchQuery || statusFilter !== 'all'"
            @click="clearFilters"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-gill-sans-semibold rounded-md text-white bg-primary hover:bg-primary/90"
          >
            Rimuovi filtri
          </button>
        </div>

        <!-- Desktop Grid View -->
        <div v-else-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          <div
            v-for="listing in listings"
            :key="listing.id"
            class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow"
          >
            <!-- Card Image -->
            <div class="aspect-[3/4] bg-gray-200 flex items-center justify-center overflow-hidden">
              <img 
                v-if="listing.images && listing.images.length > 0"
                :src="`/storage/${listing.images[0]}`"
                :alt="listing.card_model?.name || 'Carta'"
                class="w-full h-full object-cover"
                @error="handleImageError"
              />
              <div v-else class="text-center">
                <div class="w-16 h-16 bg-gray-300 rounded-lg mx-auto mb-2"></div>
                <p class="text-xs text-gray-500">Nessuna immagine</p>
              </div>
            </div>
            
            <!-- Card Details -->
            <div class="p-4">
              <h4 class="text-lg font-gill-sans-semibold text-gray-900 truncate">
                {{ listing.card_model?.name || 'Carta' }}
              </h4>
              <p class="text-sm text-gray-600 mt-1">
                {{ listing.card_model?.set_name || 'Set' }} - {{ listing.card_model?.year || 'Anno' }}
              </p>
              <p class="text-sm text-gray-500 mt-1">
                {{ listing.card_model?.player?.name || 'Giocatore' }} - {{ listing.card_model?.team?.name || 'Squadra' }}
              </p>
              
              <div class="mt-3 flex items-center justify-between">
                <div>
                  <p class="text-lg font-gill-sans-bold text-primary">
                    €{{ formatPriceItaliana(listing.price) }}
                  </p>
                  <p class="text-xs text-gray-500 capitalize">
                    {{ formatCondition(listing) }} - Qty: {{ listing.quantity }}
                  </p>
                </div>
                <div class="flex space-x-2">
                  <button
                    @click="editListing(listing)"
                    class="text-gray-400 hover:text-gray-600 transition-colors"
                    title="Modifica"
                  >
                    <PencilIcon class="h-4 w-4" />
                  </button>
                  <button
                    @click="deleteListing(listing)"
                    class="text-gray-400 hover:text-red-600 transition-colors"
                    title="Elimina"
                    :disabled="deletingListing === listing.id"
                  >
                    <TrashIcon v-if="deletingListing !== listing.id" class="h-4 w-4" />
                    <div v-else class="animate-spin rounded-full h-4 w-4 border-b-2 border-red-600"></div>
                  </button>
                </div>
              </div>
              
              <div class="mt-3">
                <span
                  :class="[
                    'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                    listing.status === 'active' 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-gray-100 text-gray-800'
                  ]"
                >
                  {{ listing.status === 'active' ? 'Attiva' : 'Inattiva' }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Desktop List View -->
        <div v-else-if="viewMode === 'list'" class="space-y-4">
          <div
            v-for="listing in listings"
            :key="listing.id"
            class="bg-white border border-gray-200 rounded-lg overflow-hidden hover:shadow-md transition-shadow"
          >
            <div class="flex">
              <!-- Image Area -->
              <div class="w-48 h-64 bg-gray-200 overflow-hidden flex-shrink-0 relative">
                <img 
                  v-if="listing.images && listing.images.length > 0"
                  :src="`/storage/${listing.images[0]}`"
                  :alt="listing.card_model?.name || 'Carta'"
                  class="w-full h-full object-cover"
                  @error="handleImageError"
                />
                <div v-else class="w-full h-full flex items-center justify-center bg-gray-200">
                  <div class="text-center">
                    <div class="w-16 h-16 bg-gray-300 rounded-lg mx-auto mb-2"></div>
                    <p class="text-xs text-gray-500">Nessuna immagine</p>
                  </div>
                </div>
              </div>
              
              <!-- Card Details -->
              <div class="flex-1 p-4 flex flex-col justify-between">
                <div>
                  <h4 class="text-lg font-gill-sans-semibold text-gray-900">
                    {{ listing.card_model?.name || 'Carta' }}
                  </h4>
                  
                  <div class="mt-2 text-sm text-gray-600 space-y-1">
                    <div class="flex justify-between">
                      <span>Set:</span>
                      <span class="font-medium">{{ listing.card_model?.set_name || 'Set' }} - {{ listing.card_model?.year || 'Anno' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Giocatore:</span>
                      <span class="font-medium">{{ listing.card_model?.player?.name || 'Giocatore' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Squadra:</span>
                      <span class="font-medium">{{ listing.card_model?.team?.name || 'Squadra' }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Condizione:</span>
                      <span class="font-medium capitalize">{{ formatCondition(listing) }}</span>
                    </div>
                    <div class="flex justify-between">
                      <span>Quantità:</span>
                      <span class="font-medium">{{ listing.quantity }}</span>
                    </div>
                  </div>
                  
                  <div class="mt-3">
                    <span
                      :class="[
                        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                        listing.status === 'active' 
                          ? 'bg-green-100 text-green-800' 
                          : 'bg-gray-100 text-gray-800'
                      ]"
                    >
                      {{ listing.status === 'active' ? 'Attiva' : 'Inattiva' }}
                    </span>
                  </div>
                </div>
                
                <!-- Price and Actions -->
                <div class="mt-4 flex items-center justify-between">
                  <p class="text-lg font-gill-sans-bold text-primary">
                    €{{ formatPriceItaliana(listing.price) }}
                  </p>
                  <div class="flex space-x-2">
                    <button
                      @click="editListing(listing)"
                      class="text-gray-400 hover:text-gray-600 transition-colors"
                      title="Modifica"
                    >
                      <PencilIcon class="h-5 w-5" />
                    </button>
                    <button
                      @click="deleteListing(listing)"
                      class="text-gray-400 hover:text-red-600 transition-colors"
                      title="Elimina"
                      :disabled="deletingListing === listing.id"
                    >
                      <TrashIcon v-if="deletingListing !== listing.id" class="h-5 w-5" />
                      <div v-else class="animate-spin rounded-full h-5 w-5 border-b-2 border-red-600"></div>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Load More Button -->
        <div v-if="hasMorePages && !loading && !loadingMore && listings.length > 0" class="text-center mt-8">
          <button 
            @click="loadMore"
            class="inline-flex items-center px-6 py-3 border border-transparent shadow-sm text-sm font-futura-bold rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
          >
            Carica Altri Risultati
          </button>
        </div>
        
        <!-- Loading indicator per caricamento incrementale -->
        <div v-if="loadingMore" class="text-center mt-8 py-4">
          <div class="flex items-center justify-center space-x-3">
            <svg class="animate-spin h-6 w-6 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-600 font-gill-sans">Caricamento risultati...</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal di Modifica con CreateListingModal -->
    <CreateListingModal
      v-if="showEditModal"
      :is-open="true"
      :is-edit="true"
      :editing-listing="editingListing"
      @close="closeEditModal"
      @updated="handleListingUpdated"
    />
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useAuthStore } from '@/stores/auth'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import CreateListingModal from '@/components/listing/CreateListingModal.vue'
import { PlusIcon, FolderIcon, ExclamationTriangleIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { Squares2X2Icon } from '@heroicons/vue/20/solid'
import { formatPriceItaliana } from '../utils/priceFormatter'
import { formatCondition } from '@/utils/conditionFormatter'

const authStore = useAuthStore()
const kycCompleted = computed(() => authStore.user?.kyc_status === 'approved')

// State
const listings = ref([])
const loading = ref(false)
const loadingMore = ref(false)
const error = ref(null)
const deletingListing = ref(null)
const viewMode = ref('grid')
const searchQuery = ref('')
const statusFilter = ref('all')
const currentPage = ref(1)
const totalListings = ref(0)
const hasMorePages = ref(false)
let searchTimeout = null

// Load listings from API
const loadListings = async (reset = true) => {
  if (reset) {
    loading.value = true
    currentPage.value = 1
  } else {
    loadingMore.value = true
  }
  
  error.value = null
  
  try {
    console.log('🔄 Caricamento inserzioni...', { page: currentPage.value, search: searchQuery.value, status: statusFilter.value })
    
    // Costruisci i parametri della query
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: '20'
    })
    
    if (statusFilter.value !== 'all') {
      params.append('status', statusFilter.value)
    }
    
    if (searchQuery.value.trim()) {
      params.append('search', searchQuery.value.trim())
    }
    
    const response = await fetch(`/api/listings/my/listings?${params.toString()}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    console.log('✅ Inserzioni caricate:', data)
    
    if (data.success && data.data) {
      if (reset) {
        listings.value = data.data
      } else {
        listings.value.push(...data.data)
      }
      
      // Aggiorna informazioni paginazione
      totalListings.value = data.pagination?.total || data.data.length
      hasMorePages.value = data.pagination?.current_page < data.pagination?.last_page
      
      console.log('📊 Numero inserzioni:', listings.value.length, 'di', totalListings.value)
    } else {
      throw new Error(data.message || 'Errore nel caricamento delle inserzioni')
    }
  } catch (err) {
    console.error('❌ Errore nel caricamento inserzioni:', err)
    error.value = err.message
    if (reset) {
      listings.value = []
    }
  } finally {
    loading.value = false
    loadingMore.value = false
  }
}

// Handle search input with debounce
const handleSearchInput = () => {
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    loadListings(true)
  }, 500)
}

// Clear search
const clearSearch = () => {
  searchQuery.value = ''
  currentPage.value = 1
  loadListings(true)
}

// Handle filter change
const handleFilterChange = () => {
  currentPage.value = 1
  loadListings(true)
}

// Clear all filters
const clearFilters = () => {
  searchQuery.value = ''
  statusFilter.value = 'all'
  currentPage.value = 1
  loadListings(true)
}

// Load more listings
const loadMore = () => {
  if (hasMorePages.value && !loading.value && !loadingMore.value) {
    currentPage.value++
    loadListings(false)
  }
}

// Edit listing function
const editListing = (listing) => {
  console.log('✏️ Modifica inserzione:', listing.id)
  
  // Apri modal di creazione in modalità modifica
  openEditModal(listing)
}

// Modal di modifica
const showEditModal = ref(false)
const editingListing = ref(null)

const openEditModal = (listing) => {
  editingListing.value = listing
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editingListing.value = null
}

// Gestisce l'aggiornamento dell'inserzione
const handleListingUpdated = (updatedListing) => {
  console.log('✅ Inserzione aggiornata:', updatedListing)
  
  // Aggiorna la lista locale
  const index = listings.value.findIndex(l => l.id === updatedListing.id)
  if (index !== -1) {
    listings.value[index] = updatedListing
  }
  
  closeEditModal()
  
  // Ricarica per aggiornare il conteggio totale
  loadListings(true)
}

// Delete listing function
const deleteListing = async (listing) => {
  if (!confirm(`Sei sicuro di voler eliminare l'inserzione "${listing.card_model?.name}"?`)) {
    return
  }
  
  deletingListing.value = listing.id
  
  try {
    console.log('🗑️ Eliminazione inserzione:', listing.id)
    
    const response = await fetch(`/api/listings/${listing.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    console.log('✅ Inserzione eliminata:', data)
    
    if (data.success) {
      // Rimuovi l'inserzione dalla lista locale
      listings.value = listings.value.filter(l => l.id !== listing.id)
      totalListings.value = Math.max(0, totalListings.value - 1)
      console.log('📊 Inserzione rimossa dalla lista')
    } else {
      throw new Error(data.message || 'Errore nell\'eliminazione dell\'inserzione')
    }
  } catch (err) {
    console.error('❌ Errore nell\'eliminazione inserzione:', err)
    alert(`Errore nell'eliminazione: ${err.message}`)
  } finally {
    deletingListing.value = null
  }
}

// Handle image loading errors
const handleImageError = (event) => {
  console.log('❌ Errore nel caricamento immagine:', event.target.src)
  // Nascondi l'immagine e mostra il placeholder
  event.target.style.display = 'none'
}

// Load listings on component mount
onMounted(() => {
  if (kycCompleted.value) {
    loadListings()
  }
})
</script>