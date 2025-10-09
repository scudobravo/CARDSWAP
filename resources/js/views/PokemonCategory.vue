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
          <!-- Pokemon Icon -->
          <div class="flex-shrink-0">
            <img src="/images/icons/pokemon.png" alt="Pokemon" class="w-48 h-auto" />
          </div>
          
          <!-- Category Info -->
          <div>
            <h1 class="text-4xl font-futura-bold text-primary">Pokemon</h1>
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

      <!-- Top Player Section -->
      <div class="mb-8">
        <ProductCarousel 
          title="Top Player" 
          :products="topPokemon" 
          category="pokemon"
          section="top_players"
          :use-dynamic-data="true"
        />
      </div>
    </div>

    <!-- Top Trend Section with fullwidth border -->
    <div class="border-b border-gray-300 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="Top Trend" 
          :products="topTrend" 
          category="pokemon"
          section="top_trend"
          :use-dynamic-data="true"
        />
      </div>
    </div>

    <!-- New Section with fullwidth border -->
    <div class="border-b border-gray-300 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="New" 
          :products="newPokemon" 
          category="pokemon"
          section="new"
          :use-dynamic-data="true"
        />
      </div>
    </div>

    <!-- Most Expensive Section (no border) -->
    <div class="py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="Most Expensive" 
          :products="mostExpensive" 
          category="pokemon"
          section="most_expensive"
          :use-dynamic-data="true"
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
    icon: "/images/icons/sottocategoria singles.png"
  },
  {
    id: 2,
    name: "SEALED PACKS",
    icon: "/images/icons/sottocategoria sealed packs.png"
  },
  {
    id: 3,
    name: "SEALED BOXES",
    icon: "/images/icons/sottocategoria sealed boxes.png"
  },
  {
    id: 4,
    name: "LOT",
    icon: "/images/icons/sottocategoria lot.png"
  }
])

// Top Pokemon data
const topPokemon = ref([
  {
    id: 1,
    name: "Charizard",
    team: "Fire Type",
    type: "Pokemon",
    description: "Carta ultra rara di Charizard VMAX",
    price: "€299.99",
    rating: "5.0",
    image_url: null
  },
  {
    id: 2,
    name: "Pikachu",
    team: "Electric Type",
    type: "Pokemon",
    description: "Carta iconica del Pokemon più famoso",
    price: "€89.99",
    rating: "4.9",
    image_url: null
  },
  {
    id: 3,
    name: "Mewtwo",
    team: "Psychic Type",
    type: "Pokemon",
    description: "Carta leggendaria di Mewtwo VMAX",
    price: "€199.99",
    rating: "4.8",
    image_url: null
  },
  {
    id: 4,
    name: "Lugia",
    team: "Psychic/Flying Type",
    type: "Pokemon",
    description: "Carta leggendaria di Lugia VMAX",
    price: "€179.99",
    rating: "4.7",
    image_url: null
  }
])

// Top trend data
const topTrend = ref([
  {
    id: 1,
    name: "Rayquaza VMAX",
    team: "Dragon/Flying Type",
    type: "Pokemon",
    description: "Carta ultra rara di Rayquaza VMAX",
    price: "€249.99",
    rating: "4.8",
    image_url: null
  },
  {
    id: 2,
    name: "Umbreon VMAX",
    team: "Dark Type",
    type: "Pokemon",
    description: "Carta popolare di Umbreon VMAX",
    price: "€159.99",
    rating: "4.7",
    image_url: null
  },
  {
    id: 3,
    name: "Sylveon VMAX",
    team: "Fairy Type",
    type: "Pokemon",
    description: "Carta elegante di Sylveon VMAX",
    price: "€139.99",
    rating: "4.6",
    image_url: null
  },
  {
    id: 4,
    name: "Gengar VMAX",
    team: "Ghost/Poison Type",
    type: "Pokemon",
    description: "Carta spettrale di Gengar VMAX",
    price: "€129.99",
    rating: "4.5",
    image_url: null
  }
])

// New Pokemon data
const newPokemon = ref([
  {
    id: 1,
    name: "Miraidon ex",
    team: "Electric Type",
    type: "Pokemon",
    description: "Carta esclusiva di Miraidon ex",
    price: "€89.99",
    rating: "4.9",
    image_url: null
  },
  {
    id: 2,
    name: "Koraidon ex",
    team: "Fighting Type",
    type: "Pokemon",
    description: "Carta esclusiva di Koraidon ex",
    price: "€89.99",
    rating: "4.8",
    image_url: null
  },
  {
    id: 3,
    name: "Iron Valiant ex",
    team: "Fairy/Fighting Type",
    type: "Pokemon",
    description: "Carta futura di Iron Valiant ex",
    price: "€79.99",
    rating: "4.7",
    image_url: null
  },
  {
    id: 4,
    name: "Roaring Moon ex",
    team: "Dark/Dragon Type",
    type: "Pokemon",
    description: "Carta antica di Roaring Moon ex",
    price: "€79.99",
    rating: "4.6",
    image_url: null
  }
])

// Most expensive data
const mostExpensive = ref([
  {
    id: 1,
    name: "Charizard Base Set",
    team: "Fire Type",
    type: "Pokemon",
    description: "Carta originale del 1999",
    price: "€15,000.00",
    rating: "5.0",
    image_url: null
  },
  {
    id: 2,
    name: "Pikachu Illustrator",
    team: "Electric Type",
    type: "Pokemon",
    description: "Carta più rara di Pikachu",
    price: "€12,000.00",
    rating: "5.0",
    image_url: null
  },
  {
    id: 3,
    name: "Blastoise Base Set",
    team: "Water Type",
    type: "Pokemon",
    description: "Carta originale del 1999",
    price: "€8,500.00",
    rating: "4.9",
    image_url: null
  },
  {
    id: 4,
    name: "Venusaur Base Set",
    team: "Grass Type",
    type: "Pokemon",
    description: "Carta originale del 1999",
    price: "€7,200.00",
    rating: "4.9",
    image_url: null
  }
])

// Methods
const goToProductType = (productType) => {
  console.log('Navigating to product type:', productType.name)
  // Converti il nome del product type in formato URL
  const subcategorySlug = productType.name.toLowerCase().replace(' ', '-')
  // Naviga alla pagina delle sotto categorie
  window.location.href = `/categories/pokemon/${subcategorySlug}`
}
</script>
