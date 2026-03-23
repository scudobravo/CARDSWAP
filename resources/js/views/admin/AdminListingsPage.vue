<template>
  <DashboardLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-futura-bold text-gray-900 mb-2">Inserzioni</h1>
      <p class="text-gray-600 font-gill-sans">Moderazione e stato delle inserzioni</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div>
          <label class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">Stato</label>
          <select v-model="filters.status" class="w-full rounded-md border-gray-300 text-sm font-gill-sans">
            <option value="">Tutti</option>
            <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">Ricerca</label>
          <input v-model="filters.search" type="text" placeholder="Titolo, ID, venditore…" class="w-full rounded-md border-gray-300 text-sm font-gill-sans" @keyup.enter="loadPage(1)" />
        </div>
        <div>
          <label class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">Da data</label>
          <input v-model="filters.date_from" type="date" class="w-full rounded-md border-gray-300 text-sm font-gill-sans" />
        </div>
        <div>
          <label class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">A data</label>
          <input v-model="filters.date_to" type="date" class="w-full rounded-md border-gray-300 text-sm font-gill-sans" />
        </div>
      </div>
      <div class="flex flex-wrap gap-2">
        <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm font-gill-sans-semibold text-white hover:opacity-90" @click="loadPage(1)">Applica filtri</button>
        <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-gill-sans text-gray-700" @click="resetFilters">Reset</button>
      </div>
    </div>

    <div v-if="error" class="rounded-md bg-red-50 p-4 text-red-800 text-sm font-gill-sans mb-4">{{ error }}</div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary" />
    </div>

    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">ID</th>
              <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Titolo</th>
              <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Venditore</th>
              <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Prezzo</th>
              <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Stato</th>
              <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Azioni</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="row in rows" :key="row.id" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm font-gill-sans text-gray-900">{{ row.id }}</td>
              <td class="px-4 py-3 text-sm font-gill-sans text-gray-900 max-w-xs truncate">{{ row.title || '—' }}</td>
              <td class="px-4 py-3 text-sm font-gill-sans text-gray-600">{{ row.seller?.username || row.seller?.email || '—' }}</td>
              <td class="px-4 py-3 text-sm font-gill-sans">€ {{ Number(row.price).toFixed(2) }}</td>
              <td class="px-4 py-3">
                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-800">{{ row.status }}</span>
              </td>
              <td class="px-4 py-3">
                <button type="button" class="text-primary text-sm font-gill-sans-semibold hover:underline" @click="openEdit(row)">Modifica stato</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t border-gray-200">
        <span class="text-sm text-gray-600 font-gill-sans">Pagina {{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button type="button" :disabled="pagination.current_page <= 1" class="rounded border px-3 py-1 text-sm disabled:opacity-40" @click="loadPage(pagination.current_page - 1)">Precedente</button>
          <button type="button" :disabled="pagination.current_page >= pagination.last_page" class="rounded border px-3 py-1 text-sm disabled:opacity-40" @click="loadPage(pagination.current_page + 1)">Successiva</button>
        </div>
      </div>
    </div>

    <div v-if="modal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="modal.open = false">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h2 class="text-lg font-futura-bold text-gray-900 mb-4">Inserzione #{{ modal.row?.id }}</h2>
        <label class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">Nuovo stato</label>
        <select v-model="modal.status" class="w-full rounded-md border-gray-300 text-sm mb-4">
          <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
        </select>
        <label v-if="modal.status === 'rejected'" class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">Motivo rifiuto</label>
        <textarea v-if="modal.status === 'rejected'" v-model="modal.rejection_reason" rows="3" class="w-full rounded-md border-gray-300 text-sm mb-4" placeholder="Opzionale ma consigliato" />
        <p v-if="modal.message" class="text-sm text-red-600 mb-2">{{ modal.message }}</p>
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded-md border px-4 py-2 text-sm" @click="modal.open = false">Annulla</button>
          <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm text-white font-gill-sans-semibold disabled:opacity-50" :disabled="modal.saving" @click="saveListing">
            {{ modal.saving ? 'Salvataggio…' : 'Salva' }}
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const statusOptions = [
  'draft',
  'pending_review',
  'approved',
  'active',
  'paused',
  'rejected',
  'expired',
  'sold',
  'inactive',
]

const loading = ref(true)
const error = ref('')
const rows = ref([])
const pagination = reactive({ current_page: 1, last_page: 1 })

const filters = reactive({
  status: '',
  search: '',
  date_from: '',
  date_to: '',
})

const modal = reactive({
  open: false,
  row: null,
  status: 'active',
  rejection_reason: '',
  saving: false,
  message: '',
})

function resetFilters() {
  filters.status = ''
  filters.search = ''
  filters.date_from = ''
  filters.date_to = ''
  loadPage(1)
}

async function loadPage(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = { page, per_page: 20 }
    if (filters.status) params.status = filters.status
    if (filters.search) params.search = filters.search
    if (filters.date_from) params.date_from = filters.date_from
    if (filters.date_to) params.date_to = filters.date_to
    const { data } = await axios.get('/api/admin/listings', { params })
    if (!data.success) {
      error.value = 'Risposta non valida'
      return
    }
    const p = data.data
    rows.value = p.data || []
    pagination.current_page = p.current_page || 1
    pagination.last_page = p.last_page || 1
  } catch (e) {
    error.value = e.response?.data?.message || 'Errore nel caricamento'
    rows.value = []
  } finally {
    loading.value = false
  }
}

function openEdit(row) {
  modal.open = true
  modal.row = row
  modal.status = row.status
  modal.rejection_reason = row.rejection_reason || ''
  modal.message = ''
  modal.saving = false
}

async function saveListing() {
  if (!modal.row) return
  modal.saving = true
  modal.message = ''
  try {
    const body = { status: modal.status }
    if (modal.status === 'rejected' && modal.rejection_reason) {
      body.rejection_reason = modal.rejection_reason
    }
    const { data } = await axios.patch(`/api/admin/listings/${modal.row.id}`, body)
    if (!data.success) {
      modal.message = data.message || 'Errore'
      return
    }
    modal.open = false
    await loadPage(pagination.current_page)
  } catch (e) {
    modal.message = e.response?.data?.message || 'Errore nel salvataggio'
  } finally {
    modal.saving = false
  }
}

onMounted(() => loadPage(1))
</script>
