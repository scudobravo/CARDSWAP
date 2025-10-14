<template>
  <div class="min-h-screen bg-white">
    <!-- Header -->
    <Header />
    
    <!-- Breadcrumb -->
    <div class="bg-gray-50 py-4">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex" aria-label="Breadcrumb">
          <ol class="flex items-center space-x-2">
            <li>
              <router-link to="/" class="text-gray-500 hover:text-primary transition-colors font-gill-sans text-sm">
                Home
              </router-link>
            </li>
            <li>
              <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </li>
            <li>
              <router-link :to="`/category/${getCategorySlug()}`" class="text-gray-500 hover:text-primary transition-colors font-gill-sans text-sm">
                {{ getCategoryName() }}
              </router-link>
            </li>
            <li>
              <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </li>
            <li>
              <span class="text-gray-500 font-gill-sans text-sm">{{ product.name }}</span>
            </li>
          </ol>
        </nav>
      </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
        <p class="mt-4 text-gray-600 font-gill-sans">Caricamento dettagli carta...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="text-center py-12">
        <div class="text-red-500 text-6xl mb-4">⚠️</div>
        <h2 class="text-2xl font-futura-bold text-primary mb-2">Errore nel caricamento</h2>
        <p class="text-gray-600 font-gill-sans mb-4">{{ error }}</p>
        <button @click="loadProductDetails" class="bg-primary text-white px-6 py-2 rounded-lg font-futura-bold hover:bg-primary/90 transition-colors">
          Riprova
        </button>
      </div>

      <!-- Product Content -->
      <div v-else>
      <!-- Top Actions -->
      <div class="flex justify-end mb-6">
        <button class="bg-secondary text-primary px-6 py-2 rounded-lg font-futura-bold text-sm hover:bg-secondary/90 transition-colors">
          SELL SAME CARD
        </button>
      </div>

      <!-- Product Details Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-6 gap-8 mb-12">
        <!-- Product Image (Left) -->
        <div class="lg:col-span-2">
          <div class="space-y-4">
            <!-- Main Image -->
            <div class="relative aspect-[3/4] bg-gray-200 rounded-lg flex items-center justify-center">
              <!-- Wishlist Button -->
              <button 
                @click="toggleWishlist"
                :disabled="isTogglingWishlist"
                class="absolute top-4 right-4 z-10 p-2 rounded-full transition-all duration-200 hover:scale-110 disabled:opacity-50 disabled:cursor-not-allowed"
                :class="[
                  isInWishlist ? 'bg-secondary text-primary' : 'bg-white/80 text-gray-600 hover:bg-secondary hover:text-primary'
                ]"
              >
                <svg class="w-6 h-6" :fill="isInWishlist ? 'currentColor' : 'none'" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                </svg>
              </button>
              
              <div v-if="product.image_url" class="w-full h-full bg-cover bg-center rounded-lg" :style="{ backgroundImage: `url(${product.image_url})` }"></div>
              <div v-else class="text-center text-gray-500">
                <svg class="w-24 h-24 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm font-gill-sans">Immagine non disponibile</p>
              </div>
            </div>
            
            <!-- Thumbnail Images -->
            <div class="grid grid-cols-2 gap-4">
              <div class="aspect-[3/4] bg-gray-200 rounded-lg flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <div class="aspect-[3/4] bg-gray-200 rounded-lg flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Product Info (Center) -->
        <div class="lg:col-span-2">
          <div class="space-y-6">
            <!-- Title -->
            <h1 class="text-2xl font-futura-bold text-primary">{{ product.name || 'Player' }}</h1>
            
            <!-- Card Attributes -->
            <div class="flex flex-wrap gap-3">
              <!-- Numbered - Mostra solo se card_number_in_set è presente -->
              <div v-if="product.card_number_in_set" class="relative group">
                <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
                  <span class="text-primary font-futura-bold text-lg">{{ product.card_number_in_set }}</span>
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                  NUMBERED
                  <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                </div>
              </div>
              
              <!-- Autograph - Mostra solo se is_autograph è true -->
              <div v-if="product.is_autograph" class="relative group">
                <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
                  <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                  </svg>
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                  AUTOGRAPH
                  <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                </div>
              </div>
              
              <!-- Relic - Mostra solo se is_relic è true -->
              <div v-if="product.is_relic" class="relative group">
                <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
                  <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.429 3.658L9.3 16.573z"/>
                  </svg>
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                  RELIC
                  <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                </div>
              </div>

              <!-- Rookie - Mostra solo se is_rookie è true -->
              <div v-if="product.is_rookie" class="relative group">
                <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
                  <span class="text-primary font-futura-bold text-lg">RC</span>
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                  ROOKIE
                  <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                </div>
              </div>

              <!-- Star - Mostra solo se is_star è true -->
              <div v-if="product.is_star" class="relative group">
                <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
                  <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                  </svg>
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                  STAR
                  <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                </div>
              </div>

              <!-- Legend - Mostra solo se is_legend è true -->
              <div v-if="product.is_legend" class="relative group">
                <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
                  <svg class="w-6 h-6 text-primary" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <!-- Tooltip -->
                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                  LEGEND
                  <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                </div>
              </div>
            </div>
            
            <!-- Key Information -->
            <div class="space-y-3">
              <div class="flex justify-between">
                <span class="text-gray-600 font-gill-sans">Team:</span>
                <span class="font-futura-bold text-primary">{{ product.team || 'Team Name' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 font-gill-sans">Set:</span>
                <span class="font-futura-bold text-primary">{{ product.set_name || 'Set Name' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 font-gill-sans">Year:</span>
                <span class="font-futura-bold text-primary">{{ product.year || '2024' }}</span>
              </div>
              <div class="flex justify-between">
                <span class="text-gray-600 font-gill-sans">Rarity:</span>
                <span class="font-futura-bold text-primary">{{ product.rarity || 'Rare' }}</span>
              </div>
            </div>
            
            <!-- Condition -->
            <div class="bg-gray-100 px-4 py-2 rounded-lg">
              <span class="text-primary font-futura-bold">{{ product.condition || 'LIGHT PLAYED' }}</span>
            </div>
            
            <!-- Price -->
            <div class="text-2xl font-futura-bold text-primary">
              {{ product.price || '95' }}
            </div>
            
            <!-- Add to Cart Button -->
            <button 
              @click="addToCart"
              :disabled="isAddingToCart || !listing"
              class="w-full bg-primary text-white py-3 px-6 rounded-lg font-futura-bold text-lg hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <span v-if="isAddingToCart">Aggiungendo...</span>
              <span v-else>ADD TO CART</span>
            </button>
            
            <!-- Add to Cart Message -->
            <div v-if="addToCartMessage" class="mt-3 text-center">
              <p 
                :class="[
                  'text-sm font-gill-sans',
                  addToCartMessage.includes('aggiunto') ? 'text-green-600' : 'text-red-600'
                ]"
              >
                {{ addToCartMessage }}
              </p>
            </div>
          </div>
        </div>

        <!-- Price Trend (Right) -->
        <div class="lg:col-span-2">
          <BarChart 
            :product-id="product.id"
            :current-price="product.price"
          />
        </div>
      </div>

      <!-- Seller Details -->
      <div class="bg-gray-50 p-6 rounded-lg mb-8">
        <h3 class="text-xl font-futura-bold text-primary mb-4">Seller Details</h3>
        
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <div class="flex items-center space-x-2">
              <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
              </svg>
              <a href="#" class="text-primary hover:text-secondary transition-colors font-futura-bold">
                Nome Venditore
              </a>
            </div>
            
            <div class="bg-primary text-white px-3 py-1 rounded-lg text-sm font-futura-bold">
              8 Numero di vendite
            </div>
            
            <div class="flex items-center space-x-1">
              <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
              <svg class="w-5 h-5 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
              </svg>
            </div>
          </div>
          
          <div class="flex space-x-3">
            <button 
              @click="showChatModal = true"
              class="bg-primary text-white px-4 py-2 rounded-lg font-futura-bold text-sm hover:bg-primary/90 transition-colors"
            >
              Chat
            </button>
            <button 
              @click="showReportPopup = true"
              class="bg-red-500 text-white px-4 py-2 rounded-lg font-futura-bold text-sm hover:bg-red-600 transition-colors"
            >
              REPORT
            </button>
          </div>
        </div>
      </div>

      <!-- Related Products -->
      <div>
        <ProductCarousel 
          :title="'Related Products'"
          :products="relatedProducts"
          :category="product.category"
          :section="'related'"
          :use-dynamic-data="false"
          :loading="relatedProductsLoading"
          :error="relatedProductsError"
          :hide-see-all="true"
        />
      </div>
      </div>
    </div>

    <!-- Footer -->
    <Footer />

    <!-- Report Popup -->
    <ReportPopup 
      :is-open="showReportPopup"
      :product-id="product.id"
      :seller-name="sellerName"
      @close="showReportPopup = false"
    />

    <!-- Chat Modal -->
    <VendorChatModal 
      :is-open="showChatModal"
      :product-id="product.id || 'temp'"
      :vendor-id="vendorId"
      :vendor-name="vendorName"
      :product-name="product.name"
      @close="showChatModal = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useCartStore } from '../stores/cart.js'
import { useWishlistStore } from '../stores/wishlist.js'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'
import ProductCarousel from '../components/ProductCarousel.vue'
import ReportPopup from '../components/ReportPopup.vue'
import VendorChatModal from '../components/VendorChatModal.vue'
import BarChart from '../components/BarChart.vue'
import cardService from '../services/cardService.js'

const route = useRoute()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()

// Determina se stiamo usando ID o slug
const isSlugRoute = computed(() => {
  return route.name === 'card.detail'
})

const productId = computed(() => {
  if (isSlugRoute.value) {
    // Per le route slug, dobbiamo cercare l'ID basandoci sullo slug
    return null // Sarà risolto tramite API
  }
  return route.params.id
})

// Product data
const product = ref({
  id: productId.value,
  name: 'Player',
  team: 'Team Name',
  set_name: 'Set Name',
  year: '2024',
  rarity: 'Rare',
  price: '95',
  image_url: null,
  category: 'football',
  condition: 'LIGHT PLAYED',
  card_number_in_set: null,
  is_autograph: false,
  is_relic: false,
  is_rookie: false,
  is_star: false,
  is_legend: false
})

// Loading and error states
const loading = ref(true)
const error = ref(null)

// Report popup
const showReportPopup = ref(false)
const sellerName = ref('Nome Venditore')

// Wishlist
const isTogglingWishlist = ref(false)

// Computed per controllare se l'articolo è nella wishlist
const isInWishlist = computed(() => {
  if (!product.value?.id) return false
  return wishlistStore.isInWishlist(product.value.id)
})

// Chat modal
const showChatModal = ref(false)
const vendorId = ref(1) // ID del venditore (da ottenere dai dati del prodotto)
const vendorName = ref('Nome Venditore')

// Cart functionality
const isAddingToCart = ref(false)
const addToCartMessage = ref('')
const listing = ref(null) // Dati del listing per il carrello

// Related products
const relatedProducts = ref([])
const relatedProductsLoading = ref(false)
const relatedProductsError = ref(null)

// Methods
const addToCart = async () => {
  if (!listing.value) {
    addToCartMessage.value = 'Dati del prodotto non disponibili'
    return
  }

  isAddingToCart.value = true
  addToCartMessage.value = ''

  try {
    const result = await cartStore.addToCart(listing.value, 1)
    
    if (result.success) {
      addToCartMessage.value = 'Prodotto aggiunto al carrello!'
      // Reset message after 3 seconds
      setTimeout(() => {
        addToCartMessage.value = ''
      }, 3000)
    } else {
      addToCartMessage.value = result.message || 'Errore nell\'aggiunta al carrello'
    }
  } catch (error) {
    console.error('Error adding to cart:', error)
    addToCartMessage.value = 'Errore nell\'aggiunta al carrello'
  } finally {
    isAddingToCart.value = false
  }
}

// Toggle wishlist
const toggleWishlist = async () => {
  if (!product.value?.id) {
    console.error('Nessun ID prodotto disponibile')
    return
  }

  isTogglingWishlist.value = true

  try {
    if (isInWishlist.value) {
      // Rimuovi dalla wishlist
      const result = await wishlistStore.removeFromWishlist(product.value.id)
      if (result.success) {
        console.log('Rimosso dalla wishlist')
      }
    } else {
      // Aggiungi alla wishlist
      const result = await wishlistStore.addToWishlist(product.value.id)
      if (result.success) {
        console.log('Aggiunto alla wishlist')
      }
    }
  } catch (error) {
    console.error('Errore nel toggle wishlist:', error)
  } finally {
    isTogglingWishlist.value = false
  }
}

const loadProductDetails = async () => {
  loading.value = true
  error.value = null

  try {
    let response
    
    if (isSlugRoute.value) {
      // Per route slug, usiamo categoria e slug
      const category = route.params.category
      const cardSlug = route.params.cardSlug
      response = await cardService.getCardDetailsBySlug(category, cardSlug)
    } else {
      // Per route ID tradizionale
      response = await cardService.getCardDetails(route.params.id)
    }
    
    if (response.success) {
      // Merge database data with fallback data, preserving fallback values for missing attributes
      product.value = {
        ...product.value,
        ...response.data
      }
      
      // Create mock listing data for cart functionality
      // TODO: In futuro, questo dovrebbe essere ottenuto da un'API reale
      listing.value = {
        id: `listing_${product.value.id}`,
        card_model_id: product.value.id,
        seller_id: 1, // Mock seller ID
        price: parseFloat(product.value.price?.replace('€', '') || '95'),
        quantity: 1,
        condition: product.value.condition || 'LIGHT PLAYED',
        description: product.value.description || 'Carta in ottime condizioni',
        images: product.value.image_url ? [product.value.image_url] : [],
        available: true,
        seller: {
          id: 1,
          name: 'Venditore Mock',
          email: 'vendor@example.com'
        },
        card_model: {
          id: product.value.id,
          name: product.value.name,
          category: product.value.category
        },
        shipping_zones: []
      }
      
      // Update vendor info for chat
      vendorId.value = listing.value.seller_id
      vendorName.value = listing.value.seller.name
      sellerName.value = listing.value.seller.name
      
      // Load related products after main product is loaded
      if (isSlugRoute.value) {
        // Per route slug, carica direttamente usando categoria e slug
        await loadRelatedProducts(null) // Passiamo null perché useremo la route
      } else {
        await loadRelatedProducts(product.value.id)
      }
      
    } else {
      error.value = response.error
      console.error('Error loading product:', response.error)
    }
  } catch (err) {
    error.value = 'Errore di connessione'
    console.error('Error loading product details:', err)
  } finally {
    loading.value = false
  }
  
  // Se non abbiamo dati del listing, crea un mock per il testing
  if (!listing.value && product.value.id) {
    listing.value = {
      id: `listing_${product.value.id}`,
      card_model_id: product.value.id,
      seller_id: 1,
      price: parseFloat(product.value.price?.replace('€', '') || '95'),
      quantity: 1,
      condition: product.value.condition || 'LIGHT PLAYED',
      description: product.value.description || 'Carta in ottime condizioni',
      images: product.value.image_url ? [product.value.image_url] : [],
      available: true,
      seller: {
        id: 1,
        name: 'Venditore Mock',
        email: 'vendor@example.com'
      },
      card_model: {
        id: product.value.id,
        name: product.value.name,
        category: product.value.category
      },
      shipping_zones: []
    }
    
    // Update vendor info
    vendorId.value = listing.value.seller_id
    vendorName.value = listing.value.seller.name
    sellerName.value = listing.value.seller.name
    
    // Load related products if we have a product ID
    if (isSlugRoute.value) {
      await loadRelatedProducts(null) // Per route slug, useremo categoria e slug
    } else if (product.value.id) {
      await loadRelatedProducts(product.value.id)
    }
  }
}

// Load related products
const loadRelatedProducts = async (cardId) => {
  relatedProductsLoading.value = true
  relatedProductsError.value = null
  
  try {
    let response
    
    if (isSlugRoute.value) {
      // Per route slug, usiamo categoria e slug
      const category = route.params.category
      const cardSlug = route.params.cardSlug
      console.log('Loading related products for slug route:', category, cardSlug)
      response = await cardService.getRelatedProductsBySlug(category, cardSlug, 8)
    } else {
      // Per route ID tradizionale
      if (!cardId || cardId === null || cardId === 'temp') {
        console.log('Skipping related products load - invalid cardId:', cardId)
        return
      }
      console.log('Loading related products for ID route:', cardId)
      response = await cardService.getRelatedProducts(cardId, 8)
    }
    
    if (response.success) {
      relatedProducts.value = response.data
      console.log('Related products loaded:', response.data.length, 'products')
      console.log('Criteria used:', response.criteria)
    } else {
      relatedProductsError.value = response.error
      console.error('Error loading related products:', response.error)
    }
  } catch (error) {
    relatedProductsError.value = 'Errore nel caricamento dei prodotti correlati'
    console.error('Error loading related products:', error)
  } finally {
    relatedProductsLoading.value = false
  }
}

const getCategorySlug = () => {
  if (isSlugRoute.value) {
    return route.params.category
  }
  // Per route ID, determina dalla categoria del prodotto
  const categoryMap = {
    'Calcio': 'football',
    'Basketball': 'basketball',
    'Pokemon': 'pokemon'
  }
  return categoryMap[product.value.category] || 'football'
}

const getCategoryName = () => {
  if (isSlugRoute.value) {
    const categoryMap = {
      'football': 'Calcio',
      'basketball': 'Basketball', 
      'pokemon': 'Pokemon'
    }
    return categoryMap[route.params.category] || 'Categoria'
  }
  return product.value.category || 'Categoria'
}

onMounted(async () => {
  // Inizializza il wishlist store
  await wishlistStore.initialize()
  loadProductDetails()
})

// Watch per ricaricare i dati quando cambia la route (navigazione tra carte diverse)
watch(() => route.params, (newParams, oldParams) => {
  // Ricarica solo se cambia l'ID o lo slug della carta
  if (newParams.id !== oldParams?.id || newParams.cardSlug !== oldParams?.cardSlug) {
    console.log('Route params changed, reloading product details...')
    loadProductDetails()
  }
}, { deep: true })
</script>
