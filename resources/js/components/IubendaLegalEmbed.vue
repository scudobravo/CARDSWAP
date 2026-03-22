<template>
  <div class="bg-gray-light min-h-screen">
    <Header />
    <div class="bg-white py-8 sm:py-12">
      <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
          <h1 class="text-4xl font-futura-bold text-primary sm:text-5xl mb-2">{{ title }}</h1>
          <p class="text-lg text-gray-600 font-gill-sans">Documento aggiornato su iubenda</p>
        </div>
        <div class="rounded-lg overflow-hidden border border-gray-200 min-h-[70vh] bg-white shadow-sm">
          <iframe
            :src="documentUrl"
            class="w-full min-h-[70vh] border-0"
            :title="title"
            loading="lazy"
          />
        </div>
        <p class="text-center mt-6 font-gill-sans text-sm text-gray-600">
          Non vedi il documento?
          <a
            :href="documentUrl"
            target="_blank"
            rel="noopener noreferrer"
            class="text-secondary hover:underline font-gill-sans-semibold"
          >
            Apri in una nuova scheda
          </a>
        </p>
      </div>
    </div>
    <Footer />
  </div>
</template>

<script setup>
import Header from './Header.vue'
import Footer from './Footer.vue'

defineProps({
  title: { type: String, required: true },
  documentUrl: { type: String, required: true },
})
</script>
