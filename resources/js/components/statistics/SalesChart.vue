<template>
  <div class="bg-white rounded-lg border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-futura-bold text-gray-900">{{ title }}</h3>
      <div class="flex space-x-2">
        <button 
          v-for="type in chartTypes" 
          :key="type.value"
          @click="selectedType = type.value"
          :class="[
            'px-3 py-1 text-xs font-gill-sans-semibold rounded-md',
            selectedType === type.value 
              ? 'bg-primary text-white' 
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
          ]"
        >
          {{ type.label }}
        </button>
      </div>
    </div>
    
    <div class="h-64 flex items-center justify-center">
      <div class="text-center">
        <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
        <p class="mt-2 text-sm text-gray-500">Grafico {{ selectedType }}</p>
        <p class="text-xs text-gray-400">{{ data?.length || 0 }} punti dati</p>
        <p class="text-xs text-gray-400 mt-1">Integrazione grafici in sviluppo</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { ChartBarIcon } from '@heroicons/vue/24/outline'

defineProps({
  title: {
    type: String,
    required: true
  },
  data: {
    type: Array,
    default: () => []
  },
  type: {
    type: String,
    default: 'line'
  }
})

const selectedType = ref('line')

const chartTypes = [
  { value: 'line', label: 'Linea' },
  { value: 'bar', label: 'Barre' },
  { value: 'pie', label: 'Torta' }
]
</script>
