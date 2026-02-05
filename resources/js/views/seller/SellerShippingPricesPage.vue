<template>
  <DashboardLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-futura-bold text-gray-900 mb-2">Prezzi spedizioni</h1>
      <p class="text-gray-600 font-gill-sans">
        Configura le tabelle prezzi per le tue spedizioni (CardSwap V1). Qui decidi quanto far pagare per lettera, pacco e metodi tracciati.
      </p>
    </div>

    <!-- Alert: paesi senza tabella -->
    <div v-if="showCountriesWithoutTableAlert" class="mb-6 rounded-md bg-amber-50 p-4 border border-amber-200">
      <div class="flex">
        <ExclamationTriangleIcon class="h-5 w-5 text-amber-500 shrink-0" />
        <div class="ml-3">
          <h3 class="text-sm font-medium text-amber-800">Configurazione incompleta</h3>
          <p class="mt-1 text-sm text-amber-700">
            Hai tabelle senza paesi associati o nessuna tabella. Aggiungi almeno una tabella e assegna paesi per poter ricevere ordini con spedizione.
          </p>
        </div>
      </div>
    </div>

    <!-- CTA Aggiungi tabella -->
    <div class="mb-6 flex items-center justify-between">
      <span class="text-sm text-gray-500">{{ tables.length }} / 5 tabelle</span>
      <button
        v-if="tables.length < 5"
        type="button"
        @click="openDrawer()"
        class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
      >
        <PlusIcon class="-ml-0.5 mr-2 h-5 w-5" />
        Aggiungi tabella
      </button>
    </div>

    <!-- Lista tabelle -->
    <div class="space-y-4">
      <div
        v-for="table in tables"
        :key="table.id"
        class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm"
      >
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h3 class="text-lg font-semibold text-gray-900">{{ table.name }}</h3>
            <div class="mt-2 flex flex-wrap gap-1">
              <span
                v-for="c in table.countries"
                :key="c.id"
                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-800"
              >
                {{ c.country_code }}
              </span>
              <span v-if="!table.countries || table.countries.length === 0" class="text-xs text-gray-500">Nessun paese</span>
            </div>
            <p class="mt-2 text-sm">
              <span
                :class="tableStatusClass(table)"
                class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
              >
                {{ tableStatusLabel(table) }}
              </span>
            </p>
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="openDrawer(table)"
              class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
            >
              <PencilSquareIcon class="-ml-0.5 mr-1.5 h-4 w-4 text-gray-500" />
              Modifica
            </button>
            <button
              type="button"
              @click="duplicateTable(table)"
              class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
            >
              <DocumentDuplicateIcon class="-ml-0.5 mr-1.5 h-4 w-4 text-gray-500" />
              Duplica
            </button>
            <button
              type="button"
              @click="confirmDelete(table)"
              class="inline-flex items-center rounded-md bg-white px-3 py-1.5 text-sm font-medium text-red-700 shadow-sm ring-1 ring-inset ring-red-300 hover:bg-red-50"
            >
              <TrashIcon class="-ml-0.5 mr-1.5 h-4 w-4" />
              Elimina
            </button>
          </div>
        </div>
      </div>

      <div v-if="tables.length === 0 && !loading" class="rounded-lg border-2 border-dashed border-gray-300 p-12 text-center">
        <TruckIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-semibold text-gray-900">Nessuna tabella prezzi</h3>
        <p class="mt-1 text-sm text-gray-500">Crea la prima tabella per definire prezzi e paesi di spedizione.</p>
        <button
          type="button"
          @click="openDrawer()"
          class="mt-4 inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-dark"
        >
          <PlusIcon class="-ml-0.5 mr-2 h-5 w-5" />
          Aggiungi tabella
        </button>
      </div>
    </div>

    <div v-if="loading" class="py-12 text-center text-gray-500">Caricamento...</div>

    <!-- Drawer Crea/Modifica -->
    <ShippingPriceTableDrawer
      v-model:open="drawerOpen"
      :table-id="editingTableId"
      :table-data="editingTableData"
      :all-tables="tables"
      @saved="onDrawerSaved"
      @closed="onDrawerClosed"
    />

    <!-- Modal conferma elimina -->
    <ConfirmDeleteModal
      v-model:open="deleteModalOpen"
      :table-name="tableToDelete?.name"
      @confirm="doDelete"
    />
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  PlusIcon,
  PencilSquareIcon,
  DocumentDuplicateIcon,
  TrashIcon,
  TruckIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import ShippingPriceTableDrawer from '@/components/seller/ShippingPriceTableDrawer.vue'
import ConfirmDeleteModal from '@/components/seller/ConfirmDeletePriceTableModal.vue'
import axios from 'axios'

const tables = ref([])
const loading = ref(true)
const drawerOpen = ref(false)
const editingTableId = ref(null)
const editingTableData = ref(null)
const deleteModalOpen = ref(false)
const tableToDelete = ref(null)

const MAX_TABLES = 5

function tableStatusLabel (table) {
  const hasCountries = table.countries && table.countries.length > 0
  const hasRates = table.rates && table.rates.length > 0
  const hasAtLeastOnePrice = table.rates?.some(r => r.price_eur != null && Number(r.price_eur) > 0)
  if (!hasCountries) return 'Incompleta (nessun paese)'
  if (!hasRates || !hasAtLeastOnePrice) return 'Incompleta (nessun prezzo)'
  return 'Completa'
}

function tableStatusClass (table) {
  const label = tableStatusLabel(table)
  if (label === 'Completa') return 'bg-green-100 text-green-800'
  return 'bg-amber-100 text-amber-800'
}

const showCountriesWithoutTableAlert = computed(() => {
  if (tables.value.length === 0) return true
  const hasIncomplete = tables.value.some(t => {
    const hasCountries = t.countries && t.countries.length > 0
    return !hasCountries
  })
  const allEmpty = tables.value.every(t => !t.countries || t.countries.length === 0)
  return hasIncomplete || allEmpty
})

async function loadTables () {
  loading.value = true
  try {
    const { data } = await axios.get('/api/seller/shipping/price-tables')
    if (data.success && data.data) {
      tables.value = data.data
    }
  } catch (e) {
    console.error('Errore caricamento tabelle prezzi', e)
  } finally {
    loading.value = false
  }
}

function openDrawer (table = null) {
  if (table) {
    editingTableId.value = table.id
    editingTableData.value = { ...table }
  } else {
    editingTableId.value = null
    editingTableData.value = null
  }
  drawerOpen.value = true
}

function onDrawerSaved () {
  loadTables()
  drawerOpen.value = false
  editingTableId.value = null
  editingTableData.value = null
}

function onDrawerClosed () {
  drawerOpen.value = false
  editingTableId.value = null
  editingTableData.value = null
}

async function duplicateTable (table) {
  if (tables.value.length >= MAX_TABLES) {
    alert(`Puoi avere al massimo ${MAX_TABLES} tabelle. Elimina una tabella per duplicare.`)
    return
  }
  try {
    const name = `Copia di ${table.name}`
    const { data: createData } = await axios.post('/api/seller/shipping/price-tables', { name })
    if (!createData.success || !createData.data?.id) {
      alert(createData.message || 'Errore creazione tabella')
      return
    }
    const newId = createData.data.id
    const countryCodes = (table.countries || []).map(c => c.country_code)
    if (countryCodes.length > 0) {
      await axios.post(`/api/seller/shipping/price-tables/${newId}/countries`, { countries: countryCodes })
    }
    if (table.rates && table.rates.length > 0) {
      const rates = table.rates
        .filter(r => r.price_eur != null)
        .map(r => ({ package_bucket: r.package_bucket, shipping_method: r.shipping_method, price_eur: r.price_eur }))
      if (rates.length > 0) {
        await axios.post(`/api/seller/shipping/price-tables/${newId}/rates`, { rates })
      }
    }
    if (table.insured_configs && table.insured_configs.length > 0) {
      const configs = table.insured_configs.map(c => ({ package_bucket: c.package_bucket, enabled: c.enabled }))
      await axios.post(`/api/seller/shipping/price-tables/${newId}/insured`, { configs })
    }
    await loadTables()
  } catch (e) {
    console.error('Errore duplicazione tabella', e)
    alert(e.response?.data?.message || 'Errore durante la duplicazione')
  }
}

function confirmDelete (table) {
  tableToDelete.value = table
  deleteModalOpen.value = true
}

async function doDelete () {
  if (!tableToDelete.value) return
  try {
    await axios.delete(`/api/seller/shipping/price-tables/${tableToDelete.value.id}`)
    await loadTables()
    deleteModalOpen.value = false
    tableToDelete.value = null
  } catch (e) {
    alert(e.response?.data?.message || 'Errore eliminazione')
  }
}

onMounted(() => {
  loadTables()
})
</script>
