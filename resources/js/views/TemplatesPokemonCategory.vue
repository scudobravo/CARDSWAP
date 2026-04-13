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
          <li class="text-gray-500">Pokemon</li>
        </ol>
      </nav>

      <!-- Category Banner -->
      <div class="p-8 mb-8">
        <div class="flex items-center justify-center space-x-2">
          <!-- Icon -->
          <div class="flex-shrink-0">
            <img src="/images/icons/Categorie/TemplatesPokemon.png" alt="Pokemon" class="w-24 md:w-48 h-auto" />
          </div>
          
          <!-- Category Info -->
          <div>
            <h1 class="text-4xl font-futura-bold text-primary">Pokemon</h1>
          </div>
        </div>
      </div>

      <!-- Coming Soon Message -->
      <div class="bg-white rounded-lg shadow-lg p-12 text-center mb-12">
        <div class="max-w-2xl mx-auto">
          <h2 class="text-4xl md:text-5xl font-futura-bold text-primary mb-6">
            Coming Soon
          </h2>
          <p class="text-xl text-gray-600 font-gill-sans mb-8">
            Stiamo lavorando per portarti i migliori prodotti Pokemon. Torna presto per scoprire la nostra collezione!
          </p>
        </div>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
        <div 
          v-for="productType in productTypes" 
          :key="productType.id"
          class="bg-white rounded-lg shadow-lg p-8 text-center hover:shadow-xl transition-shadow duration-300 cursor-pointer group opacity-50"
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
    
    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'

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
