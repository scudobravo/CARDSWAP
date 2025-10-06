<template>
  <!-- Overlay -->
  <div v-if="isOpen" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click="closePopup">
    <!-- Popup Content -->
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6" @click.stop>
      <!-- Header -->
      <div class="flex justify-between items-center mb-6">
        <h3 class="text-xl font-futura-bold text-primary">Segnala Problema</h3>
        <button @click="closePopup" class="text-gray-400 hover:text-gray-600 transition-colors">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submitReport" class="space-y-6">
        <!-- Problem Type Selection -->
        <div>
          <label class="block text-sm font-futura-bold text-primary mb-3">Tipo di Problema</label>
          <div class="space-y-2">
            <label v-for="problemType in problemTypes" :key="problemType.value" class="flex items-center space-x-3 cursor-pointer">
              <input 
                type="radio" 
                :value="problemType.value" 
                v-model="selectedProblemType"
                class="w-4 h-4 text-primary border-gray-300 focus:ring-primary"
              />
              <span class="text-sm font-gill-sans text-gray-700">{{ problemType.label }}</span>
            </label>
          </div>
        </div>

        <!-- Additional Details -->
        <div>
          <label for="details" class="block text-sm font-futura-bold text-primary mb-2">
            Dettagli Aggiuntivi
          </label>
          <textarea 
            id="details"
            v-model="details"
            rows="4"
            placeholder="Descrivi il problema in dettaglio..."
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary font-gill-sans text-sm resize-none"
          ></textarea>
        </div>

        <!-- Contact Email (Optional) -->
        <div>
          <label for="email" class="block text-sm font-futura-bold text-primary mb-2">
            Email (opzionale)
          </label>
          <input 
            type="email"
            id="email"
            v-model="email"
            placeholder="La tua email per ricevere aggiornamenti"
            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary font-gill-sans text-sm"
          />
        </div>

        <!-- Buttons -->
        <div class="flex space-x-3 pt-4">
          <button 
            type="button"
            @click="closePopup"
            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-futura-bold text-sm hover:bg-gray-50 transition-colors"
          >
            Annulla
          </button>
          <button 
            type="submit"
            :disabled="!selectedProblemType || isSubmitting"
            class="flex-1 px-4 py-2 bg-primary text-white rounded-lg font-futura-bold text-sm hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isSubmitting">Invio...</span>
            <span v-else>Invia Report</span>
          </button>
        </div>
      </form>

      <!-- Success Message -->
      <div v-if="showSuccess" class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg text-sm font-gill-sans">
        Report inviato con successo! Grazie per la tua segnalazione.
      </div>

      <!-- Error Message -->
      <div v-if="showError" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg text-sm font-gill-sans">
        Errore nell'invio del report. Riprova più tardi.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'

// Props
const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  productId: {
    type: [String, Number],
    default: null
  },
  sellerName: {
    type: String,
    default: ''
  }
})

// Emits
const emit = defineEmits(['close'])

// Reactive data
const selectedProblemType = ref('')
const details = ref('')
const email = ref('')
const isSubmitting = ref(false)
const showSuccess = ref(false)
const showError = ref(false)

// Problem types
const problemTypes = ref([
  { value: 'fake_card', label: 'Carta falsa o contraffatta' },
  { value: 'wrong_condition', label: 'Condizione non corrispondente' },
  { value: 'overpriced', label: 'Prezzo eccessivo' },
  { value: 'inappropriate_content', label: 'Contenuto inappropriato' },
  { value: 'seller_behavior', label: 'Comportamento scorretto del venditore' },
  { value: 'technical_issue', label: 'Problema tecnico del sito' },
  { value: 'other', label: 'Altro' }
])

// Methods
const closePopup = () => {
  emit('close')
  resetForm()
}

const resetForm = () => {
  selectedProblemType.value = ''
  details.value = ''
  email.value = ''
  showSuccess.value = false
  showError.value = false
  isSubmitting.value = false
}

const submitReport = async () => {
  if (!selectedProblemType.value) return

  isSubmitting.value = true
  showSuccess.value = false
  showError.value = false

  try {
    // Prepare report data
    const reportData = {
      product_id: props.productId,
      seller_name: props.sellerName,
      problem_type: selectedProblemType.value,
      details: details.value,
      email: email.value,
      timestamp: new Date().toISOString()
    }

    // Send report via API
    const response = await fetch('/api/reports', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      },
      body: JSON.stringify(reportData)
    })

    if (response.ok) {
      showSuccess.value = true
      // Auto close after 2 seconds
      setTimeout(() => {
        closePopup()
      }, 2000)
    } else {
      throw new Error('Failed to submit report')
    }
  } catch (error) {
    console.error('Error submitting report:', error)
    showError.value = true
  } finally {
    isSubmitting.value = false
  }
}

// Watch for popup open/close
watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    resetForm()
  }
})
</script>
