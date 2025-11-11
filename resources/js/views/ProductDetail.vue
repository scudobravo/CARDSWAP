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
        <button 
          @click="openSellSameCardModal"
          class="bg-secondary text-primary px-6 py-2 rounded-lg font-futura-bold text-sm hover:bg-secondary/90 transition-colors"
        >
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
              
              <img 
                v-if="mainImageUrl" 
                :src="mainImageUrl" 
                :alt="product.name || 'Carta'"
                class="w-full h-full object-cover rounded-lg"
                @error="handleImageError"
              />
              <div v-else class="text-center text-gray-500">
                <svg class="w-24 h-24 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <p class="text-sm font-gill-sans">Immagine non disponibile</p>
              </div>
            </div>
            
            <!-- Thumbnail Images -->
            <!-- Grid view quando ci sono 2 o meno immagini -->
            <div v-if="product.images && product.images.length > 0 && product.images.length <= 2" class="grid grid-cols-2 gap-4">
              <div 
                v-for="(image, index) in product.images" 
                :key="index"
                @click="selectImage(index)"
                :class="[
                  'aspect-[3/4] bg-gray-200 rounded-lg overflow-hidden cursor-pointer transition-all duration-200',
                  selectedImageIndex === index ? 'ring-2 ring-primary ring-offset-2' : 'hover:opacity-80'
                ]"
              >
                <img 
                  :src="getImageUrl(image)" 
                  :alt="`${product.name} - Immagine ${index + 1}`"
                  class="w-full h-full object-cover"
                  @error="handleImageError"
                />
              </div>
            </div>
            
            <!-- Carousel view quando ci sono più di 2 immagini -->
            <div v-else-if="product.images && product.images.length > 2" class="relative group">
              <!-- Left arrow -->
              <button 
                @click="scrollThumbnailsLeft"
                :disabled="!canScrollThumbnailsLeft"
                class="absolute left-0 top-1/2 -translate-y-1/2 z-10 bg-primary hover:bg-primary/90 text-white p-2 rounded-full shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 opacity-100 lg:opacity-0 lg:group-hover:opacity-100"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>

              <!-- Right arrow -->
              <button 
                @click="scrollThumbnailsRight"
                :disabled="!canScrollThumbnailsRight"
                class="absolute right-0 top-1/2 -translate-y-1/2 z-10 bg-primary hover:bg-primary/90 text-white p-2 rounded-full shadow-lg disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 opacity-100 lg:opacity-0 lg:group-hover:opacity-100"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>

              <!-- Thumbnails container -->
              <div 
                ref="thumbnailsContainer"
                class="flex space-x-4 overflow-x-auto scrollbar-hide px-1"
                @scroll="handleThumbnailsScroll"
              >
                <div 
                  v-for="(image, index) in product.images" 
                  :key="index"
                  @click="selectImage(index)"
                  :class="[
                    'flex-shrink-0 aspect-[3/4] w-20 bg-gray-200 rounded-lg overflow-hidden cursor-pointer transition-all duration-200',
                    selectedImageIndex === index ? 'ring-2 ring-primary ring-offset-2' : 'hover:opacity-80'
                  ]"
                >
                  <img 
                    :src="getImageUrl(image)" 
                    :alt="`${product.name} - Immagine ${index + 1}`"
                    class="w-full h-full object-cover"
                    @error="handleImageError"
                  />
                </div>
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
              <!-- Numbered - Mostra solo se presente (card_number preferito) -->
              <div v-if="product.card_number || product.card_number_in_set" class="relative group">
                <div class="bg-gray-100 p-3 rounded-lg flex items-center justify-center min-w-[48px] min-h-[48px]">
                  <span class="text-primary font-futura-bold text-lg">{{ product.card_number || product.card_number_in_set }}</span>
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
                <span class="font-futura-bold text-primary">{{ product.rarity || 'Rare' }}{{ product.rarity_variation ? ` (${product.rarity_variation})` : '' }}</span>
              </div>
            </div>
            
            <!-- Condition -->
            <div class="bg-gray-100 px-4 py-2 rounded-lg">
              <span class="text-primary font-futura-bold">{{ product.condition || 'LIGHT PLAYED' }}</span>
            </div>
            
            <!-- Notes -->
            <div v-if="product.description" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <p class="text-sm text-gray-700 font-gill-sans whitespace-pre-wrap">{{ product.description }}</p>
            </div>
            
            <!-- Price -->
            <div class="text-2xl font-futura-bold text-primary">
              {{ formatPrice(product.price) }}
            </div>
            
            <!-- Add to Cart Button -->
            <button 
              @click="addToCart"
              :disabled="isAddingToCart || !product.id"
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
            :product-id="product.listing_id || product.id"
            :current-price="parseFloat(String(product.price || 0).replace(/€/g, '').replace(/,/g, '')) || 0"
            :listing-id="product.listing_id"
          />
        </div>
      </div>

      <!-- Seller Details -->
      <div class="bg-gray-50 p-4 md:p-6 rounded-lg mb-8">
        <h3 class="text-xl font-futura-bold text-primary mb-4">Seller Details</h3>
        
        <!-- Mobile-first: Stack verticale -->
        <div class="flex flex-col space-y-4 md:flex-row md:items-center md:justify-between md:space-y-0">
          <!-- Informazioni venditore -->
          <div class="flex flex-col space-y-3 md:flex-row md:items-center md:space-x-4 md:space-y-0 flex-1 min-w-0">
            <!-- Nome venditore con indicatore online -->
            <div class="flex items-center space-x-2 flex-shrink-0">
              <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
              </svg>
              <a href="#" class="text-primary hover:text-secondary transition-colors font-futura-bold truncate">
                {{ sellerName || 'Nome Venditore' }}
              </a>
            </div>
            
            <!-- Badge vendite e stelle su stessa riga su mobile -->
            <div class="flex items-center space-x-3 md:space-x-4 flex-wrap gap-2">
              <div class="bg-primary text-white px-3 py-1 rounded-lg text-sm font-futura-bold whitespace-nowrap flex-shrink-0">
                {{ listing?.seller?.total_sales || 0 }} Numero di vendite
              </div>
              
              <div class="flex items-center space-x-1 flex-shrink-0">
                <svg 
                  v-for="(star, index) in 5" 
                  :key="index"
                  :class="['w-5 h-5', getStarClass(index)]" 
                  fill="currentColor" 
                  viewBox="0 0 20 20"
                >
                  <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
              </div>
            </div>
          </div>
          
          <!-- Bottoni Chat e REPORT -->
          <div class="flex flex-col sm:flex-row gap-2 md:flex-shrink-0 md:space-x-3 md:gap-0">
            <button 
              @click="showChatModal = true"
              class="w-full sm:w-auto bg-primary text-white px-4 py-2 rounded-lg font-futura-bold text-sm hover:bg-primary/90 transition-colors"
            >
              Chat
            </button>
            <button 
              @click="showReportPopup = true"
              class="w-full sm:w-auto bg-red-500 text-white px-4 py-2 rounded-lg font-futura-bold text-sm hover:bg-red-600 transition-colors"
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
      :product-id="listing?.id || product?.listing_id || product?.id || null"
      :seller-name="sellerName"
      @close="showReportPopup = false"
    />

    <!-- Chat Modal -->
    <VendorChatModal 
      :is-open="showChatModal"
      :product-id="listing?.id || product.listing_id || null"
      :vendor-id="vendorId"
      :vendor-name="vendorName"
      :product-name="getProductDisplayName()"
      @close="showChatModal = false"
    />

    <!-- Create Listing Modal for Sell Same Card -->
    <CreateListingModal
      :is-open="showCreateListingModal"
      :preselected-card-model="preselectedCardModel"
      @close="handleModalClose"
      @created="handleListingCreated"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { useCartStore } from '../stores/cart.js'
import { useWishlistStore } from '../stores/wishlist.js'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'
import ProductCarousel from '../components/ProductCarousel.vue'
import ReportPopup from '../components/ReportPopup.vue'
import VendorChatModal from '../components/VendorChatModal.vue'
import BarChart from '../components/BarChart.vue'
import CreateListingModal from '../components/listing/CreateListingModal.vue'
import cardService from '../services/cardService.js'
import { formatPriceItaliana } from '../utils/priceFormatter'

const route = useRoute()
const cartStore = useCartStore()
const wishlistStore = useWishlistStore()

// Determina se stiamo usando ID, slug o listing route
const isSlugRoute = computed(() => {
  return route.name === 'card.detail'
})

const isListingRoute = computed(() => {
  return route.name === 'listing.detail'
})

const productId = computed(() => {
  if (isSlugRoute.value) {
    // Per le route slug, dobbiamo cercare l'ID basandoci sullo slug
    return null // Sarà risolto tramite API
  }
  if (isListingRoute.value) {
    // Per le route listing, l'ID è il listingId dalla route
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

// Create Listing Modal for Sell Same Card
const showCreateListingModal = ref(false)
const preselectedCardModel = ref(null)

// Cart functionality
const isAddingToCart = ref(false)
const addToCartMessage = ref('')
const listing = ref(null) // Dati del listing per il carrello

// Related products
const relatedProducts = ref([])
const relatedProductsLoading = ref(false)
const relatedProductsError = ref(null)

// Image selection
const selectedImageIndex = ref(0)

// Thumbnail carousel refs and state
const thumbnailsContainer = ref(null)
const canScrollThumbnailsLeft = ref(false)
const canScrollThumbnailsRight = ref(true)

// Computed per l'immagine principale da mostrare
const mainImageUrl = computed(() => {
  if (!product.value) return null
  
  // Se ci sono immagini nella CardListing, usa quella selezionata
  if (product.value.images && Array.isArray(product.value.images) && product.value.images.length > 0) {
    const selectedImage = product.value.images[selectedImageIndex.value]
    if (selectedImage) {
      return getImageUrl(selectedImage)
    }
    // Se l'indice selezionato non esiste, usa la prima immagine
    return getImageUrl(product.value.images[0])
  }
  
  // Fallback a image_url se disponibile
  if (product.value.image_url) {
    return product.value.image_url
  }
  
  return null
})

// Computed per calcolare le stelle in base al rating del venditore
const sellerRating = computed(() => {
  if (!listing.value?.seller?.rating) return 0
  return parseFloat(listing.value.seller.rating) || 0
})

// Metodo per determinare se una stella deve essere piena o vuota
// index va da 0 a 4, corrisponde alle stelle 1-5
const getStarClass = (index) => {
  const rating = sellerRating.value
  const starNumber = index + 1 // Converti 0-4 in 1-5
  
  // Se il rating è >= numero stella, la stella è piena
  if (rating >= starNumber) {
    return 'text-yellow-400'
  }
  // Se il rating è >= numero stella - 0.5, mostra metà stella (per semplicità mostriamo piena)
  if (rating >= starNumber - 0.5) {
    return 'text-yellow-400'
  }
  // Altrimenti vuota
  return 'text-gray-300'
}

// Methods
const getImageUrl = (image) => {
  if (!image) return null
  if (image.startsWith('/storage/') || image.startsWith('http')) {
    return image
  }
  return '/storage/' + image
}

const selectImage = (index) => {
  selectedImageIndex.value = index
}

// Thumbnail carousel methods
const scrollThumbnailsLeft = () => {
  if (thumbnailsContainer.value) {
    thumbnailsContainer.value.scrollBy({ left: -120, behavior: 'smooth' })
  }
}

const scrollThumbnailsRight = () => {
  if (thumbnailsContainer.value) {
    thumbnailsContainer.value.scrollBy({ left: 120, behavior: 'smooth' })
  }
}

const handleThumbnailsScroll = () => {
  if (thumbnailsContainer.value) {
    const container = thumbnailsContainer.value
    canScrollThumbnailsLeft.value = container.scrollLeft > 0
    canScrollThumbnailsRight.value = container.scrollLeft < (container.scrollWidth - container.clientWidth - 10)
  }
}

const updateThumbnailsScrollButtons = () => {
  if (thumbnailsContainer.value) {
    handleThumbnailsScroll()
  }
}

const addToCart = async () => {
  // Se non c'è listing ma c'è product, crea un listing temporaneo
  if (!listing.value && product.value.id) {
    listing.value = {
      id: product.value.listing_id || `listing_${product.value.id}`,
      card_model_id: product.value.id,
      seller_id: product.value.seller?.id || 1,
      price: parseFloat(String(product.value.price || 0).replace(/€/g, '').replace(/,/g, '')) || 95,
      quantity: product.value.quantity || 1,
      available_quantity: product.value.quantity || 1, // Quantità disponibile totale del venditore
      condition: product.value.condition || 'LIGHT PLAYED',
      description: product.value.description || 'Carta in ottime condizioni',
      images: product.value.images || (product.value.image_url ? [product.value.image_url] : []),
      available: true,
      seller: product.value.seller || {
        id: 1,
        name: 'Venditore',
        email: 'vendor@example.com'
      },
      card_model: {
        id: product.value.id,
        name: product.value.name,
        category: product.value.category
      },
      shipping_zones: []
    }
  }
  
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

// Open Sell Same Card Modal
const openSellSameCardModal = async () => {
  try {
    // Prepara i dati della carta da pre-selezionare
    const cardModelId = product.value.id || listing.value?.card_model_id
    
    if (!cardModelId) {
      alert('Errore: Impossibile identificare la carta')
      return
    }

    // Carica i dettagli completi della carta se necessario
    let cardModelData = null
    if (listing.value?.card_model) {
      // Usa i dati già disponibili
      cardModelData = listing.value.card_model
    } else {
      // Carica i dettagli dalla API
      try {
        const response = await fetch(`/api/card-models/${cardModelId}`)
        if (response.ok) {
          const data = await response.json()
          cardModelData = data.data || data
        }
      } catch (error) {
        console.error('Errore nel caricamento dettagli carta:', error)
      }
    }

    // Pre-popolare i dati della carta
    preselectedCardModel.value = {
      id: cardModelId,
      ...cardModelData,
      // Assicurati che i dati necessari siano presenti
      player: cardModelData?.player || { name: product.value.name },
      team: cardModelData?.team || { name: product.value.team },
      card_set: cardModelData?.card_set || { name: product.value.set_name },
      year: cardModelData?.year || product.value.year,
      rarity: cardModelData?.rarity || product.value.rarity,
      card_number: cardModelData?.card_number || product.value.card_number_in_set
    }

    // Apri il modal
    showCreateListingModal.value = true
  } catch (error) {
    console.error('Errore nell\'apertura del modal:', error)
    alert('Errore nell\'apertura del form di vendita')
  }
}

// Handle listing created
const handleListingCreated = () => {
  showCreateListingModal.value = false
  preselectedCardModel.value = null
  // Opzionale: mostra un messaggio di successo o ricarica la pagina
  // Potresti anche reindirizzare alla nuova inserzione o aggiornare la vista
  console.log('Inserzione creata con successo!')
}

// Handle modal close
const handleModalClose = () => {
  showCreateListingModal.value = false
  preselectedCardModel.value = null
}

const loadProductDetails = async () => {
  loading.value = true
  error.value = null

  try {
    let response
    
    if (isListingRoute.value) {
      // Per route listing (/category/:listingId/:slug), recupera i dati dalla CardListing specifica
      const listingId = route.params.listingId
      response = await cardService.getListingDetails(listingId)
      
      if (response.success && response.data) {
        const listing = response.data
        
        // Trasforma i dati della CardListing nel formato atteso dal componente
        const cardModel = listing.card_model || listing.cardModel
        const seller = listing.seller
        
        // Formatta le immagini
        let imageUrl = null
        let images = []
        if (listing.images && Array.isArray(listing.images) && listing.images.length > 0) {
          images = listing.images
          const firstImage = listing.images[0]
          if (!firstImage.startsWith('/storage/') && !firstImage.startsWith('http')) {
            imageUrl = '/storage/' + firstImage
          } else {
            imageUrl = firstImage
          }
        } else if (cardModel?.image_url) {
          imageUrl = cardModel.image_url
        }
        
        product.value = {
          id: cardModel?.id,
          listing_id: listing.id,
          name: cardModel?.player?.name || cardModel?.player_name || cardModel?.name || 'Player',
          slug: listing.slug || product.value.name?.toLowerCase().replace(/[^a-z0-9\s-]/g, '').replace(/\s+/g, '-'),
          team: cardModel?.team?.name || 'Unknown Team',
          set_name: cardModel?.card_set?.name || cardModel?.cardSet?.name || 'Set Name',
          year: cardModel?.year || new Date().getFullYear(),
          rarity: cardModel?.rarity || 'Rare',
          price: parseFloat(listing.price) || 0, // Il prezzo viene già come numero dal backend
          rating: '4.5',
          image_url: imageUrl,
          images: images,
          category: cardModel?.category?.slug === 'calcio' ? 'football' : (cardModel?.category?.slug === 'basketball' ? 'basketball' : 'pokemon'),
          description: listing.description || cardModel?.description,
          condition: listing.condition || 'LIGHT PLAYED',
          card_number_in_set: cardModel?.card_number_in_set,
          is_autograph: cardModel?.is_autograph ?? false,
          is_relic: cardModel?.is_relic ?? false,
          is_rookie: cardModel?.is_rookie ?? false,
          is_star: cardModel?.is_star ?? false,
          is_legend: cardModel?.is_legend ?? false,
          card_number: cardModel?.card_number,
          quantity: listing.quantity || 1,
          created_at: listing.created_at,
          updated_at: listing.updated_at
        }
        
        // Reset selected image index quando cambia il prodotto
        selectedImageIndex.value = 0
        
        // Aggiorna i pulsanti di scroll delle thumbnail dopo che le immagini sono caricate
        nextTick(() => {
          updateThumbnailsScrollButtons()
        })
        
        // Crea l'oggetto listing per il carrello
        listing.value = {
          id: listing.id,
          card_model_id: cardModel?.id,
          seller_id: seller?.id,
          price: parseFloat(listing.price),
          quantity: listing.quantity || 1,
          available_quantity: listing.quantity || 1, // Quantità disponibile totale del venditore
          condition: listing.condition || 'LIGHT PLAYED',
          description: listing.description || 'Carta in ottime condizioni',
          images: images,
          available: listing.status === 'active',
          seller: seller ? {
            id: seller.id,
            name: seller.name,
            email: seller.email,
            total_sales: seller.total_sales ?? 0,
            rating: seller.rating ?? 0
          } : null,
          card_model: {
            id: cardModel?.id,
            name: cardModel?.player?.name || cardModel?.name,
            category: product.value.category
          },
          shipping_zones: listing.shipping_zones || listing.shippingZones || []
        }
        
        // Update vendor info for chat
        if (seller) {
          vendorId.value = seller.id
          vendorName.value = seller.name
          sellerName.value = seller.name
        }
        
        // Load related products
        // Per listing route, usa l'endpoint delle listing correlate invece di CardModel
        if (isListingRoute.value && listing.id) {
          await loadRelatedProducts(null) // Passiamo null perché useremo la route
        } else if (cardModel?.id) {
          await loadRelatedProducts(cardModel.id)
        }
        
        return
      }
    } else if (isSlugRoute.value) {
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
      
      // Reset selected image index quando cambia il prodotto
      selectedImageIndex.value = 0
      
      // Aggiorna i pulsanti di scroll delle thumbnail dopo che le immagini sono caricate
      nextTick(() => {
        updateThumbnailsScrollButtons()
      })
      
      // Se ci sono immagini ma non c'è image_url, usa la prima immagine come principale
      if (product.value.images && product.value.images.length > 0 && !product.value.image_url) {
        product.value.image_url = getImageUrl(product.value.images[0])
      }
      
      // Usa i dati del listing dall'API se disponibili, altrimenti crea un mock
      if (response.data.listing_id && response.data.seller) {
        // Dati reali dalla CardListing
        listing.value = {
          id: response.data.listing_id,
          card_model_id: product.value.id,
          seller_id: response.data.seller.id,
          price: parseFloat(String(product.value.price || 0).replace(/€/g, '').replace(/,/g, '')) || 95,
          quantity: product.value.quantity || 1,
          available_quantity: product.value.quantity || 1, // Quantità disponibile totale del venditore
          condition: product.value.condition || 'LIGHT PLAYED',
          description: product.value.description || 'Carta in ottime condizioni',
          images: product.value.images || (product.value.image_url ? [product.value.image_url] : []),
          available: true,
          seller: response.data.seller,
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
      } else {
        // Fallback a mock data se non ci sono dati reali
        listing.value = {
          id: `listing_${product.value.id}`,
          card_model_id: product.value.id,
          seller_id: 1,
          price: parseFloat(String(product.value.price || 0).replace(/€/g, '').replace(/,/g, '')) || 95,
          quantity: product.value.quantity || 1,
          available_quantity: product.value.quantity || 1, // Quantità disponibile totale del venditore
          condition: product.value.condition || 'LIGHT PLAYED',
          description: product.value.description || 'Carta in ottime condizioni',
          images: product.value.images || (product.value.image_url ? [product.value.image_url] : []),
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
      }
      
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
      id: product.value.listing_id || `listing_${product.value.id}`,
      card_model_id: product.value.id,
      seller_id: product.value.seller?.id || 1,
      price: parseFloat(product.value.price?.replace('€', '').replace(',', '') || '95'),
      quantity: product.value.quantity || 1,
      condition: product.value.condition || 'LIGHT PLAYED',
      description: product.value.description || 'Carta in ottime condizioni',
      images: product.value.images || (product.value.image_url ? [product.value.image_url] : []),
      available: true,
      seller: product.value.seller || {
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
    
    // Se siamo su una route listing, usa l'endpoint per listing correlate
    if (isListingRoute.value) {
      const listingId = route.params.listingId
      if (listingId) {
        console.log('Loading related listings for listing route:', listingId)
        response = await cardService.getRelatedListings(listingId, 8)
      } else {
        console.log('No listingId available for related listings')
        relatedProductsLoading.value = false
        return
      }
    } else if (isSlugRoute.value) {
      // Per route slug, usiamo categoria e slug
      const category = route.params.category
      const cardSlug = route.params.cardSlug
      console.log('Loading related products for slug route:', category, cardSlug)
      response = await cardService.getRelatedProductsBySlug(category, cardSlug, 8)
    } else {
      // Per route ID tradizionale
      if (!cardId || cardId === null || cardId === 'temp') {
        console.log('Skipping related products load - invalid cardId:', cardId)
        relatedProductsLoading.value = false
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

const handleImageError = (event) => {
  // Se l'immagine non viene caricata, mostra il placeholder
  const placeholder = event.target.parentElement.querySelector('.text-center')
  if (placeholder) {
    placeholder.style.display = 'block'
    event.target.style.display = 'none'
  }
}

const formatPrice = (price) => {
  return formatPriceItaliana(price, true) // Include il simbolo €
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

const getProductDisplayName = () => {
  // Costruisci un nome descrittivo per il prodotto
  const parts = []
  
  if (product.value.name && product.value.name !== 'Player') {
    parts.push(product.value.name)
  }
  
  if (product.value.team && product.value.team !== 'Team Name' && product.value.team !== 'Unknown Team') {
    parts.push(product.value.team)
  }
  
  if (product.value.set_name && product.value.set_name !== 'Set Name') {
    parts.push(product.value.set_name)
  }
  
  if (product.value.year) {
    parts.push(product.value.year)
  }
  
  // Se abbiamo almeno un nome, usalo, altrimenti usa un fallback
  if (parts.length > 0) {
    return parts.join(' - ')
  }
  
  // Fallback: prova a costruire da listing se disponibile
  if (listing.value?.card_model?.player?.name) {
    return listing.value.card_model.player.name
  }
  
  return product.value.name || 'Prodotto'
}

onMounted(async () => {
  // Inizializza il wishlist store
  await wishlistStore.initialize()
  loadProductDetails()
  
  // Aggiorna i pulsanti di scroll delle thumbnail dopo il mount
  nextTick(() => {
    updateThumbnailsScrollButtons()
  })
})

// Watch per ricaricare i dati quando cambia la route (navigazione tra carte diverse)
watch(() => route.params, (newParams, oldParams) => {
  // Ricarica solo se cambia l'ID, lo slug della carta o il listingId
  if (newParams.id !== oldParams?.id || 
      newParams.cardSlug !== oldParams?.cardSlug || 
      newParams.listingId !== oldParams?.listingId) {
    console.log('Route params changed, reloading product details...')
    loadProductDetails()
  }
}, { deep: true })

// Watch per cambiamenti nella route stessa (nome della route)
watch(() => route.name, (newName, oldName) => {
  if (newName !== oldName && (newName === 'listing.detail' || newName === 'card.detail' || newName === 'product.detail')) {
    console.log('Route name changed, reloading product details...')
    loadProductDetails()
  }
})

// Watch per aggiornare i pulsanti di scroll quando cambiano le immagini
watch(() => product.value?.images, () => {
  nextTick(() => {
    updateThumbnailsScrollButtons()
  })
}, { deep: true })
</script>

<style scoped>
.scrollbar-hide {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
  display: none;
}
</style>
