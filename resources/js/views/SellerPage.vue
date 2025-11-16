<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />

    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-8 pb-12">
      <!-- Breadcrumb -->
      <nav class="mb-6">
        <ol class="flex items-center space-x-2 text-sm font-gill-sans">
          <li>
            <router-link to="/" class="text-primary hover:text-secondary">
              Home
            </router-link>
          </li>
          <li class="text-gray-500">></li>
          <li class="text-gray-700 font-semibold">
            Venditore
          </li>
        </ol>
      </nav>

      <!-- Content -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <h1 class="text-3xl font-futura-bold text-primary mb-2">
          Pagina venditore
        </h1>
        <p class="text-gray-600 font-gill-sans mb-6">
          Stai visualizzando la pagina generale del venditore.
        </p>

        <div class="border-t border-gray-200 pt-4">
          <p class="text-sm text-gray-500 font-gill-sans">
            ID venditore: <span class="font-semibold text-gray-800">{{ sellerId }}</span>
          </p>
          <p class="mt-2 text-sm text-gray-500 font-gill-sans">
            Nei prossimi sviluppi qui verranno mostrate le informazioni pubbliche del venditore e tutte le sue inserzioni attive.
          </p>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'

const route = useRoute()

const sellerId = computed(() => route.params.id || route.params.sellerId || '')
</script>


