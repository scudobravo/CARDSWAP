<template>
  <div class="relative w-full">
    <!-- Campo di ricerca -->
    <div class="relative">
      <svg class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
      </svg>
      <input 
        ref="searchInput"
        v-model="searchQuery"
        type="text" 
        placeholder="Cerca su CardSwap" 
        class="w-full bg-gray-50 text-gray-900 placeholder-gray-400 rounded-full pl-12 pr-4 py-3 border border-gray-200 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent font-gill-sans text-sm"
        @input="handleSearch"
        @focus="handleFocus"
        @keydown="handleKeydown"
        @blur="handleBlur"
      />
      
      <!-- Icona di caricamento -->
      <div v-if="isLoading" class="absolute right-4 top-1/2 transform -translate-y-1/2">
        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </div>
    </div>

    <!-- Dropdown dei suggerimenti -->
    <div 
      v-if="showSuggestions" 
      class="absolute z-50 w-full mt-2 bg-white border border-gray-200 rounded-lg shadow-lg max-h-96 overflow-y-auto"
    >
      <!-- Messaggio se nessun risultato -->
      <div v-if="!isLoading && suggestions.length === 0 && searchQuery.length > 0" class="p-4 text-gray-500 text-center">
        Nessun risultato trovato per "{{ searchQuery }}"
      </div>
      
      <!-- Messaggio iniziale quando si fa focus -->
      <div v-else-if="!isLoading && suggestions.length === 0 && searchQuery.length === 0" class="p-4 text-gray-500 text-center">
        Inizia a digitare per cercare...
      </div>
      
      <!-- Lista suggerimenti -->
      <div v-else-if="suggestions.length > 0">
        <div 
          v-for="(suggestion, index) in suggestions" 
          :key="`${suggestion.type}-${suggestion.id}`"
          :class="[
            'px-4 py-3 cursor-pointer border-b border-gray-100 hover:bg-gray-50 transition-colors',
            selectedIndex === index ? 'bg-secondary/10' : ''
          ]"
          @click="selectSuggestion(suggestion)"
          @mouseenter="selectedIndex = index"
        >
          <div class="flex items-center space-x-3">
            <!-- Icona in base al tipo -->
            <div class="flex-shrink-0">
              <svg v-if="suggestion.type === 'card'" class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
              </svg>
              <svg v-else-if="suggestion.type === 'player'" class="w-5 h-5 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              <svg v-else-if="suggestion.type === 'team'" class="w-5 h-5 text-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
              </svg>
              <svg v-else class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            
            <!-- Contenuto del suggerimento -->
            <div class="flex-1 min-w-0">
              <p class="text-sm font-gill-sans-semibold text-gray-900 truncate">
                {{ suggestion.text }}
              </p>
              <p v-if="suggestion.subtext" class="text-xs text-gray-500 truncate">
                {{ suggestion.subtext }}
              </p>
            </div>
            
            <!-- Freccia -->
            <div class="flex-shrink-0">
              <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Refs
const searchInput = ref(null)
const searchQuery = ref('')
const suggestions = ref([])
const showSuggestions = ref(false)
const selectedIndex = ref(-1)
const isLoading = ref(false)

// Debounce timer
let searchTimeout = null

// Funzione per gestire il focus
const handleFocus = () => {
  showSuggestions.value = true
  // Carica suggerimenti iniziali quando si fa focus
  if (searchQuery.value.length === 0) {
    fetchSuggestions()
  }
}

// Funzione per gestire la ricerca
const handleSearch = () => {
  // Cancella il timeout precedente
  if (searchTimeout) {
    clearTimeout(searchTimeout)
  }
  
  // Reset dell'indice selezionato
  selectedIndex.value = -1
  
  // Imposta un timeout per evitare troppe richieste
  searchTimeout = setTimeout(() => {
    fetchSuggestions()
  }, 300)
}

// Funzione per recuperare i suggerimenti
const fetchSuggestions = async () => {
  isLoading.value = true
  
  try {
    // Passa anche query vuota per ottenere suggerimenti iniziali
    const response = await fetch(`/api/home/search-suggestions?q=${encodeURIComponent(searchQuery.value)}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      suggestions.value = data.data || []
    } else {
      suggestions.value = []
    }
  } catch (error) {
    console.error('Errore durante la ricerca:', error)
    suggestions.value = []
  } finally {
    isLoading.value = false
  }
}

// Funzione per gestire la navigazione con tastiera
const handleKeydown = (event) => {
  if (!showSuggestions.value || suggestions.value.length === 0) {
    if (event.key === 'Enter') {
      // Se non ci sono suggerimenti, fai una ricerca generale
      performGeneralSearch()
    }
    return
  }
  
  switch (event.key) {
    case 'ArrowDown':
      event.preventDefault()
      selectedIndex.value = Math.min(selectedIndex.value + 1, suggestions.value.length - 1)
      break
    case 'ArrowUp':
      event.preventDefault()
      selectedIndex.value = Math.max(selectedIndex.value - 1, -1)
      break
    case 'Enter':
      event.preventDefault()
      if (selectedIndex.value >= 0 && selectedIndex.value < suggestions.value.length) {
        selectSuggestion(suggestions.value[selectedIndex.value])
      } else {
        performGeneralSearch()
      }
      break
    case 'Escape':
      showSuggestions.value = false
      selectedIndex.value = -1
      break
  }
}

// Funzione per selezionare un suggerimento
const selectSuggestion = (suggestion) => {
  showSuggestions.value = false
  selectedIndex.value = -1
  
  // Naviga alla pagina appropriata
  if (suggestion.url && suggestion.type === 'card') {
    // Solo per le carte, naviga direttamente
    router.push(suggestion.url).catch(err => {
      console.error('Errore navigazione:', err)
      // Se la navigazione fallisce, fai una ricerca generale
      searchQuery.value = suggestion.text
      performGeneralSearch()
    })
  } else {
    // Per altri tipi o se manca l'URL, fai una ricerca generale
    searchQuery.value = suggestion.text
    performGeneralSearch()
  }
}

// Funzione per ricerca generale
const performGeneralSearch = () => {
  if (searchQuery.value.trim()) {
    showSuggestions.value = false
    // Naviga alla pagina di ricerca con la query
    router.push(`/search?q=${encodeURIComponent(searchQuery.value)}`)
  }
}

// Funzione per gestire il blur
const handleBlur = () => {
  // Ritarda la chiusura per permettere il click sui suggerimenti
  setTimeout(() => {
    showSuggestions.value = false
    selectedIndex.value = -1
  }, 200)
}

// Watch per resettare quando la query cambia
watch(searchQuery, (newQuery) => {
  // Non fare nulla, gestiamo tutto in handleSearch
})
</script>
