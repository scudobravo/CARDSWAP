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
          limit
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
   * Get single card details by ID
   */
  async getCardDetails(cardId) {
    try {
      const response = await this.axios.get(`/card/${cardId}`)
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
          {
            id: 'fallback-1',
            name: "Lionel Messi",
            team: "Inter Miami",
            type: "Calcio",
            description: "Carta ufficiale Panini del campione argentino",
            price: "€45.00",
            rating: "4.9",
            image_url: null,
            is_fallback: true
          },
          {
            id: 'fallback-2',
            name: "Cristiano Ronaldo",
            team: "Al Nassr",
            type: "Calcio",
            description: "Carta Topps Chrome del fenomeno portoghese",
            price: "€38.50",
            rating: "4.8",
            image_url: null,
            is_fallback: true
          },
          {
            id: 'fallback-3',
            name: "Kylian Mbappé",
            team: "Real Madrid",
            type: "Calcio",
            description: "Carta Panini del giovane talento francese",
            price: "€32.00",
            rating: "4.7",
            image_url: null,
            is_fallback: true
          },
          {
            id: 'fallback-4',
            name: "Erling Haaland",
            team: "Manchester City",
            type: "Calcio",
            description: "Carta Topps del bomber norvegese",
            price: "€28.50",
            rating: "4.6",
            image_url: null,
            is_fallback: true
          }
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
          {
            id: 'fallback-9',
            name: "LeBron James",
            team: "Los Angeles Lakers",
            type: "Basketball",
            description: "Carta Panini del re del basket",
            price: "€55.00",
            rating: "4.9",
            image_url: null,
            is_fallback: true
          },
          {
            id: 'fallback-10',
            name: "Stephen Curry",
            team: "Golden State Warriors",
            type: "Basketball",
            description: "Carta Topps del miglior tiratore da tre",
            price: "€48.00",
            rating: "4.8",
            image_url: null,
            is_fallback: true
          }
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
