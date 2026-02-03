<template>
  <div class="rounded-lg border border-gray-200 bg-white p-4">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Spedizione tracciata</h3>

    <!-- Countdown e warning (sempre visibili se in attesa) -->
    <div v-if="!existingTracking" class="mb-4 space-y-2">
      <p v-if="trackingDeadlineAt" class="text-sm text-amber-700">
        Inserisci il tracking entro <strong>{{ formatDeadline(trackingDeadlineAt) }}</strong>
      </p>
      <p class="text-sm text-red-600">
        Inserisci tracking entro 7 giorni, altrimenti l'ordine viene annullato.
      </p>
    </div>

    <!-- Già inserito: solo visualizzazione -->
    <template v-if="existingTracking">
      <div class="space-y-2 text-sm text-gray-700">
        <p><span class="font-medium">Numero tracking:</span> <span class="font-mono">{{ existingTracking.tracking_number }}</span></p>
        <p v-if="existingTracking.shipped_at"><span class="font-medium">Data inserimento:</span> {{ formatDate(existingTracking.shipped_at) }}</p>
        <p class="text-green-700 font-medium">Tracking registrato – in attesa di consegna</p>
      </div>
    </template>

    <!-- Form inserimento -->
    <template v-else>
      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label for="tracking_number" class="block text-sm font-medium text-gray-700 mb-1">Numero di tracking *</label>
          <input
            id="tracking_number"
            v-model="form.tracking_number"
            type="text"
            required
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary"
            placeholder="Es. 1234567890"
          />
        </div>
        <div>
          <label for="carrier_slug" class="block text-sm font-medium text-gray-700 mb-1">Corriere (opzionale)</label>
          <input
            id="carrier_slug"
            v-model="form.carrier_slug"
            type="text"
            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-primary focus:border-primary"
            placeholder="Es. dhl, poste-italiane"
          />
        </div>
        <button
          type="submit"
          :disabled="loading || !form.tracking_number?.trim()"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <span v-if="loading" class="flex items-center">
            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
            </svg>
            Invio...
          </span>
          <span v-else>Inserisci tracking</span>
        </button>
        <p v-if="error" class="text-sm text-red-600">{{ error }}</p>
      </form>
    </template>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue'
import axios from 'axios'

const props = defineProps({
  orderId: { type: [String, Number], required: true },
  existingTracking: { type: Object, default: null },
  trackingDeadlineAt: { type: String, default: null }
})

const emit = defineEmits(['submitted'])

const loading = ref(false)
const error = ref('')
const form = reactive({
  tracking_number: '',
  carrier_slug: ''
})

function formatDeadline (iso) {
  if (!iso) return ''
  const d = new Date(iso)
  return d.toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' })
}

function formatDate (iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleString('it-IT')
}

async function submit () {
  if (!form.tracking_number?.trim()) return
  loading.value = true
  error.value = ''
  try {
    const { data } = await axios.post(`/api/seller/orders/${props.orderId}/tracking`, {
      tracking_number: form.tracking_number.trim(),
      carrier_slug: form.carrier_slug?.trim() || undefined
    })
    if (data.success) {
      emit('submitted', data.data)
    } else {
      error.value = data.message || 'Errore nell\'inserimento del tracking'
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message || 'Errore di rete'
  } finally {
    loading.value = false
  }
}
</script>
