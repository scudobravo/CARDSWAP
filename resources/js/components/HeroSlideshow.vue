<template>
  <section class="relative bg-white py-20 md:py-32">
    <!-- Slides container -->
    <div 
      ref="slidesContainer"
      class="flex transition-transform duration-700 ease-in-out"
      :style="{ transform: `translateX(-${currentSlide * 100}%)` }"
    >
      <div 
        v-for="(slide, index) in slides" 
        :key="index"
        class="relative w-full flex-shrink-0"
      >
        <!-- Content -->
        <div class="text-center max-w-5xl mx-auto px-6">
          <!-- Logo CARDSWAP grande come nello screenshot -->
          <div class="mb-12">
            <img src="/images/logos/logo-colorato.svg" alt="CARDSWAP TCG" class="h-32 md:h-48 w-auto mx-auto" />
          </div>
          
          <!-- Tagline -->
          <h1 class="text-5xl md:text-7xl font-futura-bold text-primary mb-8 leading-tight">
            {{ slide.title }}
          </h1>
          
          <!-- Description -->
          <p class="text-xl md:text-2xl mb-16 font-futura-bold leading-relaxed max-w-4xl mx-auto font-gill-sans text-gray-500">
            {{ slide.description }}
          </p>
          
          <!-- CTA Button -->
          <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
            <button class="bg-primary text-white text-xl px-12 py-4 rounded-lg font-futura-bold hover:bg-opacity-90 transition-colors">
              {{ slide.primaryButton.text }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Dots navigation -->
    <div class="absolute bottom-12 left-1/2 -translate-x-1/2 z-20 flex space-x-3">
      <button 
        v-for="(slide, index) in slides" 
        :key="index"
        @click="goToSlide(index)"
        class="w-3 h-3 rounded-full transition-all duration-200"
        :class="index === currentSlide ? 'bg-primary scale-125' : 'bg-gray-300 hover:bg-gray-400'"
      >
        <span class="sr-only">Go to slide {{ index + 1 }}</span>
      </button>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

// Props
const props = defineProps({
  slides: {
    type: Array,
    required: true
  }
})

// Refs
const currentSlide = ref(0)
const slidesContainer = ref(null)
const autoPlayInterval = ref(null)

// Methods
const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % props.slides.length
}

const previousSlide = () => {
  currentSlide.value = currentSlide.value === 0 
    ? props.slides.length - 1 
    : currentSlide.value - 1
}

const goToSlide = (index) => {
  currentSlide.value = index
}

// Auto-play functionality
const startAutoPlay = () => {
  autoPlayInterval.value = setInterval(() => {
    nextSlide()
  }, 5000) // Cambia slide ogni 5 secondi
}

const stopAutoPlay = () => {
  if (autoPlayInterval.value) {
    clearInterval(autoPlayInterval.value)
    autoPlayInterval.value = null
  }
}

// Keyboard navigation
const handleKeydown = (event) => {
  if (event.key === 'ArrowLeft') {
    previousSlide()
  } else if (event.key === 'ArrowRight') {
    nextSlide()
  }
}

// Lifecycle
onMounted(() => {
  startAutoPlay()
  document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
  stopAutoPlay()
  document.removeEventListener('keydown', handleKeydown)
})
</script>
