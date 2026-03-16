<template>
  <section class="come-funziona">
    <div class="come-funziona-inner">
      <h2 class="come-funziona-title">Come funziona</h2>
      
      <div class="come-funziona-grid">
        <article 
          v-for="card in cards" 
          :key="card.id" 
          class="come-funziona-card"
        >
          <div class="come-funziona-card-icon-wrap">
            <img 
              :src="card.icon" 
              :alt="card.title" 
              class="come-funziona-card-icon"
            />
          </div>
          <h3 class="come-funziona-card-title">{{ card.title }}</h3>
          <div class="come-funziona-card-accent" />
          <p class="come-funziona-card-desc">{{ card.description }}</p>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
const cards = [
  {
    id: 1,
    title: 'Crea il tuo account',
    description: 'Registrati in pochi secondi e inizia ad esplorare.',
    icon: '/images/come-funziona/crea-account.svg',
  },
  {
    id: 2,
    title: 'Pubblica o trova carte',
    description: 'Carica le tue carte oppure sfoglia migliaia di annunci.',
    icon: '/images/come-funziona/trova-carte.svg',
  },
  {
    id: 3,
    title: 'Utenti reali e verificati',
    description: 'Verifichiamo l\'identità degli utenti per mantenere il marketplace autentico e affidabile.',
    icon: '/images/come-funziona/utenti-sicuri.svg',
  },
  {
    id: 4,
    title: 'Compra. Vendi. Colleziona.',
    description: 'Tutto ciò che ti serve per comprare o vendere carte in un unico posto.',
    icon: '/images/come-funziona/compra-vendi-colleziona.svg',
  },
]
</script>

<style scoped>
.come-funziona {
  background-color: #F4F4F4;
  padding: 80px 20px;
}

@media (min-width: 768px) {
  .come-funziona {
    padding: 120px 32px;
  }
}

.come-funziona-inner {
  max-width: 1200px;
  margin-left: auto;
  margin-right: auto;
}

.come-funziona-title {
  font-size: 28px;
  font-weight: 700;
  color: #1a1a2e;
  text-align: center;
  margin-bottom: 40px;
  font-family: 'Instrument Sans', sans-serif;
}

@media (min-width: 768px) {
  .come-funziona-title {
    font-size: 40px;
    margin-bottom: 60px;
  }
}

.come-funziona-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 20px;
}

@media (min-width: 768px) {
  .come-funziona-grid {
    grid-template-columns: repeat(2, 1fr);
    gap: 32px;
  }
}

@media (min-width: 1024px) {
  .come-funziona-grid {
    grid-template-columns: repeat(4, 1fr);
  }
}

.come-funziona-card {
  background: #fff;
  border-radius: 20px;
  padding: 20px;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.04);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

@media (min-width: 768px) {
  .come-funziona-card {
    padding: 32px;
  }
}

/* Mobile: layout orizzontale con icona 48px a sinistra */
@media (max-width: 767px) {
  .come-funziona-card {
    flex-direction: row;
    text-align: left;
    align-items: flex-start;
    gap: 16px;
  }
  
  .come-funziona-card-icon-wrap {
    flex-shrink: 0;
    margin-bottom: 0 !important;
  }
  
  .come-funziona-card-icon {
    height: 48px !important;
    width: 48px !important;
  }
  
  .come-funziona-card-title {
    margin-top: 0;
  }
}

.come-funziona-card-icon-wrap {
  margin-bottom: 20px;
}

.come-funziona-card-icon {
  height: 110px;
  width: auto;
  max-width: 100%;
  object-fit: contain;
}

.come-funziona-card-title {
  font-size: 1.125rem;
  font-weight: 700;
  color: #1a1a2e;
  margin-top: 0;
  margin-bottom: 0;
  font-family: 'Instrument Sans', sans-serif;
}

.come-funziona-card-accent {
  width: 40px;
  height: 3px;
  background-color: #f59e0b;
  margin: 8px 0 12px;
}

@media (max-width: 767px) {
  .come-funziona-card-accent {
    margin: 6px 0 8px;
  }
}

.come-funziona-card-desc {
  font-size: 0.9375rem;
  color: #6b7280;
  line-height: 1.5;
  margin: 0;
  font-family: 'Instrument Sans', sans-serif;
}
</style>
