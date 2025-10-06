<template>
  <div class="group relative overflow-hidden rounded-lg bg-white border border-gray-200 text-gray-800 hover:shadow-lg hover:border-gray-300 transition-all duration-300 transform hover:-translate-y-1">
    <!-- Content -->
    <div class="relative z-10 p-4 h-full flex flex-col justify-center items-center text-center">
      <!-- Icon -->
      <div class="mb-3">
        <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
          <img 
            :src="subcategory.icon" 
            :alt="subcategory.name"
            class="w-8 h-8 object-contain"
          />
        </div>
      </div>
      
      <!-- Subcategory name -->
      <h4 class="text-sm font-futura-bold mb-1 text-primary group-hover:text-secondary transition-colors duration-300">
        {{ subcategory.name }}
      </h4>
      
      <!-- Description -->
      <p class="text-xs text-gray-600 group-hover:text-gray-800 transition-colors duration-300 font-gill-sans">
        {{ subcategory.description }}
      </p>
    </div>
    
    <!-- Hover overlay -->
    <div class="absolute inset-0 bg-secondary/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
  </div>
</template>

<script setup>
// Props
const props = defineProps({
  subcategory: {
    type: Object,
    required: true
  }
})
</script>
