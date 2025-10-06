<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <div class="md:flex md:items-center md:justify-between">
        <div class="flex-1 min-w-0">
          <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
            Metodi di Pagamento
          </h2>
          <p class="mt-1 text-sm text-gray-500 font-gill-sans">
            Gestisci i tuoi metodi di pagamento per acquisti e vendite
          </p>
        </div>
        <div class="mt-4 flex md:mt-0 md:ml-4">
          <button
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-gill-sans-semibold text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Aggiungi Metodo
          </button>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="text-center py-12">
        <CreditCardIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Nessun metodo di pagamento</h3>
        <p class="mt-1 text-sm text-gray-500">Aggiungi un metodo di pagamento per iniziare a fare acquisti.</p>
        <div class="mt-6">
          <button
            type="button"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-gill-sans-semibold rounded-md text-white bg-primary hover:bg-primary/90"
          >
            <PlusIcon class="h-4 w-4 mr-2" />
            Aggiungi Metodo
          </button>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { PlusIcon, CreditCardIcon } from '@heroicons/vue/24/outline'
</script>
