<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-futura-bold text-primary mb-4">Categorie</h1>
        <p class="text-lg text-gray-600 font-gill-sans">Scegli la tua categoria preferita e esplora i prodotti</p>
      </div>

      <!-- Lista categorie principali -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
        <CategoryCard 
          v-for="category in categories" 
          :key="category.id" 
          :category="category" 
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
import CategoryCard from '../components/CategoryCard.vue'

// Categories data
const categories = ref([
  {
    id: 1,
    name: "Calcio",
    description: "Carte da calcio di tutte le leghe",
    type: "football",
    icon: "/images/icons/categoria football.png"
  },
  {
    id: 2,
    name: "Pokemon",
    description: "Colleziona le tue carte Pokemon preferite",
    type: "pokemon",
    icon: "/images/icons/categoria pokemon.png"
  },
  {
    id: 3,
    name: "Basket",
    description: "Carte da basket NBA e internazionali",
    type: "basketball",
    icon: "/images/icons/categoria basketball.png"
  }
])

</script>
