<template>
  <div class="bg-gray-50 min-h-screen">
    <div class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
      <!-- Header di successo -->
      <div class="text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-green-100">
          <CheckCircleIcon class="h-8 w-8 text-green-600" />
        </div>
        <h1 class="mt-4 text-3xl font-bold tracking-tight text-gray-900">
          Ordine confermato!
        </h1>
        <p class="mt-2 text-lg text-gray-600">
          Grazie per il tuo acquisto. Riceverai una conferma via email a breve.
        </p>
      </div>

      <!-- Dettagli ordine -->
      <div v-if="order" class="mt-8">
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
          <!-- Header ordine -->
          <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-medium text-gray-900">
                  Ordine #{{ order.order_number }}
                </h2>
                <p class="text-sm text-gray-500">
                  Effettuato il {{ formatDate(order.created_at) }}
                </p>
              </div>
              <div class="text-right">
                <p class="text-sm font-medium text-gray-900">
                  Totale: €{{ formatPriceItaliana(order.total_amount) }}
                </p>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                      :class="getStatusClass(order.status)">
                  {{ getStatusText(order.status) }}
                </span>
              </div>
            </div>
          </div>

          <!-- Articoli ordinati -->
          <div class="px-6 py-4">
            <h3 class="text-sm font-medium text-gray-900 mb-4">Articoli ordinati</h3>
            <div class="space-y-4">
              <div v-for="item in orderItems" :key="item.id" 
                   class="flex items-center space-x-4 p-4 bg-gray-50 rounded-lg">
                <img :src="item.image || '/images/placeholder-card.jpg'" 
                     :alt="item.name" 
                     class="h-16 w-16 object-cover rounded-md" />
                <div class="flex-1 min-w-0">
                  <h4 class="text-sm font-medium text-gray-900 truncate">
                    {{ item.name }}
                  </h4>
                  <p class="text-sm text-gray-500">
                    Condizione: {{ item.condition }}
                  </p>
                  <p class="text-sm text-gray-500">
                    Venditore: {{ item.seller_name }}
                  </p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium text-gray-900">
                    €{{ formatPriceItaliana(parseFloat(item.price) * item.quantity) }}
                  </p>
                  <p class="text-sm text-gray-500">
                    {{ item.quantity }}x €{{ formatPriceItaliana(parseFloat(item.price)) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Indirizzo di spedizione -->
          <div v-if="order.shipping_address" class="px-6 py-4 border-t border-gray-200">
            <h3 class="text-sm font-medium text-gray-900 mb-2">Indirizzo di spedizione</h3>
            <div class="text-sm text-gray-600">
              <p>{{ order.shipping_address.first_name }} {{ order.shipping_address.last_name }}</p>
              <p>{{ order.shipping_address.address_line_1 }}</p>
              <p v-if="order.shipping_address.address_line_2">{{ order.shipping_address.address_line_2 }}</p>
              <p>{{ order.shipping_address.postal_code }} {{ order.shipping_address.city }}</p>
              <p>{{ order.shipping_address.country }}</p>
              <p v-if="order.shipping_address.phone">Tel: {{ order.shipping_address.phone }}</p>
            </div>
          </div>

          <!-- Riepilogo costi -->
          <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <dl class="space-y-2">
              <div class="flex justify-between text-sm">
                <dt class="text-gray-600">Subtotale</dt>
                <dd class="font-medium text-gray-900">€{{ formatPriceItaliana(order.subtotal) }}</dd>
              </div>
              <div class="flex justify-between text-sm">
                <dt class="text-gray-600">Spedizione</dt>
                <dd class="font-medium text-gray-900">€{{ formatPriceItaliana(order.shipping_cost) }}</dd>
              </div>
              <div class="flex justify-between text-sm">
                <dt class="text-gray-600">Costo di gestione</dt>
                <dd class="font-medium text-gray-900">€{{ formatPriceItaliana(order.tax_amount) }}</dd>
              </div>
              <div class="flex justify-between text-lg font-medium border-t border-gray-300 pt-2">
                <dt class="text-gray-900">Totale</dt>
                <dd class="text-gray-900">€{{ formatPriceItaliana(order.total_amount) }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <!-- Azioni -->
        <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
          <!-- Bottone Apri Dispute (solo per ordini consegnati in attesa di 72h) -->
          <button 
            v-if="order.status === 'delivered_pending_72h' && !order.has_dispute"
            @click="openDisputeModal"
            class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            Apri Dispute
          </button>
          
          <!-- Badge Dispute Aperta -->
          <div 
            v-if="order.has_dispute"
            class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md bg-red-50 text-red-700 text-sm font-medium"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            Dispute Aperta
          </div>
          
          <button @click="goToOrders" 
                  class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
            </svg>
            Visualizza i miei ordini
          </button>
          <button @click="goToHome" 
                  class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            Torna alla home
          </button>
        </div>
        
        <!-- Modal Dispute -->
        <div v-if="showDisputeModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
          <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeDisputeModal"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex items-center justify-between mb-4">
                  <h3 class="text-lg font-medium text-gray-900">Apri Dispute</h3>
                  <button @click="closeDisputeModal" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>
                
                <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                  <p class="text-sm text-yellow-800">
                    <strong>Attenzione:</strong> Aprendo una dispute, il pagamento al venditore verrà bloccato fino alla risoluzione. 
                    Assicurati di aver tentato di risolvere il problema direttamente con il venditore prima di aprire una dispute.
                  </p>
                </div>
                
                <form @submit.prevent="submitDispute" class="space-y-4">
                  <div>
                    <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">
                      Motivo della dispute <span class="text-red-500">*</span>
                    </label>
                    <select 
                      id="reason"
                      v-model="disputeForm.reason"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                      <option value="">Seleziona un motivo</option>
                      <option value="Prodotto non conforme">Prodotto non conforme alla descrizione</option>
                      <option value="Prodotto danneggiato">Prodotto danneggiato o difettoso</option>
                      <option value="Prodotto mancante">Prodotto mancante o incompleto</option>
                      <option value="Prodotto sbagliato">Prodotto diverso da quello ordinato</option>
                      <option value="Problema spedizione">Problema con la spedizione</option>
                      <option value="Altro">Altro</option>
                    </select>
                  </div>
                  
                  <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                      Descrizione dettagliata
                    </label>
                    <textarea 
                      id="description"
                      v-model="disputeForm.description"
                      rows="4"
                      maxlength="5000"
                      placeholder="Descrivi il problema in dettaglio..."
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500">{{ disputeForm.description?.length || 0 }}/5000 caratteri</p>
                  </div>
                  
                  <div class="flex items-center justify-end space-x-3 pt-4">
                    <button 
                      type="button"
                      @click="closeDisputeModal"
                      class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    >
                      Annulla
                    </button>
                    <button 
                      type="submit"
                      :disabled="!disputeForm.reason || isSubmittingDispute"
                      class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:bg-gray-300 disabled:cursor-not-allowed"
                    >
                      <span v-if="isSubmittingDispute">Invio in corso...</span>
                      <span v-else>Apri Dispute</span>
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Stato di caricamento -->
      <div v-else-if="loading" class="mt-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Caricamento dettagli ordine...</p>
      </div>

      <!-- Errore -->
      <div v-else-if="error" class="mt-8 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-red-100">
          <ExclamationTriangleIcon class="h-8 w-8 text-red-600" />
        </div>
        <h2 class="mt-4 text-xl font-medium text-gray-900">Errore</h2>
        <p class="mt-2 text-gray-600">{{ error }}</p>
        <button @click="goToHome" 
                class="mt-4 inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
          Torna alla home
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CheckCircleIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'
import axios from 'axios'
import { formatPriceItaliana } from '../utils/priceFormatter'

const route = useRoute()
const router = useRouter()

const order = ref(null)
const orderItems = ref([])
const loading = ref(true)
const error = ref(null)
const showDisputeModal = ref(false)
const isSubmittingDispute = ref(false)
const disputeForm = ref({
  reason: '',
  description: ''
})

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('it-IT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 text-yellow-800',
    pending_payment: 'bg-orange-100 text-orange-800',
    paid_funds_held: 'bg-blue-100 text-blue-800',
    label_created: 'bg-indigo-100 text-indigo-800',
    in_transit_verified: 'bg-purple-100 text-purple-800',
    delivered_pending_72h: 'bg-green-100 text-green-800',
    dispute_hold: 'bg-red-100 text-red-800',
    completed: 'bg-emerald-100 text-emerald-800',
    confirmed: 'bg-blue-100 text-blue-800',
    shipped: 'bg-purple-100 text-purple-800',
    delivered: 'bg-green-100 text-green-800',
    cancelled: 'bg-red-100 text-red-800',
    refunded: 'bg-gray-100 text-gray-800',
    paid: 'bg-green-100 text-green-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getStatusText = (status) => {
  const labels = {
    pending: 'In attesa',
    pending_payment: 'Pagamento in attesa',
    paid_funds_held: 'Fondi trattenuti',
    label_created: 'Etichetta creata',
    in_transit_verified: 'In transito',
    delivered_pending_72h: 'Consegnato (72h)',
    dispute_hold: 'Dispute aperta',
    completed: 'Completato',
    confirmed: 'Confermato',
    shipped: 'Spedito',
    delivered: 'Consegnato',
    cancelled: 'Annullato',
    refunded: 'Rimborsato',
    paid: 'Pagato'
  }
  return labels[status] || status
}

const loadOrderDetails = async () => {
  try {
    const orderId = route.params.id
    if (!orderId) {
      throw new Error('ID ordine non fornito')
    }

    const response = await axios.get(`/api/orders/${orderId}`)
    if (response.data.success) {
      order.value = response.data.order
      orderItems.value = response.data.order_items || []
    } else {
      throw new Error(response.data.message || 'Errore nel caricamento dell\'ordine')
    }
  } catch (err) {
    console.error('Errore nel caricamento ordine:', err)
    error.value = err.response?.data?.message || err.message || 'Errore nel caricamento dell\'ordine'
  } finally {
    loading.value = false
  }
}

const goToOrders = () => {
  router.push('/orders')
}

const goToHome = () => {
  router.push('/')
}

const openDisputeModal = () => {
  showDisputeModal.value = true
}

const closeDisputeModal = () => {
  showDisputeModal.value = false
  disputeForm.value = {
    reason: '',
    description: ''
  }
}

const submitDispute = async () => {
  if (!disputeForm.value.reason) {
    return
  }

  isSubmittingDispute.value = true
  try {
    const response = await axios.post(`/api/orders/${order.value.id}/dispute`, {
      reason: disputeForm.value.reason,
      description: disputeForm.value.description || null
    }, {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })

    if (response.data.success) {
      // Ricarica i dettagli dell'ordine per aggiornare lo stato
      await loadOrderDetails()
      closeDisputeModal()
      alert('Dispute aperta con successo. Il pagamento al venditore è stato bloccato.')
    } else {
      throw new Error(response.data.message || 'Errore nell\'apertura della dispute')
    }
  } catch (err) {
    console.error('Errore nell\'apertura dispute:', err)
    alert(err.response?.data?.message || err.message || 'Errore nell\'apertura della dispute')
  } finally {
    isSubmittingDispute.value = false
  }
}

onMounted(() => {
  loadOrderDetails()
})
</script>
