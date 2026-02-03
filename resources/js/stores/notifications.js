import { defineStore } from 'pinia'
import { ref } from 'vue'
import axios from 'axios'

export const useNotificationsStore = defineStore('notifications', () => {
  const unreadCount = ref(0)

  async function fetchUnreadCount () {
    try {
      const { data } = await axios.get('/api/notifications/unread-count')
      if (data.success) {
        unreadCount.value = data.unread_count ?? 0
      }
    } catch (_) {
      unreadCount.value = 0
    }
  }

  function setUnreadCount (n) {
    unreadCount.value = n
  }

  return { unreadCount, fetchUnreadCount, setUnreadCount }
})
