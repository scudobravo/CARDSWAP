import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useWishlistStore = defineStore('wishlist', () => {
  // Stato della wishlist
  const wishlistItems = ref([])

  // Computed per il numero totale di articoli
  const totalItems = computed(() => wishlistItems.value.length)

  // Computed per verificare se la wishlist è vuota
  const isEmpty = computed(() => wishlistItems.value.length === 0)

  // Carica la wishlist dal backend
  const loadWishlist = async () => {
    // Verifica se c'è un token prima di caricare
    const token = localStorage.getItem('token')
    if (!token) {
      wishlistItems.value = []
      return
    }
    
    try {
      const response = await axios.get('/api/wishlist')
      if (response.data.success) {
        wishlistItems.value = response.data.data || []
      }
    } catch (error) {
      // Ignora gli errori 401 (non autenticato)
      if (error.response?.status === 401) {
        wishlistItems.value = []
      } else {
        console.error('Errore nel caricamento wishlist:', error)
        wishlistItems.value = []
      }
    }
  }

  // Aggiunge un articolo alla wishlist
  const addToWishlist = async (cardModelId) => {
    try {
      const response = await axios.post('/api/wishlist', {
        card_model_id: cardModelId
      })
      
      if (response.data.success) {
        // Ricarica la wishlist per avere i dati aggiornati
        await loadWishlist()
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Errore nell\'aggiunta alla wishlist:', error)
      return { success: false, message: 'Errore nell\'aggiunta alla wishlist' }
    }
  }

  // Rimuove un articolo dalla wishlist
  const removeFromWishlist = async (cardModelId) => {
    try {
      const response = await axios.delete(`/api/wishlist/card/${cardModelId}`)
      
      if (response.data.success) {
        // Rimuovi l'articolo dalla lista locale
        wishlistItems.value = wishlistItems.value.filter(item => item.card_model_id !== cardModelId)
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Errore nella rimozione dalla wishlist:', error)
      return { success: false, message: 'Errore nella rimozione dalla wishlist' }
    }
  }

  // Verifica se un articolo è nella wishlist
  const isInWishlist = (cardModelId) => {
    return wishlistItems.value.some(item => item.card_model_id === cardModelId)
  }

  // Svuota la wishlist
  const clearWishlist = async () => {
    try {
      const response = await axios.delete('/api/wishlist/clear')
      
      if (response.data.success) {
        wishlistItems.value = []
        return { success: true }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Errore nello svuotamento wishlist:', error)
      return { success: false, message: 'Errore nello svuotamento wishlist' }
    }
  }

  // Inizializza la wishlist
  const initialize = async () => {
    await loadWishlist()
  }

  return {
    // Stato
    wishlistItems,
    
    // Computed
    totalItems,
    isEmpty,
    
    // Azioni
    loadWishlist,
    addToWishlist,
    removeFromWishlist,
    isInWishlist,
    clearWishlist,
    initialize
  }
})
