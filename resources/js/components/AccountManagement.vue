<template>
  <div class="bg-white rounded-lg border border-gray-200 p-6">
    <div class="mb-6">
      <h2 class="text-2xl font-futura-bold text-gray-900 mb-2">Il mio Account</h2>
      <p class="text-gray-600 font-gill-sans">Gestisci le tue informazioni personali e le impostazioni di sicurezza</p>
    </div>

    <!-- Profilo Personale -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
      <!-- Campi Modificabili -->
      <div class="space-y-6">
        <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Informazioni Personali</h3>
        
        <!-- Username -->
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Username</label>
          <div class="flex gap-3">
            <input 
              v-model="profile.username" 
              type="text" 
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
              placeholder="Username"
              :disabled="!editing.username"
            />
            <button 
              @click="startEdit('username')"
              :disabled="editing.username"
              class="px-4 py-2 bg-gray-600 text-white text-sm font-gill-sans-semibold rounded-md hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ editing.username ? 'Modifica...' : 'Modifica' }}
            </button>
          </div>
        </div>

        <!-- Nome e Cognome -->
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Nome e Cognome</label>
          <div class="flex gap-3">
            <input 
              v-model="profile.full_name" 
              type="text" 
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
              placeholder="Nome e Cognome"
              :disabled="!editing.full_name"
            />
            <button 
              @click="startEdit('full_name')"
              :disabled="editing.full_name"
              class="px-4 py-2 bg-gray-600 text-white text-sm font-gill-sans-semibold rounded-md hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ editing.full_name ? 'Modifica...' : 'Modifica' }}
            </button>
          </div>
        </div>

        <!-- Email -->
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Email</label>
          <div class="flex gap-3">
            <input 
              v-model="profile.email" 
              type="email" 
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
              placeholder="Email"
              :disabled="!editing.email"
            />
            <button 
              @click="startEdit('email')"
              :disabled="editing.email"
              class="px-4 py-2 bg-gray-600 text-white text-sm font-gill-sans-semibold rounded-md hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ editing.email ? 'Modifica...' : 'Modifica' }}
            </button>
          </div>
        </div>

        <!-- Password -->
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Password</label>
          <div class="flex gap-3">
            <input 
              type="password" 
              class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
              placeholder="••••••••"
              disabled
            />
            <button 
              @click="startEdit('password')"
              class="px-4 py-2 bg-gray-600 text-white text-sm font-gill-sans-semibold rounded-md hover:bg-gray-700"
            >
              Modifica
            </button>
          </div>
        </div>
      </div>

      <!-- Campi Read-Only -->
      <div class="space-y-6">
        <h3 class="text-lg font-futura-bold text-gray-900 mb-4">Informazioni di Sistema</h3>
        
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Data di Registrazione</label>
          <div class="p-3 bg-gray-50 rounded-md">
            <span class="text-gray-900 font-gill-sans">{{ formatDate(profile.registration_date) }}</span>
          </div>
        </div>

        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Codice Fiscale</label>
          <div class="p-3 bg-gray-50 rounded-md">
            <span class="text-gray-900 font-gill-sans">{{ profile.fiscal_code || 'Non fornito' }}</span>
          </div>
        </div>

        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Data di Nascita</label>
          <div class="p-3 bg-gray-50 rounded-md">
            <span class="text-gray-900 font-gill-sans">{{ formatDate(profile.date_of_birth) }}</span>
          </div>
        </div>

        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Luogo di Nascita</label>
          <div class="p-3 bg-gray-50 rounded-md">
            <span class="text-gray-900 font-gill-sans">{{ profile.birthplace || 'Non fornito' }}</span>
          </div>
        </div>

        <!-- Status KYC -->
        <div>
          <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Status KYC</label>
          <div class="p-3 rounded-md" :class="kycStatusClass">
            <div class="flex items-center gap-2">
              <component :is="kycStatusIcon" class="w-5 h-5" />
              <span class="font-gill-sans-semibold">{{ kycStatusText }}</span>
            </div>
            <p class="text-sm mt-1">{{ kycStatusDescription }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Note Informativa -->
    <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
      <div class="flex">
        <div class="flex-shrink-0">
          <ExclamationTriangleIcon class="h-5 w-5 text-yellow-400" />
        </div>
        <div class="ml-3">
          <p class="text-sm font-gill-sans text-yellow-800">
            <strong>Nota:</strong> Al click su "Modifica" si apre un modal per modificare email o password. 
            Per completare il profilo KYC e accedere alle funzionalità di vendita, 
            <button @click="startKycProcess" class="text-yellow-900 underline hover:text-yellow-700">
              clicca qui
            </button>.
          </p>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal per Modifica Email/Password -->
  <TransitionRoot as="template" :show="showModal">
    <Dialog class="relative z-50" @close="closeModal">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
              <div class="absolute right-0 top-0 hidden pr-4 pt-4 sm:block">
                <button
                  type="button"
                  class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                  @click="closeModal"
                >
                  <span class="sr-only">Close</span>
                  <XMarkIcon class="h-6 w-6" aria-hidden="true" />
                </button>
              </div>

              <div class="sm:flex sm:items-start">
                <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-primary/10 sm:mx-0 sm:h-10 sm:w-10">
                  <component :is="modalIcon" class="h-6 w-6 text-primary" />
                </div>
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                  <DialogTitle as="h3" class="text-lg font-futura-bold leading-6 text-gray-900">
                    {{ modalTitle }}
                  </DialogTitle>
                  <div class="mt-4">
                    <p class="text-sm text-gray-500 font-gill-sans">
                      {{ modalDescription }}
                    </p>
                  </div>
                </div>
              </div>

              <!-- Modal Content -->
              <div class="mt-6">
                <!-- Email Modal -->
                <div v-if="modalType === 'email'" class="space-y-4">
                  <div>
                    <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Nuova Email</label>
                    <input
                      v-model="modalData.email"
                      type="email"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                      placeholder="nuova@email.com"
                    />
                  </div>
                </div>

                <!-- Password Modal -->
                <div v-if="modalType === 'password'" class="space-y-4">
                  <div>
                    <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Vecchia Password</label>
                    <div class="relative">
                      <input
                        v-model="modalData.oldPassword"
                        :type="showOldPassword ? 'text' : 'password'"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary pr-10"
                        placeholder="Inserisci la password attuale"
                      />
                      <button
                        type="button"
                        @click="showOldPassword = !showOldPassword"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center"
                      >
                        <EyeIcon v-if="!showOldPassword" class="h-5 w-5 text-gray-400" />
                        <EyeSlashIcon v-else class="h-5 w-5 text-gray-400" />
                      </button>
                    </div>
                  </div>

                  <div>
                    <label class="block text-sm font-gill-sans-semibold text-gray-700 mb-2">Nuova Password</label>
                    <div class="relative">
                      <input
                        v-model="modalData.newPassword"
                        :type="showNewPassword ? 'text' : 'password'"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary pr-10"
                        placeholder="Inserisci la nuova password"
                        @input="validatePassword"
                      />
                      <button
                        type="button"
                        @click="showNewPassword = !showNewPassword"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center"
                      >
                        <EyeIcon v-if="!showNewPassword" class="h-5 w-5 text-gray-400" />
                        <EyeSlashIcon v-else class="h-5 w-5 text-gray-400" />
                      </button>
                    </div>
                  </div>

                  <!-- Password Requirements -->
                  <div class="space-y-2">
                    <p class="text-sm font-gill-sans-semibold text-gray-700">Requisiti Password:</p>
                    <ul class="space-y-1 text-sm">
                      <li class="flex items-center gap-2" :class="passwordValidation.minLength ? 'text-green-600' : 'text-red-600'">
                        <CheckIcon v-if="passwordValidation.minLength" class="h-4 w-4" />
                        <XMarkIcon v-else class="h-4 w-4" />
                        Lunghezza minima: 8 caratteri
                      </li>
                      <li class="flex items-center gap-2" :class="passwordValidation.maxLength ? 'text-green-600' : 'text-red-600'">
                        <CheckIcon v-if="passwordValidation.maxLength" class="h-4 w-4" />
                        <XMarkIcon v-else class="h-4 w-4" />
                        Lunghezza massima: 64 caratteri
                      </li>
                      <li class="flex items-center gap-2" :class="passwordValidation.hasSpecialChar ? 'text-green-600' : 'text-red-600'">
                        <CheckIcon v-if="passwordValidation.hasSpecialChar" class="h-4 w-4" />
                        <XMarkIcon v-else class="h-4 w-4" />
                        Carattere speciale: è richiesto almeno un carattere speciale (!, @, #, $, %, ecc.)
                      </li>
                      <li class="flex items-center gap-2" :class="passwordValidation.hasLowerCase ? 'text-green-600' : 'text-red-600'">
                        <CheckIcon v-if="passwordValidation.hasLowerCase" class="h-4 w-4" />
                        <XMarkIcon v-else class="h-4 w-4" />
                        Lettera minuscola: è richiesta almeno una lettera minuscola (a-z)
                      </li>
                      <li class="flex items-center gap-2" :class="passwordValidation.hasUpperCase ? 'text-green-600' : 'text-red-600'">
                        <CheckIcon v-if="passwordValidation.hasUpperCase" class="h-4 w-4" />
                        <XMarkIcon v-else class="h-4 w-4" />
                        Lettera maiuscola: è richiesta almeno una lettera maiuscola (A-Z)
                      </li>
                      <li class="flex items-center gap-2" :class="passwordValidation.hasNumber ? 'text-green-600' : 'text-red-600'">
                        <CheckIcon v-if="passwordValidation.hasNumber" class="h-4 w-4" />
                        <XMarkIcon v-else class="h-4 w-4" />
                        Numero: è richiesto almeno un carattere numerico (0-9)
                      </li>
                    </ul>
                  </div>
                </div>
              </div>

              <!-- Modal Actions -->
              <div class="mt-6 sm:flex sm:flex-row-reverse gap-3">
                <button
                  type="button"
                  @click="saveChanges"
                  :disabled="!canSave"
                  class="inline-flex w-full justify-center rounded-md bg-primary px-3 py-2 text-sm font-gill-sans-semibold text-white shadow-sm hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed sm:ml-3 sm:w-auto"
                >
                  <CheckIcon class="h-4 w-4 mr-2" />
                  Invia
                </button>
                <button
                  type="button"
                  @click="closeModal"
                  class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-gill-sans-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                >
                  <XMarkIcon class="h-4 w-4 mr-2" />
                  Annulla
                </button>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useAuthStore } from '@/stores/auth'
import {
  Dialog,
  DialogPanel,
  DialogTitle,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  ExclamationTriangleIcon,
  XMarkIcon,
  CheckIcon,
  EyeIcon,
  EyeSlashIcon,
  EnvelopeIcon,
  LockClosedIcon,
  ShieldCheckIcon,
  ExclamationCircleIcon
} from '@heroicons/vue/24/outline'

const authStore = useAuthStore()

// Profile data
const profile = reactive({
  username: authStore.user?.username || authStore.user?.name || '',
  full_name: authStore.user?.full_name || authStore.user?.name || '',
  email: authStore.user?.email || '',
  registration_date: authStore.user?.created_at || '2024-01-01',
  fiscal_code: authStore.user?.fiscal_code || '',
  date_of_birth: authStore.user?.date_of_birth || '',
  birthplace: authStore.user?.birthplace || '',
  kyc_status: authStore.user?.kyc_status || 'pending'
})

// Editing state
const editing = reactive({
  username: false,
  full_name: false,
  email: false,
  password: false
})

// Modal state
const showModal = ref(false)
const modalType = ref('')
const modalData = reactive({
  email: '',
  oldPassword: '',
  newPassword: ''
})

// Password visibility
const showOldPassword = ref(false)
const showNewPassword = ref(false)

// Password validation
const passwordValidation = reactive({
  minLength: false,
  maxLength: false,
  hasSpecialChar: false,
  hasLowerCase: false,
  hasUpperCase: false,
  hasNumber: false
})

// Computed properties
const modalTitle = computed(() => {
  return modalType.value === 'email' ? 'Modifica Email' : 'Modifica Password'
})

const modalDescription = computed(() => {
  return modalType.value === 'email' 
    ? 'Inserisci la nuova email per il tuo account.'
    : 'Inserisci la password attuale e la nuova password.'
})

const modalIcon = computed(() => {
  return modalType.value === 'email' ? EnvelopeIcon : LockClosedIcon
})

const canSave = computed(() => {
  if (modalType.value === 'email') {
    return modalData.email && modalData.email.includes('@')
  } else if (modalType.value === 'password') {
    return modalData.oldPassword && 
           modalData.newPassword && 
           Object.values(passwordValidation).every(v => v)
  }
  return false
})

const kycStatusClass = computed(() => {
  switch (profile.kyc_status) {
    case 'approved':
      return 'bg-green-50 border border-green-200'
    case 'pending':
      return 'bg-yellow-50 border border-yellow-200'
    case 'rejected':
      return 'bg-red-50 border border-red-200'
    default:
      return 'bg-gray-50 border border-gray-200'
  }
})

const kycStatusText = computed(() => {
  switch (profile.kyc_status) {
    case 'approved':
      return 'Verificato'
    case 'pending':
      return 'In Attesa'
    case 'rejected':
      return 'Rifiutato'
    default:
      return 'Non Iniziato'
  }
})

const kycStatusDescription = computed(() => {
  switch (profile.kyc_status) {
    case 'approved':
      return 'Il tuo profilo è stato verificato. Puoi vendere carte.'
    case 'pending':
      return 'La verifica è in corso. Riceverai una notifica quando sarà completata.'
    case 'rejected':
      return 'La verifica è stata rifiutata. Controlla i documenti e riprova.'
    default:
      return 'Completa la verifica per iniziare a vendere carte.'
  }
})

const kycStatusIcon = computed(() => {
  switch (profile.kyc_status) {
    case 'approved':
      return CheckIcon
    case 'pending':
      return ExclamationCircleIcon
    case 'rejected':
      return XMarkIcon
    default:
      return ShieldCheckIcon
  }
})

// Methods
const formatDate = (dateString) => {
  if (!dateString) return 'Non fornito'
  return new Date(dateString).toLocaleDateString('it-IT')
}

const startEdit = (field) => {
  if (field === 'password') {
    modalType.value = 'password'
    modalData.oldPassword = ''
    modalData.newPassword = ''
    showModal.value = true
  } else if (field === 'email') {
    modalType.value = 'email'
    modalData.email = profile.email
    showModal.value = true
  } else {
    editing[field] = true
  }
}

const closeModal = () => {
  showModal.value = false
  modalType.value = ''
  modalData.email = ''
  modalData.oldPassword = ''
  modalData.newPassword = ''
  showOldPassword.value = false
  showNewPassword.value = false
  resetPasswordValidation()
}

const validatePassword = () => {
  const password = modalData.newPassword
  
  passwordValidation.minLength = password.length >= 8
  passwordValidation.maxLength = password.length <= 64
  passwordValidation.hasSpecialChar = /[!@#$%^&*(),.?":{}|<>]/.test(password)
  passwordValidation.hasLowerCase = /[a-z]/.test(password)
  passwordValidation.hasUpperCase = /[A-Z]/.test(password)
  passwordValidation.hasNumber = /\d/.test(password)
}

const resetPasswordValidation = () => {
  Object.keys(passwordValidation).forEach(key => {
    passwordValidation[key] = false
  })
}

const saveChanges = async () => {
  try {
    if (modalType.value === 'email') {
      // API call to update email
      console.log('Updating email:', modalData.email)
      // await authStore.updateEmail(modalData.email)
      profile.email = modalData.email
    } else if (modalType.value === 'password') {
      // API call to update password
      console.log('Updating password')
      // await authStore.updatePassword(modalData.oldPassword, modalData.newPassword)
    }
    
    closeModal()
  } catch (error) {
    console.error('Error updating profile:', error)
  }
}

const startKycProcess = () => {
  // Redirect to KYC process
  router.push('/dashboard/kyc')
}
</script>
