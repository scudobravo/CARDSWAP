<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Breadcrumbs -->
      <nav class="mb-6">
        <ol class="flex items-center space-x-2 text-sm font-gill-sans">
          <li><a href="/categories" class="text-primary hover:text-secondary">Categories</a></li>
          <li class="text-gray-500">></li>
          <li class="text-gray-500">Basketball</li>
        </ol>
      </nav>

      <!-- Category Banner -->
      <div class="p-8 mb-8">
        <div class="flex items-center justify-center space-x-2">
          <!-- Basketball Icon -->
          <div class="flex-shrink-0">
            <img src="/images/icons/Categorie/Basketball.png" alt="Basketball" class="w-24 md:w-48 h-auto" />
          </div>
          
          <!-- Category Info -->
          <div>
            <h1 class="text-4xl font-futura-bold text-primary">Basketball</h1>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
        <div 
          v-for="productType in productTypes" 
          :key="productType.id"
          class="bg-white rounded-lg shadow-lg p-8 text-center hover:shadow-xl transition-shadow duration-300 cursor-pointer group"
          @click="goToProductType(productType)"
        >
          <!-- Product Type Icon -->
          <div class="mb-6 h-24 flex items-center justify-center">
            <img 
              :src="productType.icon" 
              :alt="productType.name"
              class="w-full h-full object-contain group-hover:scale-110 transition-transform duration-300"
            />
          </div>
          
          <!-- Product Type Name -->
          <h3 class="text-xl font-futura-bold text-primary group-hover:text-secondary transition-colors duration-300">
            {{ productType.name }}
          </h3>
        </div>
      </div>
    </div>

    <!-- Top Player Section with fullwidth border -->
    <div class="border-b border-gray-300 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="Top Player" 
          :products="[]" 
          category="basketball"
          section="top_players"
          :use-dynamic-data="true"
          :limit="20"
        />
      </div>
    </div>

    <!-- Top Trend Section with fullwidth border -->
    <div class="border-b border-gray-300 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="Top Trend" 
          :products="[]" 
          category="basketball"
          section="top_trend"
          :use-dynamic-data="true"
          :limit="20"
        />
      </div>
    </div>

    <!-- New Section with fullwidth border -->
    <div class="border-b border-gray-300 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="New" 
          :products="[]" 
          category="basketball"
          section="new"
          :use-dynamic-data="true"
          :limit="20"
        />
      </div>
    </div>

    <!-- Most Expensive Section (no border) -->
    <div class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="Most Expensive" 
          :products="[]" 
          category="basketball"
          section="most_expensive"
          :use-dynamic-data="true"
          :limit="20"
        />
      </div>
    </div>
    
    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'
import ProductCarousel from '../components/ProductCarousel.vue'

const router = useRouter()

// Product types data (slug per rotta /categories/basketball/:subcategory)
const productTypes = ref([
  { id: 1, name: 'SINGLES', slug: 'singles', icon: '/images/icons/Sottocategorie/card.png' },
  { id: 2, name: 'SEALED PACKS', slug: 'sealed-packs', icon: '/images/icons/Sottocategorie/Pack.png' },
  { id: 3, name: 'SEALED BOXES', slug: 'sealed-boxes', icon: '/images/icons/Sottocategorie/Box.png' },
  { id: 4, name: 'LOT', slug: 'lot', icon: '/images/icons/Sottocategorie/Lot.png' }
])

const goToProductType = (productType) => {
  router.push(`/categories/basketball/${productType.slug}`)
}
</script>
