<template>
  <div class="shipping-zone-selector-advanced">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <h2 class="text-xl font-semibold text-gray-900">Destinazioni</h2>
      <button 
        @click="handleDone"
        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors font-medium"
      >
        {{ initialZone ? 'Aggiorna zona' : 'Salva zona' }}
      </button>
    </div>

    <!-- Nome zona -->
    <div class="mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        Nome della zona *
      </label>
      <input
        v-model="zoneName"
        type="text"
        required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
        placeholder="es. Europa - Spedizione Standard"
      />
    </div>

    <!-- Description -->
    <p class="text-gray-600 mb-6">
      Allarga la tua clientela rendendo il tuo oggetto disponibile agli acquirenti di tutto il mondo.
    </p>

    <!-- Shipping Options -->
    <div class="space-y-4">
      <!-- Worldwide Option -->
      <div class="flex items-start space-x-3">
        <input
          type="radio"
          id="worldwide"
          v-model="selectedOption"
          value="worldwide"
          class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
        />
        <label for="worldwide" class="text-sm font-medium text-gray-900 cursor-pointer">
          Tutto il mondo
        </label>
      </div>

      <!-- Custom Locations Option -->
      <div class="flex items-start space-x-3">
        <input
          type="radio"
          id="custom"
          v-model="selectedOption"
          value="custom"
          class="mt-1 h-4 w-4 text-blue-600 border-gray-300 focus:ring-blue-500"
        />
        <label for="custom" class="text-sm font-medium text-gray-900 cursor-pointer">
          Scegli località personalizzate
        </label>
      </div>

      <!-- Custom Locations List -->
      <div v-if="selectedOption === 'custom'" class="ml-7 space-y-6">
        <!-- Continents Selection -->
        <div class="space-y-3">
          <h3 class="text-sm font-medium text-gray-900">Seleziona continenti:</h3>
          <div class="grid grid-cols-2 gap-3">
            <div 
              v-for="continent in continents" 
              :key="continent.code"
              class="flex items-center space-x-2"
            >
              <input
                type="checkbox"
                :id="`continent-${continent.code}`"
                v-model="selectedContinents"
                :value="continent.code"
                @change="onContinentToggle(continent.code)"
                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              />
              <label 
                :for="`continent-${continent.code}`" 
                class="text-sm text-gray-900 cursor-pointer"
              >
                {{ continent.name }}
              </label>
            </div>
          </div>
        </div>

        <!-- Countries Selection for each selected continent -->
        <div v-if="selectedContinents.length > 0" class="space-y-4">
          <div 
            v-for="continentCode in selectedContinents" 
            :key="continentCode"
            class="border border-gray-200 rounded-lg p-4"
          >
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-sm font-medium text-gray-900">
                {{ getContinentName(continentCode) }}
              </h4>
              <!-- Pulsanti rimossi per evitare click accidentali -->
            </div>

            <!-- Countries Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-60 overflow-y-auto">
              <div 
                v-for="country in getCountriesForContinent(continentCode)" 
                :key="country.code"
                class="flex items-center space-x-2 p-2 hover:bg-gray-50 rounded"
              >
                <input
                  type="checkbox"
                  :id="`country-${country.code}`"
                  :checked="selectedCountries.includes(country.code)"
                  @change="(e) => toggleCountry(country.code, e.target.checked)"
                  class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                />
                <label 
                  :for="`country-${country.code}`" 
                  class="text-xs text-gray-900 cursor-pointer flex items-center justify-between w-full"
                >
                  <span>{{ country.name }}</span>
                </label>
              </div>
            </div>

            <!-- Excluded Countries Summary -->
            <div v-if="getExcludedCountriesForContinent(continentCode).length > 0" class="mt-3 pt-3 border-t border-gray-100">
              <p class="text-xs text-gray-600 mb-2">
                Paesi esclusi ({{ getExcludedCountriesForContinent(continentCode).length }}):
              </p>
              <div class="flex flex-wrap gap-1">
                <span 
                  v-for="countryCode in getExcludedCountriesForContinent(continentCode)" 
                  :key="countryCode"
                  class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-red-100 text-red-800"
                >
                  {{ getCountryName(countryCode) }}
                  <button
                    @click="includeCountry(countryCode)"
                    class="ml-1 text-red-600 hover:text-red-800"
                  >
                    ×
                  </button>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="mt-6 text-center">
      <div class="inline-flex items-center px-4 py-2 text-sm text-gray-600">
        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        Calcolo prezzi in corso...
      </div>
    </div>

    <!-- Error State -->
    <div v-if="error" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-md">
      <div class="flex">
        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Errore</h3>
          <div class="mt-2 text-sm text-red-700">{{ error }}</div>
        </div>
      </div>
    </div>

    <!-- Summary -->
    <div v-if="selectedOption === 'custom' && (selectedContinents.length > 0 || selectedCountries.length > 0)" class="mt-6 p-4 bg-gray-50 rounded-lg">
      <h3 class="text-sm font-medium text-gray-900 mb-2">Riepilogo selezione:</h3>
      <p class="text-xs text-gray-600">
        Continenti selezionati: {{ selectedContinents.length }}<br>
        Paesi inclusi: {{ selectedCountries.length }}<br>
        Paesi esclusi: {{ getTotalExcludedCountries() }}
      </p>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, watch, nextTick, reactive } from 'vue'
import axios from 'axios'

export default {
  name: 'ShippingZoneSelectorAdvanced',
  props: {
    modelValue: {
      type: Object,
      default: () => ({ 
        option: 'worldwide', 
        continents: [], 
        countries: [], 
        excludedCountries: [] 
      })
    },
    initialZone: {
      type: Object,
      default: null
    },
    listingId: {
      type: [String, Number],
      default: null
    }
  },
  emits: ['update:modelValue', 'done'],
  setup(props, { emit }) {
    // Inizializza i campi con i dati della zona esistente se presente
    const zoneName = ref(props.initialZone?.name || '')
    const selectedOption = ref('worldwide')
    const selectedContinents = ref([])
    const selectedCountries = reactive([])
    const loading = ref(false)
    const error = ref(null)

    // Continenti disponibili
    const continents = ref([
      { code: 'EU', name: 'Europa' },
      { code: 'AS', name: 'Asia' },
      { code: 'AM', name: 'America' },
      { code: 'AF', name: 'Africa' },
      { code: 'OC', name: 'Oceania' }
    ])

    // Lista completa dei paesi per continente
    const countriesByContinent = ref({
      EU: [
        { code: 'IT', name: 'Italia' },
        { code: 'FR', name: 'Francia' },
        { code: 'DE', name: 'Germania' },
        { code: 'ES', name: 'Spagna' },
        { code: 'GB', name: 'Regno Unito' },
        { code: 'NL', name: 'Paesi Bassi' },
        { code: 'BE', name: 'Belgio' },
        { code: 'AT', name: 'Austria' },
        { code: 'CH', name: 'Svizzera' },
        { code: 'SE', name: 'Svezia' },
        { code: 'NO', name: 'Norvegia' },
        { code: 'DK', name: 'Danimarca' },
        { code: 'FI', name: 'Finlandia' },
        { code: 'PL', name: 'Polonia' },
        { code: 'CZ', name: 'Repubblica Ceca' },
        { code: 'HU', name: 'Ungheria' },
        { code: 'PT', name: 'Portogallo' },
        { code: 'GR', name: 'Grecia' },
        { code: 'RO', name: 'Romania' },
        { code: 'BG', name: 'Bulgaria' },
        { code: 'HR', name: 'Croazia' },
        { code: 'SI', name: 'Slovenia' },
        { code: 'SK', name: 'Slovacchia' },
        { code: 'LT', name: 'Lituania' },
        { code: 'LV', name: 'Lettonia' },
        { code: 'EE', name: 'Estonia' },
        { code: 'IE', name: 'Irlanda' },
        { code: 'LU', name: 'Lussemburgo' },
        { code: 'MT', name: 'Malta' },
        { code: 'CY', name: 'Cipro' }
      ],
      AS: [
        { code: 'CN', name: 'Cina' },
        { code: 'JP', name: 'Giappone' },
        { code: 'KR', name: 'Corea del Sud' },
        { code: 'IN', name: 'India' },
        { code: 'ID', name: 'Indonesia' },
        { code: 'TH', name: 'Thailandia' },
        { code: 'VN', name: 'Vietnam' },
        { code: 'MY', name: 'Malesia' },
        { code: 'SG', name: 'Singapore' },
        { code: 'PH', name: 'Filippine' },
        { code: 'TW', name: 'Taiwan' },
        { code: 'HK', name: 'Hong Kong' },
        { code: 'MN', name: 'Mongolia' },
        { code: 'KZ', name: 'Kazakistan' },
        { code: 'UZ', name: 'Uzbekistan' },
        { code: 'KG', name: 'Kirghizistan' },
        { code: 'TJ', name: 'Tagikistan' },
        { code: 'TM', name: 'Turkmenistan' },
        { code: 'AF', name: 'Afghanistan' },
        { code: 'PK', name: 'Pakistan' },
        { code: 'BD', name: 'Bangladesh' },
        { code: 'LK', name: 'Sri Lanka' },
        { code: 'NP', name: 'Nepal' },
        { code: 'BT', name: 'Bhutan' },
        { code: 'MV', name: 'Maldive' },
        { code: 'MM', name: 'Myanmar' },
        { code: 'LA', name: 'Laos' },
        { code: 'KH', name: 'Cambogia' },
        { code: 'BN', name: 'Brunei' },
        { code: 'TL', name: 'Timor Est' }
      ],
      AM: [
        { code: 'US', name: 'Stati Uniti' },
        { code: 'CA', name: 'Canada' },
        { code: 'MX', name: 'Messico' },
        { code: 'BR', name: 'Brasile' },
        { code: 'AR', name: 'Argentina' },
        { code: 'CL', name: 'Cile' },
        { code: 'CO', name: 'Colombia' },
        { code: 'PE', name: 'Perù' },
        { code: 'VE', name: 'Venezuela' },
        { code: 'EC', name: 'Ecuador' },
        { code: 'BO', name: 'Bolivia' },
        { code: 'PY', name: 'Paraguay' },
        { code: 'UY', name: 'Uruguay' },
        { code: 'GY', name: 'Guyana' },
        { code: 'SR', name: 'Suriname' },
        { code: 'GF', name: 'Guyana Francese' },
        { code: 'CR', name: 'Costa Rica' },
        { code: 'PA', name: 'Panama' },
        { code: 'GT', name: 'Guatemala' },
        { code: 'HN', name: 'Honduras' },
        { code: 'SV', name: 'El Salvador' },
        { code: 'NI', name: 'Nicaragua' },
        { code: 'CU', name: 'Cuba' },
        { code: 'DO', name: 'Repubblica Dominicana' },
        { code: 'HT', name: 'Haiti' },
        { code: 'JM', name: 'Giamaica' },
        { code: 'TT', name: 'Trinidad e Tobago' },
        { code: 'BB', name: 'Barbados' },
        { code: 'BS', name: 'Bahamas' },
        { code: 'BZ', name: 'Belize' }
      ],
      AF: [
        { code: 'ZA', name: 'Sudafrica' },
        { code: 'EG', name: 'Egitto' },
        { code: 'NG', name: 'Nigeria' },
        { code: 'KE', name: 'Kenya' },
        { code: 'MA', name: 'Marocco' },
        { code: 'TN', name: 'Tunisia' },
        { code: 'DZ', name: 'Algeria' },
        { code: 'LY', name: 'Libia' },
        { code: 'ET', name: 'Etiopia' },
        { code: 'GH', name: 'Ghana' },
        { code: 'CI', name: 'Costa d\'Avorio' },
        { code: 'SN', name: 'Senegal' },
        { code: 'ML', name: 'Mali' },
        { code: 'BF', name: 'Burkina Faso' },
        { code: 'NE', name: 'Niger' },
        { code: 'TD', name: 'Ciad' },
        { code: 'SD', name: 'Sudan' },
        { code: 'UG', name: 'Uganda' },
        { code: 'TZ', name: 'Tanzania' },
        { code: 'ZW', name: 'Zimbabwe' },
        { code: 'ZM', name: 'Zambia' },
        { code: 'BW', name: 'Botswana' },
        { code: 'NA', name: 'Namibia' },
        { code: 'AO', name: 'Angola' },
        { code: 'MZ', name: 'Mozambico' },
        { code: 'MG', name: 'Madagascar' },
        { code: 'MU', name: 'Mauritius' },
        { code: 'SC', name: 'Seychelles' },
        { code: 'RW', name: 'Ruanda' },
        { code: 'BI', name: 'Burundi' }
      ],
      OC: [
        { code: 'AU', name: 'Australia' },
        { code: 'NZ', name: 'Nuova Zelanda' },
        { code: 'FJ', name: 'Fiji' },
        { code: 'PG', name: 'Papua Nuova Guinea' },
        { code: 'NC', name: 'Nuova Caledonia' },
        { code: 'VU', name: 'Vanuatu' },
        { code: 'SB', name: 'Isole Salomone' },
        { code: 'TO', name: 'Tonga' },
        { code: 'WS', name: 'Samoa' },
        { code: 'KI', name: 'Kiribati' },
        { code: 'TV', name: 'Tuvalu' },
        { code: 'NR', name: 'Nauru' },
        { code: 'PW', name: 'Palau' },
        { code: 'FM', name: 'Micronesia' },
        { code: 'MH', name: 'Isole Marshall' }
      ]
    })

    // Carica i prezzi per i paesi - RIMOSSO (prezzi fittizi non necessari)
    const loadCountryPrices = async () => {
      // Non carichiamo più prezzi fittizi durante la creazione delle zone
      // I prezzi reali vengono calcolati dalle API SHIPPO durante il checkout
      console.log('Prezzi non caricati - verranno calcolati dinamicamente da SHIPPO')
    }

    // Funzione rimossa - i prezzi vengono calcolati dinamicamente da SHIPPO

    // Metodi helper
    const getContinentName = (code) => {
      const continent = continents.value.find(c => c.code === code)
      return continent ? continent.name : code
    }

    const getCountriesForContinent = (continentCode) => {
      const countries = countriesByContinent.value[continentCode] || []
      console.log(`getCountriesForContinent(${continentCode}):`, countries.length, 'paesi')
      return countries
    }

    const getCountryName = (countryCode) => {
      for (const countries of Object.values(countriesByContinent.value)) {
        const country = countries.find(c => c.code === countryCode)
        if (country) return country.name
      }
      return countryCode
    }

    const getExcludedCountriesForContinent = (continentCode) => {
      const continentCountries = getCountriesForContinent(continentCode)
      return continentCountries
        .map(c => c.code)
        .filter(code => !selectedCountries.includes(code))
    }

    const getTotalExcludedCountries = () => {
      let total = 0
      for (const continentCode of selectedContinents.value) {
        total += getExcludedCountriesForContinent(continentCode).length
      }
      return total
    }

    // Gestione selezione/deselezione singoli paesi
        const toggleCountry = (countryCode, isChecked) => {
          console.log(`🔍 Toggle paese ${countryCode}:`, isChecked)
          console.log('🔍 selectedCountries PRIMA:', [...selectedCountries])
          
          if (isChecked) {
            // Aggiungi il paese se non è già presente
            if (!selectedCountries.includes(countryCode)) {
              selectedCountries.push(countryCode)
            }
          } else {
            // Rimuovi il paese
            const index = selectedCountries.indexOf(countryCode)
            if (index > -1) {
              selectedCountries.splice(index, 1)
            }
          }
          
          console.log('🔍 selectedCountries DOPO:', [...selectedCountries])
        }

    // Gestione selezione/deselezione continenti
        const onContinentToggle = (continentCode) => {
          console.log('🚨 onContinentToggle chiamata per continente:', continentCode)
          console.log('🚨 selectedCountries PRIMA di onContinentToggle:', [...selectedCountries])
          
          // Aspetta che il v-model aggiorni selectedContinents
          setTimeout(() => {
            const isSelected = selectedContinents.value.includes(continentCode)
            console.log('🚨 Continente', continentCode, 'selezionato:', isSelected)
            
            if (isSelected) {
              // Continente selezionato - aggiungi tutti i suoi paesi
              const continentCountries = getCountriesForContinent(continentCode)
              for (const country of continentCountries) {
                if (!selectedCountries.includes(country.code)) {
                  selectedCountries.push(country.code)
                }
              }
              console.log('🚨 Aggiunti paesi del continente', continentCode, ':', continentCountries.map(c => c.code))
            } else {
              // Continente deselezionato - rimuovi tutti i suoi paesi
              const continentCountries = getCountriesForContinent(continentCode)
              for (const country of continentCountries) {
                const index = selectedCountries.indexOf(country.code)
                if (index > -1) {
                  selectedCountries.splice(index, 1)
                }
              }
              console.log('🚨 Rimossi paesi del continente', continentCode, ':', continentCountries.map(c => c.code))
            }
            
            console.log('🚨 selectedCountries DOPO onContinentToggle:', [...selectedCountries])
          }, 0)
        }

    const selectAllCountries = (continentCode) => {
      const countries = getCountriesForContinent(continentCode)
      for (const country of countries) {
        if (!selectedCountries.includes(country.code)) {
          selectedCountries.push(country.code)
        }
      }
      updateModelValue()
    }

    const deselectAllCountries = (continentCode) => {
      const countries = getCountriesForContinent(continentCode)
      for (const country of countries) {
        const index = selectedCountries.indexOf(country.code)
        if (index > -1) {
          selectedCountries.splice(index, 1)
        }
      }
      updateModelValue()
    }

    const includeCountry = (countryCode) => {
      if (!selectedCountries.includes(countryCode)) {
        selectedCountries.push(countryCode)
        updateModelValue()
      }
    }

    // Aggiorna il valore del modello
    const updateModelValue = () => {
      const value = {
        option: selectedOption.value,
        continents: selectedOption.value === 'custom' ? [...selectedContinents.value] : [],
        countries: selectedOption.value === 'custom' ? [...selectedCountries] : [],
        excludedCountries: selectedOption.value === 'custom' ? 
          Object.values(countriesByContinent.value)
            .flat()
            .map(c => c.code)
            .filter(code => !selectedCountries.includes(code)) : []
      }
      emit('update:modelValue', value)
    }

    // Gestisce il click su "Fine"
        const handleDone = () => {
          if (!zoneName.value.trim()) {
            alert('Inserisci un nome per la zona')
            return
          }
          
          // Crea una copia profonda dei paesi selezionati per evitare interferenze reattive
          const currentSelectedCountries = JSON.parse(JSON.stringify(selectedCountries))
          
          console.log('🔍 Prima di creare zoneData:')
          console.log('🔍 selectedCountries originale:', [...selectedCountries])
          console.log('🔍 currentSelectedCountries copia profonda:', currentSelectedCountries)
          console.log('🔍 selectedContinents:', [...selectedContinents.value])
          
          const zoneData = {
            name: zoneName.value.trim(),
            option: selectedOption.value,
            continents: selectedOption.value === 'custom' ? [...selectedContinents.value] : [],
            countries: selectedOption.value === 'custom' ? currentSelectedCountries : [],
            excludedCountries: selectedOption.value === 'custom' ? 
              Object.values(countriesByContinent.value)
                .flat()
                .map(c => c.code)
                .filter(code => !currentSelectedCountries.includes(code)) : []
          }
          
          console.log('🚀 Emettendo zoneData:', zoneData)
          console.log('🚀 zoneData.countries:', zoneData.countries)
          console.log('🚀 selectedCountries al momento del salvataggio:', currentSelectedCountries)
          
          emit('done', zoneData)
        }

    // Watchers - DISABILITATI per evitare interferenze
    // watch(selectedOption, updateModelValue)
    // watch(selectedContinents, updateModelValue, { deep: true })
    // watch(() => [...selectedCountries], updateModelValue, { deep: true })

    // Inizializza i dati della zona esistente
    const initializeZoneData = () => {
      if (props.initialZone) {
        console.log('Inizializzazione zona esistente:', props.initialZone)
        console.log('included_countries:', props.initialZone.included_countries)
        console.log('excluded_countries:', props.initialZone.excluded_countries)
        
        // Determina l'opzione corretta basata sui dati della zona
        if (props.initialZone.is_worldwide) {
          // Zona worldwide
          selectedOption.value = 'worldwide'
          selectedContinents.value = []
          selectedCountries.splice(0, selectedCountries.length)
        } else if (props.initialZone.included_countries && props.initialZone.included_countries.length > 0) {
          // Zona con paesi inclusi - usa 'custom'
          selectedOption.value = 'custom'
          const countries = props.initialZone.included_countries
          const continentsToSelect = new Set()
          
          // Trova i continenti per i paesi inclusi
          for (const [continentCode, continentCountries] of Object.entries(countriesByContinent.value)) {
            const hasCountriesFromThisContinent = countries.some(countryCode => 
              continentCountries.some(c => c.code === countryCode)
            )
            if (hasCountriesFromThisContinent) {
              continentsToSelect.add(continentCode)
            }
          }
          
          selectedContinents.value = Array.from(continentsToSelect)
          // Usa solo i paesi inclusi dalla zona, non tutti i paesi del continente
          selectedCountries.splice(0, selectedCountries.length, ...countries)
          console.log('Zona con paesi inclusi - selezionati:', {
            continents: [...selectedContinents.value],
            countries: [...selectedCountries],
            included_countries_from_db: countries
          })
        } else if (props.initialZone.zone_type === 'country' && props.initialZone.country_code) {
          // Zona per singolo paese o continente - usa 'custom'
          selectedOption.value = 'custom'
          
          if (props.initialZone.country_code === 'EU') {
            // Se è 'EU', seleziona il continente Europa ma solo i paesi inclusi
            selectedContinents.value = ['EU']
            // Usa i paesi inclusi dalla zona, non tutti i paesi del continente
            let includedCountries = props.initialZone.included_countries || []
            
            // Fallback: se included_countries è null/empty, usa alcuni paesi europei di default
            if (includedCountries.length === 0) {
              includedCountries = ['IT', 'FR', 'DE', 'ES', 'GB', 'NL', 'BE', 'AT', 'CH']
              console.log('⚠️ included_countries vuoto, usando paesi di default:', includedCountries)
            }
            
            selectedCountries.splice(0, selectedCountries.length, ...includedCountries)
            console.log('Zona EU - selezionati:', {
              continents: [...selectedContinents.value],
              countries: [...selectedCountries].slice(0, 5), // Mostra solo i primi 5
              included_countries_from_db: props.initialZone.included_countries,
              included_countries_used: includedCountries
            })
          } else {
            // Se è un paese specifico, seleziona solo quel paese
            selectedContinents.value = []
            selectedCountries.splice(0, selectedCountries.length, props.initialZone.country_code)
            
            // Trova il continente per questo paese
            for (const [continentCode, continentCountries] of Object.entries(countriesByContinent.value)) {
              const hasThisCountry = continentCountries.some(c => c.code === props.initialZone.country_code)
              if (hasThisCountry) {
                selectedContinents.value = [continentCode]
                break
              }
            }
          }
        } else {
          // Zona continent o altro tipo
          selectedOption.value = props.initialZone.zone_type || 'worldwide'
          selectedContinents.value = []
          selectedCountries.splice(0, selectedCountries.length)
        }
        
        console.log('Zone inizializzata:', {
          option: selectedOption.value,
          continents: [...selectedContinents.value],
          countries: [...selectedCountries],
          countriesByContinent: Object.keys(countriesByContinent.value)
        })
        
        // Debug: verifica che i valori siano stati impostati
        setTimeout(() => {
          console.log('Verifica valori dopo inizializzazione:', {
            selectedOption: selectedOption.value,
            selectedContinents: [...selectedContinents.value],
            selectedCountries: [...selectedCountries]
          })
        }, 100)
      }
    }

    // Lifecycle
    onMounted(async () => {
      // Inizializza i dati dopo che i continenti sono stati caricati
      // Usa nextTick per assicurarsi che il DOM sia aggiornato
      await nextTick()
      initializeZoneData()
      
      // Forza un aggiornamento del DOM dopo l'inizializzazione
      await nextTick()
      console.log('DOM aggiornato dopo inizializzazione')
    })

    return {
      zoneName,
      selectedOption,
      selectedContinents,
      selectedCountries,
      loading,
      error,
      continents,
      countriesByContinent,
      getContinentName,
      getCountriesForContinent,
      getCountryName,
      getExcludedCountriesForContinent,
      getTotalExcludedCountries,
      toggleCountry,
      onContinentToggle,
      selectAllCountries,
      deselectAllCountries,
      includeCountry,
      handleDone,
      initialZone: props.initialZone
    }
  }
}
</script>

<style scoped>
.shipping-zone-selector-advanced {
  @apply max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-sm;
}

/* Stili per radio buttons personalizzati */
input[type="radio"] {
  @apply appearance-none w-4 h-4 border-2 border-gray-300 rounded-full;
}

input[type="radio"]:checked {
  @apply border-blue-600 bg-blue-600;
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3ccircle cx='8' cy='8' r='3'/%3e%3c/svg%3e");
}

/* Stili per checkbox personalizzati */
input[type="checkbox"] {
  @apply appearance-none w-4 h-4 border-2 border-gray-300 rounded;
}

input[type="checkbox"]:checked {
  @apply border-blue-600 bg-blue-600;
  background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='m13.854 3.646-7.5 7.5a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6 10.293l7.146-7.147a.5.5 0 0 1 .708.708z'/%3e%3c/svg%3e");
}
</style>
