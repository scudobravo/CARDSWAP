<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>

      <!-- This element is to trick the browser into centering the modal contents. -->
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-futura-bold text-gray-900">
              Dettagli Ordine #{{ order?.order_number }}
            </h3>
            <button 
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-primary"
            >
              <XMarkIcon class="h-6 w-6" />
            </button>
          </div>

          <div v-if="order" class="space-y-6">
            <!-- Info Generali -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Info Acquirente -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-3">Informazioni Acquirente</h4>
                <div class="space-y-2 text-sm">
                  <p><span class="font-gill-sans-semibold">Nome:</span> {{ order.buyer?.name || 'N/A' }}</p>
                  <p><span class="font-gill-sans-semibold">Email:</span> {{ order.buyer?.email || 'N/A' }}</p>
                  <p><span class="font-gill-sans-semibold">Data Ordine:</span> {{ formatDate(order.created_at) }}</p>
                </div>
              </div>

              <!-- Info Ordine -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-3">Stato Ordine</h4>
                <div class="space-y-2 text-sm">
                  <p>
                    <span class="font-gill-sans-semibold">Stato:</span> 
                    <span :class="getStatusBadgeClass(order.status)" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-gill-sans-semibold">
                      {{ getStatusLabel(order.status) }}
                    </span>
                  </p>
                  <p v-if="order.tracking_number">
                    <span class="font-gill-sans-semibold">Tracking:</span> 
                    <span class="font-mono">{{ order.tracking_number }}</span>
                  </p>
                  <p v-if="order.label_url">
                    <span class="font-gill-sans-semibold">Link Etichetta:</span> 
                    <a 
                      :href="order.label_url" 
                      target="_blank" 
                      rel="noopener noreferrer"
                      class="text-blue-600 hover:text-blue-800 underline ml-1"
                    >
                      Scarica etichetta
                    </a>
                  </p>
                  <p v-if="order.shipped_at">
                    <span class="font-gill-sans-semibold">Spedito il:</span> {{ formatDate(order.shipped_at) }}
                  </p>
                  <p v-if="order.delivered_at">
                    <span class="font-gill-sans-semibold">Consegnato il:</span> {{ formatDate(order.delivered_at) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Indirizzo di Spedizione -->
            <div v-if="order.shipping_address" class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-3">Indirizzo di Spedizione</h4>
              <div class="text-sm text-gray-600">
                <p>{{ order.shipping_address.name || order.buyer?.name }}</p>
                <p>{{ order.shipping_address.address_line_1 }}</p>
                <p v-if="order.shipping_address.address_line_2">{{ order.shipping_address.address_line_2 }}</p>
                <p>{{ order.shipping_address.city }}, {{ order.shipping_address.postal_code }}</p>
                <p>{{ order.shipping_address.country }}</p>
              </div>
            </div>

            <!-- Prodotti -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-3">Prodotti Ordinati</h4>
              <div v-if="getOrderItems(order) && getOrderItems(order).length > 0" class="space-y-3">
                <div 
                  v-for="item in getOrderItems(order)" 
                  :key="item.id"
                  class="flex items-center space-x-4 p-3 bg-white rounded-md border border-gray-200"
                >
                  <!-- Immagine Prodotto -->
                  <div class="flex-shrink-0 relative">
                    <img 
                      v-if="getItemImage(item)"
                      :src="getItemImage(item)" 
                      :alt="getItemName(item)"
                      class="h-16 w-16 object-cover rounded-md"
                      @error="handleImageError"
                    />
                    <div v-else class="h-16 w-16 bg-gray-200 rounded-md flex items-center justify-center">
                      <PhotoIcon class="h-8 w-8 text-gray-400" />
                    </div>
                    <!-- Placeholder nascosto per gestione errore -->
                    <div class="hidden h-16 w-16 bg-gray-200 rounded-md flex items-center justify-center absolute inset-0">
                      <PhotoIcon class="h-8 w-8 text-gray-400" />
                    </div>
                  </div>

                  <!-- Dettagli Prodotto -->
                  <div class="flex-1 min-w-0">
                    <h5 class="text-sm font-gill-sans-semibold text-gray-900 truncate">
                      {{ getItemName(item) }}
                    </h5>
                    <p class="text-sm text-gray-500">
                      Condizione: {{ getConditionLabel(item.condition) }}
                    </p>
                    <p class="text-sm text-gray-500">
                      Quantità: {{ item.quantity }}
                    </p>
                  </div>

                  <!-- Prezzo -->
                  <div class="text-right">
                    <p class="text-sm font-gill-sans-semibold text-gray-900">
                      €{{ formatPriceItaliana(item.unit_price || item.price || 0) }}
                    </p>
                    <p class="text-xs text-gray-500">
                      Totale: €{{ formatPriceItaliana(item.total_price || (item.unit_price || item.price || 0) * (item.quantity || 1)) }}
                    </p>
                  </div>
                </div>
              </div>
              <div v-else class="text-center py-8 text-gray-500">
                <p>Nessun prodotto trovato per questo ordine</p>
              </div>
            </div>

            <!-- Riepilogo Costi (lato venditore: solo subtotale + spedizione; lato acquirente: include costo di gestione) -->
            <div class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-3">Riepilogo Costi</h4>
              <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                  <span>Subtotale:</span>
                  <span>€{{ order.subtotal }}</span>
                </div>
                <div class="flex justify-between">
                  <span>Spedizione:</span>
                  <span>€{{ order.shipping_cost }}</span>
                </div>
                <div v-if="!isSellerView && order.tax_amount > 0" class="flex justify-between">
                  <span>Costo di gestione:</span>
                  <span>€{{ order.tax_amount }}</span>
                </div>
                <div class="flex justify-between text-base font-gill-sans-semibold text-gray-900 border-t border-gray-300 pt-2">
                  <span>Totale:</span>
                  <span>€{{ displayTotal }}</span>
                </div>
              </div>
            </div>

            <!-- Note -->
            <div v-if="order.notes" class="bg-gray-50 rounded-lg p-4">
              <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-3">Note</h4>
              <p class="text-sm text-gray-600">{{ order.notes }}</p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button 
            @click="$emit('close')"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-gill-sans-semibold text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:ml-3 sm:w-auto sm:text-sm"
          >
            Chiudi
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { XMarkIcon, PhotoIcon } from '@heroicons/vue/24/outline'
import { formatPriceItaliana } from '../../utils/priceFormatter'

const props = defineProps({
  order: {
    type: Object,
    required: true
  },
  /** Se true, vista venditore: non mostrare costo di gestione (pagato dall'acquirente) e totale = subtotale + spedizione */
  isSellerView: {
    type: Boolean,
    default: false
  }
})

// Totale da mostrare: per il venditore solo subtotale + spedizione; per l'acquirente il total_amount
const displayTotal = computed(() => {
  if (!props.order) return '0.00'
  if (props.isSellerView) {
    const sub = parseFloat(props.order.subtotal ?? props.order.subtotal_eur ?? 0) || 0
    const ship = parseFloat(props.order.shipping_cost ?? 0) || 0
    return (sub + ship).toFixed(2)
  }
  return props.order.total_amount ?? '0.00'
})

defineEmits(['close', 'status-updated'])

const getStatusLabel = (status) => {
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
    cancelled: 'Cancellato',
    refunded: 'Rimborsato'
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status) => {
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
    refunded: 'bg-gray-100 text-gray-800'
  }
  return classes[status] || 'bg-gray-100 text-gray-800'
}

const getConditionLabel = (condition) => {
  const labels = {
    mint: 'Mint',
    near_mint: 'Near Mint',
    excellent: 'Eccellente',
    good: 'Buona',
    light_played: 'Leggermente giocata',
    played: 'Giocata',
    poor: 'Scarsa'
  }
  return labels[condition] || condition
}

// Helper per gestire sia orderItems che order_items (snake_case vs camelCase)
const getOrderItems = (order) => {
  return order.orderItems || order.order_items || []
}

// Helper per ottenere il nome del prodotto
const getItemName = (item) => {
  return item.cardListing?.cardModel?.name || 
         item.card_listing?.card_model?.name || 
         item.name || 
         'Prodotto'
}

// Helper per ottenere l'immagine del prodotto
const getItemImage = (item) => {
  const images = item.cardListing?.images || 
                 item.card_listing?.images || 
                 []
  
  if (images && images.length > 0) {
    let imagePath = images[0]
    // Se l'immagine non inizia con http o /storage/, aggiungi /storage/
    if (imagePath && !imagePath.startsWith('http') && !imagePath.startsWith('/storage/')) {
      imagePath = '/storage/' + imagePath.replace(/^\//, '')
    }
    return imagePath
  }
  return null
}

// Gestione errore caricamento immagine
const handleImageError = (event) => {
  // Nascondi l'immagine fallita
  event.target.style.display = 'none'
  // Mostra il placeholder (sibling nel div parent)
  const placeholder = event.target.parentElement.querySelector('.absolute')
  if (placeholder) {
    placeholder.classList.remove('hidden')
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  return new Date(dateString).toLocaleDateString('it-IT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>
