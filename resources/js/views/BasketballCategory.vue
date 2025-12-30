<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />

    <!-- Category Banner -->
    <div class="bg-primary text-white py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between">
        <div class="text-center md:text-left mb-8 md:mb-0">
          <h1 class="text-4xl md:text-6xl font-futura-bold mb-4">BASKETBALL</h1>
          <p class="text-xl font-gill-sans opacity-90 max-w-2xl">
            Scopri la nostra selezione esclusiva di carte da basket NBA e internazionali. 
            Dalle rookie card leggendarie alle ultime uscite Panini e Topps.
          </p>
        </div>
        <div class="relative w-48 h-48 flex items-center justify-center">
          <img src="/images/icons/Categorie/Basketball.png" alt="Basketball" class="w-24 md:w-48 h-auto" />
        </div>
      </div>
    </div>

    <!-- Product Types Grid -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-16">
        <div 
          v-for="productType in productTypes" 
          :key="productType.id"
          class="bg-white rounded-xl shadow-sm p-8 flex flex-col items-center justify-center text-center cursor-pointer hover:shadow-md transition-all duration-300 group border border-gray-100"
        >
          <div class="w-24 h-24 mb-6 flex items-center justify-center transition-all duration-300">
            <img :src="productType.icon" :alt="productType.name" class="w-full h-full object-contain" />
          </div>
          <h3 class="text-xl font-futura-bold text-primary group-hover:text-secondary transition-colors duration-300">
            {{ productType.name }}
          </h3>
        </div>
      </div>

      <!-- Top Player Section -->
      <div class="mb-8">
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
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'
import ProductCarousel from '../components/ProductCarousel.vue'

// Product types data
const productTypes = ref([
  {
    id: 1,
    name: "SINGLES",
    icon: "/images/icons/Sottocategorie/card.png"
  },
  {
    id: 2,
    name: "SEALED PACKS",
    icon: "/images/icons/Sottocategorie/Pack.png"
  },
  {
    id: 3,
    name: "SEALED BOXES",
    icon: "/images/icons/Sottocategorie/Box.png"
  },
  {
    id: 4,
    name: "LOT",
    icon: "/images/icons/Sottocategorie/Lot.png"
  }
])
</script>
