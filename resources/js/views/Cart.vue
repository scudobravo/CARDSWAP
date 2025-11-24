<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-20 sm:pt-24 pb-6">
      <div class="max-w-4xl mx-auto">
        <h1 class="text-2xl sm:text-3xl font-futura-bold text-primary mb-6 sm:mb-8">Carrello</h1>
      
      <!-- Carrello vuoto -->
      <div v-if="isEmpty" class="empty-cart text-center py-12">
        <div class="text-gray-400 mb-4">
          <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v6a2 2 0 01-2 2H9a2 2 0 01-2-2v-6m8 0V9a2 2 0 00-2-2H9a2 2 0 00-2 2v4.01"></path>
          </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">Il tuo carrello è vuoto</h3>
        <p class="text-gray-500 mb-6">Aggiungi alcune carte per iniziare lo shopping</p>
        <router-link to="/" 
                     class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          Continua lo shopping
        </router-link>
      </div>

      <!-- Carrello con articoli -->
      <form v-else class="mt-12 lg:grid lg:grid-cols-12 lg:items-start lg:gap-x-12 xl:gap-x-16">
        <section aria-labelledby="cart-heading" class="lg:col-span-7">
          <h2 id="cart-heading" class="sr-only">Articoli nel tuo carrello</h2>

          <ul role="list" class="divide-y divide-gray-200 border-t border-b border-gray-200">
            <li v-for="(product, productIdx) in allCartItems" :key="product.id" class="flex py-4 sm:py-6 lg:py-10">
              <div class="shrink-0 relative">
                <img v-if="getProductImage(product)" 
                     :src="getProductImage(product)" 
                     :alt="product.cardModel?.name || 'Carta'" 
                     class="size-20 rounded-md object-cover sm:size-32 lg:size-48" />
                <div v-else class="flex items-center justify-center bg-gray-300 rounded-md size-20 sm:size-32 lg:size-48">
                  <div class="text-center text-gray-500 p-2">
                    <svg class="w-8 h-8 mx-auto mb-1 opacity-50 sm:w-12 sm:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-xs font-gill-sans sm:text-sm">Immagine non disponibile</p>
                  </div>
                </div>
              </div>

              <div class="ml-3 sm:ml-4 lg:ml-6 flex flex-1 flex-col min-w-0">
                <div class="flex-1 min-w-0">
                  <!-- Header con nome e pulsante rimuovi -->
                  <div class="flex items-start justify-between gap-2 mb-2">
                    <h3 class="text-sm sm:text-base min-w-0 flex-1">
                      <router-link :to="getCardUrl(product)" 
                                   class="font-medium text-gray-700 hover:text-gray-800 break-words">
                        {{ product.cardModel?.name || 'Carta' }}
                      </router-link>
                    </h3>
                    <button type="button" 
                            @click="removeFromCart(product.id, product.seller_id)"
                            class="shrink-0 -mt-1 -mr-1 inline-flex p-2 text-gray-400 hover:text-gray-500">
                      <span class="sr-only">Rimuovi</span>
                      <XMarkIcon class="size-5" aria-hidden="true" />
                    </button>
                  </div>

                  <!-- Condizione e Set -->
                  <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 mb-2">
                    <p class="text-xs sm:text-sm text-gray-500">{{ getConditionLabel(product.condition) }}</p>
                    <p v-if="product.cardModel?.set_name" 
                       class="text-xs sm:text-sm text-gray-500 border-l-0 sm:border-l border-gray-200 pl-0 sm:pl-4">
                      {{ product.cardModel.set_name }}
                    </p>
                  </div>

                  <!-- Prezzo -->
                  <p class="text-sm sm:text-base font-medium text-gray-900 mb-2">€{{ formatPrice(product.price) }}</p>

                  <!-- Venditore -->
                  <p class="text-xs sm:text-sm text-gray-500 mb-3 break-words">
                    Venditore: {{ product.seller?.name || 'N/A' }}
                  </p>

                  <!-- Disponibilità e Quantità -->
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-auto">
                    <p class="flex items-center space-x-2 text-xs sm:text-sm text-gray-700">
                      <CheckIcon v-if="product.available" class="size-4 shrink-0 text-green-500 sm:size-5" aria-hidden="true" />
                      <ClockIcon v-else class="size-4 shrink-0 text-gray-300 sm:size-5" aria-hidden="true" />
                      <span>{{ product.available ? 'Disponibile' : 'Non disponibile' }}</span>
                    </p>

                    <div class="flex items-center gap-3">
                      <span class="text-xs sm:text-sm text-gray-600 whitespace-nowrap">Quantità:</span>
                      <div class="grid grid-cols-1">
                        <select 
                          :value="product.quantity"
                          @change="updateQuantity(product.id, product.seller_id, parseInt($event.target.value))"
                          aria-label="Quantità" 
                          class="col-start-1 row-start-1 w-20 appearance-none rounded-md bg-white border border-gray-300 py-1.5 pr-8 pl-3 text-sm text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600"
                        >
                          <option v-for="qty in Array.from({length: getMaxQuantity(product)}, (_, i) => i + 1)" :key="qty" :value="qty">{{ qty }}</option>
                        </select>
                        <ChevronDownIcon class="pointer-events-none col-start-1 row-start-1 mr-2 size-4 self-center justify-self-end text-gray-500" aria-hidden="true" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </li>
          </ul>
        </section>

        <!-- Riepilogo ordine -->
        <section aria-labelledby="summary-heading" class="mt-8 sm:mt-16 rounded-lg bg-gray-50 px-4 py-6 sm:p-6 lg:col-span-5 lg:mt-0 lg:p-8">
          <h2 id="summary-heading" class="text-base sm:text-lg font-medium text-gray-900">Riepilogo ordine</h2>

          <dl class="mt-6 space-y-4">
            <div class="flex items-center justify-between">
              <dt class="text-sm text-gray-600">Subtotale</dt>
              <dd class="text-sm font-medium text-gray-900">€{{ formatPrice(subtotal) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
              <dt class="flex items-center text-sm text-gray-600">
                <span>Spedizione</span>
                <a href="#" class="ml-2 shrink-0 text-gray-400 hover:text-gray-500">
                  <span class="sr-only">Scopri di più su come viene calcolata la spedizione</span>
                  <QuestionMarkCircleIcon class="size-5" aria-hidden="true" />
                </a>
              </dt>
              <dd class="text-sm font-medium text-gray-900">€{{ formatPrice(totalShippingCost) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
              <dt class="flex text-sm text-gray-600">
                <span>Costo di gestione</span>
                <a href="#" class="ml-2 shrink-0 text-gray-400 hover:text-gray-500">
                  <span class="sr-only">Scopri di più su come viene calcolato il costo di gestione</span>
                  <QuestionMarkCircleIcon class="size-5" aria-hidden="true" />
                </a>
              </dt>
              <dd class="text-sm font-medium text-gray-900">€{{ formatPrice(taxAmount) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
              <dt class="text-base font-medium text-gray-900">Totale ordine</dt>
              <dd class="text-base font-medium text-gray-900">€{{ formatPrice(grandTotal) }}</dd>
            </div>
          </dl>

          <div class="mt-6">
            <button type="button" 
                    @click="proceedToCheckout"
                    :disabled="!canProceedToCheckout"
                    class="w-full rounded-md border border-transparent bg-indigo-600 px-4 py-3 text-base font-medium text-white shadow-xs hover:bg-indigo-700 focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 focus:ring-offset-gray-50 focus:outline-hidden disabled:opacity-50 disabled:cursor-not-allowed">
              Procedi al checkout
            </button>
          </div>
        </section>
      </form>
      </div>
    </main>
    
    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '../stores/cart'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'
import { ChevronDownIcon } from '@heroicons/vue/16/solid'
import { CheckIcon, ClockIcon, QuestionMarkCircleIcon, XMarkIcon } from '@heroicons/vue/20/solid'
import { formatPriceItaliana, normalizePrice } from '../utils/priceFormatter'

const router = useRouter()
const cartStore = useCartStore()

// Functions
const getCardUrl = (product) => {
  // Determina la categoria dal prodotto
  const category = product.cardModel?.category?.slug || 'football'
  
  // Genera lo slug dal nome della carta
  const slug = (product.cardModel?.name || 'carta')
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '') // Rimuove caratteri speciali
    .replace(/\s+/g, '-') // Sostituisce spazi con trattini
    .replace(/-+/g, '-') // Rimuove trattini multipli
    .replace(/^-+|-+$/g, '') // Rimuove trattini all'inizio e alla fine
  
  return `/${category}/${slug}`
}

// Computed properties
const isEmpty = computed(() => cartStore.isEmpty)
const allCartItems = computed(() => cartStore.allCartItems)
const subtotal = computed(() => {
  return cartStore.allCartItems.reduce((sum, item) => {
    const price = normalizePrice(item.price)
    const quantity = parseInt(item.quantity) || 1
    return sum + (price * quantity)
  }, 0)
})
const totalShippingCost = computed(() => {
  // Normalizza il costo di spedizione dal cartStore
  return normalizePrice(cartStore.totalShippingCost)
})
const taxAmount = computed(() => {
  // Calcola la commissione acquirente (1.5% del subtotale)
  const subtotalValue = normalizePrice(subtotal.value)
  return subtotalValue * 0.015 // 1.5% Commissione acquirente (copre parzialmente i costi Stripe)
})
const grandTotal = computed(() => {
  // Assicurati che tutti i valori siano numeri normalizzati
  const subtotalValue = normalizePrice(subtotal.value)
  const shippingValue = normalizePrice(totalShippingCost.value)
  const taxValue = normalizePrice(taxAmount.value)
  return subtotalValue + shippingValue + taxValue
})

const canProceedToCheckout = computed(() => {
  return !isEmpty.value && allCartItems.value.length > 0
})

// Metodi
const updateQuantity = async (listingId, sellerId, quantity) => {
  const result = await cartStore.updateQuantity(listingId, sellerId, quantity)
  if (!result.success) {
    // Mostra errore all'utente
    console.error(result.message)
  }
}

const removeFromCart = async (listingId, sellerId) => {
  const result = await cartStore.removeFromCart(listingId, sellerId)
  if (!result.success) {
    // Mostra errore all'utente
    console.error(result.message)
  }
}

const getProductImage = (product) => {
  if (product.images && product.images.length > 0) {
    let imagePath = product.images[0]
    // Se il percorso non inizia con /storage/ o http, aggiungi /storage/
    if (imagePath && !imagePath.startsWith('/storage/') && !imagePath.startsWith('http') && !imagePath.startsWith('//')) {
      imagePath = '/storage/' + imagePath
    }
    return imagePath
  }
  return null
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

const formatPrice = (price) => {
  return formatPriceItaliana(price, false)
}

const getMaxQuantity = (product) => {
  // Usa available_quantity se disponibile, altrimenti usa quantity o 1
  return product.available_quantity || product.quantity || 1
}

const proceedToCheckout = () => {
  if (canProceedToCheckout.value) {
    router.push('/checkout')
  }
}

// Inizializzazione
onMounted(async () => {
  await cartStore.initialize()
})
</script>

<style scoped>
.empty-cart {
  min-height: 400px;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.cart-item {
  transition: all 0.2s ease;
}

.cart-item:hover {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
</style>
