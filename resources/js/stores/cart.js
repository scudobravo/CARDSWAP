import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useCartStore = defineStore('cart', () => {
  // Stato del carrello: oggetto con seller_id come chiave
  const cartItems = ref({})
  const selectedShippingZones = ref({}) // seller_id -> shipping_zone_id
  const selectedAddress = ref(null)

  // Computed per ottenere tutti gli articoli del carrello
  const allCartItems = computed(() => {
    const items = []
    Object.values(cartItems.value).forEach(sellerItems => {
      items.push(...sellerItems)
    })
    return items
  })

  // Computed per ottenere i venditori nel carrello
  const sellers = computed(() => {
    return Object.keys(cartItems.value).map(sellerId => {
      const sellerItems = cartItems.value[sellerId]
      const seller = sellerItems[0]?.seller
      const subtotal = sellerItems.reduce((sum, item) => sum + (item.price * item.quantity), 0)
      const shippingCost = getShippingCostForSeller(sellerId)
      
      return {
        id: sellerId,
        name: seller?.name || 'Venditore',
        items: sellerItems,
        subtotal,
        shippingCost,
        total: subtotal + shippingCost
      }
    })
  })

  // Computed per il totale generale
  const grandTotal = computed(() => {
    return sellers.value.reduce((sum, seller) => sum + seller.total, 0)
  })

  // Computed per il numero totale di articoli
  const totalItems = computed(() => {
    return allCartItems.value.reduce((sum, item) => sum + item.quantity, 0)
  })

  // Computed per il costo totale di spedizione
  const totalShippingCost = computed(() => {
    return sellers.value.reduce((sum, seller) => sum + seller.shippingCost, 0)
  })

  // Computed per verificare se il carrello è vuoto
  const isEmpty = computed(() => {
    return Object.keys(cartItems.value).length === 0
  })

  // Aggiunge un articolo al carrello
  const addToCart = async (listing, quantity = 1) => {
    try {
      const response = await axios.post('/api/cart/add', {
        listing_id: listing.id,
        quantity: quantity
      })

      if (response.data.success) {
        const sellerId = listing.seller_id.toString()
        
        // Inizializza l'array per il venditore se non esiste
        if (!cartItems.value[sellerId]) {
          cartItems.value[sellerId] = []
        }

        // Cerca se l'articolo è già nel carrello
        const existingItemIndex = cartItems.value[sellerId].findIndex(
          item => item.id === listing.id
        )

        if (existingItemIndex !== -1) {
          // Aggiorna la quantità
          cartItems.value[sellerId][existingItemIndex].quantity += quantity
        } else {
          // Aggiungi nuovo articolo
          cartItems.value[sellerId].push({
            id: listing.id,
            card_model_id: listing.card_model_id,
            seller_id: listing.seller_id,
            price: listing.price,
            quantity: quantity,
            condition: listing.condition,
            description: listing.description,
            images: listing.images,
            available: true,
            seller: listing.seller,
            cardModel: listing.card_model,
            shippingZones: listing.shipping_zones || []
          })
        }

        // Salva nel localStorage
        saveToLocalStorage()
        return { success: true, message: response.data.message }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Errore nell\'aggiunta al carrello:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Errore durante l\'aggiunta al carrello' 
      }
    }
  }

  // Rimuove un articolo dal carrello
  const removeFromCart = async (listingId, sellerId) => {
    try {
      const response = await axios.delete('/api/cart/remove', {
        data: { listing_id: listingId }
      })

      if (response.data.success) {
        const sellerIdStr = sellerId.toString()
        
        if (cartItems.value[sellerIdStr]) {
          cartItems.value[sellerIdStr] = cartItems.value[sellerIdStr].filter(
            item => item.id !== listingId
          )

          // Se non ci sono più articoli per questo venditore, rimuovi la chiave
          if (cartItems.value[sellerIdStr].length === 0) {
            delete cartItems.value[sellerIdStr]
            delete selectedShippingZones.value[sellerIdStr]
          }
        }

        saveToLocalStorage()
        return { success: true, message: response.data.message }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Errore nella rimozione dal carrello:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Errore durante la rimozione dal carrello' 
      }
    }
  }

  // Aggiorna la quantità di un articolo
  const updateQuantity = async (listingId, sellerId, quantity) => {
    try {
      const response = await axios.put('/api/cart/update-quantity', {
        listing_id: listingId,
        quantity: quantity
      })

      if (response.data.success) {
        const sellerIdStr = sellerId.toString()
        
        if (cartItems.value[sellerIdStr]) {
          const itemIndex = cartItems.value[sellerIdStr].findIndex(
            item => item.id === listingId
          )

          if (itemIndex !== -1) {
            if (quantity <= 0) {
              await removeFromCart(listingId, sellerId)
            } else {
              cartItems.value[sellerIdStr][itemIndex].quantity = quantity
              cartItems.value[sellerIdStr][itemIndex].available = response.data.data.available
            }
          }
        }

        saveToLocalStorage()
        return { success: true, message: response.data.message }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Errore nell\'aggiornamento della quantità:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Errore durante l\'aggiornamento della quantità' 
      }
    }
  }

  // Svuota il carrello
  const clearCart = () => {
    cartItems.value = {}
    selectedShippingZones.value = {}
    selectedAddress.value = null
    saveToLocalStorage()
  }

  // Svuota il carrello per un venditore specifico
  const clearSellerCart = (sellerId) => {
    const sellerIdStr = sellerId.toString()
    delete cartItems.value[sellerIdStr]
    delete selectedShippingZones.value[sellerIdStr]
    saveToLocalStorage()
  }

  // Seleziona zona di spedizione per un venditore
  const selectShippingZone = (sellerId, shippingZoneId) => {
    selectedShippingZones.value[sellerId.toString()] = shippingZoneId
    saveToLocalStorage()
  }

  // Ottiene il costo di spedizione per un venditore
  const getShippingCostForSeller = (sellerId) => {
    const sellerIdStr = sellerId.toString()
    const shippingZoneId = selectedShippingZones.value[sellerIdStr]
    
    if (!shippingZoneId) return 0

    const sellerItems = cartItems.value[sellerIdStr]
    if (!sellerItems || sellerItems.length === 0) return 0

    // Prendi la prima inserzione per ottenere le zone di spedizione
    const firstItem = sellerItems[0]
    const shippingZone = firstItem.shippingZones?.find(zone => zone.id === shippingZoneId)
    
    return shippingZone?.pivot?.shipping_cost || shippingZone?.shipping_cost || 0
  }

  // Verifica se un articolo è nel carrello
  const isInCart = (listingId, sellerId) => {
    const sellerIdStr = sellerId.toString()
    return cartItems.value[sellerIdStr]?.some(item => item.id === listingId) || false
  }

  // Ottiene la quantità di un articolo nel carrello
  const getItemQuantity = (listingId, sellerId) => {
    const sellerIdStr = sellerId.toString()
    const item = cartItems.value[sellerIdStr]?.find(item => item.id === listingId)
    return item ? item.quantity : 0
  }

  // Salva il carrello nel localStorage
  const saveToLocalStorage = () => {
    localStorage.setItem('cart', JSON.stringify({
      items: cartItems.value,
      shippingZones: selectedShippingZones.value,
      address: selectedAddress.value
    }))
  }

  // Carica il carrello dal localStorage
  const loadFromLocalStorage = () => {
    try {
      const saved = localStorage.getItem('cart')
      if (saved) {
        const data = JSON.parse(saved)
        cartItems.value = data.items || {}
        selectedShippingZones.value = data.shippingZones || {}
        selectedAddress.value = data.address || null
      }
    } catch (error) {
      console.error('Errore nel caricamento del carrello:', error)
    }
  }

  // Valida il carrello prima del checkout
  const validateCart = () => {
    const errors = []

    // Verifica che ci siano articoli
    if (isEmpty.value) {
      errors.push('Il carrello è vuoto')
    }

    // Verifica che ogni venditore abbia una zona di spedizione selezionata
    Object.keys(cartItems.value).forEach(sellerId => {
      if (!selectedShippingZones.value[sellerId]) {
        errors.push(`Seleziona un metodo di spedizione per il venditore ${sellerId}`)
      }
    })

    // Verifica che sia selezionato un indirizzo
    if (!selectedAddress.value) {
      errors.push('Seleziona un indirizzo di spedizione')
    }

    return {
      isValid: errors.length === 0,
      errors
    }
  }

  // Ottiene i dati per il checkout
  const getCheckoutData = () => {
    const validation = validateCart()
    if (!validation.isValid) {
      return { success: false, errors: validation.errors }
    }

    return {
      success: true,
      data: {
        sellers: sellers.value,
        grandTotal: grandTotal.value,
        selectedAddress: selectedAddress.value,
        selectedShippingZones: selectedShippingZones.value
      }
    }
  }

  // Metodo per ottenere i dati del carrello in formato backend
  const getCartData = () => {
    return cartItems.value
  }

  // Sincronizza il carrello con il backend
  const syncWithBackend = async () => {
    try {
      const cartData = Object.keys(cartItems.value).length > 0 ? cartItems.value : {}
      const response = await axios.post('/api/cart/', { cart_data: cartData })
      
      if (response.data.success) {
        const backendData = response.data.data
        if (backendData.items && Object.keys(backendData.items).length > 0) {
          cartItems.value = backendData.items
          saveToLocalStorage()
        }
        return { success: true, data: backendData }
      } else {
        return { success: false, message: response.data.message }
      }
    } catch (error) {
      console.error('Errore nella sincronizzazione del carrello:', error)
      return { 
        success: false, 
        message: error.response?.data?.message || 'Errore durante la sincronizzazione del carrello' 
      }
    }
  }

  // Valida il carrello con il backend
  const validateCartWithBackend = async () => {
    try {
      const cartData = Object.keys(cartItems.value).length > 0 ? cartItems.value : {}
      const response = await axios.post('/api/cart/validate', { cart_data: cartData })
      
      if (response.data.success) {
        return response.data.data
      } else {
        return { 
          is_valid: false, 
          errors: [response.data.message], 
          warnings: [] 
        }
      }
    } catch (error) {
      console.error('Errore nella validazione del carrello:', error)
      return { 
        is_valid: false, 
        errors: [error.response?.data?.message || 'Errore durante la validazione del carrello'], 
        warnings: [] 
      }
    }
  }

  // Inizializza il carrello caricando i dati dal localStorage e sincronizzando con il backend
  const initialize = async () => {
    loadFromLocalStorage()
    
    // TODO: Implementare sincronizzazione con backend quando necessario
    // const token = localStorage.getItem('token')
    // if (token) {
    //   await syncWithBackend()
    // }
  }

  return {
    // Stato
    cartItems,
    selectedShippingZones,
    selectedAddress,
    
    // Computed
    allCartItems,
    sellers,
    grandTotal,
    totalItems,
    totalShippingCost,
    isEmpty,
    
    // Azioni
    addToCart,
    removeFromCart,
    updateQuantity,
    clearCart,
    clearSellerCart,
    selectShippingZone,
    getShippingCostForSeller,
    isInCart,
    getItemQuantity,
    validateCart,
    getCheckoutData,
    getCartData,
    syncWithBackend,
    validateCartWithBackend,
    initialize
  }
})
