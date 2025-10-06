<template>
  <div 
    ref="container"
    class="lazy-image-container"
    :class="containerClass"
  >
    <!-- Placeholder durante il caricamento -->
    <div 
      v-if="!loaded && !error"
      class="lazy-image-placeholder"
      :class="placeholderClass"
    >
      <div class="animate-pulse bg-gray-200 rounded flex items-center justify-center">
        <svg 
          v-if="showIcon"
          class="w-8 h-8 text-gray-400" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
          />
        </svg>
      </div>
    </div>

    <!-- Immagine caricata -->
    <img
      v-if="loaded && !error"
      :src="src"
      :alt="alt"
      :class="imageClass"
      @load="onLoad"
      @error="onError"
      loading="lazy"
    />

    <!-- Errore di caricamento -->
    <div 
      v-if="error"
      class="lazy-image-error"
      :class="errorClass"
    >
      <div class="flex flex-col items-center justify-center text-gray-400">
        <svg 
          class="w-8 h-8 mb-2" 
          fill="none" 
          stroke="currentColor" 
          viewBox="0 0 24 24"
        >
          <path 
            stroke-linecap="round" 
            stroke-linejoin="round" 
            stroke-width="2" 
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"
          />
        </svg>
        <span class="text-sm">{{ errorText }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'

const props = defineProps({
  src: {
    type: String,
    required: true
  },
  alt: {
    type: String,
    default: ''
  },
  containerClass: {
    type: String,
    default: ''
  },
  imageClass: {
    type: String,
    default: ''
  },
  placeholderClass: {
    type: String,
    default: ''
  },
  errorClass: {
    type: String,
    default: ''
  },
  errorText: {
    type: String,
    default: 'Errore nel caricamento'
  },
  showIcon: {
    type: Boolean,
    default: true
  },
  threshold: {
    type: Number,
    default: 0.1
  },
  rootMargin: {
    type: String,
    default: '50px'
  }
})

const emit = defineEmits(['load', 'error'])

const container = ref(null)
const loaded = ref(false)
const error = ref(false)
const observer = ref(null)

onMounted(() => {
  if (container.value) {
    setupIntersectionObserver()
  }
})

onUnmounted(() => {
  if (observer.value) {
    observer.value.disconnect()
  }
})

const setupIntersectionObserver = () => {
  if (!window.IntersectionObserver) {
    // Fallback per browser che non supportano IntersectionObserver
    loadImage()
    return
  }

  observer.value = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          loadImage()
          observer.value.disconnect()
        }
      })
    },
    {
      threshold: props.threshold,
      rootMargin: props.rootMargin
    }
  )

  observer.value.observe(container.value)
}

const loadImage = () => {
  const img = new Image()
  
  img.onload = () => {
    loaded.value = true
    emit('load')
  }
  
  img.onerror = () => {
    error.value = true
    emit('error')
  }
  
  img.src = props.src
}

const onLoad = () => {
  loaded.value = true
  emit('load')
}

const onError = () => {
  error.value = true
  emit('error')
}
</script>

<style scoped>
.lazy-image-container {
  position: relative;
  overflow: hidden;
}

.lazy-image-placeholder {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.lazy-image-error {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* Animazione di fade-in per l'immagine */
.lazy-image-container img {
  transition: opacity 0.3s ease-in-out;
}

.lazy-image-container img[src] {
  opacity: 1;
}
</style>
