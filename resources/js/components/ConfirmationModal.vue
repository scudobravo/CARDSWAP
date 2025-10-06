<template>
  <TransitionRoot appear :show="isOpen" as="template">
    <Dialog as="div" @close="$emit('cancel')" class="relative z-50">
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
            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                  <ExclamationTriangleIcon class="h-6 w-6 text-yellow-400" />
                </div>
                <div class="ml-3">
                  <DialogTitle as="h3" class="text-lg font-futura-bold leading-6 text-gray-900">
                    {{ title }}
                  </DialogTitle>
                </div>
              </div>

              <div class="mb-6">
                <p class="text-sm text-gray-500 font-gill-sans">
                  {{ message }}
                </p>
              </div>

              <div class="flex justify-end space-x-3">
                <button
                  type="button"
                  @click="$emit('cancel')"
                  class="px-4 py-2 text-sm font-gill-sans-semibold text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
                >
                  {{ cancelText }}
                </button>
                <button
                  type="button"
                  @click="$emit('confirm')"
                  :class="[
                    'px-4 py-2 text-sm font-gill-sans-semibold text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2',
                    confirmClass || 'bg-primary hover:bg-primary/90 focus:ring-primary'
                  ]"
                >
                  {{ confirmText }}
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
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'
import { ExclamationTriangleIcon } from '@heroicons/vue/24/outline'

defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: 'Conferma azione'
  },
  message: {
    type: String,
    default: 'Sei sicuro di voler procedere?'
  },
  confirmText: {
    type: String,
    default: 'Conferma'
  },
  cancelText: {
    type: String,
    default: 'Annulla'
  },
  confirmClass: {
    type: String,
    default: null
  }
})

defineEmits(['confirm', 'cancel'])
</script>
