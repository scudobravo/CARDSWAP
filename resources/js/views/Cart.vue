<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-24 pb-6">
      <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-futura-bold text-primary mb-8">Carrello</h1>
      
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
            <li v-for="(product, productIdx) in allCartItems" :key="product.id" class="flex py-6 sm:py-10">
              <div class="shrink-0 relative">
                <img v-if="getProductImage(product)" 
                     :src="getProductImage(product)" 
                     :alt="product.cardModel?.name || 'Carta'" 
                     class="size-24 rounded-md object-cover sm:size-48" />
                <div v-else class="absolute inset-0 flex items-center justify-center bg-gray-300 rounded-md size-24 sm:size-48">
                  <div class="text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-sm font-gill-sans">Immagine non disponibile</p>
                  </div>
                </div>
              </div>

              <div class="ml-4 flex flex-1 flex-col justify-between sm:ml-6">
                <div class="relative pr-9 sm:grid sm:grid-cols-2 sm:gap-x-6 sm:pr-0">
                  <div>
                    <div class="flex justify-between">
                      <h3 class="text-sm">
                        <router-link :to="getCardUrl(product)" 
                                     class="font-medium text-gray-700 hover:text-gray-800">
                          {{ product.cardModel?.name || 'Carta' }}
                        </router-link>
                      </h3>
                    </div>
                    <div class="mt-1 flex text-sm">
                      <p class="text-gray-500">{{ getConditionLabel(product.condition) }}</p>
                      <p v-if="product.cardModel?.set_name" 
                         class="ml-4 border-l border-gray-200 pl-4 text-gray-500">
                        {{ product.cardModel.set_name }}
                      </p>
                    </div>
                    <p class="mt-1 text-sm font-medium text-gray-900">€{{ product.price.toFixed(2) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Venditore: {{ product.seller?.name || 'N/A' }}</p>
                  </div>

                  <div class="mt-4 sm:mt-0 sm:pr-9">
                    <div class="grid w-full max-w-16 grid-cols-1">
                      <select :name="`quantity-${productIdx}`" 
                              :aria-label="`Quantità, ${product.cardModel?.name || 'Carta'}`" 
                              :value="product.quantity"
                              @change="updateQuantity(product.id, product.seller_id, parseInt($event.target.value))"
                              class="col-start-1 row-start-1 appearance-none rounded-md bg-white py-1.5 pr-8 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6">
                        <option v-for="qty in 8" :key="qty" :value="qty">{{ qty }}</option>
                      </select>
                      <ChevronDownIcon class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end text-gray-500 sm:size-4" aria-hidden="true" />
                    </div>

                    <div class="absolute top-0 right-0">
                      <button type="button" 
                              @click="removeFromCart(product.id, product.seller_id)"
                              class="-m-2 inline-flex p-2 text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Rimuovi</span>
                        <XMarkIcon class="size-5" aria-hidden="true" />
                      </button>
                    </div>
                  </div>
                </div>

                <p class="mt-4 flex space-x-2 text-sm text-gray-700">
                  <CheckIcon v-if="product.available" class="size-5 shrink-0 text-green-500" aria-hidden="true" />
                  <ClockIcon v-else class="size-5 shrink-0 text-gray-300" aria-hidden="true" />
                  <span>{{ product.available ? 'Disponibile' : 'Non disponibile' }}</span>
                </p>
              </div>
            </li>
          </ul>
        </section>

        <!-- Riepilogo ordine -->
        <section aria-labelledby="summary-heading" class="mt-16 rounded-lg bg-gray-50 px-4 py-6 sm:p-6 lg:col-span-5 lg:mt-0 lg:p-8">
          <h2 id="summary-heading" class="text-lg font-medium text-gray-900">Riepilogo ordine</h2>

          <dl class="mt-6 space-y-4">
            <div class="flex items-center justify-between">
              <dt class="text-sm text-gray-600">Subtotale</dt>
              <dd class="text-sm font-medium text-gray-900">€{{ subtotal.toFixed(2) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
              <dt class="flex items-center text-sm text-gray-600">
                <span>Spedizione</span>
                <a href="#" class="ml-2 shrink-0 text-gray-400 hover:text-gray-500">
                  <span class="sr-only">Scopri di più su come viene calcolata la spedizione</span>
                  <QuestionMarkCircleIcon class="size-5" aria-hidden="true" />
                </a>
              </dt>
              <dd class="text-sm font-medium text-gray-900">€{{ totalShippingCost.toFixed(2) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
              <dt class="flex text-sm text-gray-600">
                <span>IVA</span>
                <a href="#" class="ml-2 shrink-0 text-gray-400 hover:text-gray-500">
                  <span class="sr-only">Scopri di più su come viene calcolata l'IVA</span>
                  <QuestionMarkCircleIcon class="size-5" aria-hidden="true" />
                </a>
              </dt>
              <dd class="text-sm font-medium text-gray-900">€{{ taxAmount.toFixed(2) }}</dd>
            </div>
            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
              <dt class="text-base font-medium text-gray-900">Totale ordine</dt>
              <dd class="text-base font-medium text-gray-900">€{{ grandTotal.toFixed(2) }}</dd>
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
  return cartStore.allCartItems.reduce((sum, item) => sum + (item.price * item.quantity), 0)
})
const totalShippingCost = computed(() => cartStore.totalShippingCost)
const taxAmount = computed(() => subtotal.value * 0.22) // 22% IVA
const grandTotal = computed(() => subtotal.value + totalShippingCost.value + taxAmount.value)

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
    return product.images[0]
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
