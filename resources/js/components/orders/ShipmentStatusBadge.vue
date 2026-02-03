<template>
  <span
    :class="badgeClass"
    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
  >
    {{ label }}
  </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  status: {
    type: String,
    default: ''
  }
})

const statusLabels = {
  PAID_WAITING_SHIPMENT: 'In attesa di spedizione',
  SHIPPED_IN_TRANSIT: 'In transito',
  DELIVERED_HOLD_72H: 'Consegnato – verifica in corso',
  RELEASED: 'Pagamento rilasciato',
  DISPUTED: 'In disputa',
  CANCELLED: 'Ordine annullato'
}

const label = computed(() => statusLabels[props.status] || props.status || '—')

const badgeClass = computed(() => {
  const map = {
    PAID_WAITING_SHIPMENT: 'bg-amber-100 text-amber-800',
    SHIPPED_IN_TRANSIT: 'bg-blue-100 text-blue-800',
    DELIVERED_HOLD_72H: 'bg-green-100 text-green-800',
    RELEASED: 'bg-emerald-100 text-emerald-800',
    DISPUTED: 'bg-red-100 text-red-800',
    CANCELLED: 'bg-gray-100 text-gray-800'
  }
  return map[props.status] || 'bg-gray-100 text-gray-800'
})
</script>
