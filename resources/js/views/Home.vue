<template>
  <div class="bg-gray-light">
    <!-- Header -->
    <Header />
    
    <!-- Hero Section -->
    <HomeHero />
    
    <!-- Linea di separazione -->
    <div class="border-t-2 border-gray-300 mx-8"></div>
    
    <!-- Category Section -->
    <section class="py-16 bg-gray-light">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
          <h2 class="text-3xl font-futura-bold text-primary mb-4">Categorie</h2>
          <p class="text-lg text-gray-600 font-gill-sans">Scegli la tua categoria preferita</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-8">
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
        <!-- Football Top Players -->
        <div class="mb-12">
          <ProductCarousel 
            title="Top Player - Calcio" 
            :products="topPlayers"
            category="football"
            section="top_players"
            :use-dynamic-data="true"
            see-all-url="/category/football"
            :limit="20"
          />
        </div>

        <!-- Basketball Top Players -->
        <div class="mb-12">
          <ProductCarousel 
            title="Top Player - Basket" 
            :products="[]"
            category="basketball"
            section="top_players"
            :use-dynamic-data="true"
            see-all-url="/category/basketball"
            :limit="20"
          />
        </div>

        <!-- Disney Top Characters -->
        <div>
          <ProductCarousel 
            title="Top Character - Disney" 
            :products="[]"
            category="disney"
            section="top_players"
            :use-dynamic-data="true"
            see-all-url="/category/disney"
            :limit="20"
          />
        </div>
      </div>
    </section>
    
    <!-- Pokemon Section - Temporaneamente nascosta -->
    <!-- <section class="py-16 bg-gray-light">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="Top Pokemon" 
          :products="topPokemon"
          category="pokemon"
          see-all-url="/category/pokemon"
        />
      </div>
    </section> -->
    
    <!-- Come funziona -->
    <ComeFunziona />

    <!-- Latest Releases Section -->
    <section class="py-16 bg-white">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ProductCarousel 
          title="Nuove Inserzioni" 
          :products="latestReleases"
          category="sports"
          section="new"
          :use-dynamic-data="true"
          :limit="10"
          see-all-url="/categories"
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
import HomeHero from '../components/HomeHero.vue'
import CategoryCard from '../components/CategoryCard.vue'
import SubcategoryCard from '../components/SubcategoryCard.vue'
import ProductCarousel from '../components/ProductCarousel.vue'
import ComeFunziona from '../components/ComeFunziona.vue'
import Footer from '../components/Footer.vue'

// Categories data
const categories = ref([
  {
    id: 1,
    name: "Calcio",
    description: "Carte da calcio di tutte le leghe",
    type: "football",
    icon: "/images/icons/Categorie/Football.png"
  },
  {
    id: 2,
    name: "Basket",
    description: "Carte da basket NBA e internazionali",
    type: "basketball",
    icon: "/images/icons/Categorie/Basketball.png"
  },
  {
    id: 3,
    name: "Disney",
    description: "Colleziona le tue carte Disney preferite",
    type: "disney",
    icon: "/images/icons/Categorie/Disney.png"
  },
  {
    id: 4,
    name: "SpongeBob",
    description: "Colleziona le tue carte SpongeBob preferite",
    type: "spongebob",
    icon: "/images/icons/Categorie/Spongebob.png"
  },
  {
    id: 5,
    name: "Labubu",
    description: "Colleziona le tue carte Labubu preferite",
    type: "labubu",
    icon: "/images/icons/Categorie/Labubu.png"
  },
  {
    id: 6,
    name: "Templates pokemon",
    description: "Template e collezioni Pokémon in arrivo",
    type: "templates-pokemon",
    icon: "/images/icons/Categorie/TemplatesPokemon.png"
  }
])

// Subcategories data
const subcategories = ref([
  {
    id: 1,
    name: "Singles",
    description: "Carte singole",
    icon: "/images/icons/Sottocategorie/card.png"
  },
  {
    id: 2,
    name: "Sealed Packs",
    description: "Buste sigillate",
    icon: "/images/icons/Sottocategorie/Pack.png"
  },
  {
    id: 3,
    name: "Sealed Boxes",
    description: "Scatole sigillate",
    icon: "/images/icons/Sottocategorie/Box.png"
  },
  {
    id: 4,
    name: "Lot",
    description: "Lotti di carte",
    icon: "/images/icons/Sottocategorie/Lot.png"
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

const latestReleases = ref([])
</script>
