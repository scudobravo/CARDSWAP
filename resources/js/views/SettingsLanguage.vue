<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Preferenze Lingua
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Scegli la lingua per l'interfaccia e le comunicazioni
      </p>
    </div>

    <!-- Main Content -->
    <div class="space-y-6">
      <!-- Language Selection -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Lingua Interfaccia</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div class="flex items-center">
              <img src="/images/flags/it.svg" alt="Italiano" class="h-6 w-6 mr-3" />
              <div>
                <p class="text-sm font-gill-sans-semibold text-gray-900">Italiano</p>
                <p class="text-sm text-gray-500">Lingua predefinita</p>
              </div>
            </div>
            <input type="radio" name="language" value="it" class="h-4 w-4 text-primary focus:ring-primary border-gray-300" checked />
          </div>
          
          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div class="flex items-center">
              <img src="/images/flags/en.svg" alt="English" class="h-6 w-6 mr-3" />
              <div>
                <p class="text-sm font-gill-sans-semibold text-gray-900">English</p>
                <p class="text-sm text-gray-500">Coming soon</p>
              </div>
            </div>
            <input type="radio" name="language" value="en" class="h-4 w-4 text-primary focus:ring-primary border-gray-300" disabled />
          </div>
          
          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div class="flex items-center">
              <img src="/images/flags/es.svg" alt="Español" class="h-6 w-6 mr-3" />
              <div>
                <p class="text-sm font-gill-sans-semibold text-gray-900">Español</p>
                <p class="text-sm text-gray-500">Coming soon</p>
              </div>
            </div>
            <input type="radio" name="language" value="es" class="h-4 w-4 text-primary focus:ring-primary border-gray-300" disabled />
          </div>
        </div>
      </div>

      <!-- Communication Language -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Lingua Comunicazioni</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
            <div class="flex items-center">
              <img src="/images/flags/it.svg" alt="Italiano" class="h-6 w-6 mr-3" />
              <div>
                <p class="text-sm font-gill-sans-semibold text-gray-900">Italiano</p>
                <p class="text-sm text-gray-500">Email e notifiche in italiano</p>
              </div>
            </div>
            <input type="radio" name="communication-language" value="it" class="h-4 w-4 text-primary focus:ring-primary border-gray-300" checked />
          </div>
        </div>
      </div>

      <!-- Save Button -->
      <div class="flex justify-end">
        <button
          type="button"
          class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-gill-sans-semibold text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
        >
          Salva Preferenze
        </button>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
</script>