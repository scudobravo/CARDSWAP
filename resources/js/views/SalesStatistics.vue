<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Statistiche Vendite
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Monitora le performance delle tue vendite
      </p>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="text-center py-12">
        <ChartBarIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Nessuna statistica</h3>
        <p class="mt-1 text-sm text-gray-500">Le tue statistiche di vendita appariranno qui.</p>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { ChartBarIcon } from '@heroicons/vue/24/outline'
</script>