import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'
import { normalizePrice } from '../utils/priceFormatter'

export const useCartStore = defineStore('cart', () => {
  // Stato del carrello: oggetto con seller_id come chiave
  const cartItems = ref({})
  // ============================================
  // LEGACY: selectedShippingZones - DEPRECATED
  // ============================================
  // Questo campo era usato per il sistema legacy basato su shipping_zones.
  // NON usare più - ora usiamo CardSwap Shipping V1 (shipping_selections).
  // Mantenuto temporaneamente per backward compatibility.
  // TODO: Rimuovere quando non più referenziato.
  // ============================================
  const selectedShippingZones = ref({}) // DEPRECATED - non più usato per pricing
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
      // Normalizza i prezzi prima di calcolare il subtotale
      const subtotal = sellerItems.reduce((sum, item) => {
        const price = normalizePrice(item.price)
        const quantity = parseInt(item.quantity) || 1
        return sum + (price * quantity)
      }, 0)
      // Shipping cost viene calcolato da CardSwap Shipping V1 nel checkout
      // Non più da shipping_zones
      const shippingCost = 0 // Calcolato nel checkout tramite CardSwap V1
      
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
      // Assicurati che listing_id sia una stringa valida e quantity sia un intero come richiesto dal backend
      const listingIdRaw = listing.id || listing.listing_id
      const listingId = listingIdRaw ? String(listingIdRaw).trim() : ''
      const quantityInt = parseInt(quantity, 10) || 1
      
      // Validazione: listing_id non deve essere vuoto
      if (!listingId || listingId === '' || listingId === 'undefined' || listingId === 'null') {
        console.error('Listing ID non valido:', { listing, listingIdRaw, listingId })
        return {
          success: false,
          message: 'ID inserzione non valido o mancante'
        }
      }
      
      // Validazione: quantity deve essere tra 1 e 100
      if (quantityInt < 1 || quantityInt > 100) {
        return {
          success: false,
          message: 'Quantità deve essere tra 1 e 100'
        }
      }
      
      const response = await axios.post('/api/cart/add', {
        listing_id: listingId,
        quantity: quantityInt
      })

      if (response.data.success) {
        // Usa i dati dalla risposta API se disponibili, altrimenti usa i dati del listing passato
        const cartItemData = response.data.data || listing
        
        // Usa seller_id dalla risposta API, poi dal listing, poi fallback a 1
        const sellerId = (cartItemData.seller_id || listing.seller_id || 1).toString()
        
        // Inizializza l'array per il venditore se non esiste
        if (!cartItems.value[sellerId]) {
          cartItems.value[sellerId] = []
        }

        // Cerca se l'articolo è già nel carrello (confronto stringa: API può restituire id numerico o stringa)
        const listingKey = String(cartItemData.id ?? listing.id ?? listingId)
        const existingItemIndex = cartItems.value[sellerId].findIndex(
          item => String(item.id) === listingKey
        )

        if (existingItemIndex !== -1) {
          // Aggiorna la quantità
          cartItems.value[sellerId][existingItemIndex].quantity += quantityInt
        } else {
          // Aggiungi nuovo articolo usando i dati dalla risposta API quando disponibili
          cartItems.value[sellerId].push({
            id: cartItemData.id || listing.id,
            card_model_id: cartItemData.card_model_id || listing.card_model_id,
            seller_id: cartItemData.seller_id || listing.seller_id,
            price: cartItemData.price || listing.price,
            quantity: quantityInt,
            available_quantity: cartItemData.available_quantity || listing.quantity || 1,
            condition: cartItemData.condition || listing.condition,
            description: cartItemData.description || listing.description,
            images: cartItemData.images || listing.images,
            available: cartItemData.available !== undefined ? cartItemData.available : true,
            seller: cartItemData.seller || listing.seller,
            cardModel: cartItemData.cardModel || cartItemData.card_model || listing.card_model,
            shippingZones: cartItemData.shippingZones || cartItemData.shipping_zones || listing.shipping_zones || []
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
              if (response.data.data.available_quantity !== undefined) {
                cartItems.value[sellerIdStr][itemIndex].available_quantity = response.data.data.available_quantity
              }
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

  // ============================================
  // LEGACY: getShippingCostForSeller - DEPRECATED
  // ============================================
  // Questo metodo usa shipping_zones per il calcolo del costo.
  // NON usare più - ora usiamo CardSwap Shipping V1.
  // Mantenuto temporaneamente per backward compatibility.
  // TODO: Rimuovere quando non più referenziato.
  // ============================================
  const getShippingCostForSeller = (sellerId) => {
    console.warn('DEPRECATED: getShippingCostForSeller() - Usa CardSwap Shipping V1 invece')
    // Ritorna 0 - il costo viene calcolato da CardSwap Shipping V1
    return 0
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

    // NOTA: Validazione shipping_zones rimossa - ora usiamo CardSwap Shipping V1
    // La validazione delle spedizioni viene fatta nel checkout tramite shipping_selections

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
        // selectedShippingZones rimosso - ora usiamo CardSwap Shipping V1
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
