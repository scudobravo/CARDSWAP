<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Wishlist
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Le tue carte preferite salvate per acquisti futuri
      </p>
    </div>

    <!-- Main Content -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
      <div class="text-center py-12">
        <HeartIcon class="mx-auto h-12 w-12 text-gray-400" />
        <h3 class="mt-2 text-sm font-gill-sans-semibold text-gray-900">Wishlist vuota</h3>
        <p class="mt-1 text-sm text-gray-500">Aggiungi carte alla tua wishlist per tenerle d'occhio.</p>
        <div class="mt-6">
          <router-link
            to="/"
            class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-gill-sans-semibold rounded-md text-white bg-primary hover:bg-primary/90"
          >
            Esplora Carte
          </router-link>
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import { HeartIcon } from '@heroicons/vue/24/outline'
</script>