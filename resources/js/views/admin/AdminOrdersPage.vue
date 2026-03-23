<template>
  <DashboardLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-futura-bold text-gray-900 mb-2">Ordini</h1>
      <p class="text-gray-600 font-gill-sans">Tutti gli ordini della piattaforma</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 flex flex-wrap gap-4 items-end">
      <div>
        <label class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">Stato</label>
        <select v-model="filters.status" class="rounded-md border-gray-300 text-sm">
          <option value="">Tutti</option>
          <option value="pending">pending</option>
          <option value="confirmed">confirmed</option>
          <option value="shipped">shipped</option>
          <option value="delivered">delivered</option>
          <option value="cancelled">cancelled</option>
          <option value="refunded">refunded</option>
        </select>
      </div>
      <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">Ricerca</label>
        <input v-model="filters.search" type="text" class="w-full rounded-md border-gray-300 text-sm" placeholder="Numero ordine, acquirente…" @keyup.enter="loadPage(1)" />
      </div>
      <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm text-white font-gill-sans-semibold" @click="loadPage(1)">Cerca</button>
    </div>

    <div v-if="error" class="rounded-md bg-red-50 p-4 text-red-800 text-sm mb-4">{{ error }}</div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary" />
    </div>

    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">N. ordine</th>
            <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Acquirente</th>
            <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Totale</th>
            <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Stato</th>
            <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Data</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="o in rows" :key="o.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm font-mono">{{ o.order_number }}</td>
            <td class="px-4 py-3 text-sm">{{ o.buyer?.email || '—' }}</td>
            <td class="px-4 py-3 text-sm">€ {{ Number(o.total_amount).toFixed(2) }}</td>
            <td class="px-4 py-3 text-sm">{{ o.status }}</td>
            <td class="px-4 py-3 text-sm text-gray-600">{{ formatDate(o.created_at) }}</td>
          </tr>
        </tbody>
      </table>
      <div v-if="pagination.last_page > 1" class="flex justify-between px-4 py-3 border-t">
        <span class="text-sm text-gray-600">Pag. {{ pagination.current_page }} / {{ pagination.last_page }}</span>
        <div class="flex gap-2">
          <button type="button" class="rounded border px-3 py-1 text-sm disabled:opacity-40" :disabled="pagination.current_page <= 1" @click="loadPage(pagination.current_page - 1)">Prev</button>
          <button type="button" class="rounded border px-3 py-1 text-sm disabled:opacity-40" :disabled="pagination.current_page >= pagination.last_page" @click="loadPage(pagination.current_page + 1)">Next</button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import axios from 'axios'
import DashboardLayout from '@/layouts/DashboardLayout.vue'

const loading = ref(true)
const error = ref('')
const rows = ref([])
const pagination = reactive({ current_page: 1, last_page: 1 })
const filters = reactive({ status: '', search: '' })

function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('it-IT')
}

async function loadPage(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = { page, per_page: 20 }
    if (filters.status) params.status = filters.status
    if (filters.search) params.search = filters.search
    const { data } = await axios.get('/api/admin/orders', { params })
    const p = data.data
    rows.value = p.data || []
    pagination.current_page = p.current_page || 1
    pagination.last_page = p.last_page || 1
  } catch (e) {
    error.value = e.response?.data?.message || 'Errore'
    rows.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => loadPage(1))
</script>
