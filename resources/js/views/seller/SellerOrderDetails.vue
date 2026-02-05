<template>
  <DashboardLayout>
    <div v-if="loading" class="flex justify-center py-12">
      <svg class="animate-spin h-10 w-10 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
      </svg>
    </div>

    <template v-else-if="order">
      <!-- Header -->
      <div class="mb-8">
        <router-link
          :to="{ name: 'sales.orders' }"
          class="text-sm text-primary hover:underline mb-2 inline-block"
        >
          ← Torna agli ordini
        </router-link>
        <h1 class="text-2xl font-bold text-gray-900">Ordine #{{ order.order_number }}</h1>
        <p class="text-sm text-gray-500 mt-1">Dettaglio ordine venditore</p>
      </div>

      <!-- Stato spedizione -->
      <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-2">Stato spedizione</h2>
        <ShipmentStatusBadge :status="order.shipment_status" />
      </div>

      <!-- Riepilogo spedizione (sempre visibile) -->
      <div class="mb-6">
        <ShippingSummaryCard :order-shipping="order.order_shipping" />
      </div>

      <!-- Messaggio assicurata (se applicabile) -->
      <div
        v-if="order.order_shipping?.insurance_included || (order.order_shipping?.insurance_fee ?? 0) > 0"
        class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
      >
        Spedizione assicurata: potrebbe essere richiesta documentazione in caso di problema.
      </div>

      <!-- CASO A: Spedizione tracciata (solo se metodo è esplicitamente tracciato: richiede tracking) -->
      <div v-if="isTrackedMethod" class="mb-6">
        <TrackingForm
          :order-id="order.id"
          :existing-tracking="existingTracking"
          :tracking-deadline-at="order.tracking_deadline_at"
          @submitted="onTrackingSubmitted"
        />
      </div>

      <!-- CASO B: Spedizione non tracciata O metodo sconosciuto → solo "Segna come spedito" (nessuna minaccia annullamento) -->
      <div v-else-if="isUntrackedMethod || showTrackingFallback" class="mb-6">
        <UntrackedShipmentAction
          :order-id="order.id"
          :shipped-at="order.shipped_at"
          @marked="onMarkedShipped"
        />
      </div>

      <!-- Blocco Rilascio pagamento -->
      <div class="mb-8 rounded-lg border border-gray-200 bg-gray-50 p-4">
        <h2 class="text-sm font-semibold text-gray-700 mb-2">Rilascio pagamento</h2>
        <p v-if="order.shipment_status === 'DISPUTED'" class="text-sm text-red-700">
          Pagamento congelato per disputa.
        </p>
        <p v-else-if="isTrackedMethod" class="text-sm text-gray-700">
          Il pagamento sarà rilasciato 72h dopo la consegna verificata.
        </p>
        <p v-else class="text-sm text-gray-700">
          Il pagamento sarà rilasciato automaticamente dopo il periodo di attesa.
        </p>
      </div>

      <!-- Dati ordine (indirizzo, articoli) -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <h2 class="text-sm font-semibold text-gray-900 mb-3">Indirizzo di spedizione</h2>
          <address v-if="order.shipping_address" class="text-sm text-gray-600 not-italic">
            <span v-if="order.shipping_address.first_name || order.shipping_address.last_name">
              {{ order.shipping_address.first_name }} {{ order.shipping_address.last_name }}<br>
            </span>
            {{ order.shipping_address.address_line_1 ?? order.shipping_address.street ?? '' }}<br>
            <span v-if="order.shipping_address.address_line_2">{{ order.shipping_address.address_line_2 }}<br></span>
            {{ order.shipping_address.postal_code ?? '' }} {{ order.shipping_address.city ?? '' }}<br>
            {{ order.shipping_address.country ?? '' }}
            <span v-if="order.shipping_address.phone"><br>Tel. {{ order.shipping_address.phone }}</span>
          </address>
          <p v-else class="text-sm text-gray-500">—</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4">
          <h2 class="text-sm font-semibold text-gray-900 mb-3">Articoli (tuoi)</h2>
          <ul class="space-y-2 text-sm text-gray-700">
            <li v-for="item in order.order_items" :key="item.id" class="flex justify-between">
              <span>{{ item.card_model?.name ?? 'Articolo' }} × {{ item.quantity }}</span>
              <span>€{{ formatPrice(item.total_price) }}</span>
            </li>
          </ul>
          <p class="mt-3 text-sm font-medium text-gray-900">
            Subtotale: €{{ formatPrice(order.subtotal_eur) }}
          </p>
          <p v-if="order.shipping_cost > 0" class="text-sm text-gray-600">
            Spedizione: €{{ formatPrice(order.shipping_cost) }}
          </p>
          <p class="text-sm font-semibold text-gray-900">
            Totale: €{{ formatPrice(order.total_amount) }}
          </p>
        </div>
      </div>
    </template>

    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 p-6 text-red-700">
      {{ error }}
      <router-link :to="{ name: 'sales.orders' }" class="mt-4 inline-block text-sm font-medium underline">
        Torna agli ordini
      </router-link>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import ShipmentStatusBadge from '@/components/orders/ShipmentStatusBadge.vue'
import TrackingForm from '@/components/orders/TrackingForm.vue'
import UntrackedShipmentAction from '@/components/orders/UntrackedShipmentAction.vue'
import ShippingSummaryCard from '@/components/orders/ShippingSummaryCard.vue'

const route = useRoute()
const orderId = computed(() => route.params.orderId)

const loading = ref(true)
const error = ref('')
const order = ref(null)

const TRACKED_METHODS = ['TRACKED_STANDARD', 'TRACKED_EXPRESS', 'TRACKED_INSURED']
const UNTRACKED_METHODS = ['UNTRACKED_STANDARD']
/** Soglia prezzo (€) oltre la quale ordini legacy (senza metodo) si considerano tracciati */
const LEGACY_TRACKED_PRICE_THRESHOLD = 6

const isTrackedMethod = computed(() => {
  const os = order.value?.order_shipping
  const m = os?.shipping_method
  if (m && TRACKED_METHODS.includes(m)) return true
  // Ordini legacy (metodo assente): solo se prezzo >= soglia richiediamo tracking (es. €8 sì, €3 no)
  if (!m && (os?.shipping_price ?? 0) >= LEGACY_TRACKED_PRICE_THRESHOLD) return true
  return false
})

const isUntrackedMethod = computed(() => {
  const m = order.value?.order_shipping?.shipping_method
  return m && UNTRACKED_METHODS.includes(m)
})

const showTrackingFallback = computed(() => {
  if (isTrackedMethod.value || isUntrackedMethod.value) return false
  return true
})

const existingTracking = computed(() => {
  if (!order.value?.tracking_number) return null
  return {
    tracking_number: order.value.tracking_number,
    carrier_code: order.value.carrier_code,
    shipped_at: order.value.shipped_at
  }
})

function formatPrice (n) {
  return Number(n).toFixed(2)
}

async function fetchOrder () {
  if (!orderId.value) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.get(`/api/seller/orders/${orderId.value}`)
    if (data.success) {
      order.value = data.data
    } else {
      error.value = data.message || 'Ordine non trovato'
    }
  } catch (e) {
    if (e.response?.status === 404) {
      error.value = 'Ordine non trovato'
    } else {
      error.value = e.response?.data?.message || e.message || 'Errore nel caricamento'
    }
  } finally {
    loading.value = false
  }
}

function onTrackingSubmitted () {
  fetchOrder()
}

function onMarkedShipped () {
  fetchOrder()
}

onMounted(fetchOrder)
watch(orderId, fetchOrder)
</script>
