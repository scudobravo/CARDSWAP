class AvailabilityService {
  constructor() {
    this.baseUrl = '/api/availability'
    this.locks = new Map() // Cache locale dei lock
    this.listeners = new Map() // Listeners per eventi real-time
  }

  /**
   * Verifica disponibilità di una singola inserzione
   */
  async checkSingle(listingId, quantity) {
    try {
      const response = await fetch(`${this.baseUrl}/check-single`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({
          listing_id: listingId,
          quantity: quantity
        })
      })

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Errore nella verifica disponibilità:', error)
      return { success: false, message: 'Errore di connessione' }
    }
  }

  /**
   * Verifica disponibilità di multiple inserzioni
   */
  async checkMultiple(items) {
    try {
      const response = await fetch(`${this.baseUrl}/check-multiple`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({ items })
      })

      const data = await response.json()
      return data
    } catch (error) {
      console.error('Errore nella verifica disponibilità multiple:', error)
      return { success: false, message: 'Errore di connessione' }
    }
  }

  /**
   * Blocca temporaneamente le quantità per il checkout
   */
  async lock(items, lockMinutes = 15) {
    try {
      const response = await fetch(`${this.baseUrl}/lock`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({
          items,
          lock_minutes: lockMinutes
        })
      })

      const data = await response.json()
      
      if (data.success) {
        // Salva il lock nella cache locale
        this.locks.set('checkout_lock', {
          ...data.data,
          locked_at: new Date()
        })
      }

      return data
    } catch (error) {
      console.error('Errore nel blocco:', error)
      return { success: false, message: 'Errore di connessione' }
    }
  }

  /**
   * Rilascia un lock
   */
  async release(listingId) {
    try {
      const response = await fetch(`${this.baseUrl}/release`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({
          listing_id: listingId
        })
      })

      const data = await response.json()
      
      if (data.success) {
        // Rimuovi dalla cache locale
        this.locks.delete('checkout_lock')
      }

      return data
    } catch (error) {
      console.error('Errore nel rilascio lock:', error)
      return { success: false, message: 'Errore di connessione' }
    }
  }

  /**
   * Conferma una prenotazione (vendita)
   */
  async confirm(listingId, quantity) {
    try {
      const response = await fetch(`${this.baseUrl}/confirm`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('token')}`
        },
        body: JSON.stringify({
          listing_id: listingId,
          quantity: quantity
        })
      })

      const data = await response.json()
      
      if (data.success) {
        // Rimuovi dalla cache locale
        this.locks.delete('checkout_lock')
      }

      return data
    } catch (error) {
      console.error('Errore nella conferma vendita:', error)
      return { success: false, message: 'Errore di connessione' }
    }
  }

  /**
   * Ottieni i lock attivi dell'utente
   */
  getActiveLocks() {
    return Array.from(this.locks.values())
  }

  /**
   * Verifica se un'inserzione è bloccata
   */
  isLocked(listingId) {
    const lock = this.locks.get('checkout_lock')
    if (!lock) return false
    
    return lock.items.some(item => item.listing_id === listingId && item.locked)
  }

  /**
   * Ottieni i dettagli di un lock
   */
  getLockDetails(listingId) {
    const lock = this.locks.get('checkout_lock')
    if (!lock) return null
    
    const item = lock.items.find(item => item.listing_id === listingId)
    if (item && item.locked) {
      return {
        ...item,
        locked_until: lock.locked_until
      }
    }
    return null
  }

  /**
   * Pulisci lock scaduti
   */
  cleanExpiredLocks() {
    const now = new Date()
    for (const [id, lock] of this.locks) {
      if (new Date(lock.locked_until) < now) {
        this.locks.delete(id)
      }
    }
  }

  /**
   * Aggiungi listener per eventi di disponibilità
   */
  addListener(event, callback) {
    if (!this.listeners.has(event)) {
      this.listeners.set(event, [])
    }
    this.listeners.get(event).push(callback)
  }

  /**
   * Rimuovi listener
   */
  removeListener(event, callback) {
    if (this.listeners.has(event)) {
      const callbacks = this.listeners.get(event)
      const index = callbacks.indexOf(callback)
      if (index > -1) {
        callbacks.splice(index, 1)
      }
    }
  }

  /**
   * Emetti evento
   */
  emit(event, data) {
    if (this.listeners.has(event)) {
      this.listeners.get(event).forEach(callback => callback(data))
    }
  }

  /**
   * Avvia il monitoraggio real-time
   */
  startRealTimeMonitoring() {
    // Pulisci lock scaduti ogni minuto
    setInterval(() => {
      this.cleanExpiredLocks()
      this.emit('locks_cleaned', this.getActiveLocks())
    }, 60000)

    // Monitora i lock in scadenza
    setInterval(() => {
      const now = new Date()
      const soon = new Date(now.getTime() + 5 * 60000) // 5 minuti

      for (const lock of this.locks.values()) {
        const lockedUntil = new Date(lock.locked_until)
        if (lockedUntil <= soon && lockedUntil > now) {
          this.emit('lock_expiring', lock)
        }
      }
    }, 30000) // Controlla ogni 30 secondi
  }

  /**
   * Formatta il tempo rimanente
   */
  formatTimeRemaining(until) {
    const now = new Date()
    const untilDate = new Date(until)
    const diff = untilDate - now

    if (diff <= 0) return 'Scaduta'

    const minutes = Math.floor(diff / 60000)
    const seconds = Math.floor((diff % 60000) / 1000)

    if (minutes > 0) {
      return `${minutes}m ${seconds}s`
    } else {
      return `${seconds}s`
    }
  }

  /**
   * Ottieni lo stato di disponibilità formattato
   */
  getAvailabilityStatus(availability) {
    if (!availability) return 'Non disponibile'

    switch (availability.status) {
      case 'available':
        return `Disponibile (${availability.quantity_available})`
      case 'locked':
        return `Bloccata fino a ${this.formatTimeRemaining(availability.locked_until)}`
      case 'reserved':
        return `Prenotata fino a ${this.formatTimeRemaining(availability.reserved_until)}`
      case 'sold':
        return 'Venduta'
      default:
        return 'Sconosciuto'
    }
  }
}

// Esporta istanza singleton
export default new AvailabilityService()
