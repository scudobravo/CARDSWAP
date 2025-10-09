<template>
  <div class="bg-white rounded-lg border border-gray-200 p-6">
    <div class="flex items-center">
      <div class="flex-shrink-0">
        <component :is="icon" class="h-8 w-8" :class="iconColor" />
      </div>
      <div class="ml-3">
        <p class="text-sm font-gill-sans text-gray-500">{{ label }}</p>
        <p class="text-2xl font-futura-bold text-gray-900">{{ formattedValue }}</p>
        <p v-if="change !== undefined" class="text-xs text-gray-500">
          <span :class="change >= 0 ? 'text-green-600' : 'text-red-600'">
            {{ change >= 0 ? '+' : '' }}{{ change.toFixed(1) }}%
          </span>
          {{ changeLabel }}
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: {
    type: String,
    required: true
  },
  value: {
    type: [Number, String],
    required: true
  },
  icon: {
    type: [Object, String],
    required: true
  },
  iconColor: {
    type: String,
    default: 'text-gray-500'
  },
  change: {
    type: Number,
    default: undefined
  },
  changeLabel: {
    type: String,
    default: 'vs periodo precedente'
  },
  format: {
    type: String,
    default: 'number' // 'number', 'currency', 'percentage'
  }
})

const formattedValue = computed(() => {
  if (props.format === 'currency') {
    return `€${Number(props.value).toFixed(2)}`
  } else if (props.format === 'percentage') {
    return `${Number(props.value).toFixed(1)}%`
  } else {
    return props.value.toString()
  }
})
</script>
