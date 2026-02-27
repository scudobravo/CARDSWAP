// Card Service per gestire le chiamate API delle carte
import axios from 'axios'

const API_BASE_URL = '/api'

class CardService {
  constructor() {
    this.axios = axios.create({
      baseURL: API_BASE_URL,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    })
  }

  /**
   * Get cards by category and section
   */
  async getCardsByCategory(category, section, limit = 8) {
    try {
      const response = await this.axios.get('/category/cards', {
        params: {
          category,
          section,
          limit,
          _t: Date.now() // Cache buster
        }
      })
      return response.data
    } catch (error) {
      console.error('Error fetching cards:', error)
      return {
        success: false,
        error: error.response?.data?.error || 'Errore nel recupero delle carte',
        data: []
      }
    }
  }

  /**
   * Get single listing details by ID
   * Cache-buster per evitare risposte in cache (numero vendite / rating venditore)
   */
  async getListingDetails(listingId) {
    try {
      const response = await this.axios.get(`/listings/${listingId}`, {
        params: { _t: Date.now() }
      })
      return response.data
    } catch (error) {
      console.error('Error fetching listing details:', error)
      return {
        success: false,
        error: error.response?.data?.error || 'Errore nel recupero dei dettagli dell\'inserzione',
        data: null
      }
    }
  }

  /**
   * Get single card details by ID
   * Cache-buster per evitare risposte in cache (numero vendite / rating venditore)
   */
  async getCardDetails(cardId) {
    try {
      const response = await this.axios.get(`/card/${cardId}`, {
        params: { _t: Date.now() }
      })
      return response.data
    } catch (error) {
      console.error('Error fetching card details:', error)
      return {
        success: false,
        error: error.response?.data?.error || 'Errore nel recupero dei dettagli della carta',
        data: null
      }
    }
  }

  /**
   * Get single card details by category and slug
   * Cache-buster per evitare risposte in cache (numero vendite / rating venditore)
   */
  async getCardDetailsBySlug(category, cardSlug) {
    try {
      const response = await this.axios.get(`/card/${category}/${cardSlug}`, {
        params: { _t: Date.now() }
      })
      return response.data
    } catch (error) {
      console.error('Error fetching card details by slug:', error)
      return {
        success: false,
        error: error.response?.data?.error || 'Errore nel recupero dei dettagli della carta',
        data: null
      }
    }
  }

  /**
   * Statistiche venditore (numero vendite + rating) - sempre aggiornate, usate per la pagina carta
   */
  async getSellerStats(sellerId) {
    try {
      const response = await this.axios.get(`/sellers/${sellerId}/stats`, {
        params: { _t: Date.now() }
      })
      return response.data
    } catch (error) {
      console.warn('Error fetching seller stats:', error)
      return { success: false }
    }
  }

  /**
   * Statistiche venditore per inserzione (usa solo listingId dall'URL, non dipende da listing.seller)
   * Preferibile quando si è sulla route listing (es. utente non loggato).
   */
  async getListingSellerStats(listingId) {
    try {
      const response = await this.axios.get(`/listings/${listingId}/seller-stats`, {
        params: { _t: Date.now() }
      })
      return response.data
    } catch (error) {
      console.warn('Error fetching listing seller stats:', error)
      return { success: false }
    }
  }

  /**
   * Get related products for a specific card
   */
  async getRelatedProducts(cardId, limit = 8) {
    try {
      const response = await this.axios.get(`/card/${cardId}/related`, {
        params: {
          limit
        }
      })
      return response.data
    } catch (error) {
      console.error('Error fetching related products:', error)
      return {
        success: false,
        error: error.response?.data?.error || 'Errore nel recupero dei prodotti correlati',
        data: []
      }
    }
  }

  /**
   * Get related products for a card by category and slug
   */
  async getRelatedProductsBySlug(category, slug, limit = 8) {
    try {
      const response = await this.axios.get(`/card/${category}/${slug}/related`, {
        params: {
          limit
        }
      })
      return response.data
    } catch (error) {
      console.error('Error fetching related products by slug:', error)
      return {
        success: false,
        error: error.response?.data?.error || 'Errore nel recupero dei prodotti correlati',
        data: []
      }
    }
  }

  /**
   * Get related listings for a specific card listing
   */
  async getRelatedListings(listingId, limit = 8) {
    try {
      const response = await this.axios.get(`/listings/${listingId}/related`, {
        params: {
          limit
        }
      })
      return response.data
    } catch (error) {
      console.error('Error fetching related listings:', error)
      return {
        success: false,
        error: error.response?.data?.error || 'Errore nel recupero delle inserzioni correlate',
        data: []
      }
    }
  }

  /**
   * Get all available categories
   */
  async getCategories() {
    try {
      const response = await this.axios.get('/category/categories')
      return response.data
    } catch (error) {
      console.error('Error fetching categories:', error)
      return {
        success: false,
        error: error.response?.data?.error || 'Errore nel recupero delle categorie',
        data: []
      }
    }
  }

  /**
   * Get fallback data for a category and section
   */
  getFallbackData(category, section) {
    const fallbackData = {
      football: {
        top_players: [
          { id: 'ff-1', name: "Yamal", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/LAMINE YAMAL.png", is_fallback: true },
          { id: 'ff-2', name: "Messi", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/LIONEL MESSI.png", is_fallback: true },
          { id: 'ff-3', name: "Cristiano Ronaldo", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/Cristiano Ronaldo.png", is_fallback: true },
          { id: 'ff-4', name: "Ronaldo", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/Ronaldo.png", is_fallback: true },
          { id: 'ff-5', name: "Diego Maradona", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/DIEGO MARADONA.png", is_fallback: true },
          { id: 'ff-6', name: "Rodrigo Mora", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/Rodrigo Mora.png", is_fallback: true },
          { id: 'ff-7', name: "Estevao Willian", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/ESTEVAO WILLIAN.png", is_fallback: true },
          { id: 'ff-8', name: "Franco Mastantuono", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/FRANCO MASTANTUONO.png", is_fallback: true },
          { id: 'ff-9', name: "Desire Doue", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/DESIRE DOUE.png", is_fallback: true },
          { id: 'ff-10', name: "Erling Haaland", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/ERLING HAALAND.png", is_fallback: true },
          { id: 'ff-11', name: "Kylian Mbappe", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/KYLIAN MBAPPE.png", is_fallback: true },
          { id: 'ff-12', name: "Roberto Lewandowski", team: "Top Football", type: "Calcio", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Football/ROBERT LEWANDOWSKI.png", is_fallback: true }
        ],
        top_trend: [
          {
            id: 'fallback-5',
            name: "Jude Bellingham",
            team: "Real Madrid",
            type: "Calcio",
            description: "Carta Panini del giovane talento inglese",
            price: "€35.00",
            rating: "4.8",
            image_url: null,
            is_fallback: true
          },
          {
            id: 'fallback-6',
            name: "Vinicius Jr",
            team: "Real Madrid",
            type: "Calcio",
            description: "Carta Topps dell'ala brasiliana",
            price: "€30.00",
            rating: "4.7",
            image_url: null,
            is_fallback: true
          }
        ],
        new: [
          {
            id: 'fallback-7',
            name: "Endrick",
            team: "Real Madrid",
            type: "Calcio",
            description: "Carta Panini del giovane promessa brasiliana",
            price: "€40.00",
            rating: "4.9",
            image_url: null,
            is_fallback: true
          }
        ],
        most_expensive: [
          {
            id: 'fallback-8',
            name: "Lionel Messi Rookie",
            team: "Barcelona",
            type: "Calcio",
            description: "Carta rookie Panini del 2004",
            price: "€2,500.00",
            rating: "5.0",
            image_url: null,
            is_fallback: true
          }
        ]
      },
      basketball: {
        top_players: [
          { id: 'fb-1', name: "Cooper Flagg", team: "Top Basketball", type: "Basketball", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Basketball/Cooper Flagg.png", is_fallback: true },
          { id: 'fb-2', name: "Viktor Wembanyama", team: "Top Basketball", type: "Basketball", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Basketball/Viktor Wembanyama.png", is_fallback: true },
          { id: 'fb-3', name: "Michael Jordan", team: "Top Basketball", type: "Basketball", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Basketball/Michael Jordan.png", is_fallback: true },
          { id: 'fb-4', name: "Anthony Edwards", team: "Top Basketball", type: "Basketball", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Basketball/Anthony Edwards.png", is_fallback: true },
          { id: 'fb-5', name: "LeBron James", team: "Top Basketball", type: "Basketball", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Basketball/LeBron James.png", is_fallback: true },
          { id: 'fb-6', name: "Luka Doncic", team: "Top Basketball", type: "Basketball", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Basketball/Luka Doncic.png", is_fallback: true },
          { id: 'fb-7', name: "Nikola Jokic", team: "Top Basketball", type: "Basketball", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Basketball/Nikola Jokic.png", is_fallback: true },
          { id: 'fb-8', name: "Stephen Curry", team: "Top Basketball", type: "Basketball", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Basketball/Stephen Curry.png", is_fallback: true },
          { id: 'fb-9', name: "Zaccharie Risacher", team: "Top Basketball", type: "Basketball", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Basketball/Zaccharie Risacher.png", is_fallback: true },
          { id: 'fb-10', name: "Kobe Bryant", team: "Top Basketball", type: "Basketball", description: "Collezione ufficiale", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Player - Basketball/Kobe Bryant.png", is_fallback: true }
        ],
        top_trend: [
          {
            id: 'fallback-11',
            name: "Luka Dončić",
            team: "Dallas Mavericks",
            type: "Basketball",
            description: "Carta Panini del giovane talento sloveno",
            price: "€50.00",
            rating: "4.8",
            image_url: null,
            is_fallback: true
          }
        ],
        new: [
          {
            id: 'fallback-12',
            name: "Victor Wembanyama",
            team: "San Antonio Spurs",
            type: "Basketball",
            description: "Carta Panini della promessa francese",
            price: "€60.00",
            rating: "4.9",
            image_url: null,
            is_fallback: true
          }
        ],
        most_expensive: [
          {
            id: 'fallback-13',
            name: "Michael Jordan Rookie",
            team: "Chicago Bulls",
            type: "Basketball",
            description: "Carta rookie del 1984",
            price: "€15,000.00",
            rating: "5.0",
            image_url: null,
            is_fallback: true
          }
        ]
      },
      disney: {
        top_players: [
          { id: 'fd-1', name: "Mickey Mouse", team: "Top Disney", type: "Disney", description: "Personaggio iconico", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Character - Disney/MickeyMouse.png", is_fallback: true },
          { id: 'fd-2', name: "Elsa", team: "Top Disney", type: "Disney", description: "Personaggio iconico", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Character - Disney/Elsa.png", is_fallback: true },
          { id: 'fd-3', name: "Donald Duck", team: "Top Disney", type: "Disney", description: "Personaggio iconico", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Character - Disney/DonaldDuck.png", is_fallback: true },
          { id: 'fd-4', name: "Genie", team: "Top Disney", type: "Disney", description: "Personaggio iconico", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Character - Disney/Genie.png", is_fallback: true },
          { id: 'fd-5', name: "Stitch", team: "Top Disney", type: "Disney", description: "Personaggio iconico", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Character - Disney/Stitch.png", is_fallback: true },
          { id: 'fd-6', name: "Whitesnow", team: "Top Disney", type: "Disney", description: "Personaggio iconico", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Character - Disney/Whitesnow.png", is_fallback: true },
          { id: 'fd-7', name: "Ariel", team: "Top Disney", type: "Disney", description: "Personaggio iconico", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Character - Disney/Ariel.png", is_fallback: true },
          { id: 'fd-8', name: "Belle", team: "Top Disney", type: "Disney", description: "Personaggio iconico", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Character - Disney/Belle.png", is_fallback: true },
          { id: 'fd-9', name: "Cinderella", team: "Top Disney", type: "Disney", description: "Personaggio iconico", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Character - Disney/Cinderella.png", is_fallback: true },
          { id: 'fd-10', name: "Mulan", team: "Top Disney", type: "Disney", description: "Personaggio iconico", price: "---", rating: "5.0", image_url: null, icon_path: "/images/icons/Top Character - Disney/Mulan.png", is_fallback: true }
        ],
        most_expensive: []
      },
      pokemon: {
        top_players: [
          {
            id: 'fallback-14',
            name: "Charizard",
            team: "Fire Type",
            type: "Pokemon",
            description: "Carta ultra rara di Charizard VMAX",
            price: "€299.99",
            rating: "5.0",
            image_url: null,
            is_fallback: true
          },
          {
            id: 'fallback-15',
            name: "Pikachu",
            team: "Electric Type",
            type: "Pokemon",
            description: "Carta iconica del Pokemon più famoso",
            price: "€89.99",
            rating: "4.9",
            image_url: null,
            is_fallback: true
          }
        ],
        top_trend: [
          {
            id: 'fallback-16',
            name: "Rayquaza VMAX",
            team: "Dragon/Flying Type",
            type: "Pokemon",
            description: "Carta ultra rara di Rayquaza VMAX",
            price: "€249.99",
            rating: "4.8",
            image_url: null,
            is_fallback: true
          }
        ],
        new: [
          {
            id: 'fallback-17',
            name: "Miraidon ex",
            team: "Electric Type",
            type: "Pokemon",
            description: "Carta esclusiva di Miraidon ex",
            price: "€89.99",
            rating: "4.9",
            image_url: null,
            is_fallback: true
          }
        ],
        most_expensive: [
          {
            id: 'fallback-18',
            name: "Charizard Base Set",
            team: "Fire Type",
            type: "Pokemon",
            description: "Carta originale del 1999",
            price: "€15,000.00",
            rating: "5.0",
            image_url: null,
            is_fallback: true
          }
        ]
      }
    }

    return fallbackData[category]?.[section] || []
  }
}

// Export singleton instance
export default new CardService()
