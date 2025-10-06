<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Breadcrumbs -->
      <nav class="mb-6">
        <ol class="flex items-center space-x-2 text-sm font-gill-sans">
          <li><a href="/" class="text-primary hover:text-secondary">All Products</a></li>
          <li class="text-gray-500">></li>
          <li class="text-gray-500">Category</li>
        </ol>
      </nav>

      <!-- Category Banner -->
      <div class="p-8 mb-8">
        <div class="flex items-center justify-center space-x-2">
          <!-- Football Icon -->
          <div class="flex-shrink-0">
            <img src="/images/icons/calcio.png" alt="Football" class="w-48 h-auto" />
          </div>
          
          <!-- Category Info -->
          <div>
            <h1 class="text-4xl font-futura-bold text-primary">Football</h1>
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
          :products="topPlayers" 
          category="football"
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
          category="football"
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
          :products="newPlayers" 
          category="football"
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
          category="football"
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

// Top players data
const topPlayers = ref([
  {
    id: 1,
    name: "Lionel Messi",
    team: "Inter Miami",
    type: "Calcio",
    description: "Carta ufficiale Panini del campione argentino",
    price: "€45.00",
    rating: "4.9",
    image_url: null
  },
  {
    id: 2,
    name: "Cristiano Ronaldo",
    team: "Al Nassr",
    type: "Calcio",
    description: "Carta Topps Chrome del fenomeno portoghese",
    price: "€38.50",
    rating: "4.8",
    image_url: null
  },
  {
    id: 3,
    name: "Kylian Mbappé",
    team: "Real Madrid",
    type: "Calcio",
    description: "Carta Panini del giovane talento francese",
    price: "€32.00",
    rating: "4.7",
    image_url: null
  },
  {
    id: 4,
    name: "Erling Haaland",
    team: "Manchester City",
    type: "Calcio",
    description: "Carta Topps del bomber norvegese",
    price: "€28.50",
    rating: "4.6",
    image_url: null
  }
])

// Top trend data
const topTrend = ref([
  {
    id: 1,
    name: "Jude Bellingham",
    team: "Real Madrid",
    type: "Calcio",
    description: "Carta Panini del giovane talento inglese",
    price: "€35.00",
    rating: "4.8",
    image_url: null
  },
  {
    id: 2,
    name: "Vinicius Jr",
    team: "Real Madrid",
    type: "Calcio",
    description: "Carta Topps dell'ala brasiliana",
    price: "€30.00",
    rating: "4.7",
    image_url: null
  },
  {
    id: 3,
    name: "Phil Foden",
    team: "Manchester City",
    type: "Calcio",
    description: "Carta Panini del centrocampista inglese",
    price: "€28.00",
    rating: "4.6",
    image_url: null
  },
  {
    id: 4,
    name: "Pedri",
    team: "Barcelona",
    type: "Calcio",
    description: "Carta Topps del talento spagnolo",
    price: "€25.00",
    rating: "4.5",
    image_url: null
  }
])

// New players data
const newPlayers = ref([
  {
    id: 1,
    name: "Endrick",
    team: "Real Madrid",
    type: "Calcio",
    description: "Carta Panini del giovane promessa brasiliana",
    price: "€40.00",
    rating: "4.9",
    image_url: null
  },
  {
    id: 2,
    name: "Lamine Yamal",
    team: "Barcelona",
    type: "Calcio",
    description: "Carta Topps del talento spagnolo",
    price: "€35.00",
    rating: "4.8",
    image_url: null
  },
  {
    id: 3,
    name: "Warren Zaire-Emery",
    team: "PSG",
    type: "Calcio",
    description: "Carta Panini del centrocampista francese",
    price: "€30.00",
    rating: "4.7",
    image_url: null
  },
  {
    id: 4,
    name: "Alejandro Garnacho",
    team: "Manchester United",
    type: "Calcio",
    description: "Carta Topps dell'ala argentina",
    price: "€28.00",
    rating: "4.6",
    image_url: null
  }
])

// Most expensive data
const mostExpensive = ref([
  {
    id: 1,
    name: "Lionel Messi Rookie",
    team: "Barcelona",
    type: "Calcio",
    description: "Carta rookie Panini del 2004",
    price: "€2,500.00",
    rating: "5.0",
    image_url: null
  },
  {
    id: 2,
    name: "Cristiano Ronaldo Rookie",
    team: "Sporting CP",
    type: "Calcio",
    description: "Carta rookie del 2002",
    price: "€1,800.00",
    rating: "5.0",
    image_url: null
  },
  {
    id: 3,
    name: "Pelé 1970",
    team: "Brazil",
    type: "Calcio",
    description: "Carta storica del re del calcio",
    price: "€1,200.00",
    rating: "4.9",
    image_url: null
  },
  {
    id: 4,
    name: "Maradona 1986",
    team: "Argentina",
    type: "Calcio",
    description: "Carta del campione del mondo",
    price: "€950.00",
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
  window.location.href = `/categories/football/${subcategorySlug}`
}
</script>
