<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Crea Inserzione
          </h2>
          <p class="mt-1 text-sm text-gray-500 font-gill-sans">
            Aggiungi le tue carte alla piattaforma
          </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
          <button 
            @click="openCreateModal"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-gill-sans-semibold text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Nuova Inserzione
          </button>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Totale Inserzioni
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    {{ stats.total_listings || 0 }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Attive
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    {{ stats.active_listings || 0 }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Bozze
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    {{ stats.draft_listings || 0 }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
          <div class="p-5">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                </svg>
              </div>
              <div class="ml-5 w-0 flex-1">
                <dl>
                  <dt class="text-sm font-medium text-gray-500 truncate">
                    Valore Totale
                  </dt>
                  <dd class="text-lg font-medium text-gray-900">
                    €{{ formatPrice(stats.total_value || 0) }}
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        </div>
      </div>

    <!-- Recent Listings -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 font-futura-bold">
        Le tue inserzioni recenti
      </h3>
          
          <div v-if="recentListings && recentListings.length > 0" class="space-y-4">
            <div 
              v-for="listing in recentListings" 
              :key="listing.id"
              class="flex items-center justify-between p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
            >
              <div class="flex items-center space-x-4">
                <img 
                  v-if="listing.images && listing.images.length > 0"
                  :src="`/storage/${listing.images[0]}`" 
                  :alt="listing.card_model?.name || 'Carta'"
                  class="w-12 h-16 object-cover rounded"
                  @error="handleImageError"
                  @load="handleImageLoad"
                  @click="() => console.log('🖼️ URL immagine:', `/storage/${listing.images[0]}`)"
                />
                <div 
                  v-else
                  class="w-12 h-16 bg-gray-200 rounded flex items-center justify-center"
                >
                  <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-900">
                    {{ listing.card_model?.name }}
                  </h4>
                  <p class="text-sm text-gray-500">
                    {{ listing.card_model?.set_name }} {{ listing.card_model?.year }}
                  </p>
                  <p class="text-sm text-gray-500">
                    {{ listing.condition }} - €{{ listing.price }}
                  </p>
                </div>
              </div>
              
              <div class="flex items-center space-x-2">
                <span 
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                  :class="getStatusClass(listing.status)"
                >
                  {{ getStatusLabel(listing.status) }}
                </span>
                <button 
                  @click="editListing(listing)"
                  class="text-primary hover:text-primary-dark text-sm font-medium"
                >
                  Modifica
                </button>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Nessuna inserzione</h3>
            <p class="mt-1 text-sm text-gray-500">
              Inizia creando la tua prima inserzione.
            </p>
            <div class="mt-6">
              <button 
                @click="openCreateModal"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
              >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Crea Inserzione
              </button>
            </div>
          </div>
    </div>

    <!-- Create Listing Modal -->
    <CreateListingModal 
      :is-open="showCreateModal"
      @close="closeCreateModal"
      @created="handleListingCreated"
    />

    <!-- Edit Listing Modal -->
    <CreateListingModal 
      :is-open="showEditModal"
      :is-edit="true"
      :editing-listing="editingListing"
      @close="closeEditModal"
      @updated="handleListingUpdated"
    />
  </DashboardLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import CreateListingModal from '../../components/listing/CreateListingModal.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'

// State
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingListing = ref(null)
const stats = ref({})
const recentListings = ref([])

// Methods
const openCreateModal = () => {
  showCreateModal.value = true
}

const closeCreateModal = () => {
  showCreateModal.value = false
}

const editListing = (listing) => {
  console.log('📝 Modifica inserzione:', listing)
  editingListing.value = listing
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editingListing.value = null
}

const handleListingCreated = (listing) => {
  console.log('Inserzione creata:', listing)
  // Refresh data
  loadStats()
  loadRecentListings()
}

const handleListingUpdated = (listing) => {
  console.log('Inserzione aggiornata:', listing)
  // Refresh data
  loadStats()
  loadRecentListings()
}

const loadStats = async () => {
  try {
    const response = await fetch('/api/listings/my/stats', {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    console.log('📊 Statistiche caricate:', data)
    
    if (data.success) {
      stats.value = data.data
    } else {
      throw new Error(data.message || 'Errore nel caricamento statistiche')
    }
  } catch (error) {
    console.error('Errore nel caricamento statistiche:', error)
    stats.value = {
      total_listings: 0,
      active_listings: 0,
      draft_listings: 0,
      sold_listings: 0
    }
  }
}

const loadRecentListings = async () => {
  try {
    const response = await fetch('/api/listings/my/listings?limit=5', {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    console.log('📋 Inserzioni recenti caricate:', data)
    
    if (data.success) {
      // Filtra solo le inserzioni attive (come in SalesCards.vue)
      recentListings.value = data.data.filter(listing => listing.status === 'active')
      console.log('📊 Numero inserzioni attive:', recentListings.value.length)
      console.log('📸 Dettagli inserzioni con immagini:', recentListings.value.map(l => ({
        id: l.id,
        images: l.images,
        hasImages: l.images && l.images.length > 0,
        card_model: l.card_model,
        fullListing: l
      })))
    } else {
      throw new Error(data.message || 'Errore nel caricamento inserzioni')
    }
  } catch (error) {
    console.error('Errore nel caricamento inserzioni recenti:', error)
    recentListings.value = []
  }
}


// Handle image loading
const handleImageLoad = (event) => {
  console.log('✅ Immagine caricata con successo:', event.target.src)
}

// Handle image loading errors
const handleImageError = (event) => {
  console.log('❌ Errore nel caricamento immagine:', event.target.src)
  console.log('❌ Dettagli errore:', {
    src: event.target.src,
    naturalWidth: event.target.naturalWidth,
    naturalHeight: event.target.naturalHeight,
    complete: event.target.complete
  })
  // Nascondi l'immagine e mostra il placeholder
  event.target.style.display = 'none'
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('it-IT', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(price)
}

const getStatusClass = (status) => {
  const classes = {
    'active': 'bg-green-100 text-green-800',
    'draft': 'bg-yellow-100 text-yellow-800',
    'paused': 'bg-orange-100 text-orange-800',
    'inactive': 'bg-gray-100 text-gray-800',
    'sold': 'bg-blue-100 text-blue-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusLabel = (status) => {
  const labels = {
    'active': 'Attiva',
    'draft': 'Bozza',
    'paused': 'In pausa',
    'inactive': 'Inattiva',
    'sold': 'Venduta'
  }
  return labels[status] || status
}

// Lifecycle
onMounted(() => {
  loadStats()
  loadRecentListings()
})
</script>
