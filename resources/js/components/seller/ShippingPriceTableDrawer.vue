<template>
  <Teleport to="body">
    <TransitionRoot :show="open" as="template">
      <Dialog as="div" class="relative z-50" @close="close">
        <TransitionChild
          as="template"
          enter="ease-in-out duration-300"
          enter-from="opacity-0"
          enter-to="opacity-100"
          leave="ease-in-out duration-300"
          leave-from="opacity-100"
          leave-to="opacity-0"
        >
          <div class="fixed inset-0 bg-gray-500/75 transition-opacity" />
        </TransitionChild>
        <div class="fixed inset-0 overflow-hidden">
          <div class="absolute inset-0 overflow-hidden">
            <!-- Mobile: full width senza padding. Desktop: pannello a destra con pl-10 -->
            <div class="pointer-events-none fixed inset-y-0 right-0 flex w-full max-w-full pl-0 sm:pl-10">
              <TransitionChild
                as="template"
                enter="transform transition ease-in-out duration-300"
                enter-from="translate-x-full"
                enter-to="translate-x-0"
                leave="transform transition ease-in-out duration-300"
                leave-from="translate-x-0"
                leave-to="translate-x-full"
              >
                <DialogPanel class="pointer-events-auto w-full max-w-full sm:w-screen sm:max-w-2xl">
                  <div class="flex h-full flex-col bg-white shadow-xl min-w-0">
                    <div class="shrink-0 px-4 sm:px-6 py-4 border-b border-gray-200">
                      <div class="flex items-center justify-between gap-2 min-w-0">
                        <DialogTitle class="text-base sm:text-lg font-semibold text-gray-900 truncate min-w-0">
                          {{ isEdit ? 'Modifica tabella prezzi' : 'Nuova tabella prezzi' }}
                        </DialogTitle>
                        <button type="button" @click="close" class="shrink-0 rounded-md p-1 text-gray-400 hover:text-gray-600" aria-label="Chiudi">
                          <XMarkIcon class="h-6 w-6" />
                        </button>
                      </div>
                      <!-- Step indicator -->
                      <nav class="mt-4 flex gap-2" aria-label="Progress">
                        <span
                          v-for="s in 4"
                          :key="s"
                          :class="[
                            step >= s ? 'bg-primary text-white' : 'bg-gray-200 text-gray-500',
                            'rounded-full px-2 py-0.5 text-xs font-medium'
                          ]"
                        >
                          {{ s }}
                        </span>
                      </nav>
                    </div>
                    <div class="flex-1 overflow-y-auto overflow-x-hidden px-4 sm:px-6 py-4 min-w-0">
                      <!-- Step 1: Nome -->
                      <div v-show="step === 1" class="space-y-4">
                        <h3 class="text-sm font-medium text-gray-900">Nome tabella</h3>
                        <input
                          v-model="form.name"
                          type="text"
                          maxlength="255"
                          placeholder="es. Europa Standard, Extra UE"
                          class="block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary"
                        />
                        <p class="text-xs text-gray-500">{{ (form.name || '').length }} / 255</p>
                      </div>

                      <!-- Step 2: Paesi -->
                      <div v-show="step === 2" class="space-y-4">
                        <h3 class="text-sm font-medium text-gray-900">Paesi (Applica a)</h3>
                        <p class="text-xs text-gray-500">Paesi già in altre tabelle non sono selezionabili.</p>
                        <div v-if="isEdit && currentCountryCodes.length" class="mb-3">
                          <span class="text-xs font-medium text-gray-700">Paesi attuali:</span>
                          <div class="mt-1 flex flex-wrap gap-1">
                            <span
                              v-for="code in currentCountryCodes"
                              :key="code"
                              class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800"
                            >
                              {{ code }}
                            </span>
                          </div>
                        </div>
                        <div class="space-y-2 max-h-80 overflow-y-auto border border-gray-200 rounded-md p-3">
                          <div v-for="(countries, continentCode) in countriesByContinent" :key="continentCode" class="mb-4">
                            <p class="text-xs font-semibold text-gray-700 mb-2">{{ continentLabels[continentCode] }}</p>
                            <div class="flex flex-wrap gap-2">
                              <label
                                v-for="c in countries"
                                :key="c.code"
                                :class="[
                                  countryDisabledForDisplay(c.code) ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
                                  'inline-flex items-center rounded-full px-3 py-1 text-xs font-medium border'
                                ]"
                              >
                                <input
                                  v-model="form.countries"
                                  type="checkbox"
                                  :value="c.code"
                                  :disabled="countryDisabledForDisplay(c.code)"
                                  class="sr-only"
                                />
                                <span
                                  :class="form.countries.includes(c.code) ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-300'"
                                  class="rounded-full px-3 py-1 border"
                                >
                                  {{ c.code }} – {{ c.name }}
                                  <span v-if="countryUsedInTable(c.code)" class="text-gray-400">(già in altra tabella)</span>
                                </span>
                              </label>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Step 3: Matrice prezzi -->
                      <div v-show="step === 3" class="space-y-4">
                        <h3 class="text-sm font-medium text-gray-900">Matrice prezzi (€)</h3>
                        <p class="text-xs text-gray-500 break-words">Vuoto = disabilitato. UNTRACKED solo LETTER.</p>
                        <div class="overflow-x-auto -mx-1 overflow-y-visible">
                          <table class="w-full divide-y divide-gray-200 text-xs sm:text-sm border-collapse" style="min-width: 280px;">
                            <thead>
                              <tr>
                                <th class="py-1.5 pr-2 pl-1 text-left font-medium text-gray-700 whitespace-nowrap bg-white sticky left-0 z-10 border-r border-gray-100">Metodo</th>
                                <th v-for="b in buckets" :key="b" class="py-1.5 px-1 text-center font-medium text-gray-700 whitespace-nowrap">{{ bucketLabelShort(b) }}</th>
                              </tr>
                            </thead>
                            <tbody>
                              <tr v-for="method in methods" :key="method" class="border-t border-gray-100">
                                <td class="py-1 pr-2 pl-1 text-gray-700 whitespace-nowrap bg-white sticky left-0 z-10 border-r border-gray-100" :title="methodLabel(method)">{{ methodLabelShort(method) }}</td>
                                <td v-for="bucket in buckets" :key="bucket" class="py-1 px-1">
                                  <input
                                    v-if="method === 'UNTRACKED_STANDARD' && bucket !== 'LETTER'"
                                    type="text"
                                    disabled
                                    placeholder="–"
                                    title="UNTRACKED solo per LETTER"
                                    class="w-11 sm:w-14 min-w-0 max-w-[4rem] rounded border border-gray-200 bg-gray-100 px-1 py-0.5 sm:px-2 sm:py-1 text-center text-gray-400 cursor-not-allowed text-xs"
                                  />
                                  <input
                                    v-else
                                    v-model.number="form.rates[rateKey(method, bucket)]"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    placeholder="0"
                                    class="w-11 sm:w-14 min-w-0 max-w-[4rem] rounded border border-gray-300 px-1 py-0.5 sm:px-2 sm:py-1 text-center text-xs focus:border-primary focus:ring-1 focus:ring-primary"
                                  />
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>

                      <!-- Step 4: Assicurazione -->
                      <div v-show="step === 4" class="space-y-4">
                        <h3 class="text-sm font-medium text-gray-900">Assicurazione</h3>
                        <p class="text-xs text-gray-500">
                          Costo calcolato automaticamente (1,2% – minimo 5 €). Abilita solo per bucket in cui hai almeno un prezzo tracciato.
                        </p>
                        <div class="space-y-3">
                          <div
                            v-for="bucket in buckets"
                            :key="bucket"
                            class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3"
                          >
                            <span class="font-medium text-gray-900">{{ bucketLabel(bucket) }}</span>
                            <label class="relative inline-flex cursor-pointer items-center">
                              <input
                                v-model="form.insured[bucket]"
                                type="checkbox"
                                :disabled="!hasTrackedPriceForBucket(bucket)"
                                class="sr-only peer"
                              />
                              <div
                                :class="[
                                  'h-6 w-11 rounded-full bg-gray-200 peer-focus:ring-2 peer-focus:ring-primary',
                                  form.insured[bucket] ? 'bg-primary' : '',
                                  !hasTrackedPriceForBucket(bucket) ? 'opacity-50 cursor-not-allowed' : ''
                                ]"
                              />
                              <span
                                :class="[
                                  'ml-2 text-sm font-medium',
                                  !hasTrackedPriceForBucket(bucket) ? 'text-gray-400' : 'text-gray-700'
                                ]"
                              >
                                {{ form.insured[bucket] ? 'Sì' : 'No' }}
                              </span>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="shrink-0 border-t border-gray-200 px-4 sm:px-6 py-4 flex flex-wrap items-center justify-between gap-2">
                      <button
                        v-if="step > 1"
                        type="button"
                        @click="step--"
                        class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                      >
                        Indietro
                      </button>
                      <div v-else />
                      <div class="flex flex-wrap gap-2 justify-end">
                        <button type="button" @click="close" class="rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                          Annulla
                        </button>
                        <button
                          v-if="step < 4"
                          type="button"
                          @click="goNext"
                          :disabled="!canProceedStep"
                          class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          Avanti
                        </button>
                        <button
                          v-else
                          type="button"
                          @click="saveAll"
                          :disabled="!canSaveFinal || saving"
                          class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                          {{ saving ? 'Salvataggio...' : 'Salva' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </DialogPanel>
              </TransitionChild>
            </div>
          </div>
        </div>
      </Dialog>
    </TransitionRoot>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionRoot, TransitionChild } from '@headlessui/vue'
import { XMarkIcon } from '@heroicons/vue/24/outline'
import { countriesByContinent, continentLabels } from '@/data/countries.js'
import axios from 'axios'

const BUCKETS = ['LETTER', 'PARCEL_S', 'PARCEL_M', 'PARCEL_L']
const METHODS = ['UNTRACKED_STANDARD', 'TRACKED_STANDARD', 'TRACKED_EXPRESS']

const props = defineProps({
  open: { type: Boolean, default: false },
  tableId: { type: Number, default: null },
  tableData: { type: Object, default: null },
  allTables: { type: Array, default: () => [] }
})

const emit = defineEmits(['update:open', 'saved', 'closed'])

const step = ref(1)
const saving = ref(false)
const createdTableId = ref(null)
const form = ref({
  name: '',
  countries: [],
  rates: {},
  insured: { LETTER: false, PARCEL_S: false, PARCEL_M: false, PARCEL_L: false }
})

const isEdit = computed(() => !!props.tableId)
const currentTableId = computed(() => props.tableId || createdTableId.value)

const currentCountryCodes = computed(() => {
  if (!props.tableData?.countries) return []
  return props.tableData.countries.map(c => c.country_code)
})

function rateKey (method, bucket) {
  return `${method}|${bucket}`
}

const buckets = BUCKETS
const methods = METHODS

function bucketLabel (b) {
  const labels = { LETTER: 'LETTER', PARCEL_S: 'PARCEL S', PARCEL_M: 'PARCEL M', PARCEL_L: 'PARCEL L' }
  return labels[b] || b
}

/** Etichette brevi per la matrice (mobile) */
function bucketLabelShort (b) {
  const labels = { LETTER: 'LETTER', PARCEL_S: 'P.S', PARCEL_M: 'P.M', PARCEL_L: 'P.L' }
  return labels[b] || b
}

function methodLabel (m) {
  const labels = {
    UNTRACKED_STANDARD: 'Non tracciata',
    TRACKED_STANDARD: 'Tracciata standard',
    TRACKED_EXPRESS: 'Tracciata express'
  }
  return labels[m] || m
}

/** Etichette brevi per la matrice (mobile) */
function methodLabelShort (m) {
  const labels = {
    UNTRACKED_STANDARD: 'Non tracc.',
    TRACKED_STANDARD: 'Tracc. std',
    TRACKED_EXPRESS: 'Tracc. expr'
  }
  return labels[m] || m
}

const usedCountryCodesByOtherTables = computed(() => {
  const used = new Set()
  const currentId = currentTableId.value
  for (const t of (props.allTables || [])) {
    // Escludi la tabella in modifica (confronto loose per number vs string dall'API)
    if (t.id == currentId) continue
    for (const c of t.countries || []) {
      used.add(String(c.country_code || c).toUpperCase())
    }
  }
  // In modifica: togli esplicitamente i paesi della tabella corrente, così restano sempre modificabili
  if (isEdit.value && currentCountryCodes.value.length) {
    for (const code of currentCountryCodes.value) {
      used.delete(String(code).toUpperCase())
    }
  }
  return used
})

function isCountryDisabled (code) {
  const codeUpper = String(code).toUpperCase()
  return usedCountryCodesByOtherTables.value.has(codeUpper)
}

/** Usato in template: in modifica i paesi già selezionati non sono mai disabilitati */
function countryDisabledForDisplay (code) {
  if (isEdit.value && (form.value.countries || []).some(c => String(c).toUpperCase() === String(code).toUpperCase())) {
    return false
  }
  return isCountryDisabled(code)
}

function countryUsedInTable (code) {
  return usedCountryCodesByOtherTables.value.has(code)
}

const canProceedStep = computed(() => {
  if (step.value === 1) return (form.value.name || '').trim().length > 0 && (form.value.name || '').trim().length <= 255
  if (step.value === 2) return form.value.countries.length >= 1
  if (step.value === 3) return true
  return true
})

function hasTrackedPriceForBucket (bucket) {
  const tracked = ['TRACKED_STANDARD', 'TRACKED_EXPRESS']
  for (const m of tracked) {
    const v = form.value.rates[rateKey(m, bucket)]
    if (v != null && v !== '' && Number(v) >= 0) return true
  }
  return false
}

const canSaveFinal = computed(() => {
  const hasCountry = form.value.countries.length >= 1
  const noDup = new Set(form.value.countries).size === form.value.countries.length
  return hasCountry && noDup
})

function resetForm () {
  step.value = 1
  createdTableId.value = null
  form.value = {
    name: '',
    countries: [],
    rates: {},
    insured: { LETTER: false, PARCEL_S: false, PARCEL_M: false, PARCEL_L: false }
  }
  const r = {}
  for (const m of METHODS) {
    for (const b of BUCKETS) {
      if (m === 'UNTRACKED_STANDARD' && b !== 'LETTER') continue
      r[rateKey(m, b)] = null
    }
  }
  form.value.rates = r
}

function fillFromTable (data) {
  if (!data) return
  form.value.name = data.name || ''
  form.value.countries = (data.countries || []).map(c => c.country_code)
  const r = {}
  for (const m of METHODS) {
    for (const b of BUCKETS) {
      if (m === 'UNTRACKED_STANDARD' && b !== 'LETTER') continue
      const rate = (data.rates || []).find(x => x.package_bucket === b && x.shipping_method === m)
      r[rateKey(m, b)] = rate?.price_eur != null ? Number(rate.price_eur) : null
    }
  }
  form.value.rates = r
  form.value.insured = { LETTER: false, PARCEL_S: false, PARCEL_M: false, PARCEL_L: false }
  for (const ic of (data.insured_configs || [])) {
    if (ic.package_bucket && ic.enabled) form.value.insured[ic.package_bucket] = true
  }
}

watch(() => props.open, (val) => {
  if (val) {
    resetForm()
    if (props.tableData) fillFromTable(props.tableData)
    else {
      const r = {}
      for (const m of METHODS) {
        for (const b of BUCKETS) {
          if (m === 'UNTRACKED_STANDARD' && b !== 'LETTER') continue
          r[rateKey(m, b)] = null
        }
      }
      form.value.rates = r
    }
    step.value = 1
  }
})

watch(() => props.tableData, (val) => {
  if (props.open && val) fillFromTable(val)
}, { deep: true })

function close () {
  emit('update:open', false)
  emit('closed')
}

async function goNext () {
  if (step.value === 1) {
    if (isEdit.value) {
      try {
        await axios.put(`/api/seller/shipping/price-tables/${props.tableId}`, { name: form.value.name.trim() })
      } catch (e) {
        alert(e.response?.data?.message || 'Errore aggiornamento nome')
        return
      }
    } else {
      try {
        const { data } = await axios.post('/api/seller/shipping/price-tables', { name: form.value.name.trim() })
        if (data.success && data.data?.id) createdTableId.value = data.data.id
        else {
          alert(data.message || 'Errore creazione tabella')
          return
        }
      } catch (e) {
        alert(e.response?.data?.message || 'Errore creazione tabella')
        return
      }
    }
  }
  if (step.value === 2 && currentTableId.value) {
    try {
      await axios.put(`/api/seller/shipping/price-tables/${currentTableId.value}/countries`, {
        countries: form.value.countries.map(c => String(c).toUpperCase())
      })
    } catch (e) {
      alert(e.response?.data?.message || 'Errore sincronizzazione paesi')
      return
    }
  }
  if (step.value === 3 && currentTableId.value) {
    const rates = []
    for (const m of METHODS) {
      for (const b of BUCKETS) {
        if (m === 'UNTRACKED_STANDARD' && b !== 'LETTER') continue
        const v = form.value.rates[rateKey(m, b)]
        rates.push({
          package_bucket: b,
          shipping_method: m,
          price_eur: v != null && v !== '' ? Math.max(0, Number(v)) : null
        })
      }
    }
    try {
      await axios.post(`/api/seller/shipping/price-tables/${currentTableId.value}/rates`, { rates })
    } catch (e) {
      alert(e.response?.data?.message || 'Errore salvataggio tariffe')
      return
    }
  }
  if (step.value === 4 && currentTableId.value) {
    const configs = BUCKETS.map(bucket => ({
      package_bucket: bucket,
      enabled: !!form.value.insured[bucket]
    }))
    try {
      await axios.post(`/api/seller/shipping/price-tables/${currentTableId.value}/insured`, { configs })
    } catch (e) {
      alert(e.response?.data?.message || 'Errore configurazione assicurazione')
      return
    }
  }
  if (step.value < 4) step.value++
}

async function saveAll () {
  if (step.value !== 4 || !currentTableId.value) return
  saving.value = true
  try {
    await axios.put(`/api/seller/shipping/price-tables/${currentTableId.value}/countries`, {
      countries: form.value.countries.map(c => String(c).toUpperCase())
    })
    const rates = []
    for (const m of METHODS) {
      for (const b of BUCKETS) {
        if (m === 'UNTRACKED_STANDARD' && b !== 'LETTER') continue
        const v = form.value.rates[rateKey(m, b)]
        rates.push({
          package_bucket: b,
          shipping_method: m,
          price_eur: v != null && v !== '' ? Math.max(0, Number(v)) : null
        })
      }
    }
    await axios.post(`/api/seller/shipping/price-tables/${currentTableId.value}/rates`, { rates })
    const configs = BUCKETS.map(bucket => ({
      package_bucket: bucket,
      enabled: !!form.value.insured[bucket]
    }))
    await axios.post(`/api/seller/shipping/price-tables/${currentTableId.value}/insured`, { configs })
    emit('saved')
    close()
  } catch (e) {
    alert(e.response?.data?.message || 'Errore salvataggio')
  } finally {
    saving.value = false
  }
}

</script>
