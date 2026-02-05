<template>
  <DashboardLayout>
    <div class="mb-8">
      <h1 class="text-2xl font-bold text-gray-900">Notifiche</h1>
      <p class="mt-1 text-sm text-gray-500">Le tue notifiche in-app</p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
      <div class="flex items-center justify-between border-b border-gray-200 px-4 py-3">
        <span class="text-sm font-medium text-gray-700">
          {{ unreadCount > 0 ? `${unreadCount} non lett${unreadCount === 1 ? 'a' : 'e'}` : 'Tutte lette' }}
        </span>
        <button
          v-if="unreadCount > 0"
          type="button"
          :disabled="markingAll"
          class="text-sm font-medium text-primary hover:text-primary/80 disabled:opacity-50"
          @click="markAllAsRead"
        >
          {{ markingAll ? 'In corso...' : 'Segna tutte come lette' }}
        </button>
      </div>

      <div v-if="loading" class="flex justify-center py-12">
        <svg class="animate-spin h-8 w-8 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
        </svg>
      </div>

      <div v-else-if="notifications.length === 0" class="px-4 py-12 text-center text-gray-500">
        Nessuna notifica.
      </div>

      <ul v-else class="divide-y divide-gray-200">
        <li
          v-for="n in notifications"
          :key="n.id"
          :class="[
            'px-4 py-3 transition-colors',
            n.is_read ? 'bg-white' : 'bg-primary/5'
          ]"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0 flex-1">
              <p :class="['text-sm font-medium text-gray-900', !n.is_read && 'font-semibold']">
                {{ n.title }}
              </p>
              <p class="mt-0.5 text-sm text-gray-600 whitespace-pre-wrap">{{ n.message }}</p>
              <p class="mt-1 text-xs text-gray-400">{{ formatDate(n.created_at) }}</p>
              <a
                v-if="notificationLink(n)"
                :href="notificationLink(n)"
                class="mt-2 inline-block text-sm font-medium text-primary hover:underline"
              >
                {{ n.action_text || 'Visualizza dettaglio' }}
              </a>
            </div>
            <button
              v-if="!n.is_read"
              type="button"
              class="shrink-0 rounded p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600"
              title="Segna come letta"
              @click="markAsRead(n.id)"
            >
              <span class="sr-only">Segna come letta</span>
              <CheckIcon class="h-5 w-5" />
            </button>
          </div>
        </li>
      </ul>

      <div v-if="hasMore && !loading" class="border-t border-gray-200 px-4 py-3 text-center">
        <button
          type="button"
          class="text-sm font-medium text-primary hover:underline"
          @click="loadMore"
        >
          Carica altre
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { useNotificationsStore } from '@/stores/notifications'
import { CheckIcon } from '@heroicons/vue/24/outline'

const notificationsStore = useNotificationsStore()

const notifications = ref([])

/** Restituisce l’URL per “Visualizza dettaglio”: se il link salvato è localhost, usa l’origin corrente così funziona in produzione */
function notificationLink (n) {
  const url = n.action_url
  if (!url) return null
  try {
    const u = new URL(url)
    if (u.hostname === 'localhost' || u.hostname === '127.0.0.1') {
      return window.location.origin + u.pathname + u.search
    }
    return url
  } catch (_) {
    return url
  }
}
const loading = ref(true)
const markingAll = ref(false)
const unreadCount = ref(0)
const currentPage = ref(1)
const lastPage = ref(1)
const hasMore = computed(() => currentPage.value < lastPage.value)

function formatDate (iso) {
  if (!iso) return ''
  const d = new Date(iso)
  const now = new Date()
  const diffMs = now - d
  const diffMins = Math.floor(diffMs / 60000)
  if (diffMins < 1) return 'Adesso'
  if (diffMins < 60) return `${diffMins} min fa`
  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return `${diffHours} h fa`
  const diffDays = Math.floor(diffHours / 24)
  if (diffDays < 7) return `${diffDays} giorni fa`
  return d.toLocaleDateString('it-IT', { day: 'numeric', month: 'short', year: 'numeric' })
}

async function fetchNotifications (page = 1) {
  const { data } = await axios.get('/api/notifications', { params: { page, per_page: 20 } })
  if (data.success) {
    if (page === 1) {
      notifications.value = data.data
    } else {
      notifications.value.push(...data.data)
    }
    unreadCount.value = data.unread_count ?? 0
    currentPage.value = page
    lastPage.value = data.meta?.last_page ?? 1
  }
}

async function load () {
  loading.value = true
  try {
    await fetchNotifications(1)
  } finally {
    loading.value = false
  }
}

function loadMore () {
  fetchNotifications(currentPage.value + 1)
}

async function markAsRead (id) {
  try {
    const { data } = await axios.post(`/api/notifications/${id}/read`)
    if (data.success) {
      unreadCount.value = data.unread_count ?? 0
      notificationsStore.setUnreadCount(unreadCount.value)
      const n = notifications.value.find(nn => nn.id === id)
      if (n) n.is_read = true
    }
  } catch (e) {
    console.error(e)
  }
}

async function markAllAsRead () {
  markingAll.value = true
  try {
    const { data } = await axios.post('/api/notifications/read-all')
    if (data.success) {
      unreadCount.value = 0
      notificationsStore.setUnreadCount(0)
      notifications.value.forEach(n => { n.is_read = true })
    }
  } catch (e) {
    console.error(e)
  } finally {
    markingAll.value = false
  }
}

onMounted(() => {
  load()
})
</script>
