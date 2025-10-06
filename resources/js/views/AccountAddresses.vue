<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Indirizzi
          </h2>
          <p class="mt-1 text-sm text-gray-500 font-gill-sans">
            Gestisci i tuoi indirizzi di spedizione e fatturazione
          </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
          <button
            type="button"
            @click="openAddModal"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-gill-sans-semibold text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Aggiungi Indirizzo
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-lg border border-gray-200">
      <!-- Loading State -->
      <div v-if="loading" class="p-6">
        <div class="animate-pulse">
          <div class="space-y-4">
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            <div class="h-4 bg-gray-200 rounded w-1/2"></div>
            <div class="h-4 bg-gray-200 rounded w-2/3"></div>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-else-if="addresses.length === 0" class="p-6">
        <div class="text-center py-12">
          <MapPinIcon class="mx-auto h-12 w-12 text-gray-400" />
          <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Nessun indirizzo</h3>
          <p class="mt-1 text-sm text-gray-500">Inizia aggiungendo il tuo primo indirizzo.</p>
          <div class="mt-6">
            <button
              type="button"
              @click="openAddModal"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-gill-sans-semibold rounded-md text-white bg-primary hover:bg-primary/90"
            >
              <PlusIcon class="h-4 w-4 mr-2" />
              Aggiungi Indirizzo
            </button>
          </div>
        </div>
      </div>

      <!-- Addresses List -->
      <div v-else class="divide-y divide-gray-200">
        <div
          v-for="address in addresses"
          :key="address.id"
          class="p-6 hover:bg-gray-50 transition-colors duration-200"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <!-- Address Header -->
              <div class="flex items-center space-x-3 mb-3">
                <h3 class="text-lg font-gill-sans-semibold text-gray-900">
                  {{ address.label }}
                </h3>
                
                <!-- Badges -->
                <div class="flex space-x-2">
                  <span
                    v-if="address.is_default"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-gill-sans-semibold bg-primary/10 text-primary"
                  >
                    Predefinito
                  </span>
                  <span
                    v-if="address.is_shipping"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-gill-sans-semibold bg-blue-100 text-blue-800"
                  >
                    Spedizione
                  </span>
                  <span
                    v-if="address.is_billing"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-gill-sans-semibold bg-green-100 text-green-800"
                  >
                    Fatturazione
                  </span>
                </div>
              </div>

              <!-- Address Details -->
              <div class="text-sm text-gray-600 font-gill-sans space-y-1">
                <p class="font-semibold text-gray-900">{{ address.first_name }} {{ address.last_name }}</p>
                <p v-if="address.company" class="text-gray-700">{{ address.company }}</p>
                <p>{{ address.address_line_1 }}</p>
                <p v-if="address.address_line_2">{{ address.address_line_2 }}</p>
                <p>
                  {{ address.postal_code }} {{ address.city }}
                  <span v-if="address.state_province">, {{ address.state_province }}</span>
                </p>
                <p>{{ getCountryName(address.country) }}</p>
                <p v-if="address.phone" class="text-gray-500">{{ address.phone }}</p>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center space-x-2 ml-4">
              <!-- Set Default Button -->
              <button
                v-if="!address.is_default"
                @click="setAsDefault(address.id)"
                :disabled="settingDefault === address.id"
                class="text-sm font-gill-sans-semibold text-primary hover:text-primary/80 disabled:opacity-50"
              >
                <span v-if="settingDefault === address.id">Impostando...</span>
                <span v-else>Imposta predefinito</span>
              </button>

              <!-- Edit Button -->
              <button
                @click="openEditModal(address)"
                class="p-2 text-gray-400 hover:text-gray-600 transition-colors"
                title="Modifica indirizzo"
              >
                <PencilIcon class="h-4 w-4" />
              </button>

              <!-- Delete Button -->
              <button
                @click="confirmDelete(address)"
                :disabled="deleting === address.id"
                class="p-2 text-gray-400 hover:text-red-600 transition-colors disabled:opacity-50"
                title="Elimina indirizzo"
              >
                <TrashIcon v-if="deleting !== address.id" class="h-4 w-4" />
                <svg v-else class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Address Modal -->
    <AddressModal
      :is-open="modalOpen"
      :address="selectedAddress"
      @close="closeModal"
      @save="saveAddress"
    />

    <!-- Delete Confirmation Modal -->
    <ConfirmationModal
      :is-open="deleteModalOpen"
      title="Elimina Indirizzo"
      :message="`Sei sicuro di voler eliminare l'indirizzo '${addressToDelete?.label}'? Questa azione non può essere annullata.`"
      confirm-text="Elimina"
      confirm-class="bg-red-600 hover:bg-red-700"
      @confirm="deleteAddress"
      @cancel="cancelDelete"
    />
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import AddressModal from '@/components/AddressModal.vue'
import ConfirmationModal from '@/components/ConfirmationModal.vue'
import { PlusIcon, MapPinIcon, PencilIcon, TrashIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'

const router = useRouter()
const authStore = useAuthStore()

const addresses = ref([])
const loading = ref(true)
const modalOpen = ref(false)
const selectedAddress = ref(null)
const settingDefault = ref(null)
const deleting = ref(null)
const deleteModalOpen = ref(false)
const addressToDelete = ref(null)

const countries = {
  'IT': 'Italia',
  'FR': 'Francia',
  'DE': 'Germania',
  'ES': 'Spagna',
  'CH': 'Svizzera',
  'AT': 'Austria',
  'BE': 'Belgio',
  'NL': 'Paesi Bassi',
  'PT': 'Portogallo',
  'GB': 'Regno Unito',
  'US': 'Stati Uniti',
  'CA': 'Canada'
}

const getCountryName = (code) => {
  return countries[code] || code
}

const showNotification = (type, title, message) => {
  console.log('Showing notification:', type, title, message)
  
  // USA SOLO il sistema personalizzato grande (niente doppie notifiche)
  showCustomNotification(type, title, message)
}

const showCustomNotification = (type, title, message) => {
  // Calcola la posizione basata sulle notifiche esistenti
  const existingNotifications = document.querySelectorAll('.custom-notification')
  const topOffset = 20 + (existingNotifications.length * 120) // Spazio tra le notifiche
  
  // Crea una notifica personalizzata molto grande
  const notification = document.createElement('div')
  notification.className = 'custom-notification'
  notification.style.cssText = `
    position: fixed;
    top: ${topOffset}px;
    right: 20px;
    z-index: 9999;
    min-width: 420px;
    max-width: 520px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25), 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    padding: 24px;
    border-left: 6px solid ${type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#F59E0B'};
    animation: slideIn 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    transition: all 0.3s ease;
  `
  
  notification.innerHTML = `
    <div style="display: flex; align-items: flex-start; gap: 16px;">
      <div style="
        width: 48px; 
        height: 48px; 
        border-radius: 50%; 
        background: ${type === 'success' ? '#DCFCE7' : type === 'error' ? '#FEE2E2' : '#FEF3C7'};
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      ">
        <div style="
          width: 24px; 
          height: 24px; 
          color: ${type === 'success' ? '#059669' : type === 'error' ? '#DC2626' : '#D97706'};
        ">
          ${type === 'success' ? '✓' : type === 'error' ? '✕' : '⚠'}
        </div>
      </div>
      <div style="flex: 1;">
        <h3 style="
          margin: 0 0 8px 0;
          font-size: 20px;
          font-weight: bold;
          color: #111827;
          line-height: 1.2;
        ">${title}</h3>
        <p style="
          margin: 0;
          font-size: 16px;
          color: #6B7280;
          line-height: 1.4;
        ">${message}</p>
      </div>
      <button onclick="removeCustomNotification(this.parentElement.parentElement)" style="
        background: none;
        border: none;
        font-size: 24px;
        color: #9CA3AF;
        cursor: pointer;
        padding: 4px;
        line-height: 1;
        border-radius: 4px;
        transition: all 0.2s ease;
      " onmouseover="this.style.background='#F3F4F6'; this.style.color='#6B7280'" onmouseout="this.style.background='none'; this.style.color='#9CA3AF'">×</button>
    </div>
  `
  
  // Aggiungi gli stili per l'animazione e la funzione globale
  if (!document.getElementById('notification-styles')) {
    const styles = document.createElement('style')
    styles.id = 'notification-styles'
    styles.textContent = `
      @keyframes slideIn {
        from {
          transform: translateX(100%) scale(0.9);
          opacity: 0;
        }
        to {
          transform: translateX(0) scale(1);
          opacity: 1;
        }
      }
      @keyframes slideOut {
        from {
          transform: translateX(0) scale(1);
          opacity: 1;
        }
        to {
          transform: translateX(100%) scale(0.9);
          opacity: 0;
        }
      }
    `
    document.head.appendChild(styles)
    
    // Funzione globale per rimuovere notifiche
    window.removeCustomNotification = function(notification) {
      notification.style.animation = 'slideOut 0.3s ease-in'
      setTimeout(() => {
        if (notification.parentElement) {
          notification.remove()
          // Riposiziona le notifiche rimanenti
          repositionNotifications()
        }
      }, 300)
    }
    
    // Funzione per riposizionare le notifiche
    window.repositionNotifications = function() {
      const notifications = document.querySelectorAll('.custom-notification')
      notifications.forEach((notif, index) => {
        const newTop = 20 + (index * 120)
        notif.style.top = newTop + 'px'
      })
    }
  }
  
  document.body.appendChild(notification)
  
  // Rimuovi automaticamente dopo 6 secondi
  setTimeout(() => {
    if (notification.parentElement) {
      window.removeCustomNotification(notification)
    }
  }, 6000)
}

const fetchAddresses = async () => {
  try {
    loading.value = true
    const response = await axios.get('/api/addresses')
    if (response.data.success) {
      addresses.value = response.data.data
    }
  } catch (error) {
    console.error('Errore nel caricamento degli indirizzi:', error)
    if (error.response && error.response.status === 401) {
      showNotification('error', 'Errore di autenticazione', 'Devi effettuare il login per visualizzare gli indirizzi')
    } else {
      showNotification('error', 'Errore', 'Errore nel caricamento degli indirizzi')
    }
  } finally {
    loading.value = false
  }
}

const openAddModal = () => {
  selectedAddress.value = null
  modalOpen.value = true
}

const openEditModal = (address) => {
  selectedAddress.value = address
  modalOpen.value = true
}

const closeModal = () => {
  modalOpen.value = false
  selectedAddress.value = null
}

const saveAddress = async (addressData) => {
  try {
    if (selectedAddress.value) {
      // Modifica indirizzo esistente
      const response = await axios.put(`/api/addresses/${selectedAddress.value.id}`, addressData)
      if (response.data.success) {
        const index = addresses.value.findIndex(a => a.id === selectedAddress.value.id)
        if (index !== -1) {
          addresses.value[index] = response.data.data
        }
        closeModal()
        // Mostra messaggio di successo
        showNotification('success', 'Successo', 'Indirizzo aggiornato con successo')
      }
    } else {
      // Crea nuovo indirizzo
      const response = await axios.post('/api/addresses', addressData)
      if (response.data.success) {
        addresses.value.unshift(response.data.data)
        closeModal()
        // Mostra messaggio di successo
        showNotification('success', 'Successo', 'Indirizzo creato con successo')
      }
    }
  } catch (error) {
    console.error('Errore nel salvare l\'indirizzo:', error)
    
    if (error.response && error.response.status === 401) {
      showNotification('error', 'Errore di autenticazione', 'Devi effettuare il login per salvare gli indirizzi')
    } else if (error.response && error.response.status === 422) {
      // Errori di validazione - lascia che il modal li gestisca
      throw error
    } else {
      showNotification('error', 'Errore', 'Si è verificato un errore nel salvare l\'indirizzo')
    }
    
    // Re-throw solo per errori di validazione
    if (error.response && error.response.status === 422) {
      throw error
    }
  }
}

const setAsDefault = async (addressId) => {
  try {
    settingDefault.value = addressId
    const response = await axios.patch(`/api/addresses/${addressId}/set-default`)
    if (response.data.success) {
      // Aggiorna lo stato locale
      addresses.value.forEach(address => {
        address.is_default = address.id === addressId
      })
      showNotification('success', 'Successo', 'Indirizzo impostato come predefinito')
    }
  } catch (error) {
    console.error('Errore nell\'impostare l\'indirizzo predefinito:', error)
    if (error.response && error.response.status === 401) {
      showNotification('error', 'Errore di autenticazione', 'Devi effettuare il login')
    } else {
      showNotification('error', 'Errore', 'Errore nell\'impostare l\'indirizzo predefinito')
    }
  } finally {
    settingDefault.value = null
  }
}

const confirmDelete = (address) => {
  if (window.$notification && window.$notification.confirm) {
    window.$notification.confirm({
      title: 'Elimina Indirizzo',
      message: `Sei sicuro di voler eliminare l'indirizzo "${address.label}"? Questa azione non può essere annullata.`,
      type: 'danger',
      confirmText: 'Elimina',
      cancelText: 'Annulla',
      onConfirm: () => deleteAddress(address)
    })
  } else {
    // Fallback per il modal personalizzato
    addressToDelete.value = address
    deleteModalOpen.value = true
  }
}

const cancelDelete = () => {
  deleteModalOpen.value = false
  addressToDelete.value = null
}

const deleteAddress = async (address = null) => {
  const addressToDelete = address || addressToDelete.value
  if (!addressToDelete) return

  try {
    deleting.value = addressToDelete.id
    const response = await axios.delete(`/api/addresses/${addressToDelete.id}`)
    if (response.data.success) {
      addresses.value = addresses.value.filter(a => a.id !== addressToDelete.id)
      deleteModalOpen.value = false
      addressToDelete.value = null
      showNotification('success', 'Successo', 'Indirizzo eliminato con successo')
    }
  } catch (error) {
    console.error('Errore nell\'eliminare l\'indirizzo:', error)
    if (error.response && error.response.status === 401) {
      showNotification('error', 'Errore di autenticazione', 'Devi effettuare il login')
    } else {
      showNotification('error', 'Errore', 'Errore nell\'eliminare l\'indirizzo')
    }
  } finally {
    deleting.value = null
  }
}

onMounted(() => {
  // Verifica se l'utente è autenticato
  if (!authStore.isAuthenticated) {
    showNotification('error', 'Accesso negato', 'Devi effettuare il login per accedere a questa pagina')
    router.push('/login')
    return
  }
  
  fetchAddresses()
})
</script>
