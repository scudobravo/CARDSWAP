<template>
  <div class="shipping-zones-page">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Amministrazione Zone di Spedizione</h1>
        <p class="mt-2 text-gray-600">
          Configura le zone geografiche dove i venditori possono spedire le loro carte
        </p>
      </div>

      <!-- Componente Gestione Zone -->
      <ShippingZonesManager 
        :user="user" 
        @zones-updated="handleZonesUpdated"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ShippingZonesManager from '@/components/admin/ShippingZonesManager.vue'

// State
const user = ref({
  id: 1,
  name: 'Admin',
  role: 'admin'
})

// Methods
const handleZonesUpdated = () => {
  console.log('Zone di spedizione aggiornate')
  // Qui potresti aggiungere logica per notificare altri componenti
}

// Lifecycle
onMounted(() => {
  // Inizializzazione se necessaria
})
</script>
