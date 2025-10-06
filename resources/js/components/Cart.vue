<template>
  <div class="cart-container">
    <!-- Header del carrello -->
    <div class="cart-header">
      <h1 class="text-2xl font-bold text-gray-900 mb-4">
        Carrello ({{ totalItems }} {{ totalItems === 1 ? 'articolo' : 'articoli' }})
      </h1>
    </div>

    <!-- Carrello vuoto -->
    <div v-if="isEmpty" class="empty-cart text-center py-12">
      <div class="text-gray-400 mb-4">
        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">Il tuo carrello è vuoto</h3>
      <p class="text-gray-500 mb-6">Aggiungi alcuni articoli per iniziare lo shopping</p>
      <router-link to="/" 
                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
        Continua lo shopping
      </router-link>
    </div>

    <!-- Carrello con articoli -->
    <div v-else class="cart-content">
      <!-- Lista venditori -->
      <div class="sellers-list space-y-6">
        <div v-for="seller in sellers" :key="seller.id" class="seller-group">
          <!-- Header venditore -->
          <div class="seller-header bg-gray-50 p-4 rounded-lg mb-4">
            <div class="flex items-center justify-between">
              <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                  <span class="text-blue-600 font-semibold text-sm">
                    {{ seller.name.charAt(0).toUpperCase() }}
                  </span>
                </div>
                <div>
                  <h3 class="font-semibold text-gray-900">{{ seller.name }}</h3>
                  <p class="text-sm text-gray-500">{{ seller.items.length }} {{ seller.items.length === 1 ? 'articolo' : 'articoli' }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-sm text-gray-500">Subtotale</p>
                <p class="font-semibold text-gray-900">€{{ seller.subtotal.toFixed(2) }}</p>
              </div>
            </div>
          </div>

          <!-- Articoli del venditore -->
          <div class="seller-items space-y-4">
            <div v-for="item in seller.items" :key="item.id" 
                 class="cart-item bg-white border border-gray-200 rounded-lg p-4">
              <div class="flex items-start space-x-4">
                <!-- Immagine prodotto -->
                <div class="product-image w-20 h-20 bg-gray-100 rounded-lg flex-shrink-0">
                  <img v-if="item.images && item.images[0]" 
                       :src="item.images[0]" 
                       :alt="item.cardModel?.name || 'Prodotto'"
                       class="w-full h-full object-cover rounded-lg">
                  <div v-else class="w-full h-full flex items-center justify-center text-gray-400">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                  </div>
                </div>

                <!-- Dettagli prodotto -->
                <div class="product-details flex-1">
                  <h4 class="font-medium text-gray-900 mb-1">
                    {{ item.cardModel?.name || 'Prodotto' }}
                  </h4>
                  <p class="text-sm text-gray-500 mb-2">
                    Condizione: {{ getConditionLabel(item.condition) }}
                  </p>
                  <p class="text-sm text-gray-600">
                    {{ item.description || 'Nessuna descrizione' }}
                  </p>
                </div>

                <!-- Prezzo e quantità -->
                <div class="product-actions flex items-center space-x-4">
                  <!-- Controllo quantità -->
                  <div class="quantity-controls flex items-center space-x-2">
                    <button @click="updateQuantity(item.id, seller.id, item.quantity - 1)"
                            class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                      </svg>
                    </button>
                    <span class="w-8 text-center font-medium">{{ item.quantity }}</span>
                    <button @click="updateQuantity(item.id, seller.id, item.quantity + 1)"
                            class="w-8 h-8 bg-gray-100 hover:bg-gray-200 rounded-full flex items-center justify-center transition-colors">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                      </svg>
                    </button>
                  </div>

                  <!-- Prezzo -->
                  <div class="text-right">
                    <p class="font-semibold text-gray-900">€{{ (item.price * item.quantity).toFixed(2) }}</p>
                    <p class="text-sm text-gray-500">€{{ item.price.toFixed(2) }} cad.</p>
                  </div>

                  <!-- Rimuovi -->
                  <button @click="removeFromCart(item.id, seller.id)"
                          class="text-red-500 hover:text-red-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Selezione spedizione per venditore -->
          <div class="shipping-selection bg-gray-50 p-4 rounded-lg mt-4">
            <h4 class="font-medium text-gray-900 mb-3">Metodo di spedizione</h4>
            <div class="shipping-options space-y-2">
              <div v-for="zone in getAvailableShippingZones(seller.items[0])" :key="zone.id"
                   class="shipping-option flex items-center justify-between p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-blue-300 transition-colors"
                   :class="{ 'border-blue-500 bg-blue-50': selectedShippingZones[seller.id] === zone.id }"
                   @click="selectShippingZone(seller.id, zone.id)">
                <div>
                  <p class="font-medium text-gray-900">{{ zone.name }}</p>
                  <p class="text-sm text-gray-500">
                    {{ zone.delivery_days_min }}-{{ zone.delivery_days_max }} giorni lavorativi
                  </p>
                </div>
                <div class="text-right">
                  <p class="font-semibold text-gray-900">€{{ getShippingCost(zone, seller.items[0]) }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Totale venditore -->
          <div class="seller-total bg-blue-50 p-4 rounded-lg mt-4">
            <div class="flex justify-between items-center">
              <div>
                <p class="text-sm text-gray-600">Subtotale articoli</p>
                <p class="text-sm text-gray-600">Spedizione</p>
                <p class="font-semibold text-gray-900">Totale {{ seller.name }}</p>
              </div>
              <div class="text-right">
                <p class="text-sm text-gray-600">€{{ seller.subtotal.toFixed(2) }}</p>
                <p class="text-sm text-gray-600">€{{ seller.shippingCost.toFixed(2) }}</p>
                <p class="font-semibold text-gray-900">€{{ seller.total.toFixed(2) }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Riepilogo finale -->
      <div class="cart-summary bg-white border border-gray-200 rounded-lg p-6 mt-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Riepilogo ordine</h3>
        
        <div class="space-y-2 mb-4">
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">Subtotale ({{ totalItems }} articoli)</span>
            <span class="text-gray-900">€{{ (grandTotal - totalShippingCost).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-sm">
            <span class="text-gray-600">Spedizione</span>
            <span class="text-gray-900">€{{ totalShippingCost.toFixed(2) }}</span>
          </div>
        </div>

        <div class="border-t border-gray-200 pt-4">
          <div class="flex justify-between items-center">
            <span class="text-lg font-semibold text-gray-900">Totale</span>
            <span class="text-xl font-bold text-gray-900">€{{ grandTotal.toFixed(2) }}</span>
          </div>
        </div>

        <!-- Pulsanti azione -->
        <div class="flex space-x-4 mt-6">
          <router-link to="/" 
                       class="flex-1 text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
            Continua lo shopping
          </router-link>
          <button @click="proceedToCheckout"
                  :disabled="!canProceedToCheckout"
                  class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed transition-colors">
            Procedi al checkout
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'

const router = useRouter()
const cartStore = useCartStore()

// Computed properties
const isEmpty = computed(() => cartStore.isEmpty)
const totalItems = computed(() => cartStore.totalItems)
const sellers = computed(() => cartStore.sellers)
const grandTotal = computed(() => cartStore.grandTotal)
const selectedShippingZones = computed(() => cartStore.selectedShippingZones)

const totalShippingCost = computed(() => {
  return sellers.value.reduce((sum, seller) => sum + seller.shippingCost, 0)
})

const canProceedToCheckout = computed(() => {
  const validation = cartStore.validateCart()
  return validation.isValid
})

// Metodi
const updateQuantity = (listingId, sellerId, quantity) => {
  cartStore.updateQuantity(listingId, sellerId, quantity)
}

const removeFromCart = (listingId, sellerId) => {
  cartStore.removeFromCart(listingId, sellerId)
}

const selectShippingZone = (sellerId, shippingZoneId) => {
  cartStore.selectShippingZone(sellerId, shippingZoneId)
}

const getAvailableShippingZones = (item) => {
  return item.shippingZones || []
}

const getShippingCost = (zone, item) => {
  return zone.pivot?.shipping_cost || zone.shipping_cost || 0
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

const proceedToCheckout = () => {
  if (canProceedToCheckout.value) {
    router.push('/checkout')
  }
}

// Inizializzazione
onMounted(() => {
  cartStore.initialize()
})
</script>

<style scoped>
.cart-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

@media (min-width: 768px) {
  .cart-container {
    padding: 2rem;
  }
}

.seller-group {
  border: 1px solid #e5e7eb;
  border-radius: 0.5rem;
  padding: 1rem;
}

.cart-item {
  transition: all 0.2s ease;
}

.cart-item:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.shipping-option {
  transition: all 0.2s ease;
}

.shipping-option:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.quantity-controls button {
  transition: all 0.2s ease;
}

.quantity-controls button:hover {
  transform: scale(1.1);
}
</style>
