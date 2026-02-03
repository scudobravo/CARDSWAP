<template>
  <div v-if="insuranceFee > 0" class="rounded-lg border border-gray-200 bg-white p-4">
    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
      Spedizione assicurata
    </span>
    <p class="mt-3 text-sm text-gray-600">
      Include costo aggiuntivo calcolato sul valore dell'ordine. In caso di problema potrebbe essere richiesta documentazione.
    </p>
  </div>
</template>

<script setup>
defineProps({
  insuranceFee: { type: Number, default: 0 }
})
</script>
