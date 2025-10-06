<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="closeModal" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle as="h3" class="text-lg font-futura-bold leading-6 text-gray-900 mb-6">
                {{ isEditing ? 'Modifica Indirizzo' : 'Nuovo Indirizzo' }}
              </DialogTitle>

              <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Label -->
                <div>
                  <label for="label" class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                    Etichetta *
                  </label>
                  <input
                    id="label"
                    v-model="formData.label"
                    type="text"
                    required
                    placeholder="es. Casa, Ufficio, etc."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary font-gill-sans"
                    :class="{ 'border-red-500': errors.label }"
                  />
                  <p v-if="errors.label" class="mt-1 text-sm text-red-600">{{ errors.label[0] }}</p>
                </div>

                <!-- Nome e Cognome -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label for="first_name" class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                      Nome *
                    </label>
                    <input
                      id="first_name"
                      v-model="formData.first_name"
                      type="text"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary font-gill-sans"
                      :class="{ 'border-red-500': errors.first_name }"
                    />
                    <p v-if="errors.first_name" class="mt-1 text-sm text-red-600">{{ errors.first_name[0] }}</p>
                  </div>
                  <div>
                    <label for="last_name" class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                      Cognome *
                    </label>
                    <input
                      id="last_name"
                      v-model="formData.last_name"
                      type="text"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary font-gill-sans"
                      :class="{ 'border-red-500': errors.last_name }"
                    />
                    <p v-if="errors.last_name" class="mt-1 text-sm text-red-600">{{ errors.last_name[0] }}</p>
                  </div>
                </div>

                <!-- Azienda -->
                <div>
                  <label for="company" class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                    Azienda
                  </label>
                  <input
                    id="company"
                    v-model="formData.company"
                    type="text"
                    placeholder="Opzionale"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary font-gill-sans"
                  />
                </div>

                <!-- Indirizzo -->
                <div>
                  <label for="address_line_1" class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                    Indirizzo *
                  </label>
                  <input
                    id="address_line_1"
                    v-model="formData.address_line_1"
                    type="text"
                    required
                    placeholder="Via, Numero civico"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary font-gill-sans"
                    :class="{ 'border-red-500': errors.address_line_1 }"
                  />
                  <p v-if="errors.address_line_1" class="mt-1 text-sm text-red-600">{{ errors.address_line_1[0] }}</p>
                </div>

                <!-- Indirizzo 2 -->
                <div>
                  <label for="address_line_2" class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                    Indirizzo 2
                  </label>
                  <input
                    id="address_line_2"
                    v-model="formData.address_line_2"
                    type="text"
                    placeholder="Appartamento, Piano, etc. (opzionale)"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary font-gill-sans"
                  />
                </div>

                <!-- Città, Provincia, CAP -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div>
                    <label for="city" class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                      Città *
                    </label>
                    <input
                      id="city"
                      v-model="formData.city"
                      type="text"
                      required
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary font-gill-sans"
                      :class="{ 'border-red-500': errors.city }"
                    />
                    <p v-if="errors.city" class="mt-1 text-sm text-red-600">{{ errors.city[0] }}</p>
                  </div>
                  <div>
                    <label for="state_province" class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                      Provincia
                    </label>
                    <input
                      id="state_province"
                      v-model="formData.state_province"
                      type="text"
                      placeholder="es. MI"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary font-gill-sans"
                    />
                  </div>
                  <div>
                    <label for="postal_code" class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                      CAP *
                    </label>
                    <input
                      id="postal_code"
                      v-model="formData.postal_code"
                      type="text"
                      required
                      placeholder="es. 20100"
                      class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary font-gill-sans"
                      :class="{ 'border-red-500': errors.postal_code }"
                    />
                    <p v-if="errors.postal_code" class="mt-1 text-sm text-red-600">{{ errors.postal_code[0] }}</p>
                  </div>
                </div>

                <!-- Paese -->
                <div>
                  <label for="country" class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                    Paese *
                  </label>
                  <select
                    id="country"
                    v-model="formData.country"
                    required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary font-gill-sans"
                    :class="{ 'border-red-500': errors.country }"
                  >
                    <option value="">Seleziona paese</option>
                    <option value="IT">Italia</option>
                    <option value="FR">Francia</option>
                    <option value="DE">Germania</option>
                    <option value="ES">Spagna</option>
                    <option value="CH">Svizzera</option>
                    <option value="AT">Austria</option>
                    <option value="BE">Belgio</option>
                    <option value="NL">Paesi Bassi</option>
                    <option value="PT">Portogallo</option>
                    <option value="GB">Regno Unito</option>
                    <option value="US">Stati Uniti</option>
                    <option value="CA">Canada</option>
                  </select>
                  <p v-if="errors.country" class="mt-1 text-sm text-red-600">{{ errors.country[0] }}</p>
                </div>

                <!-- Telefono -->
                <div>
                  <label for="phone" class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">
                    Telefono
                  </label>
                  <input
                    id="phone"
                    v-model="formData.phone"
                    type="tel"
                    placeholder="+39 123 456 7890"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary focus:border-primary font-gill-sans"
                  />
                </div>

                <!-- Opzioni -->
                <div class="space-y-3">
                  <div class="flex items-center">
                    <input
                      id="is_default"
                      v-model="formData.is_default"
                      type="checkbox"
                      class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                    />
                    <label for="is_default" class="ml-2 block text-sm font-gill-sans text-gray-700">
                      Imposta come indirizzo predefinito
                    </label>
                  </div>
                  <div class="flex items-center">
                    <input
                      id="is_shipping"
                      v-model="formData.is_shipping"
                      type="checkbox"
                      class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                    />
                    <label for="is_shipping" class="ml-2 block text-sm font-gill-sans text-gray-700">
                      Usa per spedizioni
                    </label>
                  </div>
                  <div class="flex items-center">
                    <input
                      id="is_billing"
                      v-model="formData.is_billing"
                      type="checkbox"
                      class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
                    />
                    <label for="is_billing" class="ml-2 block text-sm font-gill-sans text-gray-700">
                      Usa per fatturazione
                    </label>
                  </div>
                </div>

                <!-- Pulsanti -->
                <div class="flex justify-end space-x-3 pt-6">
                  <button
                    type="button"
                    @click="closeModal"
                    class="px-4 py-2 text-sm font-gill-sans-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                  >
                    Annulla
                  </button>
                  <button
                    type="submit"
                    :disabled="loading"
                    class="px-4 py-2 text-sm font-gill-sans-semibold text-white bg-primary border border-transparent rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <span v-if="loading" class="flex items-center">
                      <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                      </svg>
                      Salvando...
                    </span>
                    <span v-else>
                      {{ isEditing ? 'Aggiorna' : 'Salva' }}
                    </span>
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  address: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close', 'save'])

const loading = ref(false)
const errors = ref({})

const isEditing = computed(() => !!props.address)

const formData = reactive({
  label: '',
  first_name: '',
  last_name: '',
  company: '',
  address_line_1: '',
  address_line_2: '',
  city: '',
  state_province: '',
  postal_code: '',
  country: 'IT',
  phone: '',
  is_default: false,
  is_shipping: true,
  is_billing: false
})

// Resetta i dati del form quando si apre/chiude il modal
watch(() => props.isOpen, (newValue) => {
  if (newValue) {
    if (props.address) {
      // Modalità modifica - popola i campi
      Object.keys(formData).forEach(key => {
        formData[key] = props.address[key] || (key === 'country' ? 'IT' : key === 'is_shipping' ? true : key.startsWith('is_') ? false : '')
      })
    } else {
      // Modalità creazione - resetta i campi
      Object.keys(formData).forEach(key => {
        formData[key] = key === 'country' ? 'IT' : key === 'is_shipping' ? true : key.startsWith('is_') ? false : ''
      })
    }
    errors.value = {}
  }
})

const closeModal = () => {
  emit('close')
}

const submitForm = async () => {
  loading.value = true
  errors.value = {}

  try {
    const addressData = { ...formData }
    
    // Rimuovi campi vuoti opzionali
    if (!addressData.company) delete addressData.company
    if (!addressData.address_line_2) delete addressData.address_line_2
    if (!addressData.state_province) delete addressData.state_province
    if (!addressData.phone) delete addressData.phone

    emit('save', addressData)
  } catch (error) {
    if (error.response && error.response.status === 422) {
      errors.value = error.response.data.errors || {}
    }
  } finally {
    loading.value = false
  }
}
</script>
