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
        <div v-if="kycCompleted" class="mt-4 flex md:mt-0 md:ml-4">
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

    <!-- Alert Stripe Connect Obbligatorio -->
    <div v-if="!stripeConnectConfigured" class="mb-8">
      <div class="rounded-md bg-red-50 border border-red-200 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
          </div>
          <div class="ml-3 flex-1">
            <h3 class="text-sm font-gill-sans-semibold text-red-800">
              ⚠️ Configurazione Stripe Connect Obbligatoria
            </h3>
            <div class="mt-2 text-sm text-red-700">
              <p class="font-gill-sans-semibold mb-2">
                Non puoi pubblicare inserzioni senza aver configurato Stripe Connect.
              </p>
              <p class="mb-3">
                Stripe Connect è necessario per ricevere i pagamenti quando vendi le tue carte. 
                Configuralo ora per iniziare a vendere.
              </p>
              <div class="mt-4">
                <router-link
                  to="/account/payment-methods"
                  class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-gill-sans-semibold rounded-md text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                >
                  Configura Stripe Connect
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Listings -->
    <div class="bg-white rounded-lg border border-gray-200 p-4 md:p-6">
      <h3 class="text-base md:text-lg leading-6 font-medium text-gray-900 mb-4 font-futura-bold px-1 md:px-0">
        Le tue inserzioni recenti
      </h3>
      
      <!-- Alert per inserzioni senza Stripe Connect -->
      <div v-if="listingsWithoutStripe.length > 0" class="mb-4 rounded-md bg-red-50 border border-red-200 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
          </div>
          <div class="ml-3 flex-1">
            <h4 class="text-sm font-gill-sans-semibold text-red-800">
              ⚠️ {{ listingsWithoutStripe.length }} inserzione/i non può/vengono essere pubblicata/e
            </h4>
            <p class="mt-1 text-sm text-red-700">
              Hai {{ listingsWithoutStripe.length }} inserzione/i che non possono essere pubblicate perché Stripe Connect non è configurato. 
              Configura Stripe Connect per pubblicarle.
            </p>
            <div class="mt-3">
              <router-link
                to="/account/payment-methods"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-gill-sans-semibold rounded-md text-red-800 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
              >
                Configura Stripe Connect
              </router-link>
            </div>
          </div>
        </div>
      </div>
          
          <div v-if="recentListings && recentListings.length > 0" class="space-y-3 md:space-y-4">
            <div 
              v-for="listing in recentListings" 
              :key="listing.id"
              class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3 md:p-4 border border-gray-200 rounded-lg hover:bg-gray-50"
            >
              <div class="flex items-start sm:items-center space-x-3 md:space-x-4 flex-1 min-w-0">
                <img 
                  v-if="listing.images && listing.images.length > 0"
                  :src="`/storage/${listing.images[0]}`" 
                  :alt="listing.card_model?.name || 'Carta'"
                  class="w-16 h-20 md:w-12 md:h-16 object-cover rounded flex-shrink-0"
                  @error="handleImageError"
                  @load="handleImageLoad"
                  @click="() => console.log('🖼️ URL immagine:', `/storage/${listing.images[0]}`)"
                />
                <div 
                  v-else
                  class="w-16 h-20 md:w-12 md:h-16 bg-gray-200 rounded flex items-center justify-center flex-shrink-0"
                >
                  <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <div class="flex-1 min-w-0 space-y-1.5">
                  <span 
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap self-start"
                    :class="getStatusClass(listing.status)"
                  >
                    {{ getStatusLabel(listing.status) }}
                  </span>
                  <h4 class="text-sm font-medium text-gray-900 truncate">
                    {{ formatCardName(listing.card_model?.name) }}
                  </h4>
                  <p class="text-xs md:text-sm text-gray-500 truncate">
                    {{ listing.card_model?.set_name }} {{ listing.card_model?.year }}
                  </p>
                  <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs md:text-sm text-gray-500">
                      {{ formatCondition(listing) }}
                    </p>
                    <span class="text-gray-300">•</span>
                    <p class="text-xs md:text-sm font-medium text-gray-900">
                      €{{ formatPrice(listing.price) }}
                    </p>
                  </div>
                </div>
              </div>
              
              <div class="flex items-center justify-end gap-1 flex-shrink-0">
                <button 
                  @click="editListing(listing)"
                  class="p-2 text-gray-600 hover:text-primary transition-colors"
                  title="Modifica"
                >
                  <PencilIcon class="w-4 h-4 md:w-5 md:h-5" />
                </button>
                <button 
                  v-if="canDeleteListing(listing)"
                  @click="deleteListing(listing)"
                  class="p-2 text-gray-600 hover:text-red-600 transition-colors"
                  title="Cancella"
                  :disabled="deletingListing === listing.id"
                >
                  <TrashIcon class="w-4 h-4 md:w-5 md:h-5" />
                </button>
                <span 
                  v-else
                  class="p-2 text-gray-400 cursor-not-allowed"
                  :title="getDeleteDisabledReason(listing)"
                >
                  <TrashIcon class="w-4 h-4 md:w-5 md:h-5" />
                </span>
              </div>
            </div>
          </div>
          
          <div v-else class="text-center py-8">
            <!-- Alert KYC se non completato -->
            <div v-if="!kycCompleted" class="mb-8">
              <div class="rounded-md bg-yellow-50 p-4 text-center">
                <div class="flex justify-center items-center mb-2">
                  <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400 mr-2" />
                  <h3 class="text-sm font-gill-sans-semibold text-yellow-800">
                    Verifica KYC Richiesta
                  </h3>
                </div>
                <div class="text-sm text-yellow-700 mb-4">
                  <p>Per creare inserzioni e vendere carte, devi completare la verifica KYC. Questo processo è necessario per garantire la sicurezza della piattaforma.</p>
                </div>
                <div>
                  <button
                    @click="goToKyc"
                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-gill-sans-semibold rounded-md text-yellow-800 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
                  >
                    Inizia Verifica KYC
                  </button>
                </div>
              </div>
            </div>
            
            <!-- Contenuto normale se KYC completato -->
            <div v-if="kycCompleted">
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
import { ref, onMounted, computed } from 'vue'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import CreateListingModal from '../../components/listing/CreateListingModal.vue'
import { PlusIcon, ExclamationTriangleIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import { formatCondition } from '@/utils/conditionFormatter'

// State
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingListing = ref(null)
const stats = ref({})
const recentListings = ref([])
const kycStatus = ref(null)
const kycCompleted = ref(false)
const deletingListing = ref(null)
const stripeConnectConfigured = ref(false)

// Methods
const checkKycStatus = async () => {
  try {
    console.log('🔄 Controllo stato KYC...')
    const response = await fetch('/api/kyc/status', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      console.log('✅ Stato KYC:', data.data)
      kycStatus.value = data.data
      kycCompleted.value = data.data.is_kyc_complete
    } else {
      console.error('❌ Errore nel controllo KYC:', response.status)
      kycStatus.value = null
      kycCompleted.value = false
    }
  } catch (error) {
    console.error('❌ Errore nel controllo KYC:', error)
    kycStatus.value = null
    kycCompleted.value = false
  }
}

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

const deleteListing = async (listing) => {
  // Verifica preventiva (non dovrebbe mai arrivare qui se il pulsante è nascosto)
  if (!canDeleteListing(listing)) {
    alert(getDeleteDisabledReason(listing))
    return
  }
  
  if (!confirm(`Sei sicuro di voler eliminare l'inserzione "${listing.card_model?.name || 'questa inserzione'}"?`)) {
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
      const errorData = await response.json().catch(() => ({}))
      throw new Error(errorData.message || `HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    console.log('✅ Risposta eliminazione:', data)
    
    if (data.success) {
      // Rimuovi l'inserzione dalla lista locale
      recentListings.value = recentListings.value.filter(l => l.id !== listing.id)
      // Refresh stats
      loadStats()
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

// Verifica se un'inserzione può essere cancellata
const canDeleteListing = (listing) => {
  // Non può essere cancellata se è venduta
  if (listing.status === 'sold') {
    return false
  }
  // Nota: Non possiamo verificare qui se ha ordini associati senza una chiamata API
  // ma il backend lo verificherà comunque
  return true
}

// Ottiene il motivo per cui un'inserzione non può essere cancellata
const getDeleteDisabledReason = (listing) => {
  if (listing.status === 'sold') {
    return 'Non è possibile cancellare un\'inserzione venduta. Le inserzioni vendute rimangono in archivio per riferimento storico.'
  }
  return 'Questa inserzione non può essere cancellata perché ha ordini associati.'
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
      // Mostra inserzioni attive e vendute (le vendute per vedere lo storico)
      recentListings.value = data.data.filter(listing => 
        listing.status === 'active' || listing.status === 'sold'
      )
      console.log('📊 Numero inserzioni attive e vendute:', recentListings.value.length)
      console.log('📸 Dettagli inserzioni con immagini:', recentListings.value.map(l => ({
        id: l.id,
        status: l.status,
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

const formatCardName = (name) => {
  if (!name) return ''
  // Sostituisce # seguito da numeri con / seguito dai numeri
  // Esempio: "Fabio Cannavaro #75" -> "Fabio Cannavaro /75"
  // Gestisce anche spazi: "# 75" -> "/75"
  return name.replace(/#\s*(\d+)/g, '/$1')
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

const goToKyc = () => {
  window.location.href = '/dashboard/kyc'
}

const checkStripeConnect = async () => {
  try {
    const response = await fetch('/api/stripe/account/can-receive-payments', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      stripeConnectConfigured.value = data.can_receive_payments || false
    } else {
      stripeConnectConfigured.value = false
    }
  } catch (error) {
    console.error('Errore nel controllo Stripe Connect:', error)
    stripeConnectConfigured.value = false
  }
}

// Lifecycle
onMounted(async () => {
  await checkKycStatus()
  await checkStripeConnect()
  loadStats()
  loadRecentListings()
})

  // Computed per trovare inserzioni senza Stripe Connect (usato nel template come listingsWithoutStripe)
  const listingsWithoutStripe = computed(() => {
    if (!recentListings.value || stripeConnectConfigured.value) {
      return []
    }
    return recentListings.value.filter(listing =>
      listing.status === 'draft' || listing.status === 'active'
    )
  })
</script>
