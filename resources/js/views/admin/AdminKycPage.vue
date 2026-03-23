<template>
  <DashboardLayout>
    <div class="mb-8">
      <h1 class="text-3xl font-futura-bold text-gray-900 mb-2">KYC in attesa</h1>
      <p class="text-gray-600 font-gill-sans">Utenti con verifica documenti pending</p>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 p-4 mb-6">
      <label class="block text-xs font-futura-bold text-gray-500 uppercase mb-1">Ricerca</label>
      <div class="flex gap-2">
        <input v-model="filters.search" type="text" class="flex-1 rounded-md border-gray-300 text-sm" placeholder="Nome, email, CF" @keyup.enter="loadPage(1)" />
        <button type="button" class="rounded-md bg-primary px-4 py-2 text-sm text-white font-gill-sans-semibold" @click="loadPage(1)">Cerca</button>
      </div>
    </div>

    <div v-if="error" class="rounded-md bg-red-50 p-4 text-red-800 text-sm mb-4">{{ error }}</div>

    <div v-if="loading" class="flex justify-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary" />
    </div>

    <div v-else class="space-y-4">
      <div v-for="u in rows" :key="u.id" class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-wrap justify-between gap-2 mb-3">
          <div>
            <p class="font-gill-sans-semibold text-gray-900">{{ u.name }}</p>
            <p class="text-sm text-gray-600">{{ u.email }}</p>
            <p class="text-xs text-gray-500">Documenti: {{ u.kyc_documents_count ?? 0 }}</p>
          </div>
          <div class="flex flex-wrap gap-2">
            <button type="button" class="rounded-md bg-green-600 px-3 py-1.5 text-sm text-white" @click="approve(u)">Approva</button>
            <button type="button" class="rounded-md bg-red-600 px-3 py-1.5 text-sm text-white" @click="openReject(u)">Rifiuta</button>
          </div>
        </div>
      </div>
      <div v-if="!rows.length" class="text-center text-gray-500 py-8">Nessun KYC in attesa</div>
      <div v-if="pagination.last_page > 1" class="flex justify-between">
        <button type="button" class="rounded border px-3 py-1 text-sm disabled:opacity-40" :disabled="pagination.current_page <= 1" @click="loadPage(pagination.current_page - 1)">Prev</button>
        <button type="button" class="rounded border px-3 py-1 text-sm disabled:opacity-40" :disabled="pagination.current_page >= pagination.last_page" @click="loadPage(pagination.current_page + 1)">Next</button>
      </div>
    </div>

    <div v-if="rejectModal.open" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="rejectModal.open = false">
      <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
        <h2 class="text-lg font-futura-bold mb-2">Rifiuta KYC</h2>
        <textarea v-model="rejectModal.reason" rows="4" class="w-full rounded-md border-gray-300 text-sm mb-4" placeholder="Motivo obbligatorio" />
        <div class="flex justify-end gap-2">
          <button type="button" class="rounded border px-4 py-2 text-sm" @click="rejectModal.open = false">Annulla</button>
          <button type="button" class="rounded bg-red-600 px-4 py-2 text-sm text-white" :disabled="rejectModal.saving || !rejectModal.reason.trim()" @click="confirmReject">Conferma</button>
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
const filters = reactive({ search: '' })

const rejectModal = reactive({
  open: false,
  user: null,
  reason: '',
  saving: false,
})

async function loadPage(page = 1) {
  loading.value = true
  error.value = ''
  try {
    const params = { page, per_page: 15 }
    if (filters.search) params.search = filters.search
    const { data } = await axios.get('/api/admin/kyc/pending', { params })
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

async function approve(u) {
  try {
    await axios.post(`/api/admin/kyc/users/${u.id}/approve`, {})
    await loadPage(pagination.current_page)
  } catch (e) {
    error.value = e.response?.data?.message || 'Errore approvazione'
  }
}

function openReject(u) {
  rejectModal.open = true
  rejectModal.user = u
  rejectModal.reason = ''
}

async function confirmReject() {
  if (!rejectModal.user || !rejectModal.reason.trim()) return
  rejectModal.saving = true
  try {
    await axios.post(`/api/admin/kyc/users/${rejectModal.user.id}/reject`, { reason: rejectModal.reason.trim() })
    rejectModal.open = false
    await loadPage(pagination.current_page)
  } catch (e) {
    error.value = e.response?.data?.message || 'Errore rifiuto'
  } finally {
    rejectModal.saving = false
  }
}

onMounted(() => loadPage(1))
</script>
