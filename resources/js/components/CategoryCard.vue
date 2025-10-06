<template>
  <router-link 
    :to="getCategoryRoute(category.type)" 
    class="group relative overflow-hidden rounded-lg bg-primary border-2 border-secondary text-white hover:shadow-2xl hover:border-secondary/80 transition-all duration-300 transform hover:-translate-y-2 block"
  >
    <!-- Content -->
    <div class="relative z-10 p-8 h-full flex flex-col justify-center items-center text-center">
      <!-- Icon -->
      <div class="mb-6">
        <div class="w-30 h-30 bg-secondary/20 rounded-full flex items-center justify-center group-hover:bg-secondary/30 transition-colors duration-300">
          <img 
            :src="category.icon" 
            :alt="category.name"
            class="w-30 h-auto object-contain"
          />
        </div>
      </div>
      
      <!-- Category name -->
      <h3 class="text-5xl font-futura-bold mb-3 text-white group-hover:text-secondary transition-colors duration-300">
        {{ category.name }}
      </h3>
    </div>
    
    <!-- Hover overlay -->
    <div class="absolute inset-0 bg-secondary/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    
    <!-- Decorative elements -->
    <!-- <div class="absolute top-4 right-4 opacity-20 group-hover:opacity-40 transition-opacity duration-300">
      <div class="w-8 h-8 bg-secondary rounded-full flex items-center justify-center">
        <span class="text-xs font-futura-bold text-primary">100%</span>
      </div>
    </div> -->
  </router-link>
</template>

<script setup>
// Props
const props = defineProps({
  category: {
    type: Object,
    required: true
  }
})

// Methods
const getCategoryRoute = (type) => {
  switch (type) {
    case 'football':
      return '/category/football'
    case 'pokemon':
      return '/category/pokemon'
    case 'basketball':
      return '/category/basketball'
    default:
      return '/categories'
  }
}
</script>
