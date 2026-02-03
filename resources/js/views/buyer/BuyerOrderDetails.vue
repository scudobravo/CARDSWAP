<template>
  <DashboardLayout>
    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin h-10 w-10 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-4 text-red-800">
      <p>{{ error }}</p>
      <router-link :to="{ name: 'purchases.orders' }" class="mt-2 inline-block text-sm font-medium text-red-700 hover:underline">
        ← Torna ai miei ordini
      </router-link>
    </div>

    <template v-else-if="order">
      <!-- Header e link indietro -->
      <div class="mb-8">
        <router-link
          :to="{ name: 'purchases.orders' }"
          class="text-sm text-primary hover:underline mb-2 inline-block"
        >
          ← Torna ai miei ordini
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">Ordine #{{ order.order_number }}</h1>
        <p class="text-sm text-gray-500 mt-1">Dettaglio ordine</p>
      </div>

      <!-- 1. Header stato ordine (badge + testo) -->
      <div class="mb-6">
        <OrderStatusHeader :status="order.shipment_status" />
      </div>

      <!-- 2. Blocco Spedizione -->
      <div class="mb-6">
        <ShipmentInfoCard
          :is-tracked="!!order.is_tracked"
          :tracking-number="order.tracking_number || null"
          :carrier-slug="order.carrier_code || null"
          :tracking-url="order.tracking_url || null"
          :shipped-at="shippedAtIso"
        />
      </div>

      <!-- 3. Blocco Assicurazione -->
      <div class="mb-6">
        <InsuranceBadge :insurance-fee="insuranceFee" />
      </div>

      <!-- 4. Blocco Pagamento & Garanzie -->
      <div class="mb-6">
        <PaymentStatusBox
          :shipment-status="order.shipment_status"
          :is-tracked="!!order.is_tracked"
        />
      </div>

      <!-- 5. Blocco Apri Disputa -->
      <div class="mb-6">
        <DisputeButton
          :visible="disputeButtonVisible"
          :loading="disputeLoading"
          @open="openDisputeModal"
        />
      </div>

      <!-- 6. Timeline ordine -->
      <div class="mb-8">
        <OrderTimeline
          :paid-at="order.paid_at"
          :shipped-at="shippedAtIso"
          :delivered-at="order.delivered_at"
          :shipment-status="order.shipment_status"
          :payout-status="order.payout_status"
        />
      </div>

      <!-- Articoli ordinati -->
      <div class="mb-8 rounded-lg border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Articoli ordinati</h2>
        <div class="space-y-4">
          <div
            v-for="item in orderItems"
            :key="item.id"
            class="flex items-center gap-4 p-4 bg-gray-50 rounded-lg"
          >
            <img
              :src="item.image || '/images/placeholder-card.jpg'"
              :alt="item.name"
              class="h-16 w-16 object-cover rounded-md"
            />
            <div class="flex-1 min-w-0">
              <h3 class="text-sm font-medium text-gray-900 truncate">{{ item.name }}</h3>
              <p class="text-sm text-gray-500">Condizione: {{ item.condition }}</p>
              <p class="text-sm text-gray-500">Venditore: {{ item.seller_name }}</p>
            </div>
            <div class="text-right">
              <p class="text-sm font-medium text-gray-900">
                €{{ formatPrice(parseFloat(item.price) * item.quantity) }}
              </p>
              <p class="text-sm text-gray-500">{{ item.quantity }}× €{{ formatPrice(parseFloat(item.price)) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Indirizzo di spedizione -->
      <div v-if="order.shipping_address" class="mb-8 rounded-lg border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-3">Indirizzo di spedizione</h2>
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
      <div class="rounded-lg border border-gray-200 bg-gray-50 p-6">
        <dl class="space-y-2">
          <div class="flex justify-between text-sm">
            <dt class="text-gray-600">Subtotale</dt>
            <dd class="font-medium text-gray-900">€{{ formatPrice(order.subtotal) }}</dd>
          </div>
          <div class="flex justify-between text-sm">
            <dt class="text-gray-600">Spedizione</dt>
            <dd class="font-medium text-gray-900">€{{ formatPrice(order.shipping_cost) }}</dd>
          </div>
          <div v-if="order.tax_amount > 0" class="flex justify-between text-sm">
            <dt class="text-gray-600">Costo di gestione</dt>
            <dd class="font-medium text-gray-900">€{{ formatPrice(order.tax_amount) }}</dd>
          </div>
          <div class="flex justify-between text-lg font-medium border-t border-gray-300 pt-2 mt-2">
            <dt class="text-gray-900">Totale</dt>
            <dd class="text-gray-900">€{{ formatPrice(order.total_amount) }}</dd>
          </div>
        </dl>
      </div>

      <!-- Modal Disputa -->
      <div
        v-if="showDisputeModal"
        class="fixed inset-0 z-50 overflow-y-auto"
        aria-labelledby="modal-title"
        role="dialog"
        aria-modal="true"
      >
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="closeDisputeModal" />
          <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
              <div class="flex justify-between items-center mb-4">
                <h3 id="modal-title" class="text-lg font-medium text-gray-900">Apri disputa</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" @click="closeDisputeModal">
                  <span class="sr-only">Chiudi</span>
                  <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                  </svg>
                </button>
              </div>
              <p class="text-sm text-gray-600 mb-4">
                Puoi aprire una disputa fino al rilascio del pagamento.
              </p>
              <form @submit.prevent="submitDispute" class="space-y-4">
                <div>
                  <label for="dispute-reason" class="block text-sm font-medium text-gray-700 mb-1">
                    Motivo <span class="text-red-500">*</span>
                  </label>
                  <select
                    id="dispute-reason"
                    v-model="disputeForm.reason"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                  >
                    <option value="">Seleziona un motivo</option>
                    <option value="NOT_RECEIVED">Non ho ricevuto l'ordine</option>
                    <option value="NOT_AS_DESCRIBED">Prodotto non come descritto</option>
                    <option value="OTHER">Altro</option>
                  </select>
                </div>
                <div>
                  <label for="dispute-description" class="block text-sm font-medium text-gray-700 mb-1">
                    Descrizione (opzionale)
                  </label>
                  <textarea
                    id="dispute-description"
                    v-model="disputeForm.description"
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary"
                    placeholder="Descrivi il problema..."
                  />
                </div>
                <div class="flex justify-end gap-3 pt-2">
                  <button
                    type="button"
                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50"
                    @click="closeDisputeModal"
                  >
                    Annulla
                  </button>
                  <button
                    type="submit"
                    :disabled="disputeLoading"
                    class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
                  >
                    {{ disputeLoading ? 'Invio...' : 'Apri disputa' }}
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </template>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import OrderStatusHeader from '@/components/orders/buyer/OrderStatusHeader.vue'
import ShipmentInfoCard from '@/components/orders/buyer/ShipmentInfoCard.vue'
import InsuranceBadge from '@/components/orders/buyer/InsuranceBadge.vue'
import PaymentStatusBox from '@/components/orders/buyer/PaymentStatusBox.vue'
import DisputeButton from '@/components/orders/buyer/DisputeButton.vue'
import OrderTimeline from '@/components/orders/buyer/OrderTimeline.vue'

const route = useRoute()
const orderId = computed(() => route.params.orderId)

const order = ref(null)
const orderItems = ref([])
const loading = ref(true)
const error = ref(null)
const disputeLoading = ref(false)
const showDisputeModal = ref(false)
const disputeForm = ref({ reason: '', description: '' })

const insuranceFee = computed(() => {
  const v = order.value?.insurance_fee
  return typeof v === 'number' ? v : parseFloat(v) || 0
})

const shippedAtIso = computed(() => {
  const d = order.value?.shipped_at
  if (!d) return null
  return typeof d === 'string' ? d : (d && d.toISOString ? d.toISOString() : null)
})

const disputeButtonVisible = computed(() => {
  if (!order.value) return false
  const s = order.value.shipment_status
  if (s === 'RELEASED' || s === 'REFUNDED' || s === 'CANCELLED') return false
  if (order.value.has_dispute) return false
  return true
})

function formatPrice (value) {
  const n = parseFloat(value)
  if (Number.isNaN(n)) return '0,00'
  return n.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

async function loadOrder () {
  loading.value = true
  error.value = null
  try {
    const { data } = await axios.get(`/api/orders/${orderId.value}`)
    if (data.success) {
      order.value = data.order
      orderItems.value = data.order_items || []
    } else {
      error.value = data.message || 'Errore nel caricamento dell\'ordine'
    }
  } catch (err) {
    if (err.response?.status === 404) {
      error.value = 'Ordine non trovato.'
    } else {
      error.value = err.response?.data?.message || err.message || 'Errore nel caricamento dell\'ordine'
    }
  } finally {
    loading.value = false
  }
}

function openDisputeModal () {
  showDisputeModal.value = true
  disputeForm.value = { reason: '', description: '' }
}

function closeDisputeModal () {
  showDisputeModal.value = false
  disputeForm.value = { reason: '', description: '' }
}

async function submitDispute () {
  if (!disputeForm.value.reason) return
  disputeLoading.value = true
  try {
    const { data } = await axios.post(`/api/orders/${order.value.id}/dispute`, {
      reason: disputeForm.value.reason,
      description: disputeForm.value.description || null
    })
    if (data.success) {
      await loadOrder()
      closeDisputeModal()
      // Messaggio successo opzionale
    } else {
      throw new Error(data.message || 'Errore nell\'apertura della disputa')
    }
  } catch (err) {
    alert(err.response?.data?.message || err.message || 'Errore nell\'apertura della disputa')
  } finally {
    disputeLoading.value = false
  }
}

onMounted(() => {
  loadOrder()
})
</script>
