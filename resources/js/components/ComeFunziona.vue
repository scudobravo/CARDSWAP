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
          <div class="come-funziona-card-content">
            <h3 class="come-funziona-card-title">{{ card.title }}</h3>
            <div class="come-funziona-card-accent" />
            <p class="come-funziona-card-desc">{{ card.description }}</p>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
const cards = [
  {
    id: 1,
    title: 'Crea il tuo\naccount',
    description: 'Registrati in pochi secondi e inizia ad esplorare.',
    icon: '/images/come-funziona/crea-account.svg',
  },
  {
    id: 2,
    title: 'Pubblica o\ntrova carte',
    description: 'Carica le tue carte oppure sfoglia migliaia di annunci.',
    icon: '/images/come-funziona/trova-carte.svg',
  },
  {
    id: 3,
    title: 'Utenti reali e\nverificati',
    description: 'Verifichiamo l\'identità degli utenti per mantenere il marketplace autentico e affidabile.',
    icon: '/images/come-funziona/utenti-sicuri.svg',
  },
  {
    id: 4,
    title: 'Compra. Vendi.\nColleziona.',
    description: 'Tutto ciò che ti serve per comprare o vendere carte in un unico posto.',
    icon: '/images/come-funziona/compra-vendi-colleziona.svg',
  },
]
</script>

<style scoped>
.come-funziona {
  background-color: var(--color-gray-light);
  padding: 48px 20px;
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

/* Titolo sezione: stessi font e scala del resto del sito (es. Categorie) */
.come-funziona-title {
  font-size: 1.5rem;
  font-weight: 700;
  text-align: center;
  margin-bottom: 2rem;
  font-family: 'Futura', 'Futura PT', 'Trebuchet MS', Arial, sans-serif;
  color: var(--color-primary);
}

@media (min-width: 768px) {
  .come-funziona-title {
    font-size: 1.875rem;
    margin-bottom: 3.75rem;
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

/* Mobile: layout orizzontale, icona 48px a sinistra centrata in altezza alla card */
@media (max-width: 767px) {
  .come-funziona-card {
    flex-direction: row;
    text-align: left;
    align-items: center;
    gap: 16px;
    padding: 20px 16px;
  }
  
  .come-funziona-card-content {
    flex: 1;
    min-width: 0;
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
  
  .come-funziona-card-accent {
    margin: 6px 0 8px;
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

/* Titolo card: Futura bold come il resto del sito */
.come-funziona-card-title {
  font-size: 1rem;
  font-weight: 700;
  margin-top: 0;
  margin-bottom: 0;
  font-family: 'Futura', 'Futura PT', 'Trebuchet MS', Arial, sans-serif;
  color: var(--color-primary);
}

@media (min-width: 768px) {
  .come-funziona-card-title {
    font-size: 1.125rem;
  }
}

/* Desktop: altezza fissa per i titoli così stanno tutti allineati (2 righe) */
@media (min-width: 1024px) {
  .come-funziona-card-title {
    min-height: 2.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    white-space: pre-line;
  }
}

.come-funziona-card-accent {
  width: 40px;
  height: 3px;
  background-color: var(--color-secondary);
  margin: 8px auto 12px;
}

/* Descrizione: Gill Sans come il resto del sito */
.come-funziona-card-desc {
  font-size: 0.875rem;
  line-height: 1.5;
  margin: 0;
  font-family: 'Gill Sans', 'Gill Sans MT', Calibri, 'Trebuchet MS', sans-serif;
  color: var(--color-text-light);
}
</style>
