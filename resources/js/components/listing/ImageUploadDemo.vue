<template>
  <div class="max-w-2xl mx-auto p-6">
    <h2 class="text-2xl font-bold text-gray-900 mb-6">Demo Drag & Drop Upload</h2>
    
    <!-- Upload Zone -->
    <div 
      ref="dropZone"
      class="border-2 border-dashed rounded-lg p-8 text-center transition-all duration-200"
      :class="{
        'border-primary bg-primary/5 scale-105': isDragOver,
        'border-gray-300 hover:border-gray-400': !isDragOver
      }"
      @drop="handleDrop"
      @dragover.prevent="handleDragOver"
      @dragenter.prevent="handleDragEnter"
      @dragleave="handleDragLeave"
    >
      <input 
        ref="fileInput"
        type="file"
        multiple
        accept="image/*"
        @change="handleFileSelect"
        class="hidden"
      />
      
      <!-- Icona animata -->
      <div class="space-y-4">
        <div class="relative">
          <svg 
            class="mx-auto h-16 w-16 text-gray-400 transition-colors duration-200" 
            :class="{ 'text-primary scale-110': isDragOver }"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
          >
            <path 
              stroke-linecap="round" 
              stroke-linejoin="round" 
              stroke-width="1.5" 
              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" 
            />
          </svg>
          
          <!-- Effetto hover -->
          <div 
            v-if="isDragOver"
            class="absolute inset-0 rounded-full bg-primary/20 animate-pulse"
          ></div>
        </div>
        
        <!-- Testo dinamico -->
        <div class="space-y-2">
          <h3 
            class="text-lg font-medium transition-colors duration-200"
            :class="{ 'text-primary': isDragOver, 'text-gray-900': !isDragOver }"
          >
            {{ isDragOver ? 'Rilascia le immagini qui!' : 'Trascina le immagini qui' }}
          </h3>
          
          <div class="text-sm text-gray-600">
            <button 
              @click="$refs.fileInput.click()"
              class="text-primary hover:text-primary-dark font-medium underline"
            >
              o clicca per selezionare
            </button>
          </div>
          
          <p class="text-xs text-gray-500">
            PNG, JPG, JPEG fino a 1MB ciascuna (max 4 immagini)
          </p>
        </div>
      </div>
    </div>
    
    <!-- Anteprima Immagini -->
    <div v-if="images.length > 0" class="mt-8">
      <h3 class="text-lg font-medium text-gray-900 mb-4">
        Immagini Caricate ({{ images.length }}/4)
      </h3>
      
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div 
          v-for="(image, index) in images" 
          :key="index"
          class="relative group bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
        >
          <img 
            :src="image.preview" 
            :alt="`Immagine ${index + 1}`"
            class="w-full h-32 object-cover"
          />
          
          <!-- Overlay con info -->
          <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all duration-200 flex items-center justify-center">
            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 text-white text-center">
              <p class="text-xs font-medium">{{ image.file.name }}</p>
              <p class="text-xs">{{ formatFileSize(image.file.size) }}</p>
            </div>
          </div>
          
          <!-- Pulsante rimuovi -->
          <button 
            @click="removeImage(index)"
            class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200 hover:bg-red-600"
          >
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Statistiche -->
    <div v-if="images.length > 0" class="mt-6 bg-gray-50 rounded-lg p-4">
      <h4 class="text-sm font-medium text-gray-900 mb-2">Statistiche</h4>
      <div class="grid grid-cols-2 gap-4 text-sm text-gray-600">
        <div>
          <span class="font-medium">Totale file:</span> {{ images.length }}
        </div>
        <div>
          <span class="font-medium">Dimensione totale:</span> {{ formatFileSize(totalSize) }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// State
const isDragOver = ref(false)
const images = ref([])

// Computed
const totalSize = computed(() => {
  return images.value.reduce((total, image) => total + image.file.size, 0)
})

// Methods
const handleDragOver = (event) => {
  event.preventDefault()
  isDragOver.value = true
}

const handleDragEnter = (event) => {
  event.preventDefault()
  isDragOver.value = true
}

const handleDragLeave = (event) => {
  if (!event.currentTarget.contains(event.relatedTarget)) {
    isDragOver.value = false
  }
}

const handleDrop = (event) => {
  event.preventDefault()
  isDragOver.value = false
  
  const files = Array.from(event.dataTransfer.files)
  processFiles(files)
}

const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  processFiles(files)
}

const processFiles = (files) => {
  const maxFiles = 4
  const maxSize = 1 * 1024 * 1024 // 1MB
  
  // Controlla se superiamo il limite di file
  if (images.value.length + files.length > maxFiles) {
    alert(`Massimo ${maxFiles} immagini. Hai già ${images.value.length} immagini.`)
    return
  }
  
  files.forEach(file => {
    if (file.type.startsWith('image/')) {
      // Controllo dimensione
      if (file.size > maxSize) {
        alert(`L'immagine "${file.name}" è troppo grande. Dimensione massima: 1MB`)
        return
      }
      
      // Controllo se abbiamo già raggiunto il limite
      if (images.value.length >= maxFiles) {
        alert(`Massimo ${maxFiles} immagini`)
        return
      }
      
      const reader = new FileReader()
      reader.onload = (e) => {
        images.value.push({
          file: file,
          preview: e.target.result
        })
      }
      reader.readAsDataURL(file)
    } else {
      alert(`Il file "${file.name}" non è un'immagine valida`)
    }
  })
}

const removeImage = (index) => {
  images.value.splice(index, 1)
}

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}
</script>
