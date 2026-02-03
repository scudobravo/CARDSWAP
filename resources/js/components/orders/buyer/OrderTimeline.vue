<template>
  <div class="rounded-lg border border-gray-200 bg-white p-4">
    <h3 class="text-sm font-semibold text-gray-900 mb-4">Timeline ordine</h3>
    <div class="space-y-4">
      <div
        v-for="(step, index) in steps"
        :key="step.key"
        class="flex gap-3"
      >
        <div class="flex flex-col items-center">
          <span
            :class="[
              'flex h-8 w-8 items-center justify-center rounded-full text-xs font-medium',
              step.done ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-400'
            ]"
          >
            <span v-if="step.done">✓</span>
            <span v-else>{{ index + 1 }}</span>
          </span>
          <span
            v-if="index < steps.length - 1"
            class="mt-1 w-0.5 flex-1 bg-gray-200 min-h-[24px]"
          />
        </div>
        <div class="flex-1 pb-4">
          <p class="text-sm font-medium text-gray-900">{{ step.label }}</p>
          <p v-if="step.date" class="text-xs text-gray-500">{{ step.date }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  paidAt: { type: String, default: null },
  shippedAt: { type: String, default: null },
  deliveredAt: { type: String, default: null },
  shipmentStatus: { type: String, default: '' },
  payoutStatus: { type: String, default: null }
})

function formatDate (iso) {
  if (!iso) return ''
  return new Date(iso).toLocaleDateString('it-IT', { day: 'numeric', month: 'short', year: 'numeric' })
}

const steps = computed(() => {
  const released = props.shipmentStatus === 'RELEASED' || props.payoutStatus === 'paid'
  return [
    {
      key: 'paid',
      label: 'Pagato',
      date: props.paidAt ? formatDate(props.paidAt) : null,
      done: !!props.paidAt
    },
    {
      key: 'shipped',
      label: 'Spedito',
      date: props.shippedAt ? formatDate(props.shippedAt) : null,
      done: !!props.shippedAt
    },
    {
      key: 'delivered',
      label: props.shipmentStatus === 'DELIVERED_HOLD_72H' ? 'Consegnato / Attesa' : 'Consegnato',
      date: props.deliveredAt ? formatDate(props.deliveredAt) : null,
      done: !!props.deliveredAt || props.shipmentStatus === 'DELIVERED_HOLD_72H' || props.shipmentStatus === 'RELEASED'
    },
    {
      key: 'released',
      label: 'Rilascio pagamento',
      date: null,
      done: released
    }
  ]
})
</script>
