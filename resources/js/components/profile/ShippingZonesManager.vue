<template>
  <div class="bg-white rounded-lg border border-gray-200 p-6">
    <div class="flex justify-between items-center mb-6">
      <h3 class="text-lg font-medium text-gray-900 font-futura-bold">
        Le mie zone di spedizione
      </h3>
      <button
        @click="openCreateModal"
        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
      >
        <PlusIcon class="h-4 w-4 mr-2" />
        Nuova Zona
      </button>
    </div>

    <!-- Lista zone -->
    <div v-if="zones.length > 0" class="space-y-4">
      <div
        v-for="zone in zones"
        :key="zone.id"
        class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <h4 class="text-sm font-medium text-gray-900">{{ zone.name }}</h4>
              <span
                :class="zone.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
              >
                {{ zone.is_active ? 'Attiva' : 'Inattiva' }}
              </span>
            </div>
            <div class="text-sm text-gray-600 space-y-1">
              <p><span class="font-medium">Paese:</span> {{ zone.country_code }}</p>
              <p v-if="zone.region"><span class="font-medium">Regione:</span> {{ zone.region }}</p>
              <p v-if="zone.city"><span class="font-medium">Città:</span> {{ zone.city }}</p>
              <p><span class="font-medium">Costo:</span> €{{ zone.shipping_cost }}</p>
              <p>
                <span class="font-medium">Consegna:</span>
                {{ zone.delivery_days_min }}-{{ zone.delivery_days_max }} giorni
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2 ml-4">
            <button
              @click="openEditModal(zone)"
              class="text-indigo-600 hover:text-indigo-900 text-sm font-medium"
            >
              Modifica
            </button>
            <button
              @click="deleteZone(zone)"
              class="text-red-600 hover:text-red-900 text-sm font-medium"
            >
              Elimina
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Messaggio se non ci sono zone -->
    <div v-else class="text-center py-8">
      <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
      </svg>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Nessuna zona di spedizione</h3>
      <p class="mt-1 text-sm text-gray-500">
        Crea la tua prima zona di spedizione per iniziare a vendere.
      </p>
      <div class="mt-6">
        <button
          @click="openCreateModal"
          class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
        >
          <PlusIcon class="w-4 h-4 mr-2" />
          Crea Zona di Spedizione
        </button>
      </div>
    </div>

    <!-- Modal Creazione/Modifica -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click="closeModal"
    >
      <div
        class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white"
        @click.stop
      >
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ editingZone ? 'Modifica Zona' : 'Nuova Zona di Spedizione' }}
          </h3>
          
          <form @submit.prevent="saveZone" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Nome zona *
              </label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="es. Italia - Spedizione Standard"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Codice Paese *
              </label>
              <input
                v-model="form.country_code"
                type="text"
                required
                maxlength="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="IT"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Regione
              </label>
              <input
                v-model="form.region"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="es. Lombardia"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Città
              </label>
              <input
                v-model="form.city"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="es. Milano"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                CAP
              </label>
              <input
                v-model="form.postal_code"
                type="text"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="es. 20100"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Costo spedizione (€) *
              </label>
              <input
                v-model="form.shipping_cost"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                placeholder="3.50"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Giorni min *
                </label>
                <input
                  v-model="form.delivery_days_min"
                  type="number"
                  min="1"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                  placeholder="2"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Giorni max *
                </label>
                <input
                  v-model="form.delivery_days_max"
                  type="number"
                  min="1"
                  required
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                  placeholder="4"
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
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
              >
                Annulla
              </button>
              <button
                type="submit"
                :disabled="loading"
                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50"
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
import { ref, onMounted, reactive } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'

const zones = ref([])
const showModal = ref(false)
const editingZone = ref(null)
const loading = ref(false)

const form = reactive({
  name: '',
  country_code: '',
  region: '',
  city: '',
  postal_code: '',
  shipping_cost: '',
  delivery_days_min: '',
  delivery_days_max: '',
  is_active: true
})

const loadZones = async () => {
  try {
    const response = await fetch('/api/shipping-zones', {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    if (response.ok) {
      zones.value = await response.json()
    } else {
      console.error('Errore nel caricamento zone:', response.status)
    }
  } catch (error) {
    console.error('Errore nel caricamento zone:', error)
  }
}

const openCreateModal = () => {
  editingZone.value = null
  resetForm()
  showModal.value = true
}

const openEditModal = (zone) => {
  editingZone.value = zone
  form.name = zone.name
  form.country_code = zone.country_code
  form.region = zone.region || ''
  form.city = zone.city || ''
  form.postal_code = zone.postal_code || ''
  form.shipping_cost = zone.shipping_cost
  form.delivery_days_min = zone.delivery_days_min
  form.delivery_days_max = zone.delivery_days_max
  form.is_active = zone.is_active
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingZone.value = null
  resetForm()
}

const resetForm = () => {
  form.name = ''
  form.country_code = ''
  form.region = ''
  form.city = ''
  form.postal_code = ''
  form.shipping_cost = ''
  form.delivery_days_min = ''
  form.delivery_days_max = ''
  form.is_active = true
}

const saveZone = async () => {
  loading.value = true
  
  try {
    const url = editingZone.value 
      ? `/api/shipping-zones/${editingZone.value.id}`
      : '/api/shipping-zones'
    
    const method = editingZone.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      },
      body: JSON.stringify(form)
    })
    
    if (response.ok) {
      const result = await response.json()
      console.log('Zona salvata:', result)
      await loadZones()
      closeModal()
    } else {
      const error = await response.json()
      alert(`Errore: ${error.message}`)
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
    const response = await fetch(`/api/shipping-zones/${zone.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    if (response.ok) {
      await loadZones()
    } else {
      const error = await response.json()
      alert(`Errore: ${error.message}`)
    }
  } catch (error) {
    console.error('Errore nell\'eliminazione:', error)
    alert('Errore nell\'eliminazione della zona')
  }
}

onMounted(() => {
  loadZones()
})
</script>
