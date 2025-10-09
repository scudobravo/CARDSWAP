<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" @click="$emit('close')"></div>

      <!-- This element is to trick the browser into centering the modal contents. -->
      <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
        <form @submit.prevent="saveResponse">
          <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-futura-bold text-gray-900">
                Rispondi al Feedback
              </h3>
              <button 
                type="button"
                @click="$emit('close')"
                class="text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-primary"
              >
                <XMarkIcon class="h-6 w-6" />
              </button>
            </div>

            <div v-if="feedback" class="space-y-4">
              <!-- Feedback Originale -->
              <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-gill-sans-semibold text-gray-900 mb-2">Feedback Originale</h4>
                <div class="flex items-center space-x-2 mb-2">
                  <div class="flex items-center space-x-1">
                    <div v-for="i in 5" :key="i" class="flex-shrink-0">
                      <StarIcon 
                        :class="[
                          'h-4 w-4',
                          i <= feedback.rating ? 'text-yellow-400' : 'text-gray-300'
                        ]" 
                        fill="currentColor"
                      />
                    </div>
                  </div>
                  <span class="text-sm text-gray-600">
                    {{ feedback.buyer?.name || 'Cliente' }}
                  </span>
                </div>
                <p v-if="feedback.comment" class="text-sm text-gray-700">
                  "{{ feedback.comment }}"
                </p>
                <p v-else class="text-sm text-gray-500 italic">
                  Nessun commento
                </p>
              </div>

              <!-- Form Risposta -->
              <div>
                <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                  La tua risposta
                </label>
                <textarea 
                  v-model="formData.response"
                  rows="4"
                  placeholder="Rispondi al feedback del cliente..."
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                  required
                ></textarea>
                <p class="mt-1 text-xs text-gray-500">
                  La tua risposta sarà visibile pubblicamente insieme al feedback
                </p>
              </div>

              <!-- Error Message -->
              <div v-if="error" class="bg-red-50 border border-red-200 rounded-md p-4">
                <div class="flex">
                  <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                  <div class="ml-3">
                    <h3 class="text-sm font-gill-sans-semibold text-red-800">Errore</h3>
                    <p class="mt-1 text-sm text-red-700">{{ error }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
            <button 
              type="submit"
              :disabled="loading || !formData.response.trim()"
              class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary text-base font-gill-sans-semibold text-white hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto sm:text-sm"
            >
              <span v-if="loading" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Salvando...
              </span>
              <span v-else>{{ feedback?.seller_response ? 'Aggiorna Risposta' : 'Invia Risposta' }}</span>
            </button>
            <button 
              type="button"
              @click="$emit('close')"
              class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-gill-sans-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
            >
              Annulla
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { XMarkIcon, StarIcon, ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  feedback: {
    type: Object,
    required: true
  }
})

const emit = defineEmits(['close', 'response-saved'])

const loading = ref(false)
const error = ref('')

const formData = reactive({
  response: ''
})

// Watch per popolare il form con la risposta esistente
watch(() => props.feedback, (newFeedback) => {
  if (newFeedback) {
    formData.response = newFeedback.seller_response || ''
  }
}, { immediate: true })

const saveResponse = async () => {
  if (!formData.response.trim()) return

  loading.value = true
  error.value = ''

  try {
    const response = await fetch(`/api/sales/feedback/${props.feedback.id}/response`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        response: formData.response.trim()
      })
    })

    if (response.ok) {
      const data = await response.json()
      emit('response-saved', data.feedback)
    } else {
      const errorData = await response.json()
      throw new Error(errorData.message || 'Errore nel salvataggio della risposta')
    }
  } catch (err) {
    console.error('Errore nel salvataggio risposta:', err)
    error.value = err.message
  } finally {
    loading.value = false
  }
}
</script>
