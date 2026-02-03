<template>
  <div class="rounded-lg border border-gray-200 bg-white p-4">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Spedizione</h3>

    <!-- Caso A: Tracciata -->
    <template v-if="isTracked">
      <p class="text-sm text-gray-700 mb-2">
        <span class="font-medium">Numero di tracking:</span>
        <span class="font-mono ml-1">{{ trackingNumber }}</span>
      </p>
      <p v-if="carrierSlug" class="text-sm text-gray-700 mb-2">
        <span class="font-medium">Corriere:</span>
        <span class="ml-1">{{ carrierLabel }}</span>
      </p>
      <p v-if="shippedAt" class="text-sm text-gray-500 mb-3">
        Spedito il {{ formatDate(shippedAt) }}
      </p>
      <p class="text-sm text-green-700 mb-3">
        La consegna è verificata automaticamente.
      </p>
      <a
        v-if="trackingUrl"
        :href="trackingUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/90"
      >
        Segui spedizione
      </a>
      <a
        v-else-if="trackingNumber && genericTrackingUrl"
        :href="genericTrackingUrl"
        target="_blank"
        rel="noopener noreferrer"
        class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-primary rounded-md hover:bg-primary/90"
      >
        Segui spedizione
      </a>
    </template>

    <!-- Caso B: Non tracciata -->
    <template v-else>
      <p class="text-sm font-medium text-gray-700 mb-1">Spedizione non tracciata</p>
      <p v-if="shippedAt" class="text-sm text-gray-600 mb-3">
        Segnalato come spedito il {{ formatDate(shippedAt) }}
      </p>
      <div class="rounded-md bg-amber-50 border border-amber-200 p-3 text-sm text-amber-800">
        Nessun tracking. CardSwap non può verificare la consegna.
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  isTracked: { type: Boolean, default: false },
  trackingNumber: { type: String, default: null },
  carrierSlug: { type: String, default: null },
  trackingUrl: { type: String, default: null },
  shippedAt: { type: String, default: null }
})

const carrierLabel = computed(() => {
  const s = props.carrierSlug
  if (!s) return '—'
  return s.replace(/-/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
})

const genericTrackingUrl = computed(() => {
  if (!props.trackingNumber) return null
  return `https://track.aftership.com/?tracking_number=${encodeURIComponent(props.trackingNumber)}`
})

function formatDate (iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('it-IT', { day: 'numeric', month: 'long', year: 'numeric' })
}
</script>
