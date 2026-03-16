<template>
  <section 
    class="hero-section relative overflow-hidden flex items-center"
    :style="{ backgroundImage: `url(${heroBgUrl})` }"
  >
    <!-- Overlay leggibilità -->
    <div class="absolute inset-0 bg-white/60" aria-hidden="true" />
    
    <div class="hero-container relative z-10 w-full">
      <!-- Logo -->
      <div class="hero-logo mb-6">
        <img 
          src="/images/logos/logo-colorato.svg" 
          alt="CARDSWAP" 
          class="hero-logo-img mx-auto"
        />
      </div>
      
      <!-- Titolo Hero -->
      <h1 class="hero-title text-primary font-futura-bold text-center leading-[1.1]">
        Il marketplace per le tue carte collezionabili
      </h1>
      
      <!-- Sottotitolo Hero -->
      <p class="hero-subtitle text-primary/90 text-center font-gill-sans mt-4">
        Pubblica le tue carte e trova quelle che cerchi
      </p>
    </div>
  </section>
</template>

<script setup>
// Background 1920x850 responsive (cover, center, no-repeat)
const heroBgUrl = '/images/hero/hero-cardswap.svg'
</script>

<style scoped>
.hero-section {
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
  min-height: 600px;
  padding-top: 80px;
  padding-bottom: 80px;
}

@media (min-width: 768px) {
  .hero-section {
    min-height: 850px;
  }
}

.hero-container {
  max-width: 1100px;
  margin-left: auto;
  margin-right: auto;
  padding-left: 20px;
  padding-right: 20px;
}

@media (min-width: 768px) {
  .hero-container {
    padding-left: 32px;
    padding-right: 32px;
  }
}

/* Logo: desktop 43px, mobile 32px, margin-bottom 24px */
.hero-logo {
  margin-bottom: 24px;
}

.hero-logo-img {
  height: 32px;
  width: auto;
}

@media (min-width: 768px) {
  .hero-logo-img {
    height: 43px;
  }
}

/* Titolo: 53px desktop / 42px tablet / 32px mobile, line-height 1.1 */
.hero-title {
  font-size: 32px;
}

@media (min-width: 768px) {
  .hero-title {
    font-size: 42px;
  }
}

@media (min-width: 1024px) {
  .hero-title {
    font-size: 53px;
  }
}

/* Sottotitolo: 25px desktop / 20px tablet / 16px mobile */
.hero-subtitle {
  font-size: 16px;
}

@media (min-width: 768px) {
  .hero-subtitle {
    font-size: 20px;
  }
}

@media (min-width: 1024px) {
  .hero-subtitle {
    font-size: 25px;
  }
}
</style>
