<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-24 pb-6">
      <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-futura-bold text-primary mb-8">Checkout</h1>

      <form @submit.prevent="processPayment" class="lg:grid lg:grid-cols-2 lg:gap-x-12 xl:gap-x-16">
        <div>
          <!-- Informazioni di contatto -->
          <div>
            <h2 class="text-lg font-medium text-gray-900">Informazioni di contatto</h2>

            <div class="mt-4">
              <label for="email-address" class="block text-sm/6 font-medium text-gray-700">Indirizzo email</label>
              <div class="mt-2">
                <input 
                  type="email" 
                  id="email-address" 
                  name="email-address" 
                  v-model="formData.email"
                  autocomplete="email" 
                  class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6" 
                />
              </div>
            </div>
          </div>

          <!-- Informazioni di spedizione -->
          <div class="mt-10 border-t border-gray-200 pt-10">
            <h2 class="text-lg font-medium text-gray-900">Informazioni di spedizione</h2>

            <!-- Selezione indirizzo esistente -->
            <div v-if="userAddresses.length > 0" class="mt-4">
              <h3 class="text-sm font-medium text-gray-700 mb-3">Seleziona un indirizzo salvato</h3>
              <div class="space-y-3">
                <div v-for="address in userAddresses" :key="address.id"
                     class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-300 transition-colors"
                     :class="{ 'border-blue-500 bg-blue-50': selectedAddress?.id === address.id }"
                     @click="selectAddress(address)">
                  <div class="flex items-start justify-between">
                    <div>
                      <p class="font-medium text-gray-900">{{ address.label }}</p>
                      <p class="text-sm text-gray-600">
                        {{ address.first_name }} {{ address.last_name }}
                      </p>
                      <p class="text-sm text-gray-600">
                        {{ address.address_line_1 }}{{ address.address_line_2 ? ', ' + address.address_line_2 : '' }}
                      </p>
                      <p class="text-sm text-gray-600">
                        {{ address.postal_code }} {{ address.city }}, {{ address.country }}
                      </p>
                      <p v-if="address.phone" class="text-sm text-gray-600">
                        Tel: {{ address.phone }}
                      </p>
                    </div>
                    <div v-if="selectedAddress?.id === address.id" class="text-blue-600">
                      <CheckCircleIcon class="w-5 h-5" />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Form nuovo indirizzo -->
            <div class="mt-4 grid grid-cols-1 gap-y-6 sm:grid-cols-2 sm:gap-x-4">
              <div>
                <label for="first-name" class="block text-sm/6 font-medium text-gray-700">Nome</label>
                <div class="mt-2">
                  <input 
                    type="text" 
                    id="first-name" 
                    name="first-name" 
                    v-model="formData.firstName"
                    autocomplete="given-name" 
                    class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6" 
                  />
                </div>
              </div>

              <div>
                <label for="last-name" class="block text-sm/6 font-medium text-gray-700">Cognome</label>
                <div class="mt-2">
                  <input 
                    type="text" 
                    id="last-name" 
                    name="last-name" 
                    v-model="formData.lastName"
                    autocomplete="family-name" 
                    class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6" 
                  />
                </div>
              </div>

              <div class="sm:col-span-2">
                <label for="company" class="block text-sm/6 font-medium text-gray-700">Azienda (opzionale)</label>
                <div class="mt-2">
                  <input 
                    type="text" 
                    name="company" 
                    id="company" 
                    v-model="formData.company"
                    class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6" 
                  />
                </div>
              </div>

              <div class="sm:col-span-2">
                <label for="address" class="block text-sm/6 font-medium text-gray-700">Indirizzo</label>
                <div class="mt-2">
                  <input 
                    type="text" 
                    name="address" 
                    id="address" 
                    v-model="formData.address"
                    autocomplete="street-address" 
                    class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6" 
                  />
                </div>
              </div>

              <div class="sm:col-span-2">
                <label for="apartment" class="block text-sm/6 font-medium text-gray-700">Appartamento, interno, ecc.</label>
                <div class="mt-2">
                  <input 
                    type="text" 
                    name="apartment" 
                    id="apartment" 
                    v-model="formData.apartment"
                    class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6" 
                  />
                </div>
              </div>

              <div>
                <label for="city" class="block text-sm/6 font-medium text-gray-700">Città</label>
                <div class="mt-2">
                  <input 
                    type="text" 
                    name="city" 
                    id="city" 
                    v-model="formData.city"
                    autocomplete="address-level2" 
                    class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6" 
                  />
                </div>
              </div>

              <div>
                <label for="country" class="block text-sm/6 font-medium text-gray-700">Paese</label>
                <div class="mt-2 grid grid-cols-1">
                  <select 
                    id="country" 
                    name="country" 
                    v-model="formData.country"
                    autocomplete="country-name" 
                    class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-2 pr-8 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"
                  >
                    <option value="IT">Italia</option>
                    <option value="FR">Francia</option>
                    <option value="DE">Germania</option>
                    <option value="ES">Spagna</option>
                    <option value="GB">Regno Unito</option>
                    <option value="US">Stati Uniti</option>
                  </select>
                  <ChevronDownIcon class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end text-gray-500 sm:size-4" aria-hidden="true" />
                </div>
              </div>

              <div>
                <label for="region" class="block text-sm/6 font-medium text-gray-700">Regione / Provincia</label>
                <div class="mt-2">
                  <input 
                    type="text" 
                    name="region" 
                    id="region" 
                    v-model="formData.region"
                    autocomplete="address-level1" 
                    class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6" 
                  />
                </div>
              </div>

              <div>
                <label for="postal-code" class="block text-sm/6 font-medium text-gray-700">CAP</label>
                <div class="mt-2">
                  <input 
                    type="text" 
                    name="postal-code" 
                    id="postal-code" 
                    v-model="formData.postalCode"
                    autocomplete="postal-code" 
                    class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6" 
                  />
                </div>
              </div>

              <div class="sm:col-span-2">
                <label for="phone" class="block text-sm/6 font-medium text-gray-700">Telefono</label>
                <div class="mt-2">
                  <input 
                    type="text" 
                    name="phone" 
                    id="phone" 
                    v-model="formData.phone"
                    autocomplete="tel" 
                    class="block w-full rounded-md bg-white px-3 py-2 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 placeholder:text-gray-400 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6" 
                  />
                </div>
              </div>
            </div>
          </div>

          <!-- Metodi di spedizione per venditore -->
          <div class="mt-10 border-t border-gray-200 pt-10">
            <h2 class="text-lg font-medium text-gray-900">Metodi di spedizione</h2>
            
            <!-- Selezione spedizione per ogni venditore -->
            <div v-for="seller in cartStore.sellers" :key="seller.id" class="mt-6">
              <div class="bg-gray-50 rounded-lg p-4">
                <div class="flex items-center space-x-3 mb-4">
                  <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <span class="text-blue-600 font-semibold text-sm">
                      {{ seller.name.charAt(0).toUpperCase() }}
                    </span>
                  </div>
                  <div>
                    <h3 class="text-sm font-medium text-gray-900">{{ seller.name }}</h3>
                    <p class="text-xs text-gray-500">{{ seller.items.length }} articoli</p>
                  </div>
                </div>

                <fieldset>
                  <legend class="sr-only">Metodo di spedizione per {{ seller.name }}</legend>
                  
                  <!-- Indicatore di caricamento -->
                  <div v-if="loadingShippingRates" class="flex items-center justify-center p-4 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-2">
                      <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                      <span class="text-sm text-gray-600">Calcolo prezzi di spedizione...</span>
                    </div>
                  </div>
                  
                  <!-- Messaggio errore se nessuna opzione disponibile -->
                  <div v-else-if="getShippingMethodsForSeller(seller.id).length === 0" 
                       class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-sm text-yellow-800">
                      Nessuna opzione di spedizione disponibile per questo venditore e paese.
                    </p>
                  </div>
                  
                  <!-- Metodi di spedizione CardSwap V1 -->
                  <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label v-for="option in getShippingMethodsForSeller(seller.id)" 
                           :key="`${seller.id}-${option.shipping_method}`" 
                           class="group relative flex rounded-lg border-2 p-3 cursor-pointer transition-all duration-200"
                           :class="selectedShippingMethods[seller.id] === option.shipping_method 
                             ? 'border-blue-600 bg-blue-50 shadow-md' 
                             : 'border-gray-300 bg-white hover:border-blue-400 hover:bg-gray-50'">
                      <input 
                        type="radio" 
                        :name="`delivery-method-${seller.id}`" 
                        :value="option.shipping_method" 
                        v-model="selectedShippingMethods[seller.id]"
                        class="absolute inset-0 appearance-none focus:outline-none" 
                      />
                      <div class="flex-1">
                        <div class="flex items-center justify-between">
                          <span class="block text-sm font-medium" 
                                :class="selectedShippingMethods[seller.id] === option.shipping_method ? 'text-blue-900' : 'text-gray-900'">
                            {{ option.label }}
                          </span>
                          <span v-if="option.insurance_required && option.insurance_available" 
                                class="ml-2 px-2 py-0.5 text-xs bg-green-100 text-green-800 rounded">
                            Assicurata
                          </span>
                        </div>
                        <span class="mt-1 block text-xs" 
                              :class="selectedShippingMethods[seller.id] === option.shipping_method ? 'text-blue-700' : 'text-gray-500'">
                          {{ option.package_bucket_label }}
                        </span>
                        <div class="mt-2 flex items-baseline justify-between">
                          <span class="text-sm font-semibold" 
                                :class="selectedShippingMethods[seller.id] === option.shipping_method ? 'text-blue-900' : 'text-gray-900'">
                            €{{ formatPriceItaliana(option.total_price) }}
                          </span>
                          <span v-if="option.insurance_fee > 0" class="ml-2 text-xs text-gray-500">
                            (spedizione: €{{ formatPriceItaliana(option.price) }} + assicurazione: €{{ formatPriceItaliana(option.insurance_fee) }})
                          </span>
                        </div>
                      </div>
                      <CheckCircleIcon 
                        class="size-5 flex-shrink-0 transition-opacity duration-200" 
                        :class="selectedShippingMethods[seller.id] === option.shipping_method 
                          ? 'text-blue-600 opacity-100' 
                          : 'text-gray-400 opacity-0'" 
                        aria-hidden="true" />
                    </label>
                  </div>
                </fieldset>
              </div>
            </div>
          </div>

          <!-- Pagamento -->
          <div class="mt-10 border-t border-gray-200 pt-10">
            <h2 class="text-lg font-medium text-gray-900">Pagamento</h2>

            <div class="mt-4">
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l.89-5.494C18.252.274 15.697 0 12.165 0 9.667 0 7.589.654 6.104 1.872 4.56 3.147 3.757 4.992 3.757 7.218c0 4.039 2.467 5.76 6.476 7.219 2.585.92 3.445 1.574 3.445 2.583 0 .98-.84 1.407-2.354 1.407-1.903 0-4.357-.921-6.03-1.757L4.35 24.553c1.395.49 3.76.922 6.029.922 2.469 0 4.536-.636 6.03-1.876 1.512-1.251 2.38-3.146 2.38-5.432 0-4.194-2.467-5.95-6.476-7.219z"/>
                  </svg>
                </div>
                <div>
                  <h3 class="text-lg font-medium text-gray-900">Pagamento sicuro con Stripe</h3>
                  <p class="text-sm text-gray-600">I tuoi dati di pagamento sono protetti e crittografati</p>
                </div>
              </div>
            </div>

            <!-- Stripe Elements per carta di credito -->
            <div class="mt-6">
              <div class="bg-white border border-gray-300 rounded-lg p-4">
                <label class="block text-sm font-medium text-gray-700 mb-3">Dettagli carta di credito</label>
                <div id="card-element" class="p-3 border border-gray-300 rounded-md">
                  <!-- Stripe Elements verrà montato qui -->
                </div>
                <div id="card-errors" class="mt-2 text-sm text-red-600" role="alert"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Riepilogo ordine -->
        <div class="mt-10 lg:mt-0">
          <h2 class="text-lg font-medium text-gray-900">Riepilogo ordine</h2>

          <div class="mt-4 rounded-lg border border-gray-200 bg-white shadow-xs">
            <h3 class="sr-only">Articoli nel tuo carrello</h3>
            <ul role="list" class="divide-y divide-gray-200">
              <li v-for="product in cartProducts" :key="product.id" class="flex px-4 py-6 sm:px-6">
                <div class="shrink-0 relative">
                  <img v-if="product.imageSrc" 
                       :src="product.imageSrc" 
                       :alt="product.imageAlt || product.title" 
                       class="w-20 rounded-md" />
                  <div v-else class="w-20 h-20 flex items-center justify-center bg-gray-300 rounded-md">
                    <div class="text-center text-gray-500">
                      <svg class="w-8 h-8 mx-auto mb-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                      <p class="text-xs font-gill-sans">Immagine non disponibile</p>
                    </div>
                  </div>
                </div>

                <div class="ml-6 flex flex-1 flex-col">
                  <div class="flex">
                    <div class="min-w-0 flex-1">
                      <h4 class="text-sm">
                        <a :href="product.href" class="font-medium text-gray-700 hover:text-gray-800">{{ product.title }}</a>
                      </h4>
                      <p class="mt-1 text-sm text-gray-500">{{ product.condition }}</p>
                      <p class="mt-1 text-sm text-gray-500">{{ product.seller }}</p>
                    </div>

                    <div class="ml-4 flow-root shrink-0">
                      <button 
                        type="button" 
                        @click="removeFromCart(product)"
                        class="-m-2.5 flex items-center justify-center bg-white p-2.5 text-gray-400 hover:text-gray-500"
                      >
                        <span class="sr-only">Rimuovi</span>
                        <TrashIcon class="size-5" aria-hidden="true" />
                      </button>
                    </div>
                  </div>

                  <div class="flex flex-1 items-end justify-between pt-2">
                    <p class="mt-1 text-sm font-medium text-gray-900">€{{ formatPriceItaliana(product.price * product.quantity) }}</p>

                    <div class="ml-4">
                      <div class="grid grid-cols-1">
                        <select 
                          v-model="product.quantity"
                          @change="updateQuantity(product)"
                          aria-label="Quantità" 
                          class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-2 pr-8 pl-3 text-base text-gray-900 outline-1 -outline-offset-1 outline-gray-300 focus:outline-2 focus:-outline-offset-2 focus:outline-blue-600 sm:text-sm/6"
                        >
                          <option v-for="qty in Array.from({length: product.maxQuantity}, (_, i) => i + 1)" :key="qty" :value="qty">{{ qty }}</option>
                        </select>
                        <ChevronDownIcon class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end text-gray-500 sm:size-4" aria-hidden="true" />
                      </div>
                    </div>
                  </div>
                </div>
              </li>
            </ul>
            <dl class="space-y-6 border-t border-gray-200 px-4 py-6 sm:px-6">
              <div class="flex items-center justify-between">
                <dt class="text-sm">Subtotale</dt>
                <dd class="text-sm font-medium text-gray-900">€{{ formatPriceItaliana(orderSummary.subtotal) }}</dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-sm">Spedizione</dt>
                <dd class="text-sm font-medium text-gray-900">€{{ formatPriceItaliana(orderSummary.shipping) }}</dd>
              </div>
              <div class="flex items-center justify-between">
                <dt class="text-sm">Commissioni di servizio</dt>
                <dd class="text-sm font-medium text-gray-900">€{{ formatPriceItaliana(orderSummary.tax) }}</dd>
              </div>
              <div class="flex items-center justify-between border-t border-gray-200 pt-6">
                <dt class="text-base font-medium">Totale</dt>
                <dd class="text-base font-medium text-gray-900">€{{ formatPriceItaliana(orderSummary.total) }}</dd>
              </div>
            </dl>

            <div class="border-t border-gray-200 px-4 py-6 sm:px-6 space-y-3">
              <button 
                type="button"
                @click="router.push('/cart')"
                class="w-full rounded-md border border-gray-300 bg-white px-4 py-3 text-base font-medium text-gray-700 shadow-xs hover:bg-gray-50 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 focus:ring-offset-gray-50 focus:outline-hidden"
              >
                Annulla checkout
              </button>
              <p class="text-sm text-gray-600 text-center leading-relaxed">
                Effettuando l'ordine accetti i
                <router-link to="/terms-and-conditions" class="text-secondary hover:underline font-medium">Termini e Condizioni</router-link>,
                la
                <router-link to="/privacy-policy" class="text-secondary hover:underline font-medium">Privacy Policy</router-link>
                e i
                <router-link to="/platform-terms" class="text-secondary hover:underline font-medium">Termini della piattaforma</router-link>.
              </p>
              <button 
                type="submit" 
                :disabled="!canProcessPayment || isProcessing"
                class="w-full rounded-md border border-transparent bg-blue-600 px-4 py-3 text-base font-medium text-white shadow-xs hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-50 focus:outline-hidden disabled:bg-gray-300 disabled:cursor-not-allowed"
              >
                <span v-if="isProcessing">Elaborazione...</span>
                <span v-else>Conferma e paga ordine</span>
              </button>
            </div>
          </div>
        </div>
      </form>
      </div>
    </main>
    
    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useCartStore } from '@/stores/cart'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'
import { useAuthStore } from '@/stores/auth'
import { ChevronDownIcon } from '@heroicons/vue/16/solid'
import { CheckCircleIcon, TrashIcon } from '@heroicons/vue/20/solid'
import axios from 'axios'
import { formatPriceItaliana, normalizePrice } from '../utils/priceFormatter'

const router = useRouter()
const cartStore = useCartStore()
const authStore = useAuthStore()

// Functions
const getCardUrl = (item) => {
  // Determina la categoria dal prodotto
  const category = item.cardModel?.category?.slug || 'football'
  
  // Genera lo slug dal nome della carta
  const slug = (item.cardModel?.name || 'carta')
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '') // Rimuove caratteri speciali
    .replace(/\s+/g, '-') // Sostituisce spazi con trattini
    .replace(/-+/g, '-') // Rimuove trattini multipli
    .replace(/^-+|-+$/g, '') // Rimuove trattini all'inizio e alla fine
  
  return `/${category}/${slug}`
}

// Stato reattivo
const userAddresses = ref([])
const selectedAddress = ref(null)
const isProcessing = ref(false)
const stripe = ref(null)
const cardElement = ref(null)
const selectedShippingMethods = ref({})

// Dati del form
const formData = ref({
  email: '',
  firstName: '',
  lastName: '',
  company: '',
  address: '',
  apartment: '',
  city: '',
  country: 'IT',
  region: '',
  postalCode: '',
  phone: '',
  paymentMethod: 'stripe'
})

// Metodi di spedizione (CardSwap V1)
const deliveryMethods = ref({}) // seller_id -> array di opzioni
const shippingData = ref({}) // seller_id -> { package_bucket, logistic_units_total, options }
const loadingShippingRates = ref(false)

// Metodo di pagamento fisso: Stripe
const paymentMethod = 'stripe'

// Computed
const cartProducts = computed(() => {
  return cartStore.allCartItems.map(item => ({
    id: item.id,
    title: item.cardModel?.name || 'Prodotto',
    condition: item.condition,
    seller: item.seller?.name || 'Venditore',
    seller_id: item.seller_id || item.seller?.id,
    price: parseFloat(item.price),
    quantity: item.quantity,
    maxQuantity: item.available_quantity || item.quantity || 1, // Usa la quantità disponibile del venditore
    imageSrc: item.images?.[0] || null,
    imageAlt: item.cardModel?.name || 'Prodotto',
    href: getCardUrl(item)
  }))
})

const orderSummary = computed(() => {
  const sellers = cartStore.sellers
  // Normalizza i subtotali per assicurarsi che siano numeri
  const subtotal = sellers.reduce((sum, seller) => {
    const sellerSubtotal = normalizePrice(seller.subtotal)
    return sum + sellerSubtotal
  }, 0)
  
  // Calcola spedizione per ogni venditore basata sul metodo selezionato
  const shipping = sellers.reduce((sum, seller) => {
    const selectedMethod = selectedShippingMethods.value[seller.id]
    const shippingCost = getShippingCostForMethod(selectedMethod, seller.id)
    return sum + normalizePrice(shippingCost)
  }, 0)
  
  const BUYER_MANAGEMENT_FEE_RATE = 0.035 // Allineare a config/services.php → services.cardswap.buyer_management_fee_rate
  const tax = Math.round((subtotal + shipping) * BUYER_MANAGEMENT_FEE_RATE * 100) / 100
  const total = subtotal + shipping + tax
  
  return {
    subtotal,
    shipping,
    tax,
    total
  }
})

const canProcessPayment = computed(() => {
  const hasAddress = selectedAddress.value || (
    formData.value.firstName && 
    formData.value.lastName && 
    formData.value.address && 
    formData.value.city && 
    formData.value.postalCode && 
    formData.value.country
  )
  
  // Verifica che sia selezionato un metodo di spedizione per ogni venditore
  const hasShippingMethods = cartStore.sellers.every(seller => 
    selectedShippingMethods.value[seller.id]
  )
  
  return hasAddress && cartProducts.value.length > 0 && formData.value.paymentMethod && hasShippingMethods
})

// Metodi
const selectAddress = (address) => {
  selectedAddress.value = address
  // Popola il form con i dati dell'indirizzo selezionato
  formData.value.firstName = address.first_name
  formData.value.lastName = address.last_name
  formData.value.company = address.company || ''
  formData.value.address = address.address_line_1
  formData.value.apartment = address.address_line_2 || ''
  formData.value.city = address.city
  formData.value.country = address.country
  formData.value.region = address.state_province || ''
  formData.value.postalCode = address.postal_code
  formData.value.phone = address.phone || ''
}

const removeFromCart = async (product) => {
  try {
    // Usa seller_id dal prodotto mappato, altrimenti cerca nell'item originale
    const sellerId = product.seller_id || cartStore.allCartItems.find(item => item.id === product.id)?.seller_id
    if (!sellerId) {
      console.error('Seller ID non trovato per il prodotto:', product)
      return
    }
    const result = await cartStore.removeFromCart(product.id, sellerId)
    if (!result.success) {
      console.error('Errore nella rimozione:', result.message)
    }
  } catch (error) {
    console.error('Errore nella rimozione dal carrello:', error)
  }
}

const updateQuantity = async (product) => {
  try {
    // Usa seller_id dal prodotto mappato, altrimenti cerca nell'item originale
    const sellerId = product.seller_id || cartStore.allCartItems.find(item => item.id === product.id)?.seller_id
    if (!sellerId) {
      console.error('Seller ID non trovato per il prodotto:', product)
      return
    }
    const result = await cartStore.updateQuantity(product.id, sellerId, product.quantity)
    if (!result.success) {
      console.error('Errore nell\'aggiornamento quantità:', result.message)
      // Ripristina la quantità precedente dal carrello
      const originalItem = cartStore.allCartItems.find(item => item.id === product.id)
      if (originalItem) {
        product.quantity = originalItem.quantity
      }
    } else {
      // Aggiorna maxQuantity se disponibile nella risposta
      if (result.data?.available_quantity !== undefined) {
        product.maxQuantity = result.data.available_quantity
      }
    }
  } catch (error) {
    console.error('Errore nell\'aggiornamento quantità:', error)
    // Ripristina la quantità precedente dal carrello
    const originalItem = cartStore.allCartItems.find(item => item.id === product.id)
    if (originalItem) {
      product.quantity = originalItem.quantity
    }
  }
}

const loadUserAddresses = async () => {
  try {
    const response = await axios.get('/api/user/addresses')
    if (response.data.success) {
      userAddresses.value = response.data.data
      // Seleziona l'indirizzo predefinito se disponibile
      const defaultAddress = userAddresses.value.find(addr => addr.is_default)
      if (defaultAddress) {
        selectAddress(defaultAddress)
      }
    }
  } catch (error) {
    console.error('Errore nel caricamento indirizzi:', error)
  }
}

const saveNewAddress = async () => {
  try {
    const addressData = {
      label: formData.value.firstName + ' ' + formData.value.lastName,
      first_name: formData.value.firstName,
      last_name: formData.value.lastName,
      company: formData.value.company,
      address_line_1: formData.value.address,
      address_line_2: formData.value.apartment,
      city: formData.value.city,
      country: formData.value.country,
      state_province: formData.value.region,
      postal_code: formData.value.postalCode,
      phone: formData.value.phone,
      is_default: userAddresses.value.length === 0
    }

    const response = await axios.post('/api/user/addresses', addressData)
    if (response.data.success) {
      userAddresses.value.push(response.data.data)
      selectAddress(response.data.data)
    }
  } catch (error) {
    console.error('Errore nel salvataggio indirizzo:', error)
  }
}

const initializeStripe = async () => {
  try {
    console.log('Inizializzazione Stripe...')
    
    // Carica Stripe.js se non è già caricato
    if (!window.Stripe) {
      console.log('Caricamento Stripe.js...')
      const script = document.createElement('script')
      script.src = 'https://js.stripe.com/v3/'
      script.async = true
      document.head.appendChild(script)
      
      await new Promise((resolve, reject) => {
        script.onload = resolve
        script.onerror = reject
        // Timeout dopo 10 secondi
        setTimeout(() => reject(new Error('Timeout caricamento Stripe.js')), 10000)
      })
      console.log('Stripe.js caricato')
    } else {
      console.log('Stripe.js già caricato')
    }
    
    // Ottieni la chiave Stripe dal meta tag (sempre aggiornata dal .env) o dal vite config
    const metaKey = document.querySelector('meta[name="stripe-publishable-key"]')?.getAttribute('content')
    const envKey = import.meta.env.VITE_STRIPE_PUBLISHABLE_KEY
    const stripeKey = (metaKey && metaKey.trim() !== '') ? metaKey.trim() : (envKey && envKey.trim() !== '' ? envKey.trim() : null)
    
    console.log('Chiave Stripe:', {
      hasMetaKey: !!metaKey,
      metaKeyValue: metaKey ? metaKey.substring(0, 20) + '...' : 'null',
      hasEnvKey: !!envKey,
      envKeyValue: envKey ? envKey.substring(0, 20) + '...' : 'null',
      finalKey: stripeKey ? stripeKey.substring(0, 20) + '...' : 'NONE',
      keyPrefix: stripeKey ? stripeKey.substring(0, 7) : 'NONE'
    })
    
    if (!stripeKey || stripeKey === 'null' || stripeKey === '') {
      console.error('Stripe publishable key non trovata!', {
        metaKey,
        envKey,
        allMetaTags: Array.from(document.querySelectorAll('meta')).map(m => ({
          name: m.getAttribute('name'),
          content: m.getAttribute('content')?.substring(0, 20)
        }))
      })
      const errorDiv = document.getElementById('card-errors')
      if (errorDiv) {
        errorDiv.textContent = 'Errore: Chiave Stripe non configurata. Contatta il supporto.'
        errorDiv.classList.add('text-red-600')
        errorDiv.style.display = 'block'
      }
      return
    }
    
    stripe.value = window.Stripe(stripeKey)
    console.log('Stripe inizializzato')
    
    // Inizializza Stripe Elements
    if (stripe.value) {
      const elements = stripe.value.elements({
        appearance: {
          theme: 'stripe',
          variables: {
            colorPrimary: '#1f2937', // primary color
            colorBackground: '#ffffff',
            colorText: '#374151',
            colorDanger: '#dc2626',
            fontFamily: 'Inter, system-ui, sans-serif',
            spacingUnit: '4px',
            borderRadius: '8px',
          }
        }
      })
      
      cardElement.value = elements.create('card', {
        style: {
          base: {
            fontSize: '16px',
            color: '#374151',
            '::placeholder': {
              color: '#9ca3af',
            },
          },
        },
      })
      
      console.log('Stripe Elements creato')
      
      // Attendi che il DOM sia pronto e che il div esista
      let attempts = 0
      const maxAttempts = 10
      let cardElementDiv = null
      
      while (attempts < maxAttempts && !cardElementDiv) {
      await nextTick()
        cardElementDiv = document.getElementById('card-element')
        if (!cardElementDiv) {
          attempts++
          await new Promise(resolve => setTimeout(resolve, 100))
        }
      }
      
      if (cardElementDiv) {
        console.log('Div card-element trovato, montaggio Stripe Elements...')
        cardElement.value.mount('#card-element')
        console.log('Stripe Elements montato con successo')
        
        // Gestisci errori di validazione
        cardElement.value.on('change', ({error}) => {
          const displayError = document.getElementById('card-errors')
          if (displayError) {
          if (error) {
            displayError.textContent = error.message
              displayError.classList.add('text-red-600')
          } else {
            displayError.textContent = ''
              displayError.classList.remove('text-red-600')
            }
          }
        })
      } else {
        console.error('Div #card-element non trovato dopo', maxAttempts, 'tentativi')
        const errorDiv = document.getElementById('card-errors')
        if (errorDiv) {
          errorDiv.textContent = 'Errore: Impossibile inizializzare il campo carta di credito. Ricarica la pagina.'
          errorDiv.classList.add('text-red-600')
        }
      }
    }
  } catch (error) {
    console.error('Errore nell\'inizializzazione Stripe:', error)
    const errorDiv = document.getElementById('card-errors')
    if (errorDiv) {
      errorDiv.textContent = `Errore: ${error.message || 'Impossibile inizializzare Stripe. Ricarica la pagina.'}`
      errorDiv.classList.add('text-red-600')
    }
  }
}

// Funzione per precompilare i dati dell'utente
const populateUserData = () => {
  if (authStore.user) {
    console.log('Debug populateUserData - Dati utente completi:', authStore.user)
    
    // Informazioni di contatto
    formData.value.email = authStore.user.email || ''
    formData.value.phone = authStore.user.phone || ''
    console.log('Email precompilata:', formData.value.email)
    console.log('📞 Telefono precompilato:', formData.value.phone)
    
    // Estrai nome e cognome dal campo 'name' se first_name e last_name sono null
    if (authStore.user.first_name && authStore.user.last_name) {
      formData.value.firstName = authStore.user.first_name
      formData.value.lastName = authStore.user.last_name
    } else if (authStore.user.name) {
      const nameParts = authStore.user.name.trim().split(' ')
      formData.value.firstName = nameParts[0] || ''
      formData.value.lastName = nameParts.slice(1).join(' ') || ''
    } else {
      formData.value.firstName = authStore.user.first_name || ''
      formData.value.lastName = authStore.user.last_name || ''
    }
    console.log('👤 Nome precompilato:', formData.value.firstName)
    console.log('👤 Cognome precompilato:', formData.value.lastName)
    
    // Informazioni aziendali (se disponibili)
    formData.value.company = authStore.user.business_name || ''
    console.log('🏢 Azienda precompilata:', formData.value.company)
    
    // Indirizzo principale (se disponibile direttamente nel modello User)
    console.log('🏠 Debug indirizzo utente:')
    console.log('  - address:', authStore.user.address)
    console.log('  - city:', authStore.user.city)
    console.log('  - postal_code:', authStore.user.postal_code)
    console.log('  - country:', authStore.user.country)
    
    if (authStore.user.address) {
      formData.value.address = authStore.user.address
      console.log('Indirizzo precompilato:', formData.value.address)
    }
    if (authStore.user.city) {
      formData.value.city = authStore.user.city
      console.log('Città precompilata:', formData.value.city)
    }
    if (authStore.user.postal_code) {
      formData.value.postalCode = authStore.user.postal_code
      console.log('CAP precompilato:', formData.value.postalCode)
    }
    if (authStore.user.country) {
      formData.value.country = authStore.user.country
      console.log('Paese precompilato:', formData.value.country)
    }
    
    console.log('Form finale precompilato:', formData.value)
  }
}

// Watcher per reagire quando l'utente viene caricato
watch(() => authStore.user, (newUser) => {
  if (newUser) {
    populateUserData()
  }
}, { immediate: true })

// Watcher per reagire quando gli indirizzi vengono caricati
watch(() => userAddresses.value, (newAddresses) => {
  if (newAddresses.length > 0 && authStore.user) {
    const defaultAddress = newAddresses.find(addr => addr.is_default) || newAddresses[0]
    if (defaultAddress) {
      selectAddress(defaultAddress)
    }
  } else if (authStore.user && !authStore.user.address) {
    // Se non ci sono indirizzi salvati ma l'utente ha dati di indirizzo nel profilo,
    // precompila i campi del form con quelli
    if (authStore.user.address || authStore.user.city || authStore.user.postal_code) {
      formData.value.address = authStore.user.address || ''
      formData.value.city = authStore.user.city || ''
      formData.value.postalCode = authStore.user.postal_code || ''
      formData.value.country = authStore.user.country || 'IT'
    }
  }
}, { immediate: true })

// Watcher per calcolare i prezzi CardSwap V1 quando cambia l'indirizzo
watch([
  () => formData.value.country,
  () => cartStore.sellers.length
], () => {
  // Calcola i prezzi solo se abbiamo il paese e ci sono venditori
  // CardSwap V1 richiede solo country_code, non serve city/postalCode per il calcolo
  if (formData.value.country && cartStore.sellers.length > 0) {
    calculateShippingRates()
  }
}, { deep: true })

// Metodi di utilità
const getShippingMethodsForSeller = (sellerId) => {
  // Ottieni i metodi di spedizione disponibili per questo venditore
  return deliveryMethods.value[sellerId] || []
}

// Calcola i prezzi di spedizione usando CardSwap Shipping V1
const calculateShippingRates = async () => {
  if (!formData.value.country || !formData.value.city || !formData.value.postalCode) {
    return
  }

  try {
    loadingShippingRates.value = true
    
    // Prepara i dati dei venditori con items dal carrello
    const sellers = cartStore.sellers.map(seller => ({
      seller_id: seller.id,
      items: seller.items.map(item => ({
        listing_id: item.id,
        quantity: item.quantity
      }))
    }))

    // Prepara l'indirizzo di spedizione (solo country_code richiesto)
    const shippingAddress = {
      country_code: formData.value.country
    }

    // Chiama l'API CardSwap Shipping V1
    const response = await axios.post('/api/shipping/v1/calculate-rates', {
      sellers,
      shipping_address: shippingAddress
    })

    if (response.data.success) {
      // Processa i risultati per ogni venditore
      const newDeliveryMethods = {}
      const newShippingData = {}
      
      Object.entries(response.data.data).forEach(([sellerId, sellerData]) => {
        // Verifica se c'è un errore
        if (sellerData.error) {
          console.error(`Errore per venditore ${sellerId}:`, sellerData.error)
          newDeliveryMethods[sellerId] = []
          return
        }

        // Salva i dati completi per questo venditore
        newShippingData[sellerId] = {
          package_bucket: sellerData.package_bucket,
          package_bucket_label: sellerData.package_bucket_label,
          logistic_units_total: sellerData.logistic_units_total,
          subtotal: sellerData.subtotal
        }

        // Processa le opzioni di spedizione
        if (sellerData.options && sellerData.options.length > 0) {
          newDeliveryMethods[sellerId] = sellerData.options.map(option => ({
            key: option.shipping_method,
            shipping_method: option.shipping_method,
            label: option.label,
            price: option.price,
            total_price: option.total_price,
            insurance_available: option.insurance_available,
            insurance_required: option.insurance_required,
            insurance_fee: option.insurance_fee,
            package_bucket: option.package_bucket,
            package_bucket_label: option.package_bucket_label
          }))
        } else {
          // Nessuna opzione disponibile
          newDeliveryMethods[sellerId] = []
        }
      })
      
      deliveryMethods.value = newDeliveryMethods
      shippingData.value = newShippingData
      
      // Seleziona automaticamente il metodo più economico per ogni venditore
      Object.keys(newDeliveryMethods).forEach(sellerId => {
        if (!selectedShippingMethods.value[sellerId] && newDeliveryMethods[sellerId].length > 0) {
          // Seleziona la prima opzione (già ordinata per prezzo dal backend)
          selectedShippingMethods.value[sellerId] = newDeliveryMethods[sellerId][0].shipping_method
        }
      })
    }
  } catch (error) {
    console.error('Errore calcolo tariffe CardSwap V1:', error)
    
    // In caso di errore, mostra messaggio e non imposta fallback
    // L'utente non potrà procedere senza opzioni valide
    cartStore.sellers.forEach(seller => {
      deliveryMethods.value[seller.id] = []
      shippingData.value[seller.id] = null
    })
  } finally {
    loadingShippingRates.value = false
  }
}

const getShippingCostForMethod = (shippingMethod, sellerId = null) => {
  // Se abbiamo un sellerId, cerca nei metodi specifici del venditore
  if (sellerId && deliveryMethods.value[sellerId]) {
    const method = deliveryMethods.value[sellerId].find(m => m.shipping_method === shippingMethod)
    if (method) {
      // Usa total_price che include già eventuale assicurazione
      return normalizePrice(method.total_price)
    }
  }
  
  // Fallback: cerca in tutti i metodi
  for (const sellerMethods of Object.values(deliveryMethods.value)) {
    const method = sellerMethods.find(m => m.shipping_method === shippingMethod)
    if (method) {
      return normalizePrice(method.total_price)
    }
  }
  
  return 0
}

const processPayment = async () => {
  if (!canProcessPayment.value) {
    alert('Completa tutti i campi obbligatori prima di procedere con il pagamento.')
    return
  }
  
  // Verifica che Stripe Elements sia inizializzato
  if (!stripe.value || !cardElement.value) {
    console.error('Stripe Elements non inizializzato')
    alert('Errore: Il campo carta di credito non è stato inizializzato correttamente. Ricarica la pagina e riprova.')
    return
  }
  
  isProcessing.value = true
  
  try {
    // Salva l'indirizzo se non è selezionato uno esistente
    if (!selectedAddress.value) {
      await saveNewAddress()
    }

    // Prepara i dati per il pagamento con metodi di spedizione CardSwap V1
    // Costruisce shippingSelections per ogni venditore
    const shippingSelections = []
    const shippingCosts = {}
    
    cartStore.sellers.forEach(seller => {
      const shippingMethod = selectedShippingMethods.value[seller.id]
      if (shippingMethod) {
        // Trova l'opzione selezionata per ottenere tutti i dettagli
        const selectedOption = deliveryMethods.value[seller.id]?.find(
          opt => opt.shipping_method === shippingMethod
        )
        
        if (selectedOption) {
          shippingSelections.push({
            seller_id: seller.id,
            shipping_method: selectedOption.shipping_method,
            price: selectedOption.price,
            insurance_fee: selectedOption.insurance_fee || 0
          })
          
          // Mantieni anche shipping_costs per backward compatibility
          shippingCosts[seller.id] = selectedOption.total_price
        }
      }
    })

    // AUDIT-FIX: verifica che ogni seller abbia una selezione prima di inviare (evita mismatch frontend/backend)
    if (shippingSelections.length !== cartStore.sellers.length) {
      isProcessing.value = false
      alert('Seleziona un metodo di spedizione per ogni venditore prima di procedere.')
      return
    }
    
    const paymentData = {
      address: selectedAddress.value || {
        first_name: formData.value.firstName,
        last_name: formData.value.lastName,
        company: formData.value.company,
        address_line_1: formData.value.address,
        address_line_2: formData.value.apartment,
        city: formData.value.city,
        country: formData.value.country,
        state_province: formData.value.region,
        postal_code: formData.value.postalCode,
        phone: formData.value.phone
      },
      shipping_methods: selectedShippingMethods.value, // Metodi per venditore (backward compatibility)
      shipping_costs: shippingCosts, // Costi totali (backward compatibility)
      shipping_selections: shippingSelections, // Nuovo formato CardSwap V1
      payment_method: paymentMethod, // Sempre Stripe
      cart_data: cartStore.getCartData()
    }

    console.log('💳 Invio richiesta pagamento...', {
      hasAddress: !!paymentData.address,
      shippingMethods: Object.keys(paymentData.shipping_methods || {}),
      cartDataKeys: Object.keys(paymentData.cart_data || {})
    })

    // Crea l'ordine e processa il pagamento
    const response = await axios.post('/api/payments/create', paymentData)
    
    if (response.data.success) {
      // Se il pagamento è stato processato con successo
      if (response.data.payment_intent) {
        // Conferma il pagamento con Stripe Elements
        const { error, paymentIntent } = await stripe.value.confirmCardPayment(
          response.data.payment_intent.client_secret,
          {
            payment_method: {
              card: cardElement.value,
              billing_details: {
                name: `${formData.value.firstName} ${formData.value.lastName}`,
                email: formData.value.email,
                address: {
                  line1: formData.value.address,
                  line2: formData.value.apartment,
                  city: formData.value.city,
                  state: formData.value.region,
                  postal_code: formData.value.postalCode,
                  country: formData.value.country,
                }
              }
            }
          }
        )
        
        if (error) {
          throw new Error(error.message)
        }
        
        if (paymentIntent.status === 'succeeded') {
          // Aggiorna immediatamente lo stato dell'ordine a "confirmed" 
          // invece di attendere il webhook (che potrebbe essere lento)
          const orderId = response.data.data?.order_id || response.data.order_id
          if (orderId) {
            try {
              await axios.post(`/api/orders/${orderId}/confirm-payment`, {}, {
                headers: {
                  'Authorization': `Bearer ${authStore.token}`,
                  'Accept': 'application/json'
                }
              })
            } catch (err) {
              console.warn('Impossibile aggiornare stato ordine immediatamente:', err)
              // Non blocchiamo il flusso, il webhook lo aggiornerà comunque
            }
          }
          
          // Svuota il carrello
          await cartStore.clearCart()
          
          // Redirect alla pagina di conferma
          if (orderId) {
            router.push(`/order-confirmation/${orderId}`)
          } else {
            throw new Error('ID ordine non trovato nella risposta')
          }
        }
      } else {
        // Se non c'è payment_intent, l'ordine è già stato processato
        await cartStore.clearCart()
        const orderId = response.data.data?.order_id || response.data.order_id
        if (orderId) {
          router.push(`/order-confirmation/${orderId}`)
        } else {
          throw new Error('ID ordine non trovato nella risposta')
        }
      }
    } else {
      throw new Error(response.data.message || 'Errore nel processamento dell\'ordine')
    }
    
  } catch (error) {
    console.error('Errore nel pagamento:', error)
    
    // Gestisci errori specifici
    let errorMessage = 'Errore nel processamento del pagamento'
    
    if (error.response) {
      // Errore dal server
      const status = error.response.status
      const data = error.response.data
      
      if (status === 422) {
        // Errore di validazione
        const errors = data.errors || {}
        const errorText = Object.values(errors).flat().join(', ') || data.message || 'Dati non validi'
        
        // Verifica se l'errore è relativo a Stripe Connect del venditore
        if (errorText.includes('Stripe Connect') || errorText.includes('non può ricevere pagamenti') || errorText.includes('non ha configurato') || errorText.includes('non ha completato')) {
          errorMessage = `Impossibile completare l'acquisto\n\n${errorText}\n\nIl venditore deve configurare Stripe Connect prima di poter vendere. Contatta il supporto se il problema persiste.`
        } else {
          errorMessage = `Errore di validazione: ${errorText}`
        }
        console.error('Errore 422 - Dettagli:', data)
      } else if (status === 403) {
        errorMessage = 'Accesso negato. Verifica di essere autenticato e di aver completato la verifica KYC.'
      } else if (status === 500) {
        errorMessage = 'Errore del server. Riprova più tardi o contatta il supporto.'
      } else {
        errorMessage = data.message || `Errore ${status}: ${error.message}`
      }
    } else if (error.request) {
      // Richiesta inviata ma nessuna risposta
      errorMessage = 'Nessuna risposta dal server. Verifica la connessione internet.'
    } else {
      // Errore nella configurazione della richiesta
      errorMessage = error.message || 'Errore nella configurazione della richiesta'
    }
    
    // Mostra messaggio di errore all'utente
    alert(errorMessage)
  } finally {
    isProcessing.value = false
  }
}

// Lifecycle
onMounted(async () => {
  // Inizializza il carrello
  await cartStore.initialize()
  
  // Carica gli indirizzi dell'utente
  await loadUserAddresses()
  
  // Se l'utente è autenticato ma non caricato, caricalo
  if (authStore.isAuthenticated && !authStore.user) {
    await authStore.fetchUser()
  }
  
  // I dati dell'utente vengono precompilati dal watcher
  
  // Inizializza i metodi di spedizione per ogni venditore
  initializeShippingMethods()
  
  // Inizializza Stripe
  await initializeStripe()
})

// Inizializza i metodi di spedizione per ogni venditore
// Non imposta default qui, verrà fatto dopo il calcolo delle tariffe
const initializeShippingMethods = () => {
  // Reset delle selezioni quando cambiano i venditori
  selectedShippingMethods.value = {}
}

onUnmounted(() => {
  // Cleanup Stripe Elements se necessario
  if (cardElement.value) {
    cardElement.value.destroy()
  }
})
</script>

<style scoped>
/* Stili personalizzati per il checkout */
.has-checked\:outline-2:checked {
  outline-width: 2px;
}

.has-checked\:-outline-offset-2:checked {
  outline-offset: -2px;
}

.has-checked\:outline-blue-600:checked {
  outline-color: #2563eb;
}

.has-focus-visible\:outline-3:focus-visible {
  outline-width: 3px;
}

.has-focus-visible\:-outline-offset-1:focus-visible {
  outline-offset: -1px;
}

.has-disabled\:border-gray-400:disabled {
  border-color: #9ca3af;
}

.has-disabled\:bg-gray-200:disabled {
  background-color: #e5e7eb;
}

.has-disabled\:opacity-25:disabled {
  opacity: 0.25;
}

.group-has-checked\:visible:checked {
  visibility: visible;
}

.not-checked\:before\:hidden:not(:checked)::before {
  display: none;
}

.checked\:border-blue-600:checked {
  border-color: #2563eb;
}

.checked\:bg-blue-600:checked {
  background-color: #2563eb;
}

.forced-colors\:appearance-auto {
  appearance: auto;
}

.forced-colors\:before\:hidden::before {
  display: none;
}
</style>
