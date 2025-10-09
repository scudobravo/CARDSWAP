<template>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
    <!-- Ordini in Attesa -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <ClockIcon class="h-8 w-8 text-yellow-500" />
        </div>
        <div class="ml-3">
          <p class="text-sm font-gill-sans text-gray-500">In Attesa</p>
          <p class="text-2xl font-futura-bold text-gray-900">{{ stats.pending }}</p>
        </div>
      </div>
    </div>

    <!-- Ordini Spediti -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <TruckIcon class="h-8 w-8 text-blue-500" />
        </div>
        <div class="ml-3">
          <p class="text-sm font-gill-sans text-gray-500">Spediti</p>
          <p class="text-2xl font-futura-bold text-gray-900">{{ stats.shipped }}</p>
        </div>
      </div>
    </div>

    <!-- Ordini Consegnati -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <CheckCircleIcon class="h-8 w-8 text-green-500" />
        </div>
        <div class="ml-3">
          <p class="text-sm font-gill-sans text-gray-500">Consegnati</p>
          <p class="text-2xl font-futura-bold text-gray-900">{{ stats.delivered }}</p>
        </div>
      </div>
    </div>

    <!-- Totale Vendite -->
    <div class="bg-white rounded-lg border border-gray-200 p-4">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <CurrencyEuroIcon class="h-8 w-8 text-purple-500" />
        </div>
        <div class="ml-3">
          <p class="text-sm font-gill-sans text-gray-500">Totale Vendite</p>
          <p class="text-2xl font-futura-bold text-gray-900">€{{ stats.total_sales.toFixed(2) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { 
  ClockIcon, 
  TruckIcon, 
  CheckCircleIcon, 
  CurrencyEuroIcon 
} from '@heroicons/vue/24/outline'

defineProps({
  stats: {
    type: Object,
    required: true,
    default: () => ({
      pending: 0,
      shipped: 0,
      delivered: 0,
      total_sales: 0
    })
  }
})
</script>
