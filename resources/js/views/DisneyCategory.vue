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
          <li class="text-gray-500">Disney</li>
        </ol>
      </nav>

      <!-- Category Banner -->
      <div class="p-8 mb-8">
        <div class="flex items-center justify-center space-x-2">
          <!-- Disney Icon -->
          <div class="flex-shrink-0">
            <img src="/images/icons/Disney.svg" alt="Disney" class="w-24 md:w-48 h-auto" />
          </div>
          
          <!-- Category Info -->
          <div>
            <h1 class="text-4xl font-futura-bold text-primary">Disney</h1>
          </div>
        </div>
      </div>

      <!-- Product Type Sections -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 mb-12">
        <div 
          v-for="productType in productTypes" 
          :key="productType.id"
          class="bg-white rounded-lg shadow-lg p-6 text-center hover:shadow-xl transition-shadow duration-300 cursor-pointer group"
          @click="goToProductType(productType)"
        >
          <!-- Product Type Icon -->
          <div class="mb-4">
            <img 
              :src="productType.icon" 
              :alt="productType.name"
              class="w-16 h-16 mx-auto group-hover:scale-110 transition-transform duration-300"
            />
          </div>
          
          <!-- Product Type Name -->
          <h3 class="text-lg font-futura-bold text-primary group-hover:text-secondary transition-colors duration-300">
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
import { useRouter } from 'vue-router'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'

const router = useRouter()

// Product types data
const productTypes = ref([
  {
    id: 1,
    name: "SINGLES",
    slug: "singles",
    icon: "/images/icons/sottocategoria singles.png"
  },
  {
    id: 2,
    name: "SEALED PACKS",
    slug: "sealed-packs",
    icon: "/images/icons/sottocategoria sealed packs.png"
  },
  {
    id: 3,
    name: "SEALED BOXES",
    slug: "sealed-boxes",
    icon: "/images/icons/sottocategoria sealed boxes.png"
  },
  {
    id: 4,
    name: "LOT",
    slug: "lot",
    icon: "/images/icons/sottocategoria lot.png"
  }
])

const goToProductType = (productType) => {
  router.push(`/categories/disney/${productType.slug}`)
}
</script>

