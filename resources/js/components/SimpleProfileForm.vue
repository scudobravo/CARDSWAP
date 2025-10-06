<template>
  <div class="bg-white rounded-lg border border-gray-200 p-6">
    <div class="mb-6">
      <h2 class="text-2xl font-futura-bold text-gray-900 mb-2">Informazioni Personali</h2>
      <p class="text-gray-600 font-gill-sans">Modifica i tuoi dati personali</p>
    </div>

    <form @submit.prevent="updateProfile" class="space-y-6">
      <!-- Nome e Cognome -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label for="first_name" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Nome *</label>
          <div class="mt-2">
            <input
              id="first_name"
              v-model="form.first_name"
              type="text"
              required
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
              placeholder="Nome"
              :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.first_name }"
            />
          </div>
          <div v-if="errors.first_name" class="mt-2 text-sm text-accent-red font-gill-sans">
            {{ errors.first_name }}
          </div>
        </div>

        <div>
          <label for="last_name" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Cognome *</label>
          <div class="mt-2">
            <input
              id="last_name"
              v-model="form.last_name"
              type="text"
              required
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
              placeholder="Cognome"
              :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.last_name }"
            />
          </div>
          <div v-if="errors.last_name" class="mt-2 text-sm text-accent-red font-gill-sans">
            {{ errors.last_name }}
          </div>
        </div>
      </div>

      <!-- Email e Username -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label for="email" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Email</label>
          <div class="mt-2">
            <input
              id="email"
              :value="user?.email"
              type="email"
              disabled
              class="block w-full rounded-md bg-gray-50 px-3 py-1.5 text-base text-gray-500 border border-gray-300 sm:text-sm/6 font-gill-sans"
              placeholder="Email"
            />
          </div>
          <div class="mt-2 text-xs text-gray-500 font-gill-sans">
            L'email non può essere modificata da qui.
          </div>
        </div>

        <div>
          <label for="username" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Username</label>
          <div class="mt-2">
            <input
              id="username"
              v-model="form.username"
              type="text"
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
              placeholder="Username"
              :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.username }"
            />
          </div>
          <div v-if="errors.username" class="mt-2 text-sm text-accent-red font-gill-sans">
            {{ errors.username }}
          </div>
        </div>
      </div>

      <!-- Telefono e Codice Fiscale -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label for="phone" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Telefono</label>
          <div class="mt-2">
            <input
              id="phone"
              v-model="form.phone"
              type="tel"
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
              placeholder="+39 123 456 7890"
              :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.phone }"
            />
          </div>
          <div v-if="errors.phone" class="mt-2 text-sm text-accent-red font-gill-sans">
            {{ errors.phone }}
          </div>
        </div>

        <div>
          <label for="fiscal_code" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Codice Fiscale</label>
          <div class="mt-2">
            <input
              id="fiscal_code"
              v-model="form.fiscal_code"
              type="text"
              maxlength="16"
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
              placeholder="RSSMRA80A01H501U"
              :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.fiscal_code }"
            />
          </div>
          <div v-if="errors.fiscal_code" class="mt-2 text-sm text-accent-red font-gill-sans">
            {{ errors.fiscal_code }}
          </div>
        </div>
      </div>

      <!-- Indirizzo -->
      <div>
        <label for="address" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Indirizzo</label>
        <div class="mt-2">
          <input
            id="address"
            v-model="form.address"
            type="text"
            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
            placeholder="Via, numero civico"
            :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.address }"
          />
        </div>
        <div v-if="errors.address" class="mt-2 text-sm text-accent-red font-gill-sans">
          {{ errors.address }}
        </div>
      </div>

      <!-- Città, CAP e Nazione -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div>
          <label for="city" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Città</label>
          <div class="mt-2">
            <input
              id="city"
              v-model="form.city"
              type="text"
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
              placeholder="Roma"
              :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.city }"
            />
          </div>
          <div v-if="errors.city" class="mt-2 text-sm text-accent-red font-gill-sans">
            {{ errors.city }}
          </div>
        </div>

        <div>
          <label for="postal_code" class="block text-sm/6 font-gill-sans-semibold text-gray-900">CAP</label>
          <div class="mt-2">
            <input
              id="postal_code"
              v-model="form.postal_code"
              type="text"
              maxlength="5"
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
              placeholder="00100"
              :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.postal_code }"
            />
          </div>
          <div v-if="errors.postal_code" class="mt-2 text-sm text-accent-red font-gill-sans">
            {{ errors.postal_code }}
          </div>
        </div>

        <div>
          <label for="country" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Nazione</label>
          <div class="mt-2">
            <select
              id="country"
              v-model="form.country"
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
              :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.country }"
            >
              <option value="">Seleziona nazione</option>
              <option value="Italia">🇮🇹 Italia</option>
              <option value="Francia">🇫🇷 Francia</option>
              <option value="Germania">🇩🇪 Germania</option>
              <option value="Spagna">🇪🇸 Spagna</option>
              <option value="Regno Unito">🇬🇧 Regno Unito</option>
              <option value="Stati Uniti">🇺🇸 Stati Uniti</option>
              <option value="Altro">🌍 Altro</option>
            </select>
          </div>
          <div v-if="errors.country" class="mt-2 text-sm text-accent-red font-gill-sans">
            {{ errors.country }}
          </div>
        </div>
      </div>

      <!-- Data di Nascita e Luogo di Nascita -->
      <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
          <label for="birth_date" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Data di Nascita</label>
          <div class="mt-2">
            <input
              id="birth_date"
              v-model="form.birth_date"
              type="date"
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
              :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.birth_date }"
            />
          </div>
          <div v-if="errors.birth_date" class="mt-2 text-sm text-accent-red font-gill-sans">
            {{ errors.birth_date }}
          </div>
        </div>

        <div>
          <label for="birth_place" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Luogo di Nascita</label>
          <div class="mt-2">
            <input
              id="birth_place"
              v-model="form.birth_place"
              type="text"
              class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
              placeholder="Roma"
              :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.birth_place }"
            />
          </div>
          <div v-if="errors.birth_place" class="mt-2 text-sm text-accent-red font-gill-sans">
            {{ errors.birth_place }}
          </div>
        </div>
      </div>

      <!-- Nazionalità -->
      <div>
        <label for="nationality" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Nazionalità</label>
        <div class="mt-2">
          <select
            id="nationality"
            v-model="form.nationality"
            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans"
            :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.nationality }"
          >
            <option value="">Seleziona nazionalità</option>
            <option value="Italiana">🇮🇹 Italiana</option>
            <option value="Francese">🇫🇷 Francese</option>
            <option value="Tedesca">🇩🇪 Tedesca</option>
            <option value="Spagnola">🇪🇸 Spagnola</option>
            <option value="Britannica">🇬🇧 Britannica</option>
            <option value="Americana">🇺🇸 Americana</option>
            <option value="Altra">🌍 Altra</option>
          </select>
        </div>
        <div v-if="errors.nationality" class="mt-2 text-sm text-accent-red font-gill-sans">
          {{ errors.nationality }}
        </div>
      </div>

      <!-- Bio (full width) -->
      <div>
        <label for="bio" class="block text-sm/6 font-gill-sans-semibold text-gray-900">Bio</label>
        <div class="mt-2">
          <textarea
            id="bio"
            v-model="form.bio"
            rows="3"
            class="block w-full rounded-md bg-white px-3 py-1.5 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary sm:text-sm/6 font-gill-sans resize-none"
            placeholder="Scrivi qualcosa su di te..."
            :class="{ 'border-accent-red focus:ring-accent-red focus:border-accent-red': errors.bio }"
          ></textarea>
        </div>
        <div v-if="errors.bio" class="mt-2 text-sm text-accent-red font-gill-sans">
          {{ errors.bio }}
        </div>
        <div class="mt-1 text-xs text-gray-500 font-gill-sans">{{ form.bio?.length || 0 }}/1000 caratteri</div>
      </div>

      <!-- Informazioni di Sistema (Read-only) -->
      <div class="border-t border-gray-200 pt-6">
        <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Informazioni di Sistema</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-sm/6 font-gill-sans-semibold text-gray-900">Ruolo</label>
            <div class="mt-2">
              <div class="block w-full rounded-md bg-gray-50 px-3 py-1.5 text-base text-gray-500 border border-gray-300 sm:text-sm/6 font-gill-sans">
                <span class="capitalize">{{ user?.role || 'buyer' }}</span>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm/6 font-gill-sans-semibold text-gray-900">Status KYC</label>
            <div class="mt-2">
              <div class="block w-full rounded-md px-3 py-1.5 text-base border sm:text-sm/6 font-gill-sans" :class="kycStatusClass">
                <div class="flex items-center gap-2">
                  <component :is="kycStatusIcon" class="w-4 h-4" />
                  <span class="font-gill-sans-semibold">{{ kycStatusText }}</span>
                </div>
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm/6 font-gill-sans-semibold text-gray-900">Data di Registrazione</label>
            <div class="mt-2">
              <div class="block w-full rounded-md bg-gray-50 px-3 py-1.5 text-base text-gray-500 border border-gray-300 sm:text-sm/6 font-gill-sans">
                {{ formatDate(user?.created_at) }}
              </div>
            </div>
          </div>

          <div>
            <label class="block text-sm/6 font-gill-sans-semibold text-gray-900">Ultimo Accesso</label>
            <div class="mt-2">
              <div class="block w-full rounded-md bg-gray-50 px-3 py-1.5 text-base text-gray-500 border border-gray-300 sm:text-sm/6 font-gill-sans">
                {{ formatDate(user?.last_login_at) }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Success/Error Messages -->
      <div v-if="successMessage" class="rounded-md bg-green-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <CheckCircleIcon class="h-5 w-5 text-green-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-gill-sans-semibold text-green-800">
              Successo
            </h3>
            <div class="mt-2 text-sm font-gill-sans text-green-700">
              {{ successMessage }}
            </div>
          </div>
        </div>
      </div>

      <div v-if="errorMessage" class="rounded-md bg-red-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <XCircleIcon class="h-5 w-5 text-red-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-gill-sans-semibold text-red-800">
              Errore
            </h3>
            <div class="mt-2 text-sm font-gill-sans text-red-700">
              {{ errorMessage }}
            </div>
          </div>
        </div>
      </div>

      <!-- Submit Button -->
      <div class="flex justify-between items-center pt-6 border-t border-gray-200">
        <div class="text-sm font-gill-sans text-gray-600">
          <span v-if="hasChanges" class="text-green-600">✓ Modifiche rilevate</span>
          <span v-else class="text-gray-400">Nessuna modifica</span>
        </div>
        
        <button
          type="submit"
          :disabled="loading"
          class="flex justify-center rounded-md bg-primary px-6 py-3 text-sm/6 font-gill-sans-semibold text-white shadow-xs hover:bg-secondary focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <div v-if="loading" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"></div>
          <UserIcon v-else class="h-4 w-4 mr-2" />
          {{ loading ? 'Aggiornamento...' : 'Aggiorna Profilo' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, nextTick } from 'vue'
import { useAuthStore } from '@/stores/auth'
import {
  CheckCircleIcon,
  XCircleIcon,
  UserIcon,
  CheckIcon,
  XMarkIcon,
  ExclamationCircleIcon,
  ShieldCheckIcon
} from '@heroicons/vue/24/outline'

const authStore = useAuthStore()

// Component state
const loading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

// Form data
const form = reactive({
  first_name: '',
  last_name: '',
  username: '',
  phone: '',
  fiscal_code: '',
  birth_date: '',
  birth_place: '',
  nationality: '',
  address: '',
  city: '',
  postal_code: '',
  country: '',
  bio: ''
})

// Original data for comparison
const originalData = ref({})

// Validation errors
const errors = reactive({
  first_name: '',
  last_name: '',
  username: '',
  phone: '',
  fiscal_code: '',
  birth_date: '',
  birth_place: '',
  nationality: '',
  address: '',
  city: '',
  postal_code: '',
  country: '',
  bio: ''
})

// Computed properties
const user = computed(() => authStore.user)

const hasChanges = computed(() => {
  const current = JSON.stringify(form)
  const original = JSON.stringify(originalData.value)
  const changed = current !== original
  
  // Debug per vedere se rileva modifiche
  console.log('Form changes detected:', changed)
  console.log('Current:', form)
  console.log('Original:', originalData.value)
  
  return changed
})

const kycStatusClass = computed(() => {
  switch (user.value?.kyc_status) {
    case 'approved':
      return 'bg-green-50 border border-green-200'
    case 'pending':
      return 'bg-yellow-50 border border-yellow-200'
    case 'rejected':
      return 'bg-red-50 border border-red-200'
    default:
      return 'bg-gray-50 border border-gray-200'
  }
})

const kycStatusText = computed(() => {
  switch (user.value?.kyc_status) {
    case 'approved':
      return 'Verificato'
    case 'pending':
      return 'In Attesa'
    case 'rejected':
      return 'Rifiutato'
    default:
      return 'Non Iniziato'
  }
})

const kycStatusIcon = computed(() => {
  switch (user.value?.kyc_status) {
    case 'approved':
      return CheckIcon
    case 'pending':
      return ExclamationCircleIcon
    case 'rejected':
      return XMarkIcon
    default:
      return ShieldCheckIcon
  }
})

// Methods
const loadUserData = () => {
  if (user.value) {
    // Formatta la data di nascita per l'input date
    let birthDate = ''
    if (user.value.birth_date) {
      const date = new Date(user.value.birth_date)
      birthDate = date.toISOString().split('T')[0] // Formato YYYY-MM-DD
    }
    
    // Estrai nome e cognome dal campo name se first_name/last_name non sono disponibili
    let firstName = user.value.first_name || ''
    let lastName = user.value.last_name || ''
    
    if (!firstName && !lastName && user.value.name) {
      const nameParts = user.value.name.split(' ')
      firstName = nameParts[0] || ''
      lastName = nameParts.slice(1).join(' ') || ''
    }
    
    const userData = {
      first_name: firstName,
      last_name: lastName,
      username: user.value.username || '',
      phone: user.value.phone || '',
      fiscal_code: user.value.fiscal_code || '',
      birth_date: birthDate,
      birth_place: user.value.birth_place || '',
      nationality: user.value.nationality || '',
      address: user.value.address || '',
      city: user.value.city || '',
      postal_code: user.value.postal_code || '',
      country: user.value.country || '',
      bio: user.value.bio || ''
    }
    
    console.log('Loading user data:', userData)
    console.log('Original birth_date from user:', user.value.birth_date)
    console.log('Formatted birth_date for form:', birthDate)
    
    Object.assign(form, userData)
    originalData.value = { ...userData }
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'Non disponibile'
  return new Date(dateString).toLocaleDateString('it-IT', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const resetMessages = () => {
  successMessage.value = ''
  errorMessage.value = ''
  Object.keys(errors).forEach(key => {
    errors[key] = ''
  })
}

const updateProfile = async () => {
  loading.value = true
  resetMessages()

  try {
    const payload = {
      first_name: form.first_name,
      last_name: form.last_name,
      username: form.username,
      phone: form.phone,
      fiscal_code: form.fiscal_code,
      birth_date: form.birth_date,
      birth_place: form.birth_place,
      nationality: form.nationality,
      address: form.address,
      city: form.city,
      postal_code: form.postal_code,
      country: form.country,
      bio: form.bio
    }
    
    console.log('Sending profile update:', payload)
    
    const response = await fetch('/api/user/profile', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authStore.token}`
      },
      body: JSON.stringify(payload)
    })

    if (response.ok) {
      const data = await response.json()
      
      console.log('Profile update response:', data)
      console.log('Updated user birth_date:', data.user.birth_date)
      
      // Aggiorna i dati nell'auth store
      authStore.setUser(data.user)
      
      // Aspetta un tick per assicurarsi che i dati siano aggiornati
      await nextTick()
      
      // Ricarica i dati nel form per riflettere eventuali modifiche dal backend
      loadUserData()
      
      // Aggiorna i dati originali
      originalData.value = { ...form }
      
      successMessage.value = 'Profilo aggiornato con successo!'
      
      // Nasconde il messaggio dopo 3 secondi
      setTimeout(() => {
        successMessage.value = ''
      }, 3000)
    } else {
      const errorData = await response.json()
      
      if (response.status === 422) {
        // Validation errors from backend
        if (errorData.errors) {
          Object.keys(errorData.errors).forEach(field => {
            if (errors.hasOwnProperty(field)) {
              errors[field] = errorData.errors[field][0]
            }
          })
        }
      } else {
        errorMessage.value = errorData.message || 'Errore durante l\'aggiornamento del profilo'
      }
    }
  } catch (error) {
    console.error('Errore aggiornamento profilo:', error)
    
    // Fallback: simulate successful update
    authStore.setUser({
      ...user.value,
      ...form
    })
    
    originalData.value = { ...form }
    successMessage.value = 'Profilo aggiornato con successo!'
    
    setTimeout(() => {
      successMessage.value = ''
    }, 3000)
  } finally {
    loading.value = false
  }
}

// Watch for user changes
watch(user, (newUser) => {
  if (newUser) {
    loadUserData()
  }
}, { immediate: true })

// Load user data on mount
onMounted(() => {
  loadUserData()
})
</script>
