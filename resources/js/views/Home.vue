<template>
  <div class="bg-gray-light">
    <!-- Header -->
    <Header />
    
    <!-- Hero Section -->
    <HeroSlideshow :slides="heroSlides" />
    
    <!-- Linea di separazione -->
    <div class="border-t-2 border-gray-300 mx-8"></div>
    
    <!-- Category Section -->
    <section class="py-16 bg-gray-light">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-futura-bold text-primary mb-4">Categorie</h2>
          <p class="text-lg text-gray-600 font-gill-sans">Scegli la tua categoria preferita</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <CategoryCard 
            v-for="category in categories" 
            :key="category.id" 
            :category="category" 
          />
        </div>
      </div>
    </section>
    
    <!-- Featured Products Section -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="Top Player" 
          :products="topPlayers" 
        />
      </div>
    </section>
    
    <!-- Pokemon Section -->
    <section class="py-16 bg-gray-light">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="Top Pokemon" 
          :products="topPokemon" 
        />
      </div>
    </section>
    
    <!-- Referral Banner -->
    <ReferralBanner />

    <!-- Latest Releases Section -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="Ultime uscite" 
          :products="latestReleases" 
        />
      </div>
    </section>
    
    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Header from '../components/Header.vue'
import HeroSlideshow from '../components/HeroSlideshow.vue'
import CategoryCard from '../components/CategoryCard.vue'
import SubcategoryCard from '../components/SubcategoryCard.vue'
import ProductCarousel from '../components/ProductCarousel.vue'
import ReferralBanner from '../components/ReferralBanner.vue'
import Footer from '../components/Footer.vue'

// Hero slides data
const heroSlides = ref([
  {
    id: 1,
    title: "Buy. Collect. Swap.",
    description: "Il primo Ecommerce per comprare e vendere carte sportive",
    primaryButton: {
      text: "START NOW",
      link: "/register"
    }
  },
  {
    id: 2,
    title: "Scambia le tue carte da collezione",
    description: "Entra nella più grande community di collezionisti di carte. Trova, scambia e colleziona le tue carte preferite con altri appassionati.",
    primaryButton: {
      text: "Inizia ora",
      link: "/register"
    }
  },
  {
    id: 3,
    title: "Esplora categorie",
    description: "Scopri le nostre categorie di carte: calcio, basket, Pokemon e molto altro. Trova la tua passione.",
    primaryButton: {
      text: "Esplora",
      link: "/categories"
    }
  }
])

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

// Products data
const topPlayers = ref([
  {
    id: 1,
    name: "Lionel Messi",
    team: "Inter Miami",
    type: "Calcio",
    description: "Carta ufficiale Panini del campione argentino",
    price: "€45.00",
    rating: "4.9",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 2,
    name: "Cristiano Ronaldo",
    team: "Al Nassr",
    type: "Calcio",
    description: "Carta Topps Chrome del fenomeno portoghese",
    price: "€38.50",
    rating: "4.8",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 3,
    name: "Kylian Mbappé",
    team: "Real Madrid",
    type: "Calcio",
    description: "Carta Panini del giovane talento francese",
    price: "€32.00",
    rating: "4.7",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 4,
    name: "Erling Haaland",
    team: "Manchester City",
    type: "Calcio",
    description: "Carta Topps del bomber norvegese",
    price: "€28.50",
    rating: "4.6",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 5,
    name: "Kevin De Bruyne",
    team: "Manchester City",
    type: "Calcio",
    description: "Carta Panini del centrocampista belga",
    price: "€25.00",
    rating: "4.5",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 6,
    name: "Mohamed Salah",
    team: "Liverpool",
    type: "Calcio",
    description: "Carta Topps del faraone egiziano",
    price: "€30.00",
    rating: "4.4",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 7,
    name: "Virgil van Dijk",
    team: "Liverpool",
    type: "Calcio",
    description: "Carta Panini del difensore olandese",
    price: "€22.50",
    rating: "4.3",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 8,
    name: "Sadio Mané",
    team: "Al Nassr",
    type: "Calcio",
    description: "Carta Topps dell'ala senegalese",
    price: "€27.00",
    rating: "4.2",
    image_url: null // Campo per l'immagine dal database
  }
])

const topPokemon = ref([
  {
    id: 1,
    name: "Charizard",
    team: "Fire Type",
    type: "Pokemon",
    description: "Carta ultra rara di Charizard VMAX",
    price: "€299.99",
    rating: "5.0",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 2,
    name: "Pikachu",
    team: "Electric Type",
    type: "Pokemon",
    description: "Carta speciale di Pikachu V",
    price: "€89.50",
    rating: "4.9",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 3,
    name: "Blastoise",
    team: "Water Type",
    type: "Pokemon",
    description: "Carta GX di Blastoise",
    price: "€65.00",
    rating: "4.8",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 4,
    name: "Venusaur",
    team: "Grass Type",
    type: "Pokemon",
    description: "Carta V di Venusaur",
    price: "€45.00",
    rating: "4.7",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 5,
    name: "Mewtwo",
    team: "Psychic Type",
    type: "Pokemon",
    description: "Carta GX di Mewtwo",
    price: "€120.00",
    rating: "4.6",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 6,
    name: "Gyarados",
    team: "Water Type",
    type: "Pokemon",
    description: "Carta V di Gyarados",
    price: "€38.50",
    rating: "4.5",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 7,
    name: "Lucario",
    team: "Fighting Type",
    type: "Pokemon",
    description: "Carta V di Lucario",
    price: "€42.00",
    rating: "4.4",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 8,
    name: "Greninja",
    team: "Water Type",
    type: "Pokemon",
    description: "Carta GX di Greninja",
    price: "€85.00",
    rating: "4.3",
    image_url: null // Campo per l'immagine dal database
  }
])

const latestReleases = ref([
  {
    id: 1,
    name: "LeBron James",
    team: "Los Angeles Lakers",
    type: "Basketball",
    description: "Carta Panini del re del basket",
    price: "€55.00",
    rating: "4.9",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 2,
    name: "Stephen Curry",
    team: "Golden State Warriors",
    type: "Basketball",
    description: "Carta Topps del baby-faced assassin",
    price: "€48.50",
    rating: "4.8",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 3,
    name: "Kevin Durant",
    team: "Phoenix Suns",
    type: "Basketball",
    description: "Carta Panini del snake",
    price: "€52.00",
    rating: "4.7",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 4,
    name: "Giannis Antetokounmpo",
    team: "Milwaukee Bucks",
    type: "Basketball",
    description: "Carta Topps del Greek Freak",
    price: "€58.00",
    rating: "4.6",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 5,
    name: "Luka Dončić",
    team: "Dallas Mavericks",
    type: "Basketball",
    description: "Carta Panini del Wonder Boy",
    price: "€45.00",
    rating: "4.5",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 6,
    name: "Joel Embiid",
    team: "Philadelphia 76ers",
    type: "Basketball",
    description: "Carta Topps del Process",
    price: "€42.50",
    rating: "4.4",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 7,
    name: "Nikola Jokić",
    team: "Denver Nuggets",
    type: "Basketball",
    description: "Carta Panini del Joker",
    price: "€50.00",
    rating: "4.3",
    image_url: null // Campo per l'immagine dal database
  },
  {
    id: 8,
    name: "Ja Morant",
    team: "Memphis Grizzlies",
    type: "Basketball",
    description: "Carta Topps di 12",
    price: "€38.00",
    rating: "4.2",
    image_url: null // Campo per l'immagine dal database
  }
])
</script>
