<template>
  <div class="rounded-lg border border-gray-200 bg-white p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <span
          :class="badgeClass"
          class="inline-flex items-center px-4 py-2 rounded-lg text-base font-semibold"
        >
          {{ label }}
        </span>
        <p v-if="subtext" class="mt-2 text-sm text-gray-600">{{ subtext }}</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: { type: String, default: '' }
})

const statusConfig = {
  PAID_WAITING_SHIPMENT: { label: 'In preparazione', subtext: 'Il venditore sta preparando il tuo ordine.', class: 'bg-amber-100 text-amber-800' },
  SHIPPED_IN_TRANSIT: { label: 'In transito', subtext: 'Il pacco è stato spedito ed è in viaggio.', class: 'bg-blue-100 text-blue-800' },
  DELIVERED_HOLD_72H: { label: 'Consegnato – verifica in corso', subtext: 'Consegna registrata. CardSwap verifica entro 72 ore.', class: 'bg-green-100 text-green-800' },
  RELEASED: { label: 'Completato', subtext: 'Ordine concluso.', class: 'bg-emerald-100 text-emerald-800' },
  DISPUTED: { label: 'In disputa', subtext: 'È stata aperta una disputa su questo ordine.', class: 'bg-red-100 text-red-800' },
  CANCELLED: { label: 'Ordine annullato', subtext: '', class: 'bg-gray-100 text-gray-800' },
  REFUNDED: { label: 'Rimborsato', subtext: 'L\'ordine è stato rimborsato.', class: 'bg-gray-100 text-gray-800' }
}

const label = computed(() => statusConfig[props.status]?.label ?? props.status ?? '—')
const subtext = computed(() => statusConfig[props.status]?.subtext ?? '')
const badgeClass = computed(() => statusConfig[props.status]?.class ?? 'bg-gray-100 text-gray-800')
</script>
