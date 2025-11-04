<template>
  <div class="space-y-6">
    <!-- Sezione Zone Avanzate -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="flex justify-between items-center mb-6">
        <div>
          <h3 class="text-lg font-medium text-gray-900 font-futura-bold">
            Zone di Spedizione
          </h3>
          <p class="text-sm text-gray-600 mt-1">
            Seleziona continenti e paesi
          </p>
        </div>
        <button
          @click="openAdvancedModal"
          class="inline-flex items-center justify-center p-2 md:px-4 md:py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors"
          title="Nuova Zona Avanzata"
        >
          <PlusIcon class="h-5 w-5 md:h-4 md:w-4 md:mr-2" />
          <span class="hidden md:inline">Nuova Zona Avanzata</span>
        </button>
      </div>

      <!-- Lista zone avanzate -->
      <div v-if="advancedZones.length > 0" class="space-y-4">
        <div
          v-for="zone in advancedZones"
          :key="zone.id"
          class="border border-gray-200 rounded-lg p-4 md:p-6 hover:border-gray-300 transition-colors bg-white"
        >
          <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
            <div class="flex-1 min-w-0">
              <!-- Header con nome e stato -->
              <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2 flex-wrap">
                  <h4 class="text-base font-semibold text-gray-900">{{ zone.name }}</h4>
                  <span
                    :class="zone.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                    :title="zone.is_active ? 'La zona è attiva e può essere usata per le inserzioni' : 'La zona è inattiva e non può essere usata per le inserzioni'"
                  >
                    {{ zone.is_active ? 'Attiva' : 'Inattiva' }}
                  </span>
                </div>
                
                <!-- Azioni -->
                <div class="flex items-center gap-2 ml-2">
                  <button
                    @click="openEditAdvancedModal(zone)"
                    class="text-indigo-600 hover:text-indigo-900 text-sm font-medium whitespace-nowrap"
                  >
                    Modifica
                  </button>
                  <button
                    @click="deleteZone(zone)"
                    class="text-red-600 hover:text-red-900 text-sm font-medium whitespace-nowrap"
                  >
                    Elimina
                  </button>
                </div>
              </div>
              
              <!-- Dettagli zona -->
              <div class="text-sm text-gray-600 space-y-1.5">
                <p><span class="font-medium text-gray-900">Tipo:</span> {{ getZoneTypeLabel(zone.zone_type) }}</p>
                <p v-if="zone.is_worldwide"><span class="font-medium text-gray-900">Copertura:</span> Tutto il mondo</p>
                <p v-else-if="zone.included_countries && zone.included_countries.length > 0">
                  <span class="font-medium text-gray-900">Continente:</span> {{ getContinentFromCountries(zone.included_countries) }}
                </p>
                <p v-if="zone.included_countries && zone.included_countries.length > 0" class="break-words">
                  <span class="font-medium text-gray-900">Paesi inclusi:</span> 
                  <span class="text-xs">{{ zone.included_countries.join(', ') }}</span>
                </p>
                <p v-if="zone.use_shippo_pricing && zone.shippo_markup > 0">
                  <span class="font-medium text-gray-900">Markup:</span> +€{{ zone.shippo_markup }}
                </p>
                <p>
                  <span class="font-medium text-gray-900">Consegna:</span>
                  {{ zone.delivery_days_min }}-{{ zone.delivery_days_max }} giorni
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Messaggio se non ci sono zone avanzate -->
      <div v-else class="text-center py-8">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Nessuna zona avanzata</h3>
        <p class="mt-1 text-sm text-gray-500">
          Crea zone di spedizione avanzate con prezzi SHIPPO in tempo reale.
        </p>
        <div class="mt-6">
          <button
            @click="openAdvancedModal"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
          >
            <PlusIcon class="w-4 h-4 mr-2" />
            Crea Zona Avanzata
          </button>
        </div>
      </div>
    </div>


    <!-- Modal Zone Avanzate -->
    <div
      v-if="showAdvancedModal"
      class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
      @click="closeAdvancedModal"
    >
      <div
        class="relative top-10 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white"
        @click.stop
      >
        <div class="mt-3">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ editingAdvancedZone ? 'Modifica Zona Avanzata' : 'Nuova Zona di Spedizione Avanzata' }}
          </h3>
          
          <!-- Componente Selettore Avanzato -->
          <ShippingZoneSelectorAdvanced
            :initial-zone="editingAdvancedZone"
            @done="handleAdvancedZoneDone"
            @cancel="closeAdvancedModal"
          />
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import ShippingZoneSelectorAdvanced from '@/components/ShippingZoneSelectorAdvanced.vue'

const advancedZones = ref([])
const showAdvancedModal = ref(false)
const editingAdvancedZone = ref(null)
const loading = ref(false)

const loadZones = async () => {
  try {
    const response = await fetch('/api/shipping-zones', {
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      }
    })
    
    if (response.ok) {
      const responseData = await response.json()
      console.log('Risposta API zone:', responseData)
      
      // Estrai le zone dalla risposta (può essere responseData.data o responseData direttamente)
      const allZones = responseData.data || responseData
      
      if (Array.isArray(allZones)) {
        // Carica solo zone avanzate (includi anche 'region')
        advancedZones.value = allZones.filter(zone => 
          zone.zone_type && ['worldwide', 'continent', 'country', 'region'].includes(zone.zone_type)
        )
        console.log('Zone avanzate caricate:', advancedZones.value.length)
        if (advancedZones.value.length > 0) {
          console.log('Prima zona caricata:', advancedZones.value[0])
          console.log('is_active della prima zona:', advancedZones.value[0].is_active, typeof advancedZones.value[0].is_active)
        }
      } else {
        console.error('Risposta API non è un array:', allZones)
        advancedZones.value = []
      }
    } else {
      console.error('Errore nel caricamento zone:', response.status)
    }
  } catch (error) {
    console.error('Errore nel caricamento zone:', error)
  }
}

const getZoneTypeLabel = (type) => {
  const labels = {
    'worldwide': 'Mondiale',
    'continent': 'Continentale',
    'country': 'Paese specifico',
    'region': 'Regione'
  }
  return labels[type] || 'Semplice'
}

const getContinentFromCountries = (countries) => {
  if (!countries || countries.length === 0) return 'N/A'
  
  // Mappa dei paesi per continente
  const continentMap = {
    'EU': ['IT', 'FR', 'DE', 'ES', 'GB', 'NL', 'BE', 'AT', 'CH', 'SE', 'NO', 'DK', 'FI', 'PL', 'CZ', 'HU', 'PT', 'GR', 'RO', 'BG', 'HR', 'SI', 'SK', 'LT', 'LV', 'EE', 'IE', 'LU', 'MT', 'CY'],
    'AS': ['CN', 'JP', 'KR', 'IN', 'ID', 'TH', 'VN', 'MY', 'SG', 'PH', 'TW', 'HK', 'MN', 'KZ', 'UZ', 'KG', 'TJ', 'TM', 'AF', 'PK', 'BD', 'LK', 'NP', 'BT', 'MV', 'MM', 'LA', 'KH', 'BN', 'TL'],
    'AM': ['US', 'CA', 'MX', 'BR', 'AR', 'CL', 'CO', 'PE', 'VE', 'EC', 'BO', 'PY', 'UY', 'GY', 'SR', 'GF', 'CR', 'PA', 'GT', 'HN', 'SV', 'NI', 'CU', 'DO', 'HT', 'JM', 'TT', 'BB', 'BS', 'BZ'],
    'AF': ['ZA', 'EG', 'NG', 'KE', 'MA', 'TN', 'DZ', 'LY', 'ET', 'GH', 'CI', 'SN', 'ML', 'BF', 'NE', 'TD', 'SD', 'UG', 'TZ', 'ZW', 'ZM', 'BW', 'NA', 'AO', 'MZ', 'MG', 'MU', 'SC', 'RW', 'BI'],
    'OC': ['AU', 'NZ', 'FJ', 'PG', 'NC', 'VU', 'SB', 'TO', 'WS', 'KI', 'TV', 'NR', 'PW', 'FM', 'MH']
  }
  
  // Controlla quale continente contiene la maggior parte dei paesi
  let maxMatches = 0
  let detectedContinent = 'Misto'
  
  for (const [continent, continentCountries] of Object.entries(continentMap)) {
    const matches = countries.filter(country => continentCountries.includes(country)).length
    if (matches > maxMatches) {
      maxMatches = matches
      detectedContinent = continent
    }
  }
  
  // Mappa i codici continente ai nomi
  const continentNames = {
    'EU': 'Europa',
    'AS': 'Asia',
    'AM': 'America',
    'AF': 'Africa',
    'OC': 'Oceania',
    'Misto': 'Misto'
  }
  
  return continentNames[detectedContinent] || 'Sconosciuto'
}

const openAdvancedModal = () => {
  editingAdvancedZone.value = null
  showAdvancedModal.value = true
}

const openEditAdvancedModal = (zone) => {
  editingAdvancedZone.value = zone
  showAdvancedModal.value = true
}

const closeAdvancedModal = () => {
  showAdvancedModal.value = false
  editingAdvancedZone.value = null
}

        const handleAdvancedZoneDone = async (zoneData) => {
          loading.value = true
          
          try {
            console.log('🔍 zoneData ricevuto:', zoneData)
            console.log('🔍 zoneData.countries:', zoneData.countries)
            console.log('🔍 zoneData.excludedCountries:', zoneData.excludedCountries)
            console.log('🔍 editingAdvancedZone.value:', editingAdvancedZone.value)
    
    // Prepara i dati per il salvataggio
    const saveData = {
      name: zoneData.name,
      country_code: zoneData.option === 'worldwide' ? 'WW' : (zoneData.countries && zoneData.countries.length > 0 ? zoneData.countries[0] : 'EU'),
      zone_type: zoneData.option === 'worldwide' ? 'worldwide' : (zoneData.countries && zoneData.countries.length > 0 ? 'country' : 'continent'),
      is_worldwide: zoneData.option === 'worldwide',
      included_countries: zoneData.option === 'worldwide' ? null : zoneData.countries,
      excluded_countries: zoneData.option === 'worldwide' ? null : zoneData.excludedCountries,
      // Preserva i dati esistenti per le modifiche
      shipping_cost: editingAdvancedZone.value?.shipping_cost || 15.00,
      use_shippo_pricing: editingAdvancedZone.value?.use_shippo_pricing ?? true,
      shippo_service_type: editingAdvancedZone.value?.shippo_service_type || 'standard',
      shippo_markup: editingAdvancedZone.value?.shippo_markup || 0.00,
      delivery_days_min: editingAdvancedZone.value?.delivery_days_min || 7,
      delivery_days_max: editingAdvancedZone.value?.delivery_days_max || 21,
      is_active: editingAdvancedZone.value ? Boolean(editingAdvancedZone.value.is_active ?? true) : true,
      description: editingAdvancedZone.value?.description || `Zona ${zoneData.option} con prezzi SHIPPO`
    }
    
            console.log('🔍 saveData preparato:', saveData)
            console.log('🔍 saveData.is_active:', saveData.is_active, typeof saveData.is_active)
            console.log('🔍 saveData.included_countries:', saveData.included_countries)
            console.log('🔍 saveData.excluded_countries:', saveData.excluded_countries)

    // Controlla se esiste già una zona con lo stesso nome per l'utente corrente
    if (!editingAdvancedZone.value) {
      const existingZone = advancedZones.value.find(zone => 
        zone.name.toLowerCase() === saveData.name.toLowerCase()
      )
      if (existingZone) {
        alert(`Esiste già una zona con il nome "${saveData.name}". Scegli un nome diverso.`)
        return
      }
    }

    const url = editingAdvancedZone.value 
      ? `/api/shipping-zones/${editingAdvancedZone.value.id}`
      : '/api/shipping-zones'
    
    const method = editingAdvancedZone.value ? 'PUT' : 'POST'
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`
      },
      body: JSON.stringify(saveData)
    })
    
            if (response.ok) {
              const result = await response.json()
              console.log('✅ Zona avanzata salvata:', result)
              console.log('✅ is_active salvato:', result.data?.is_active, typeof result.data?.is_active)
              console.log('✅ included_countries salvati:', result.data?.included_countries)
              console.log('✅ excluded_countries salvati:', result.data?.excluded_countries)
              await loadZones()
              closeAdvancedModal()
            } else {
              const error = await response.json()
              console.error('❌ Errore nel salvataggio:', error)
              alert(`Errore: ${error.message}`)
            }
  } catch (error) {
    console.error('Errore nel salvataggio zona avanzata:', error)
    alert('Errore nel salvataggio della zona avanzata')
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
