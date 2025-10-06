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

      <!-- Sezione sottocategorie -->
      <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
          <h2 class="text-2xl font-futura-bold text-primary mb-4">Tipi di Prodotto</h2>
          <p class="text-gray-600 font-gill-sans">Scegli il tipo di prodotto che stai cercando</p>
        </div>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <SubcategoryCard 
            v-for="subcategory in subcategories" 
            :key="subcategory.id" 
            :subcategory="subcategory" 
          />
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
import CategoryCard from '../components/CategoryCard.vue'
import SubcategoryCard from '../components/SubcategoryCard.vue'

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

// Subcategories data
const subcategories = ref([
  {
    id: 1,
    name: "Singles",
    description: "Carte singole",
    icon: "/images/icons/sottocategoria singles.svg"
  },
  {
    id: 2,
    name: "Sealed Packs",
    description: "Buste sigillate",
    icon: "/images/icons/sottocategoria sealed packs.svg"
  },
  {
    id: 3,
    name: "Sealed Boxes",
    description: "Scatole sigillate",
    icon: "/images/icons/sottocategoria sealed boxes.svg"
  },
  {
    id: 4,
    name: "Lot",
    description: "Lotti di carte",
    icon: "/images/icons/sottocategoria lot.svg"
  }
])
</script>
