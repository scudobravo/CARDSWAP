<template>
  <div v-if="message" class="rounded-lg border border-gray-200 bg-gray-50 p-4">
    <h3 class="text-sm font-semibold text-gray-900 mb-2">Pagamento & Garanzie</h3>
    <p class="text-sm text-gray-700">{{ message }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  shipmentStatus: { type: String, default: '' },
  isTracked: { type: Boolean, default: false }
})

const message = computed(() => {
  if (props.shipmentStatus === 'DISPUTED') {
    return 'Pagamento congelato per disputa.'
  }
  if (props.shipmentStatus === 'RELEASED' || props.shipmentStatus === 'REFUNDED' || props.shipmentStatus === 'CANCELLED') {
    return ''
  }
  if (props.isTracked) {
    if (props.shipmentStatus === 'DELIVERED_HOLD_72H') {
      return 'Pagamento in verifica (72h). Puoi aprire una disputa in questo periodo.'
    }
    return 'Il pagamento al venditore verrà rilasciato 72h dopo la consegna verificata.'
  }
  return 'Il pagamento verrà rilasciato 72h dopo che il venditore ha segnato come spedito.'
})
</script>
