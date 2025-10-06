<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Privacy
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Gestisci le tue impostazioni di privacy
      </p>
    </div>

    <!-- Main Content -->
    <div class="space-y-6">
      <!-- Profile Visibility -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Visibilità Profilo</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-gill-sans-semibold text-gray-900">Profilo pubblico</p>
              <p class="text-sm text-gray-500">Rendi il tuo profilo visibile ad altri utenti</p>
            </div>
            <input type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" checked />
          </div>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-gill-sans-semibold text-gray-900">Mostra statistiche vendite</p>
              <p class="text-sm text-gray-500">Mostra le tue statistiche di vendita nel profilo</p>
            </div>
            <input type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" checked />
          </div>
        </div>
      </div>

      <!-- Data Sharing -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Condivisione Dati</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-gill-sans-semibold text-gray-900">Analisi utilizzo</p>
              <p class="text-sm text-gray-500">Consenti l'analisi del tuo utilizzo per migliorare il servizio</p>
            </div>
            <input type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" checked />
          </div>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-gill-sans-semibold text-gray-900">Marketing</p>
              <p class="text-sm text-gray-500">Ricevi comunicazioni promozionali</p>
            </div>
            <input type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" />
          </div>
        </div>
      </div>

    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
</script>