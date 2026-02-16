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
          Il pagamento sarà rilasciato 72h dopo che hai segnato come spedito.
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
        <div class="rounded-lg border border-gray-200 bg-white p-4 sm:p-6">
          <h2 class="text-base sm:text-lg font-semibold text-gray-900 mb-4">Articoli (tuoi)</h2>
          <ul role="list" class="divide-y divide-gray-200 border-t border-b border-gray-200">
            <li
              v-for="item in order.order_items"
              :key="item.id"
              class="flex py-4 sm:py-5 first:pt-0 last:pb-0"
            >
              <div class="shrink-0 relative">
                <img
                  v-if="item.image"
                  :src="normalizeImageUrl(item.image)"
                  :alt="item.card_model?.name ?? 'Carta'"
                  class="size-20 rounded-md object-cover sm:size-24"
                />
                <div
                  v-else
                  class="flex items-center justify-center bg-gray-200 rounded-md size-20 sm:size-24"
                >
                  <svg class="w-8 h-8 text-gray-400 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
              </div>
              <div class="ml-3 sm:ml-4 flex flex-1 flex-col min-w-0">
                <h3 class="text-sm sm:text-base font-medium text-gray-900 break-words">
                  {{ item.card_model?.name ?? 'Articolo' }}
                </h3>
                <!-- Condizione e Set (come nel carrello) -->
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 mt-1 mb-2">
                  <p class="text-xs sm:text-sm text-gray-500">{{ getConditionLabel(item.condition) }}</p>
                  <p
                    v-if="item.card_model?.set_name"
                    class="text-xs sm:text-sm text-gray-500 border-l-0 sm:border-l border-gray-200 pl-0 sm:pl-4"
                  >
                    {{ item.card_model.set_name }}
                  </p>
                </div>
                <!-- Prezzo -->
                <p class="text-sm sm:text-base font-medium text-gray-900 mb-2">
                  €{{ formatPrice(item.total_price) }}
                </p>
                <!-- Acquirente (invece di "Venditore" nel carrello) -->
                <p v-if="order.buyer?.name" class="text-xs sm:text-sm text-gray-500 mb-3">
                  Acquirente: {{ order.buyer.name }}
                </p>
                <!-- Da preparare + Quantità (come Disponibilità + Quantità nel carrello) -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mt-auto">
                  <p class="flex items-center space-x-2 text-xs sm:text-sm text-gray-700">
                    <CheckIcon class="size-4 shrink-0 text-green-500 sm:size-5" aria-hidden="true" />
                    <span>Da preparare</span>
                  </p>
                  <p class="text-xs sm:text-sm text-gray-600 whitespace-nowrap">
                    Quantità: {{ item.quantity }}
                  </p>
                </div>
              </div>
            </li>
          </ul>
          <!-- Riepilogo (come nel carrello) -->
          <dl class="mt-4 sm:mt-6 space-y-2">
            <div class="flex justify-between text-sm">
              <dt class="text-gray-600">Subtotale</dt>
              <dd class="font-medium text-gray-900">€{{ formatPrice(order.subtotal_eur) }}</dd>
            </div>
            <div v-if="order.shipping_cost > 0" class="flex justify-between text-sm">
              <dt class="text-gray-600">Spedizione</dt>
              <dd class="font-medium text-gray-900">€{{ formatPrice(order.shipping_cost) }}</dd>
            </div>
            <div class="flex justify-between text-base font-semibold border-t border-gray-200 pt-3 mt-2">
              <dt class="text-gray-900">Totale</dt>
              <dd class="text-gray-900">€{{ formatPrice(order.total_amount) }}</dd>
            </div>
          </dl>
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
import { CheckIcon } from '@heroicons/vue/20/solid'
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

function normalizeImageUrl (url) {
  if (!url) return ''
  if (url.startsWith('/storage/') || url.startsWith('http') || url.startsWith('//')) return url
  return '/storage/' + url
}

function getConditionLabel (condition) {
  const labels = {
    mint: 'Mint',
    near_mint: 'Near Mint',
    excellent: 'Eccellente',
    good: 'Buona',
    light_played: 'Leggermente giocata',
    played: 'Giocata',
    poor: 'Scarsa'
  }
  return labels[condition] || condition || '—'
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
