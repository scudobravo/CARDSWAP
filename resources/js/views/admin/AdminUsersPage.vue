<template>
  <DashboardLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-futura-bold text-gray-900 mb-2">Utenti</h1>
      <p class="text-gray-600 font-gill-sans">Elenco utenti, ruolo e KYC</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 flex flex-wrap gap-4 items-end">
      <div>
        <label class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">Ruolo</label>
        <select v-model="filters.role" class="rounded-md border-gray-300 text-sm">
          <option value="">Tutti</option>
          <option value="buyer">buyer</option>
          <option value="seller">seller</option>
          <option value="admin">admin</option>
        </select>
      </div>
      <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">Ricerca</label>
        <input v-model="filters.search" type="text" class="w-full rounded-md border-gray-300 text-sm" placeholder="Nome, email, username" @keyup.enter="loadPage(1)" />
      </div>
      <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm font-gill-sans-semibold text-white" @click="loadPage(1)">Cerca</button>
    </div>

    <div v-if="error" class="rounded-md bg-red-50 p-4 text-red-800 text-sm mb-4">{{ error }}</div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary" />
    </div>

    <div v-else class="bg-white rounded-lg border border-gray-200 overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">ID</th>
            <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Nome</th>
            <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Email</th>
            <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Ruolo</th>
            <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">KYC</th>
            <th class="px-4 py-3 text-left text-xs font-futura-bold text-gray-500 uppercase">Sospeso</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="u in rows" :key="u.id" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm">{{ u.id }}</td>
            <td class="px-4 py-3 text-sm">{{ u.name }}</td>
            <td class="px-4 py-3 text-sm">{{ u.email }}</td>
            <td class="px-4 py-3 text-sm">{{ u.role }}</td>
            <td class="px-4 py-3 text-sm">{{ u.kyc_status }}</td>
            <td class="px-4 py-3 text-sm">{{ u.is_suspended ? 'Sì' : 'No' }}</td>
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
const filters = reactive({ role: '', search: '' })

async function loadPage(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = { page, per_page: 20 }
    if (filters.role) params.role = filters.role
    if (filters.search) params.search = filters.search
    const { data } = await axios.get('/api/admin/users', { params })
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
