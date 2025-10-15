<template>
  <div class="shipping-zones-manager">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">Gestione Zone di Spedizione</h2>
        <p class="text-gray-600 mt-1">Configura le zone dove i venditori possono spedire le loro carte</p>
      </div>
      <button
        @click="openCreateModal"
        class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors flex items-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Aggiungi Zona
      </button>
    </div>

    <!-- Alert se non ci sono zone -->
    <div v-if="zones.length === 0" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
      <div class="flex items-center">
        <svg class="w-5 h-5 text-yellow-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
        </svg>
        <div>
          <h3 class="text-sm font-medium text-yellow-800">Nessuna zona di spedizione configurata</h3>
          <p class="text-sm text-yellow-700 mt-1">
            Devi creare almeno una zona di spedizione prima che i venditori possano pubblicare inserzioni.
          </p>
        </div>
      </div>
    </div>

    <!-- Lista Zone -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Nome
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Paese/Regione
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Costo
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Giorni
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                Stato
              </th>
              <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                Azioni
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="zone in zones" :key="zone.id" class="hover:bg-gray-50">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">{{ zone.name }}</div>
                <div v-if="zone.region || zone.city" class="text-sm text-gray-500">
                  {{ [zone.region, zone.city].filter(Boolean).join(', ') }}
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                  {{ zone.country_code }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                €{{ parseFloat(zone.shipping_cost).toFixed(2) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ zone.delivery_days_min }}-{{ zone.delivery_days_max }} giorni
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span :class="zone.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                  {{ zone.is_active ? 'Attiva' : 'Disattiva' }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <div class="flex justify-end gap-2">
                  <button
                    @click="openEditModal(zone)"
                    class="text-indigo-600 hover:text-indigo-900"
                  >
                    Modifica
                  </button>
                  <button
                    @click="deleteZone(zone)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Elimina
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Creazione/Modifica -->
    <div v-if="showModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ editingZone ? 'Modifica Zona' : 'Nuova Zona di Spedizione' }}
          </h3>
          
          <form @submit.prevent="saveZone" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Nome Zona</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                placeholder="es. Italia - Spedizione Standard"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Codice Paese</label>
              <select
                v-model="form.country_code"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
              >
                <option value="">Seleziona paese</option>
                <option value="IT">Italia</option>
                <option value="EU">Unione Europea</option>
                <option value="WW">Extra Unione Europea</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Regione (opzionale)</label>
                <input
                  v-model="form.region"
                  type="text"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                  placeholder="es. Lombardia"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Città (opzionale)</label>
                <input
                  v-model="form.city"
                  type="text"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                  placeholder="es. Milano"
                />
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">CAP (opzionale)</label>
              <input
                v-model="form.postal_code"
                type="text"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                placeholder="es. 20100"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700">Costo Spedizione (€)</label>
              <input
                v-model="form.shipping_cost"
                type="number"
                step="0.01"
                min="0"
                required
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                placeholder="0.00"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Giorni Min</label>
                <input
                  v-model="form.delivery_days_min"
                  type="number"
                  min="1"
                  required
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Giorni Max</label>
                <input
                  v-model="form.delivery_days_max"
                  type="number"
                  min="1"
                  required
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary focus:border-primary"
                />
              </div>
            </div>

            <div class="flex items-center">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
              />
              <label class="ml-2 block text-sm text-gray-900">
                Zona attiva
              </label>
            </div>

            <div class="flex justify-end gap-3 pt-4">
              <button
                type="button"
                @click="closeModal"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
              >
                Annulla
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/90 disabled:opacity-50"
              >
                {{ loading ? 'Salvataggio...' : (editingZone ? 'Aggiorna' : 'Crea') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['zones-updated'])

// State
const zones = ref([])
const showModal = ref(false)
const editingZone = ref(null)
const loading = ref(false)

const form = ref({
  name: '',
  country_code: '',
  region: '',
  city: '',
  postal_code: '',
  shipping_cost: '',
  delivery_days_min: 1,
  delivery_days_max: 7,
  is_active: true
})

// Methods
const loadZones = async () => {
  try {
    const response = await fetch('/api/admin/shipping-zones', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      zones.value = data.data
    } else {
      console.error('Errore nel caricamento delle zone')
    }
  } catch (error) {
    console.error('Errore nel caricamento delle zone:', error)
  }
}

const openCreateModal = () => {
  editingZone.value = null
  resetForm()
  showModal.value = true
}

const openEditModal = (zone) => {
  editingZone.value = zone
  form.value = { ...zone }
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingZone.value = null
  resetForm()
}

const resetForm = () => {
  form.value = {
    name: '',
    country_code: '',
    region: '',
    city: '',
    postal_code: '',
    shipping_cost: '',
    delivery_days_min: 1,
    delivery_days_max: 7,
    is_active: true
  }
}

const saveZone = async () => {
  loading.value = true
  
  try {
    const url = editingZone.value 
      ? `/api/admin/shipping-zones/${editingZone.value.id}`
      : '/api/admin/shipping-zones'
    
    const method = editingZone.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    })

    if (response.ok) {
      const data = await response.json()
      console.log('Zona salvata:', data.message)
      await loadZones()
      closeModal()
      emit('zones-updated')
    } else {
      const errorData = await response.json()
      console.error('Errore nel salvataggio:', errorData.message)
      alert(`Errore: ${errorData.message}`)
    }
  } catch (error) {
    console.error('Errore nel salvataggio:', error)
    alert('Errore nel salvataggio della zona')
  } finally {
    loading.value = false
  }
}

const deleteZone = async (zone) => {
  if (!confirm(`Sei sicuro di voler eliminare la zona "${zone.name}"?`)) {
    return
  }

  try {
    const response = await fetch(`/api/admin/shipping-zones/${zone.id}`, {
      method: 'DELETE',
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      console.log('Zona eliminata:', data.message)
      await loadZones()
      emit('zones-updated')
    } else {
      const errorData = await response.json()
      console.error('Errore nell\'eliminazione:', errorData.message)
      alert(`Errore: ${errorData.message}`)
    }
  } catch (error) {
    console.error('Errore nell\'eliminazione:', error)
    alert('Errore nell\'eliminazione della zona')
  }
}

// Lifecycle
onMounted(() => {
  loadZones()
})
</script>
