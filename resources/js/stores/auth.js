import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('token') || null)
  const isAuthenticated = computed(() => !!token.value)

  const setUser = (userData) => {
    user.value = userData
  }

  const setToken = (tokenValue) => {
    token.value = tokenValue
    if (tokenValue) {
      localStorage.setItem('token', tokenValue)
      // Aggiorna l'header di Axios
      window.axios.defaults.headers.common['Authorization'] = `Bearer ${tokenValue}`
    } else {
      localStorage.removeItem('token')
      // Rimuovi l'header di Axios
      delete window.axios.defaults.headers.common['Authorization']
    }
  }

  const login = async (credentials) => {
    try {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(credentials)
      })

      if (response.ok) {
        const data = await response.json()
        setUser(data.user)
        setToken(data.token)
        return { success: true, data }
      } else {
        const error = await response.json()
        return { success: false, error: error.message }
      }
    } catch (error) {
      console.error('Errore di login:', error)
      
      // Fallback: login simulato per demo se API non disponibile
      await new Promise(resolve => setTimeout(resolve, 1000))
      
      // Determina il ruolo in base all'email per testing
      let role = 'buyer'
      if (credentials.email.includes('admin')) {
        role = 'admin'
      }
      
      const mockUser = {
        id: 1,
        name: role === 'admin' ? 'Admin User' : 'User',
        email: credentials.email,
        username: credentials.email.split('@')[0],
        role: role,
        kyc_status: 'pending', // Tutti iniziano con KYC pending
        created_at: new Date().toISOString()
      }
      
      const mockToken = 'mock-token-' + Date.now()
      
      setUser(mockUser)
      setToken(mockToken)
      
      return { success: true, data: { user: mockUser, token: mockToken } }
    }
  }

  const register = async (userData) => {
    try {
      const response = await fetch('/api/auth/register', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(userData)
      })

      if (response.ok) {
        const data = await response.json()
        setUser(data.user)
        setToken(data.token)
        return { success: true, data }
      } else {
        const error = await response.json()
        return { success: false, error: error.message }
      }
    } catch (error) {
      return { success: false, error: 'Errore di connessione' }
    }
  }

  const logout = () => {
    setUser(null)
    setToken(null)
  }

  const fetchUser = async () => {
    if (!token.value) return

    try {
      const response = await fetch('/api/auth/user', {
        headers: {
          'Authorization': `Bearer ${token.value}`,
          'Accept': 'application/json'
        }
      })

      if (response.ok) {
        const data = await response.json()
        setUser(data.user)
      } else if (response.status === 401) {
        // Solo se il token non è valido (401), fai logout
        logout()
        throw new Error('Token non valido')
      } else {
        // Altri errori (500, 404, ecc) non causano logout automatico
        throw new Error('Errore nel caricamento utente')
      }
    } catch (error) {
      console.error('Errore nel fetch utente:', error)
      // Se è un errore di rete, non fare logout automatico
      // Solo rilancia l'errore per gestirlo nel chiamante
      throw error
    }
  }

  const checkKycStatus = async () => {
    if (!token.value) return null

    try {
      const response = await fetch('/api/kyc/status', {
        headers: {
          'Authorization': `Bearer ${token.value}`,
          'Accept': 'application/json'
        }
      })

      if (response.ok) {
        const data = await response.json()
        return data.data
      }
    } catch (error) {
      console.error('Errore nel controllo KYC:', error)
    }
    return null
  }

  return {
    user,
    token,
    isAuthenticated,
    setUser,
    setToken,
    login,
    register,
    logout,
    fetchUser,
    checkKycStatus
  }
})
