<template>
  <div v-if="visible" class="rounded-lg border border-gray-200 bg-white p-4">
    <p class="text-sm text-gray-600 mb-2">
      Puoi aprire una disputa fino al rilascio del pagamento.
    </p>
    <button
      type="button"
      :disabled="loading"
      @click="$emit('open')"
      class="inline-flex items-center px-4 py-2 border border-red-300 text-sm font-medium rounded-md text-red-700 bg-red-50 hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50 disabled:cursor-not-allowed"
    >
      <span v-if="loading">Apertura in corso...</span>
      <span v-else>Apri disputa</span>
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'

defineProps({
  visible: { type: Boolean, default: false },
  loading: { type: Boolean, default: false }
})

defineEmits(['open'])
</script>
