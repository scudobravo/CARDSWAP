<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        I miei Ordini
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Visualizza e gestisci i tuoi ordini
      </p>
    </div>

    <!-- KYC Warning -->
    <div v-if="!kycCompleted" class="mb-8">
      <div class="rounded-md bg-yellow-50 p-4">
        <div class="flex">
          <div class="flex-shrink-0">
            <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-gill-sans-semibold text-yellow-800">
              Verifica KYC Richiesta
            </h3>
            <div class="mt-2 text-sm text-yellow-700">
              <p>Per completare ordini e accedere a tutte le funzionalità, devi completare la verifica KYC. Questo processo è necessario per garantire la sicurezza della piattaforma.</p>
            </div>
            <div class="mt-4">
              <router-link
                to="/kyc"
                class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-gill-sans-semibold rounded-md text-yellow-800 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500"
              >
                Inizia Verifica KYC
              </router-link>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="text-center py-12">
        <ShoppingBagIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Nessun ordine</h3>
        <p class="mt-1 text-sm text-gray-500">I tuoi ordini appariranno qui quando effettuerai degli acquisti.</p>
        <div class="mt-6">
          <router-link
            to="/"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-gill-sans-semibold rounded-md text-white bg-primary hover:bg-primary/90"
          >
            Inizia a Comprare
          </router-link>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { ShoppingBagIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const authStore = useAuthStore()
const kycCompleted = computed(() => authStore.user?.kyc_status === 'approved')
</script>