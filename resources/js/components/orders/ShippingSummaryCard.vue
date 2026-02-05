<template>
  <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
    <h3 class="text-sm font-semibold text-gray-900 mb-3">Riepilogo Spedizione</h3>
    <dl class="space-y-2 text-sm">
      <div class="flex justify-between">
        <dt class="text-gray-600">Metodo</dt>
        <dd class="font-medium text-gray-900">{{ methodLabel }}</dd>
      </div>
      <div v-if="orderShipping?.package_bucket" class="flex justify-between">
        <dt class="text-gray-600">Bucket</dt>
        <dd class="font-medium text-gray-900">{{ bucketLabel }}</dd>
      </div>
      <div class="flex justify-between">
        <dt class="text-gray-600">Prezzo spedizione</dt>
        <dd class="font-medium text-gray-900">€{{ formatPrice(orderShipping?.shipping_price ?? 0) }}</dd>
      </div>
      <div class="flex justify-between">
        <dt class="text-gray-600">Assicurazione</dt>
        <dd class="font-medium text-gray-900">
          <template v-if="insuranceIncluded">
            Inclusa
            <span v-if="insuranceFee > 0" class="text-gray-500">(€{{ formatPrice(insuranceFee) }})</span>
          </template>
          <template v-else>Non inclusa</template>
        </dd>
      </div>
    </dl>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  orderShipping: {
    type: Object,
    default: null
  }
})

const bucketLabels = {
  LETTER: 'LETTER',
  PARCEL_S: 'PARCEL S',
  PARCEL_M: 'PARCEL M',
  PARCEL_L: 'PARCEL L'
}

const methodLabels = {
  TRACKED_STANDARD: 'Tracciata standard',
  TRACKED_EXPRESS: 'Tracciata express',
  TRACKED_INSURED: 'Tracciata assicurata',
  UNTRACKED_STANDARD: 'Non tracciata'
}

const methodLabel = computed(() => {
  const m = props.orderShipping?.shipping_method
  if (!m) return '—'
  return methodLabels[m] || m.replace(/_/g, ' ')
})

const bucketLabel = computed(() => {
  const b = props.orderShipping?.package_bucket
  return bucketLabels[b] || b || '—'
})

const insuranceIncluded = computed(() => {
  return props.orderShipping?.insurance_included ?? (Number(props.orderShipping?.insurance_fee ?? 0) > 0)
})

const insuranceFee = computed(() => Number(props.orderShipping?.insurance_fee ?? 0))

function formatPrice (n) {
  return Number(n).toFixed(2)
}
</script>
