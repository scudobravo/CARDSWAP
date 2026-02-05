<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-24 pb-6">
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h1 class="text-4xl font-futura-bold text-primary mb-6">Le tue conversazioni</h1>
        
        <!-- Loading State -->
        <div v-if="loading" class="text-center py-12">
          <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
          <p class="mt-4 text-gray-600 font-gill-sans">Caricamento conversazioni...</p>
        </div>

        <!-- Error State -->
        <div v-else-if="error" class="text-center py-12">
          <div class="text-red-500 text-6xl mb-4">!</div>
          <h2 class="text-2xl font-futura-bold text-primary mb-2">Errore nel caricamento</h2>
          <p class="text-gray-600 font-gill-sans mb-4">{{ error }}</p>
          <button @click="loadConversations" class="bg-primary text-white px-6 py-2 rounded-lg font-futura-bold hover:bg-primary/90 transition-colors">
            Riprova
          </button>
        </div>

        <!-- Empty State -->
        <div v-else-if="conversations.length === 0" class="text-center py-12">
          <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <h2 class="text-2xl font-futura-bold text-primary mb-2">Nessuna conversazione</h2>
          <p class="text-gray-600 font-gill-sans">Non hai ancora conversazioni. Inizia a chattare con i venditori!</p>
        </div>

        <!-- Conversations List -->
        <div v-else class="space-y-4">
          <div 
            v-for="conversation in conversations" 
            :key="conversation.id"
            @click="openConversation(conversation)"
            class="bg-gray-50 hover:bg-gray-100 rounded-lg p-4 cursor-pointer transition-colors border border-gray-200"
          >
            <div class="flex items-start justify-between">
              <div class="flex-1 min-w-0">
                <!-- Header con nome utente e badge -->
                <div class="flex items-center space-x-3 mb-2">
                  <div class="flex-shrink-0 relative">
                    <div class="h-10 w-10 rounded-full bg-primary text-white flex items-center justify-center font-futura-bold">
                      {{ getInitials(getOtherUserName(conversation)) }}
                    </div>
                    <!-- Badge messaggi non letti (stile WhatsApp) -->
                    <span 
                      v-if="getUnreadCount(conversation) > 0"
                      :class="[
                        'absolute -top-1 -right-1 bg-red-500 text-white text-xs font-futura-bold rounded-full flex items-center justify-center border-2 border-white',
                        getUnreadCount(conversation) > 99 ? 'px-1.5 py-0.5 min-w-[24px]' : 'h-5 w-5'
                      ]"
                    >
                      {{ getUnreadCount(conversation) > 99 ? '99+' : getUnreadCount(conversation) }}
                    </span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                      <h3 class="text-lg font-futura-bold text-primary truncate">
                        {{ getOtherUserName(conversation) }}
                      </h3>
                    </div>
                    <p class="text-sm text-gray-600 font-gill-sans truncate">
                      {{ getConversationTitle(conversation) }}
                    </p>
                  </div>
                </div>

                <!-- Ultimo messaggio -->
                <div v-if="conversation.last_message_at" class="mt-2">
                  <p class="text-sm text-gray-500 font-gill-sans truncate">
                    {{ formatTime(conversation.last_message_at) }}
                  </p>
                </div>
              </div>

              <!-- Icona freccia -->
              <div class="flex-shrink-0 ml-4">
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
    
    <!-- Footer -->
    <Footer />

    <!-- Chat Modal -->
    <VendorChatModal 
      v-if="selectedConversation"
      :is-open="showChatModal"
      :conversation-id="selectedConversation.id"
      :product-id="selectedConversation.listing?.id || null"
      :vendor-id="getOtherUserId(selectedConversation)"
      :vendor-name="getOtherUserName(selectedConversation)"
      :product-name="getProductName(selectedConversation)"
      @close="closeChatModal"
      @messages-updated="loadConversations"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '../stores/auth.js'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'
import VendorChatModal from '../components/VendorChatModal.vue'

const authStore = useAuthStore()

const conversations = ref([])
const loading = ref(false)
const error = ref(null)
const showChatModal = ref(false)
const selectedConversation = ref(null)

const loadConversations = async () => {
  if (!authStore.isAuthenticated) {
    error.value = 'Devi essere loggato per vedere le conversazioni'
    return
  }

  loading.value = true
  error.value = null

  try {
    const response = await fetch('/api/conversations', {
      headers: {
        'Authorization': `Bearer ${authStore.token}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      
      // La risposta paginata ha questa struttura: { success: true, data: { data: [...], current_page: 1, ... } }
      if (data.data?.data && Array.isArray(data.data.data)) {
        conversations.value = data.data.data
      } else if (Array.isArray(data.data)) {
        conversations.value = data.data
      } else if (Array.isArray(data)) {
        conversations.value = data
      } else {
        conversations.value = []
      }
    } else if (response.status === 401) {
      error.value = 'Sessione scaduta. Effettua il login.'
      authStore.logout()
    } else {
      const errorData = await response.json().catch(() => ({ message: 'Errore sconosciuto' }))
      error.value = errorData.message || 'Errore nel caricamento delle conversazioni'
    }
  } catch (err) {
    error.value = 'Errore di connessione'
  } finally {
    loading.value = false
  }
}

const getInitials = (name) => {
  if (!name) return 'U'
  const names = name.trim().split(' ')
  if (names.length === 1) {
    return names[0].charAt(0).toUpperCase()
  }
  return (names[0].charAt(0) + names[names.length - 1].charAt(0)).toUpperCase()
}

const getOtherUserName = (conversation) => {
  const userId = authStore.user?.id
  if (!userId) return 'Utente'
  
  // Se l'utente loggato è il buyer, mostra il nome del seller
  if (userId === conversation.buyer_id) {
    return conversation.seller?.name || 'Venditore'
  }
  // Se l'utente loggato è il seller, mostra il nome del buyer
  if (userId === conversation.seller_id) {
    return conversation.buyer?.name || 'Acquirente'
  }
  
  return 'Utente'
}

const getOtherUserId = (conversation) => {
  const userId = authStore.user?.id
  if (!userId) return null
  
  // Se l'utente loggato è il buyer, restituisce l'ID del seller
  if (userId === conversation.buyer_id) {
    return conversation.seller_id
  }
  // Se l'utente loggato è il seller, restituisce l'ID del buyer
  if (userId === conversation.seller_id) {
    return conversation.buyer_id
  }
  
  return null
}

const getConversationTitle = (conversation) => {
  // L'API restituisce snake_case, quindi usiamo card_model invece di cardModel
  if (conversation.listing?.card_model?.player?.name) {
    return `Prodotto: ${conversation.listing.card_model.player.name}`
  } else if (conversation.order?.order_number) {
    return `Ordine #${conversation.order.order_number}`
  } else if (conversation.order?.id) {
    return `Ordine #${conversation.order.id}`
  }
  return 'Conversazione'
}

const getProductName = (conversation) => {
  // Costruisci un nome descrittivo per il prodotto
  // L'API restituisce snake_case, quindi usiamo card_model invece di cardModel
  if (conversation.listing?.card_model) {
    const cardModel = conversation.listing.card_model
    const parts = []
    
    if (cardModel.player?.name) {
      parts.push(cardModel.player.name)
    }
    
    if (cardModel.team?.name) {
      parts.push(cardModel.team.name)
    }
    
    // L'API restituisce card_set invece di cardSet
    if (cardModel.card_set?.name) {
      parts.push(cardModel.card_set.name)
    }
    
    if (cardModel.year) {
      parts.push(cardModel.year)
    }
    
    if (parts.length > 0) {
      return parts.join(' - ')
    }
    
    // Fallback al nome del player se disponibile
    if (cardModel.player?.name) {
      return cardModel.player.name
    }
  }
  
  // Per conversazioni basate su ordini
  if (conversation.order?.order_number) {
    return `Ordine #${conversation.order.order_number}`
  } else if (conversation.order?.id) {
    return `Ordine #${conversation.order.id}`
  }
  
  return 'Prodotto'
}

const getUnreadCount = (conversation) => {
  const userId = authStore.user?.id
  if (!userId) return 0
  
  // Controlla se l'utente è il buyer o il seller in questa conversazione specifica
  if (userId === conversation.buyer_id) {
    return conversation.unread_count_buyer || 0
  } else if (userId === conversation.seller_id) {
    return conversation.unread_count_seller || 0
  }
  return 0
}

const formatTime = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diff = now - date
  const days = Math.floor(diff / (1000 * 60 * 60 * 24))
  
  if (days === 0) {
    return date.toLocaleTimeString('it-IT', { 
      hour: '2-digit', 
      minute: '2-digit' 
    })
  } else if (days === 1) {
    return 'Ieri'
  } else if (days < 7) {
    return date.toLocaleDateString('it-IT', { weekday: 'short' })
  } else {
    return date.toLocaleDateString('it-IT', { 
      day: '2-digit', 
      month: '2-digit' 
    })
  }
}

const openConversation = (conversation) => {
  selectedConversation.value = conversation
  showChatModal.value = true
}

const closeChatModal = () => {
  showChatModal.value = false
  selectedConversation.value = null
  // Ricarica le conversazioni per aggiornare i contatori
  loadConversations()
}

onMounted(async () => {
  // Verifica autenticazione
  if (!authStore.isAuthenticated) {
    error.value = 'Devi essere loggato per vedere le conversazioni'
    return
  }

  // Carica i dati utente se necessario
  if (authStore.token && !authStore.user) {
    try {
      await authStore.fetchUser()
    } catch (err) {
      error.value = 'Errore nel caricamento dei dati utente'
      return
    }
  }

  // Carica le conversazioni
  await loadConversations()
})
</script>
