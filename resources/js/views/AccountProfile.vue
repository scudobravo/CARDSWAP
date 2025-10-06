<template>
  <DashboardLayout>
    <!-- Header -->
    <div class="mb-8">
      <h2 class="text-2xl font-futura-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
        Profilo Personale
      </h2>
      <p class="mt-1 text-sm text-gray-500 font-gill-sans">
        Gestisci le tue informazioni personali
      </p>
    </div>

    <!-- Main Content -->
    <SimpleProfileForm />
  </DashboardLayout>
</template>

<script setup>
import DashboardLayout from '@/layouts/DashboardLayout.vue'
import SimpleProfileForm from '@/components/SimpleProfileForm.vue'
</script>
