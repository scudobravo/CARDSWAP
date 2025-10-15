<template>
  <div class="shipping-zones-setup">
    <!-- Controllo Zone -->
    <div v-if="!hasZones" class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
      <div class="flex items-center">
        <svg class="w-8 h-8 text-red-600 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
        </svg>
        <div>
          <h3 class="text-lg font-medium text-red-800">Configurazione Zone di Spedizione Richiesta</h3>
          <p class="text-red-700 mt-1">
            Prima di poter creare inserzioni, devi configurare almeno una zona di spedizione.
            I venditori potranno selezionare le zone dove vogliono spedire le loro carte.
          </p>
        </div>
      </div>
      
      <div class="mt-4">
        <button
          @click="openSetupModal"
          class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          Configura Zone di Spedizione
        </button>
      </div>
    </div>

    <!-- Messaggio di Successo -->
    <div v-else class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
      <div class="flex items-center">
        <svg class="w-5 h-5 text-green-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
        </svg>
        <div>
          <h3 class="text-sm font-medium text-green-800">Zone di spedizione configurate</h3>
          <p class="text-sm text-green-700 mt-1">
            Hai {{ zonesCount }} zona{{ zonesCount !== 1 ? 'e' : '' }} di spedizione attiva{{ zonesCount !== 1 ? 'e' : '' }}. 
            I venditori possono ora creare inserzioni.
          </p>
        </div>
      </div>
    </div>

    <!-- Modal Setup -->
    <div v-if="showSetupModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-10 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-medium text-gray-900">Configurazione Zone di Spedizione</h3>
            <button
              @click="closeSetupModal"
              class="text-gray-400 hover:text-gray-600"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <p class="text-gray-600 mb-6">
            Configura le zone geografiche dove i venditori possono spedire le loro carte. 
            Puoi creare zone per Italia, Unione Europea e paesi extra-UE.
          </p>

          <!-- Componente Gestione Zone -->
          <ShippingZonesManager 
            :user="user" 
            @zones-updated="handleZonesUpdated"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ShippingZonesManager from './ShippingZonesManager.vue'

// Props
const props = defineProps({
  user: {
    type: Object,
    required: true
  }
})

// Emits
const emit = defineEmits(['setup-completed'])

// State
const hasZones = ref(false)
const zonesCount = ref(0)
const showSetupModal = ref(false)

// Methods
const checkZones = async () => {
  try {
    const response = await fetch('/api/admin/shipping-zones/check', {
      headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      }
    })

    if (response.ok) {
      const data = await response.json()
      hasZones.value = data.has_zones
      zonesCount.value = data.zones_count
    }
  } catch (error) {
    console.error('Errore nel controllo delle zone:', error)
  }
}

const openSetupModal = () => {
  showSetupModal.value = true
}

const closeSetupModal = () => {
  showSetupModal.value = false
}

const handleZonesUpdated = async () => {
  await checkZones()
  if (hasZones.value) {
    emit('setup-completed')
  }
}

// Lifecycle
onMounted(() => {
  checkZones()
})
</script>
