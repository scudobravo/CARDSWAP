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
      </div>
    </div>

    <!-- Main Content -->
    <div class="space-y-6">
      <!-- Stripe Connect - Disponibile per tutti (per vendere in futuro) -->
      <StripeConnectManager />

      <!-- Informazioni metodi di pagamento per acquisti -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Metodi di Pagamento per Acquisti</h3>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <CreditCardIcon class="h-5 w-5 text-blue-400" />
            </div>
            <div class="ml-3">
              <h4 class="text-sm font-gill-sans-semibold text-blue-800">Pagamenti durante il checkout</h4>
              <p class="mt-1 text-sm text-blue-700">
                I metodi di pagamento vengono gestiti direttamente durante il checkout. 
                Non è necessario salvare carte di credito in anticipo. 
                Puoi inserire i dati della carta quando effettui un acquisto.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import StripeConnectManager from '@/components/StripeConnectManager.vue'
import { CreditCardIcon } from '@heroicons/vue/24/outline'
</script>
