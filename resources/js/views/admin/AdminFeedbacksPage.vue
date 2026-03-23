<template>
  <DashboardLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-futura-bold text-gray-900 mb-2">Feedback</h1>
      <p class="text-gray-600 font-gill-sans">Moderazione recensioni</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6 flex flex-wrap gap-4 items-end">
      <div>
        <label class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">Visibilità</label>
        <select v-model="filters.is_hidden" class="rounded-md border-gray-300 text-sm">
          <option value="">Tutti</option>
          <option value="0">Visibili</option>
          <option value="1">Nascosti</option>
        </select>
      </div>
      <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm text-white font-gill-sans-semibold" @click="loadPage(1)">Applica</button>
    </div>

    <div v-if="error" class="rounded-md bg-red-50 p-4 text-red-800 text-sm mb-4">{{ error }}</div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary" />
    </div>

    <div v-else class="space-y-4">
      <div v-for="f in rows" :key="f.id" class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-wrap justify-between gap-2 mb-2">
          <span class="text-sm font-gill-sans-semibold">Venditore: {{ f.seller?.name || f.seller_id }}</span>
          <span class="text-sm text-gray-600">Voto: {{ f.rating }} / 5</span>
          <span class="text-xs rounded-full px-2 py-0.5" :class="f.is_hidden ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'">
            {{ f.is_hidden ? 'Nascosto' : 'Visibile' }}
          </span>
        </div>
        <p class="text-sm font-gill-sans text-gray-800 mb-3">{{ f.comment || '(nessun testo)' }}</p>
        <div class="flex gap-2">
          <button
            v-if="!f.is_hidden"
            type="button"
            class="rounded-md border border-red-200 bg-red-50 px-3 py-1 text-sm text-red-800"
            @click="moderate(f, true)"
          >
            Nascondi
          </button>
          <button
            v-else
            type="button"
            class="rounded-md border border-green-200 bg-green-50 px-3 py-1 text-sm text-green-800"
            @click="moderate(f, false)"
          >
            Mostra
          </button>
        </div>
      </div>
      <div v-if="!rows.length" class="text-center text-gray-500 py-8">Nessun feedback</div>
      <div v-if="pagination.last_page > 1" class="flex justify-between">
        <button type="button" class="rounded border px-3 py-1 text-sm disabled:opacity-40" :disabled="pagination.current_page <= 1" @click="loadPage(pagination.current_page - 1)">Prev</button>
        <button type="button" class="rounded border px-3 py-1 text-sm disabled:opacity-40" :disabled="pagination.current_page >= pagination.last_page" @click="loadPage(pagination.current_page + 1)">Next</button>
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
const filters = reactive({ is_hidden: '' })

async function loadPage(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = { page, per_page: 15 }
    if (filters.is_hidden !== '') params.is_hidden = filters.is_hidden
    const { data } = await axios.get('/api/admin/feedbacks', { params })
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

async function moderate(f, hide) {
  try {
    await axios.put(`/api/admin/feedbacks/${f.id}/moderate`, { is_hidden: hide })
    await loadPage(pagination.current_page)
  } catch (e) {
    error.value = e.response?.data?.message || 'Errore moderazione'
  }
}

onMounted(() => loadPage(1))
</script>
