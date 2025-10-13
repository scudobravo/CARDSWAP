<template>
  <DashboardLayout>
    <!-- Header della pagina -->
    <div class="mb-8">
      <h1 class="text-3xl font-futura-bold text-gray-900 mb-2">
        Zone di Spedizione
      </h1>
      <p class="text-gray-600 font-gill-sans">
        Gestisci le tue zone di spedizione per vendere le tue carte
      </p>
    </div>

    <!-- Alert informativo -->
    <div class="mb-6">
      <div class="rounded-md bg-blue-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <InformationCircleIcon class="h-5 w-5 text-blue-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-blue-800">
              Informazioni sulle Zone di Spedizione
            </h3>
            <div class="mt-2 text-sm text-blue-700">
              <p>
                Le zone di spedizione definiscono dove puoi spedire le tue carte e a quale costo.
                Devi creare almeno una zona attiva prima di poter creare inserzioni.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Componente gestione zone -->
    <ShippingZonesManager />
  </DashboardLayout>
</template>

<script setup>
import { InformationCircleIcon } from '@heroicons/vue/24/outline'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import ShippingZonesManager from '@/components/profile/ShippingZonesManager.vue'
</script>
