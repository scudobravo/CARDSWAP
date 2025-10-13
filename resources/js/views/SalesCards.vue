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
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-gill-sans-semibold text-gray-900">
          Le tue carte in vendita ({{ listings.length }})
        </h3>
      </div>
      
      <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                    €{{ parseFloat(listing.price).toFixed(2) }}
                  </p>
                  <p class="text-xs text-gray-500 capitalize">
                    {{ listing.condition }} - Qty: {{ listing.quantity }}
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
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import CreateListingModal from '@/components/listing/CreateListingModal.vue'
import { PlusIcon, FolderIcon, ExclamationTriangleIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const kycCompleted = computed(() => authStore.user?.kyc_status === 'approved')

// State
const listings = ref([])
const loading = ref(false)
const error = ref(null)
const deletingListing = ref(null)

// Load listings from API
const loadListings = async () => {
  loading.value = true
  error.value = null
  
  try {
    console.log('🔄 Caricamento inserzioni...')
    
    const response = await fetch('/api/listings/my/listings', {
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
      // Filtra solo le inserzioni attive
      listings.value = data.data.filter(listing => listing.status === 'active')
      console.log('📊 Numero inserzioni attive:', listings.value.length)
    } else {
      throw new Error(data.message || 'Errore nel caricamento delle inserzioni')
    }
  } catch (err) {
    console.error('❌ Errore nel caricamento inserzioni:', err)
    error.value = err.message
    listings.value = []
  } finally {
    loading.value = false
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