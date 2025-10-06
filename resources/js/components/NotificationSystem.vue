<template>
  <div class="notification-system">
    <!-- Toast Notifications -->
    <div class="fixed top-4 right-4 z-50 space-y-4 min-w-[400px] max-w-[500px]">
      <TransitionGroup
        name="notification"
        tag="div"
        class="space-y-4"
      >
        <div
          v-for="notification in notifications"
          :key="notification.id"
          :class="[
            'w-full bg-white shadow-2xl rounded-xl pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden transform transition-all duration-300 hover:scale-105',
            notification.type === 'success' ? 'border-l-4 border-green-500 bg-green-50' : '',
            notification.type === 'error' ? 'border-l-4 border-red-500 bg-red-50' : '',
            notification.type === 'warning' ? 'border-l-4 border-yellow-500 bg-yellow-50' : '',
            notification.type === 'info' ? 'border-l-4 border-blue-500 bg-blue-50' : '',
          ]"
        >
          <div class="p-8">
            <div class="flex items-start">
              <div class="flex-shrink-0">
                <!-- Success Icon -->
                <div
                  v-if="notification.type === 'success'"
                  class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center"
                >
                  <svg
                    class="h-7 w-7 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M5 13l4 4L19 7"
                    />
                  </svg>
                </div>
                
                <!-- Error Icon -->
                <div
                  v-else-if="notification.type === 'error'"
                  class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center"
                >
                  <svg
                    class="h-7 w-7 text-red-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </div>
                
                <!-- Warning Icon -->
                <div
                  v-else-if="notification.type === 'warning'"
                  class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center"
                >
                  <svg
                    class="h-7 w-7 text-yellow-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"
                    />
                  </svg>
                </div>
                
                <!-- Info Icon -->
                <div
                  v-else
                  class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center"
                >
                  <svg
                    class="h-7 w-7 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </div>
              </div>
              
              <div class="ml-5 w-0 flex-1">
                <p class="text-xl font-futura-bold text-gray-900 leading-tight">
                  {{ notification.title }}
                </p>
                <p class="mt-3 text-lg font-gill-sans text-gray-700 leading-relaxed">
                  {{ notification.message }}
                </p>
              </div>
              
              <div class="ml-4 flex-shrink-0">
                <button
                  @click="removeNotification(notification.id)"
                  class="rounded-md inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200"
                >
                  <span class="sr-only">Chiudi</span>
                  <svg class="h-7 w-7" viewBox="0 0 20 20" fill="currentColor">
                    <path
                      fill-rule="evenodd"
                      d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                      clip-rule="evenodd"
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </TransitionGroup>
    </div>

    <!-- Global Loading Overlay -->
    <div
      v-if="isLoading"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    >
      <div class="bg-white rounded-lg p-6 flex items-center space-x-3">
        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
        <span class="text-gray-700">{{ loadingMessage }}</span>
      </div>
    </div>

    <!-- Confirmation Modal -->
    <div
      v-if="confirmationModal.show"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
      @click="closeConfirmationModal"
    >
      <div
        class="bg-white rounded-xl shadow-2xl p-8 max-w-lg w-full mx-4 transform transition-all duration-300"
        @click.stop
      >
        <div class="flex items-center mb-6">
          <div class="flex-shrink-0">
            <div class="w-12 h-12 rounded-full bg-yellow-100 flex items-center justify-center">
              <svg
                class="h-7 w-7 text-yellow-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"
                />
              </svg>
            </div>
          </div>
          <div class="ml-4">
            <h3 class="text-xl font-futura-bold text-gray-900">
              {{ confirmationModal.title }}
            </h3>
          </div>
        </div>
        
        <div class="mb-8">
          <p class="text-base font-gill-sans text-gray-700 leading-relaxed">
            {{ confirmationModal.message }}
          </p>
        </div>
        
        <div class="flex justify-end space-x-4">
          <button
            @click="closeConfirmationModal"
            class="px-6 py-3 border border-gray-300 text-gray-700 font-gill-sans-semibold rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors duration-200"
          >
            {{ confirmationModal.cancelText || 'Annulla' }}
          </button>
          <button
            @click="confirmAction"
            :class="[
              'px-6 py-3 rounded-lg text-white font-gill-sans-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors duration-200',
              confirmationModal.type === 'danger' 
                ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' 
                : 'bg-primary hover:bg-primary/90 focus:ring-primary'
            ]"
          >
            {{ confirmationModal.confirmText || 'Conferma' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

// State
const notifications = ref([])
const isLoading = ref(false)
const loadingMessage = ref('')
const confirmationModal = ref({
  show: false,
  title: '',
  message: '',
  type: 'info',
  confirmText: '',
  cancelText: '',
  onConfirm: null
})

// Auto-remove notifications
let notificationTimeouts = new Map()

onMounted(() => {
  // Listen for global events
  window.addEventListener('show-notification', handleShowNotification)
  window.addEventListener('show-loading', handleShowLoading)
  window.addEventListener('hide-loading', handleHideLoading)
  window.addEventListener('show-confirmation', handleShowConfirmation)
})

onUnmounted(() => {
  window.removeEventListener('show-notification', handleShowNotification)
  window.removeEventListener('show-loading', handleShowLoading)
  window.removeEventListener('hide-loading', handleHideLoading)
  window.removeEventListener('show-confirmation', handleShowConfirmation)
  
  // Clear all timeouts
  notificationTimeouts.forEach(timeout => clearTimeout(timeout))
})

// Notification methods
const addNotification = (notification) => {
  const id = Date.now() + Math.random()
  const newNotification = {
    id,
    type: 'info',
    title: '',
    message: '',
    duration: 5000,
    ...notification
  }
  
  notifications.value.push(newNotification)
  
  // Auto-remove after duration
  if (newNotification.duration > 0) {
    const timeout = setTimeout(() => {
      removeNotification(id)
    }, newNotification.duration)
    
    notificationTimeouts.set(id, timeout)
  }
  
  return id
}

const removeNotification = (id) => {
  const index = notifications.value.findIndex(n => n.id === id)
  if (index > -1) {
    notifications.value.splice(index, 1)
  }
  
  // Clear timeout
  const timeout = notificationTimeouts.get(id)
  if (timeout) {
    clearTimeout(timeout)
    notificationTimeouts.delete(id)
  }
}

// Loading methods
const showLoading = (message = 'Caricamento...') => {
  isLoading.value = true
  loadingMessage.value = message
}

const hideLoading = () => {
  isLoading.value = false
  loadingMessage.value = ''
}

// Confirmation modal methods
const showConfirmation = (options) => {
  confirmationModal.value = {
    show: true,
    title: options.title || 'Conferma azione',
    message: options.message || 'Sei sicuro di voler procedere?',
    type: options.type || 'info',
    confirmText: options.confirmText || 'Conferma',
    cancelText: options.cancelText || 'Annulla',
    onConfirm: options.onConfirm || null
  }
}

const closeConfirmationModal = () => {
  confirmationModal.value.show = false
  confirmationModal.value.onConfirm = null
}

const confirmAction = () => {
  if (confirmationModal.value.onConfirm) {
    confirmationModal.value.onConfirm()
  }
  closeConfirmationModal()
}

// Event handlers
const handleShowNotification = (event) => {
  addNotification(event.detail)
}

const handleShowLoading = (event) => {
  showLoading(event.detail?.message || 'Caricamento...')
}

const handleHideLoading = () => {
  hideLoading()
}

const handleShowConfirmation = (event) => {
  showConfirmation(event.detail)
}

// Expose methods globally
window.$notification = {
  success: (title, message, duration = 5000) => addNotification({ type: 'success', title, message, duration }),
  error: (title, message, duration = 7000) => addNotification({ type: 'error', title, message, duration }),
  warning: (title, message, duration = 6000) => addNotification({ type: 'warning', title, message, duration }),
  info: (title, message, duration = 5000) => addNotification({ type: 'info', title, message, duration }),
  loading: {
    show: showLoading,
    hide: hideLoading
  },
  confirm: showConfirmation
}
</script>

<style scoped>
.notification-enter-active {
  transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

.notification-leave-active {
  transition: all 0.3s ease-in;
}

.notification-enter-from {
  opacity: 0;
  transform: translateX(100%) scale(0.9);
}

.notification-leave-to {
  opacity: 0;
  transform: translateX(100%) scale(0.95);
}

.notification-move {
  transition: transform 0.4s ease;
}

/* Effetto hover per le notifiche */
.notification-system .w-full:hover {
  transform: scale(1.02);
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
}
</style>
