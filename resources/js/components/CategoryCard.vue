<template>
  <router-link 
    :to="getCategoryRoute(category.type)" 
    class="bg-white rounded-lg shadow-lg p-6 text-center hover:shadow-xl transition-shadow duration-300 cursor-pointer group block"
  >
    <!-- Icon -->
    <div class="mb-4">
      <img 
        :src="category.icon" 
        :alt="category.name"
        class="w-24 h-24 md:w-48 md:h-48 mx-auto object-contain"
      />
    </div>
    
    <!-- Category name -->
    <h3 class="text-2xl font-futura-bold text-gray-900 group-hover:text-primary transition-colors duration-300">
      {{ category.name }}
    </h3>
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
    case 'disney':
      return '/category/disney'
    case 'spongebob':
      return '/category/spongebob'
    case 'labubu':
      return '/category/labubu'
    default:
      return '/categories'
  }
}
</script>
