<template>
  <!-- Overlay -->
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="closeModal">
    <!-- Modal Content -->
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 h-[600px] flex flex-col" @click.stop>
      <!-- Header -->
      <div class="flex justify-between items-center p-6 border-b border-gray-200">
        <div>
          <h3 class="text-xl font-futura-bold text-primary">Chat con {{ vendorName }}</h3>
          <p class="text-sm text-gray-600 font-gill-sans">Prodotto: {{ productName }}</p>
        </div>
        <button @click="closeModal" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Messages Area -->
      <div class="flex-1 overflow-y-auto p-6 space-y-4" ref="messagesContainer">
        <!-- Loading State -->
        <div v-if="isLoading" class="flex justify-center items-center h-32">
          <div class="flex items-center space-x-3">
            <svg class="animate-spin h-6 w-6 text-secondary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-gray-600 font-gill-sans">Caricamento messaggi...</span>
          </div>
        </div>

        <!-- No Messages -->
        <div v-else-if="messages.length === 0" class="text-center py-8">
          <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
          </svg>
          <p class="text-gray-500 font-gill-sans">Nessun messaggio ancora. Inizia la conversazione!</p>
        </div>

        <!-- Messages List -->
        <div v-else class="space-y-4">
          <div 
            v-for="message in messages" 
            :key="message.id"
            :class="[
              'flex',
              message.sender_id === currentUserId ? 'justify-end' : 'justify-start'
            ]"
          >
            <div 
              :class="[
                'max-w-xs lg:max-w-md px-4 py-2 rounded-lg',
                message.sender_id === currentUserId 
                  ? 'bg-primary text-white' 
                  : 'bg-gray-100 text-gray-900'
              ]"
            >
              <p class="text-sm font-gill-sans">{{ message.message }}</p>
              <p 
                :class="[
                  'text-xs mt-1',
                  message.sender_id === currentUserId ? 'text-primary-200' : 'text-gray-500'
                ]"
              >
                {{ formatTime(message.created_at) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Message Input -->
      <div class="p-6 border-t border-gray-200">
        <form @submit.prevent="sendMessage" class="flex space-x-3">
          <input 
            v-model="newMessage"
            type="text" 
            placeholder="Scrivi un messaggio..."
            class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary font-gill-sans text-sm"
            :disabled="isSending"
          />
          <button 
            type="submit"
            :disabled="!newMessage.trim() || isSending"
            class="px-6 py-2 bg-primary text-white rounded-lg font-futura-bold text-sm hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isSending">Invio...</span>
            <span v-else>Invia</span>
          </button>
        </form>
      </div>

      <!-- Error Message -->
      <div v-if="error" class="p-4 bg-red-100 border-t border-red-200 text-red-700 text-sm font-gill-sans">
        {{ error }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue'
import { useAuthStore } from '../stores/auth.js'

// Props
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  productId: {
    type: [String, Number],
    required: true
  },
  vendorId: {
    type: [String, Number],
    required: true
  },
  vendorName: {
    type: String,
    default: 'Venditore'
  },
  productName: {
    type: String,
    default: 'Prodotto'
  }
})

// Emits
const emit = defineEmits(['close'])

// Store
const authStore = useAuthStore()

// Refs
const messagesContainer = ref(null)
const newMessage = ref('')
const messages = ref([])
const isLoading = ref(false)
const isSending = ref(false)
const error = ref('')
const conversationId = ref(null)

// Computed
const currentUserId = computed(() => authStore.user?.id)

// Methods
const closeModal = () => {
  emit('close')
  resetModal()
}

const resetModal = () => {
  newMessage.value = ''
  messages.value = []
  conversationId.value = null
  error.value = ''
}

const formatTime = (timestamp) => {
  const date = new Date(timestamp)
  return date.toLocaleTimeString('it-IT', { 
    hour: '2-digit', 
    minute: '2-digit' 
  })
}

// TODO: Implementare il caricamento dei messaggi quando l'API sarà estesa

const sendMessage = async () => {
  if (!newMessage.value.trim()) return
  
  isSending.value = true
  error.value = ''
  
  try {
    // Per ora, simula l'invio del messaggio
    const message = {
      id: Date.now(),
      message: newMessage.value.trim(),
      sender_id: currentUserId.value,
      created_at: new Date().toISOString()
    }
    
    messages.value.push(message)
    newMessage.value = ''
    scrollToBottom()
    
    // TODO: Implementare l'invio reale dei messaggi
    // Il sistema attuale richiede una conversazione basata su ordine
    // Dovremmo estendere l'API per supportare conversazioni su prodotti
    
  } catch (err) {
    console.error('Error sending message:', err)
    error.value = 'Errore nell\'invio del messaggio'
  } finally {
    isSending.value = false
  }
}

const startConversation = async () => {
  isLoading.value = true
  error.value = ''
  
  try {
    // Per ora, simula una conversazione esistente
    // In futuro, questo dovrebbe creare una conversazione basata sul prodotto
    conversationId.value = `product_${props.productId}_vendor_${props.vendorId}`
    
    // Simula il caricamento dei messaggi
    await new Promise(resolve => setTimeout(resolve, 1000))
    messages.value = []
    
    // TODO: Implementare la creazione di conversazioni per prodotti
    // Il sistema attuale richiede un order_id, ma per i prodotti dovremmo
    // creare un sistema diverso o modificare l'API esistente
  } catch (err) {
    console.error('Error starting conversation:', err)
    error.value = 'Errore nell\'avvio della conversazione. Assicurati di essere loggato.'
  } finally {
    isLoading.value = false
  }
}

const scrollToBottom = () => {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
    }
  })
}

// Watch for modal open
watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    resetModal()
    startConversation()
  }
})

// Auto-scroll when new messages arrive
watch(messages, () => {
  scrollToBottom()
}, { deep: true })
</script>
