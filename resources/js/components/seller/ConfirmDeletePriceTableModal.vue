<template>
  <TransitionRoot appear :show="open" as="template">
    <Dialog as="div" @close="close" class="relative z-50">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black/25" />
      </TransitionChild>
      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
              <DialogTitle as="h3" class="text-lg font-semibold text-gray-900">
                Elimina tabella prezzi
              </DialogTitle>
              <p class="mt-2 text-sm text-gray-600">
                Eliminare la tabella <strong>{{ tableName || 'selezionata' }}</strong>? I paesi e i prezzi associati verranno rimossi. Questa azione non si può annullare.
              </p>
              <div class="mt-6 flex justify-end gap-3">
                <button
                  type="button"
                  @click="close"
                  class="rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                >
                  Annulla
                </button>
                <button
                  type="button"
                  @click="confirm"
                  class="rounded-md bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-500"
                >
                  Elimina
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
import { TransitionRoot, TransitionChild, Dialog, DialogPanel, DialogTitle } from '@headlessui/vue'

defineProps({
  open: Boolean,
  tableName: { type: String, default: '' }
})

const emit = defineEmits(['update:open', 'confirm'])

function close () {
  emit('update:open', false)
}

function confirm () {
  emit('confirm')
  close()
}
</script>
