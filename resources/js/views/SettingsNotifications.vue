<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Notifiche
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Gestisci le tue preferenze di notifica
      </p>
    </div>

    <!-- Main Content -->
    <div class="space-y-6">
      <!-- Email Notifications -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Notifiche Email</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-gill-sans-semibold text-gray-900">Nuovi ordini</p>
              <p class="text-sm text-gray-500">Ricevi notifiche quando ricevi un nuovo ordine</p>
            </div>
            <input type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" checked />
          </div>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-gill-sans-semibold text-gray-900">Aggiornamenti ordine</p>
              <p class="text-sm text-gray-500">Ricevi notifiche sui cambiamenti di stato degli ordini</p>
            </div>
            <input type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" checked />
          </div>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-gill-sans-semibold text-gray-900">Feedback ricevuti</p>
              <p class="text-sm text-gray-500">Ricevi notifiche quando ricevi un nuovo feedback</p>
            </div>
            <input type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" checked />
          </div>
        </div>
      </div>

      <!-- Push Notifications -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Notifiche Push</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-gill-sans-semibold text-gray-900">Messaggi</p>
              <p class="text-sm text-gray-500">Ricevi notifiche push per i nuovi messaggi</p>
            </div>
            <input type="checkbox" class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded" />
          </div>
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-gill-sans-semibold text-gray-900">Offerte</p>
              <p class="text-sm text-gray-500">Ricevi notifiche per le offerte speciali</p>
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