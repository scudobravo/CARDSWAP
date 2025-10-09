<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>

      <!-- This element is to trick the browser into centering the modal contents. -->
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="updateStatus">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-futura-bold text-gray-900">
                Aggiorna Stato Ordine #{{ order?.order_number }}
              </h3>
              <button 
                type="button"
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>

            <div v-if="order" class="space-y-4">
              <!-- Stato Attuale -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-2">Stato Attuale</h4>
                <span :class="getStatusBadgeClass(order.status)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-gill-sans-semibold">
                  {{ getStatusLabel(order.status) }}
                </span>
              </div>

              <!-- Nuovo Stato -->
              <div>
                <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                  Nuovo Stato
                </label>
                <select 
                  v-model="formData.status" 
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                >
                  <option value="">Seleziona stato</option>
                  <option value="confirmed">Confermato</option>
                  <option value="shipped">Spedito</option>
                  <option value="delivered">Consegnato</option>
                  <option value="cancelled">Cancellato</option>
                </select>
              </div>

              <!-- Tracking Number (solo per spedito) -->
              <div v-if="formData.status === 'shipped'">
                <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                  Numero di Tracking
                </label>
                <input 
                  v-model="formData.tracking_number"
                  type="text" 
                  placeholder="Inserisci numero di tracking"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                />
                <p class="mt-1 text-xs text-gray-500">
                  Il numero di tracking sarà inviato all'acquirente via email
                </p>
              </div>

              <!-- Note -->
              <div>
                <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                  Note (opzionale)
                </label>
                <textarea 
                  v-model="formData.notes"
                  rows="3"
                  placeholder="Aggiungi note per l'acquirente..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                ></textarea>
              </div>

              <!-- Preview Email -->
              <div v-if="formData.status" class="bg-blue-50 rounded-lg p-4">
                <h4 class="text-sm font-gill-sans-semibold text-blue-900 mb-2">Anteprima Notifica</h4>
                <p class="text-sm text-blue-800">
                  L'acquirente riceverà una notifica email quando aggiorni lo stato dell'ordine.
                </p>
                <div v-if="formData.status === 'shipped'" class="mt-2 text-sm text-blue-700">
                  <p><strong>Email spedizione:</strong> "Il tuo ordine #{{ order.order_number }} è stato spedito!"</p>
                  <p v-if="formData.tracking_number"><strong>Tracking:</strong> {{ formData.tracking_number }}</p>
                </div>
                <div v-else-if="formData.status === 'delivered'" class="mt-2 text-sm text-blue-700">
                  <p><strong>Email consegna:</strong> "Il tuo ordine #{{ order.order_number }} è stato consegnato!"</p>
                </div>
              </div>

              <!-- Error Message -->
              <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                  <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                  <div class="ml-3">
                    <h3 class="text-sm font-gill-sans-semibold text-red-800">Errore</h3>
                    <p class="mt-1 text-sm text-red-700">{{ error }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button 
              type="submit"
              :disabled="loading || !formData.status"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-gill-sans-semibold text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto sm:text-sm"
            >
              <span v-if="loading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Aggiornando...
              </span>
              <span v-else>Aggiorna Stato</span>
            </button>
            <button 
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-gill-sans-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Annulla
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { XMarkIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  order: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'status-updated'])

const loading = ref(false)
const error = ref('')

const formData = reactive({
  status: '',
  tracking_number: '',
  notes: ''
})

// Watch per resettare tracking number quando cambia stato
watch(() => formData.status, (newStatus) => {
  if (newStatus !== 'shipped') {
    formData.tracking_number = ''
  }
})

const getStatusLabel = (status) => {
  const labels = {
    pending: 'In attesa',
    confirmed: 'Confermato',
    shipped: 'Spedito',
    delivered: 'Consegnato',
    cancelled: 'Cancellato',
    refunded: 'Rimborsato'
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    confirmed: 'bg-blue-100 text-blue-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const updateStatus = async () => {
  if (!formData.status) return

  loading.value = true
  error.value = ''

  try {
    const response = await fetch(`/api/orders/${props.order.id}/status`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        status: formData.status,
        tracking_number: formData.tracking_number || null,
        notes: formData.notes || null
      })
    })

    if (response.ok) {
      const data = await response.json()
      emit('status-updated', data.order)
    } else {
      const errorData = await response.json()
      throw new Error(errorData.message || 'Errore nell\'aggiornamento dello stato')
    }
  } catch (err) {
    console.error('Errore nell\'aggiornamento stato:', err)
    error.value = err.message
  } finally {
    loading.value = false
  }
}
</script>
