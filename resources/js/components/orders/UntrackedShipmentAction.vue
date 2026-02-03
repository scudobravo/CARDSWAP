<template>
  <div class="rounded-lg border border-gray-200 bg-white p-4">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Spedizione non tracciata</h3>
    <p class="text-sm text-gray-600 mb-4">
      Spedizione non tracciata: il pagamento verrà rilasciato dopo 14/30 giorni + 72h.
    </p>

    <template v-if="shippedAt">
      <p class="text-sm text-gray-700 mb-1"><strong>Spedito il:</strong> {{ formatDate(shippedAt) }}</p>
      <p class="text-sm text-gray-500">Countdown stimato gestito dal sistema.</p>
    </template>

    <template v-else>
      <button
        type="button"
        :disabled="loading"
        @click="markShipped"
        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="loading" class="flex items-center">
          <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
          </svg>
          Invio...
        </span>
        <span v-else>Segna come spedito</span>
      </button>
      <p v-if="error" class="mt-2 text-sm text-red-600">{{ error }}</p>
    </template>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const props = defineProps({
  orderId: { type: [String, Number], required: true },
  shippedAt: { type: String, default: null }
})

const emit = defineEmits(['marked'])

const loading = ref(false)
const error = ref('')

function formatDate (iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('it-IT')
}

async function markShipped () {
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.post(`/api/seller/orders/${props.orderId}/mark-shipped`)
    if (data.success) {
      emit('marked', data.data)
    } else {
      error.value = data.message || 'Errore'
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Errore di rete'
  } finally {
    loading.value = false
  }
}
</script>
