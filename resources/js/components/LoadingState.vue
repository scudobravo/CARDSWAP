<template>
  <div class="loading-state" :class="containerClass">
    <!-- Skeleton Loader -->
    <div v-if="type === 'skeleton'" class="animate-pulse">
      <div class="space-y-4">
        <!-- Header skeleton -->
        <div v-if="showHeader" class="flex items-center space-x-4">
          <div class="rounded-full bg-gray-200 h-10 w-10"></div>
          <div class="space-y-2 flex-1">
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
          </div>
        </div>
        
        <!-- Content skeleton -->
        <div class="space-y-3">
          <div
            v-for="i in lines"
            :key="i"
            class="h-4 bg-gray-200 rounded"
            :style="{ width: getRandomWidth() }"
          ></div>
        </div>
        
        <!-- Button skeleton -->
        <div v-if="showButton" class="flex space-x-2">
          <div class="h-8 bg-gray-200 rounded w-20"></div>
          <div class="h-8 bg-gray-200 rounded w-16"></div>
        </div>
      </div>
    </div>

    <!-- Spinner Loader -->
    <div v-else-if="type === 'spinner'" class="flex flex-col items-center justify-center py-8">
      <div class="relative">
        <div class="w-12 h-12 border-4 border-gray-200 rounded-full animate-spin border-t-blue-600"></div>
        <div v-if="showProgress" class="absolute inset-0 flex items-center justify-center">
          <span class="text-xs font-medium text-gray-600">{{ progress }}%</span>
        </div>
      </div>
      <p v-if="message" class="mt-4 text-sm text-gray-600">{{ message }}</p>
    </div>

    <!-- Dots Loader -->
    <div v-else-if="type === 'dots'" class="flex items-center justify-center py-8">
      <div class="flex space-x-1">
        <div
          v-for="i in 3"
          :key="i"
          class="w-2 h-2 bg-blue-600 rounded-full animate-bounce"
          :style="{ animationDelay: `${i * 0.1}s` }"
        ></div>
      </div>
      <p v-if="message" class="ml-3 text-sm text-gray-600">{{ message }}</p>
    </div>

    <!-- Pulse Loader -->
    <div v-else-if="type === 'pulse'" class="flex items-center justify-center py-8">
      <div class="relative">
        <div class="w-8 h-8 bg-blue-600 rounded-full animate-ping"></div>
        <div class="absolute inset-0 w-8 h-8 bg-blue-600 rounded-full animate-pulse"></div>
      </div>
      <p v-if="message" class="ml-3 text-sm text-gray-600">{{ message }}</p>
    </div>

    <!-- Card Skeleton -->
    <div v-else-if="type === 'card'" class="animate-pulse">
      <div class="bg-white rounded-lg shadow-sm border p-4">
        <!-- Image skeleton -->
        <div class="aspect-w-16 aspect-h-12 mb-4">
          <div class="w-full h-48 bg-gray-200 rounded-lg"></div>
        </div>
        
        <!-- Content skeleton -->
        <div class="space-y-3">
          <div class="h-4 bg-gray-200 rounded w-3/4"></div>
          <div class="h-3 bg-gray-200 rounded w-1/2"></div>
          <div class="h-3 bg-gray-200 rounded w-2/3"></div>
          
          <!-- Price skeleton -->
          <div class="flex justify-between items-center pt-2">
            <div class="h-6 bg-gray-200 rounded w-16"></div>
            <div class="h-8 bg-gray-200 rounded w-20"></div>
          </div>
        </div>
      </div>
    </div>

    <!-- List Skeleton -->
    <div v-else-if="type === 'list'" class="animate-pulse">
      <div class="space-y-3">
        <div
          v-for="i in items"
          :key="i"
          class="flex items-center space-x-4 p-4 bg-white rounded-lg shadow-sm border"
        >
          <div class="w-12 h-12 bg-gray-200 rounded-lg"></div>
          <div class="flex-1 space-y-2">
            <div class="h-4 bg-gray-200 rounded w-3/4"></div>
            <div class="h-3 bg-gray-200 rounded w-1/2"></div>
          </div>
          <div class="w-20 h-6 bg-gray-200 rounded"></div>
        </div>
      </div>
    </div>

    <!-- Table Skeleton -->
    <div v-else-if="type === 'table'" class="animate-pulse">
      <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300">
          <!-- Header -->
          <thead class="bg-gray-50">
            <tr>
              <th
                v-for="i in columns"
                :key="i"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                <div class="h-4 bg-gray-200 rounded w-20"></div>
              </th>
            </tr>
          </thead>
          
          <!-- Body -->
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="i in rows" :key="i">
              <td
                v-for="j in columns"
                :key="j"
                class="px-6 py-4 whitespace-nowrap"
              >
                <div class="h-4 bg-gray-200 rounded" :style="{ width: getRandomWidth() }"></div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="type === 'error'" class="flex flex-col items-center justify-center py-8">
      <div class="w-16 h-16 text-red-400 mb-4">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"
          />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">{{ errorTitle || 'Errore' }}</h3>
      <p class="text-sm text-gray-600 mb-4">{{ errorMessage || 'Si è verificato un errore durante il caricamento.' }}</p>
      <button
        v-if="showRetry"
        @click="$emit('retry')"
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        Riprova
      </button>
    </div>

    <!-- Empty State -->
    <div v-else-if="type === 'empty'" class="flex flex-col items-center justify-center py-8">
      <div class="w-16 h-16 text-gray-400 mb-4">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
          />
        </svg>
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">{{ emptyTitle || 'Nessun elemento' }}</h3>
      <p class="text-sm text-gray-600 mb-4">{{ emptyMessage || 'Non ci sono elementi da visualizzare.' }}</p>
      <button
        v-if="showAction"
        @click="$emit('action')"
        class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        {{ actionText || 'Aggiungi' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  type: {
    type: String,
    default: 'spinner',
    validator: (value) => [
      'skeleton', 'spinner', 'dots', 'pulse', 
      'card', 'list', 'table', 'error', 'empty'
    ].includes(value)
  },
  message: {
    type: String,
    default: ''
  },
  lines: {
    type: Number,
    default: 3
  },
  items: {
    type: Number,
    default: 5
  },
  columns: {
    type: Number,
    default: 4
  },
  rows: {
    type: Number,
    default: 5
  },
  showHeader: {
    type: Boolean,
    default: true
  },
  showButton: {
    type: Boolean,
    default: false
  },
  showProgress: {
    type: Boolean,
    default: false
  },
  progress: {
    type: Number,
    default: 0
  },
  containerClass: {
    type: String,
    default: ''
  },
  // Error state props
  errorTitle: {
    type: String,
    default: ''
  },
  errorMessage: {
    type: String,
    default: ''
  },
  showRetry: {
    type: Boolean,
    default: true
  },
  // Empty state props
  emptyTitle: {
    type: String,
    default: ''
  },
  emptyMessage: {
    type: String,
    default: ''
  },
  showAction: {
    type: Boolean,
    default: false
  },
  actionText: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['retry', 'action'])

const getRandomWidth = () => {
  const widths = ['w-1/4', 'w-1/2', 'w-3/4', 'w-full']
  return widths[Math.floor(Math.random() * widths.length)]
}
</script>
