<template>
  <footer class="bg-primary text-white">
    <!-- Layout verticale centrato (mobile-first come da allegato) -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <div class="flex flex-col items-center text-center">
        <!-- Logo -->
        <div class="mb-6">
          <img src="/images/logos/SOLO SIMBOLO - BIANCO.png" alt="CardSwap" class="h-12 w-auto mx-auto" />
        </div>

        <!-- Link legali -->
        <div class="flex flex-wrap justify-center gap-x-6 gap-y-2 mb-6">
          <router-link to="/terms-and-conditions" class="text-white font-gill-sans text-sm hover:text-secondary transition-colors">Termini e Condizioni</router-link>
          <router-link to="/privacy-policy" class="text-white font-gill-sans text-sm hover:text-secondary transition-colors">Privacy Policy</router-link>
          <router-link to="/cookie-policy" class="text-white font-gill-sans text-sm hover:text-secondary transition-colors">Cookie Policy</router-link>
        </div>

        <!-- Hai domande? + Contattaci (CTA oro #C9A14A) -->
        <p class="text-white font-gill-sans text-sm mb-4">Hai domande?</p>
        <router-link
          to="/contact"
          class="inline-block bg-[#C9A14A] text-white px-6 py-2.5 rounded-lg font-gill-sans-semibold text-sm hover:opacity-90 transition-opacity"
        >
          Contattaci
        </router-link>

        <!-- Separatore e copyright / P.IVA -->
        <div class="w-full border-t border-white/20 mt-8 pt-6">
          <p class="text-white font-gill-sans text-xs sm:text-sm">
            © 2026 CardSwap S.r.l. — P.IVA 04320241203 — Tutti i diritti riservati
          </p>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup>
</script>
