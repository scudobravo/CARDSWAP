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
        <div class="flex items-center justify-center space-x-6">
          <!-- Basketball Icon -->
          <div class="flex-shrink-0">
            <img src="/images/icons/basket.png" alt="Basketball" class="w-48 h-auto" />
          </div>
          
          <!-- Category Info -->
          <div>
            <h1 class="text-4xl font-futura-bold text-primary">Basketball</h1>
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
          category="basketball"
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
          category="basketball"
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
          category="basketball"
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
          category="basketball"
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
    name: "LeBron James",
    team: "Los Angeles Lakers",
    type: "Basketball",
    description: "Carta Panini del re del basket",
    price: "€55.00",
    rating: "4.9",
    image_url: null
  },
  {
    id: 2,
    name: "Stephen Curry",
    team: "Golden State Warriors",
    type: "Basketball",
    description: "Carta Topps del miglior tiratore da tre",
    price: "€48.00",
    rating: "4.8",
    image_url: null
  },
  {
    id: 3,
    name: "Kevin Durant",
    team: "Phoenix Suns",
    type: "Basketball",
    description: "Carta Panini del campione NBA",
    price: "€45.00",
    rating: "4.7",
    image_url: null
  },
  {
    id: 4,
    name: "Giannis Antetokounmpo",
    team: "Milwaukee Bucks",
    type: "Basketball",
    description: "Carta Topps del greco fenomeno",
    price: "€42.00",
    rating: "4.6",
    image_url: null
  }
])

// Top trend data
const topTrend = ref([
  {
    id: 1,
    name: "Luka Dončić",
    team: "Dallas Mavericks",
    type: "Basketball",
    description: "Carta Panini del giovane talento sloveno",
    price: "€50.00",
    rating: "4.8",
    image_url: null
  },
  {
    id: 2,
    name: "Jayson Tatum",
    team: "Boston Celtics",
    type: "Basketball",
    description: "Carta Topps dell'ala dei Celtics",
    price: "€38.00",
    rating: "4.7",
    image_url: null
  },
  {
    id: 3,
    name: "Joel Embiid",
    team: "Philadelphia 76ers",
    type: "Basketball",
    description: "Carta Panini del centro camerunense",
    price: "€40.00",
    rating: "4.6",
    image_url: null
  },
  {
    id: 4,
    name: "Nikola Jokić",
    team: "Denver Nuggets",
    type: "Basketball",
    description: "Carta Topps del centro serbo",
    price: "€35.00",
    rating: "4.5",
    image_url: null
  }
])

// New players data
const newPlayers = ref([
  {
    id: 1,
    name: "Victor Wembanyama",
    team: "San Antonio Spurs",
    type: "Basketball",
    description: "Carta Panini della promessa francese",
    price: "€60.00",
    rating: "4.9",
    image_url: null
  },
  {
    id: 2,
    name: "Paolo Banchero",
    team: "Orlando Magic",
    type: "Basketball",
    description: "Carta Topps del rookie dell'anno",
    price: "€45.00",
    rating: "4.8",
    image_url: null
  },
  {
    id: 3,
    name: "Chet Holmgren",
    team: "Oklahoma City Thunder",
    type: "Basketball",
    description: "Carta Panini del centro versatile",
    price: "€40.00",
    rating: "4.7",
    image_url: null
  },
  {
    id: 4,
    name: "Jabari Smith Jr.",
    team: "Houston Rockets",
    type: "Basketball",
    description: "Carta Topps dell'ala dei Rockets",
    price: "€35.00",
    rating: "4.6",
    image_url: null
  }
])

// Most expensive data
const mostExpensive = ref([
  {
    id: 1,
    name: "Michael Jordan Rookie",
    team: "Chicago Bulls",
    type: "Basketball",
    description: "Carta rookie del 1984",
    price: "€15,000.00",
    rating: "5.0",
    image_url: null
  },
  {
    id: 2,
    name: "LeBron James Rookie",
    team: "Cleveland Cavaliers",
    type: "Basketball",
    description: "Carta rookie del 2003",
    price: "€8,500.00",
    rating: "5.0",
    image_url: null
  },
  {
    id: 3,
    name: "Kobe Bryant Rookie",
    team: "Los Angeles Lakers",
    type: "Basketball",
    description: "Carta rookie del 1996",
    price: "€6,200.00",
    rating: "4.9",
    image_url: null
  },
  {
    id: 4,
    name: "Magic Johnson Rookie",
    team: "Los Angeles Lakers",
    type: "Basketball",
    description: "Carta rookie del 1979",
    price: "€4,800.00",
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
  window.location.href = `/categories/basketball/${subcategorySlug}`
}
</script>
