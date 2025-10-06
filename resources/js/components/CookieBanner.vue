<template>
  <div
    v-if="showBanner"
    class="fixed bottom-0 left-0 right-0 z-50 bg-gray-900 text-white p-4 shadow-lg"
  >
    <div class="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-4">
      <div class="flex-1">
        <h3 class="text-lg font-semibold mb-2">
          {{ t('cookie_banner.title') }}
        </h3>
        <p class="text-sm text-gray-300">
          {{ t('cookie_banner.description') }}
          <router-link
            to="/privacy-policy"
            class="text-blue-400 hover:text-blue-300 underline"
          >
            {{ t('cookie_banner.privacy_policy') }}
          </router-link>
          {{ t('cookie_banner.and') }}
          <router-link
            to="/terms-of-service"
            class="text-blue-400 hover:text-blue-300 underline"
          >
            {{ t('cookie_banner.terms_of_service') }}
          </router-link>
        </p>
      </div>
      
      <div class="flex flex-col sm:flex-row gap-2">
        <button
          @click="acceptAll"
          class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-md text-sm font-medium transition-colors"
        >
          {{ t('cookie_banner.accept_all') }}
        </button>
        <button
          @click="acceptNecessary"
          class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-md text-sm font-medium transition-colors"
        >
          {{ t('cookie_banner.necessary_only') }}
        </button>
        <button
          @click="openPreferences"
          class="px-4 py-2 border border-gray-600 hover:bg-gray-800 rounded-md text-sm font-medium transition-colors"
        >
          {{ t('cookie_banner.preferences') }}
        </button>
      </div>
    </div>
  </div>

  <!-- Cookie Preferences Modal -->
  <div
    v-if="showPreferences"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
    @click="closePreferences"
  >
    <div
      class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"
      @click.stop
    >
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-semibold text-gray-900">
          {{ t('cookie_preferences.title') }}
        </h2>
        <button
          @click="closePreferences"
          class="text-gray-400 hover:text-gray-600"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div class="space-y-6">
        <!-- Necessary Cookies -->
        <div class="border rounded-lg p-4">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-medium text-gray-900">
              {{ t('cookie_preferences.necessary.title') }}
            </h3>
            <div class="bg-gray-200 text-gray-600 px-2 py-1 rounded text-xs">
              {{ t('cookie_preferences.always_active') }}
            </div>
          </div>
          <p class="text-sm text-gray-600">
            {{ t('cookie_preferences.necessary.description') }}
          </p>
        </div>

        <!-- Analytics Cookies -->
        <div class="border rounded-lg p-4">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-medium text-gray-900">
              {{ t('cookie_preferences.analytics.title') }}
            </h3>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="preferences.analytics"
                type="checkbox"
                class="sr-only peer"
              >
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
          </div>
          <p class="text-sm text-gray-600">
            {{ t('cookie_preferences.analytics.description') }}
          </p>
        </div>

        <!-- Marketing Cookies -->
        <div class="border rounded-lg p-4">
          <div class="flex items-center justify-between mb-2">
            <h3 class="font-medium text-gray-900">
              {{ t('cookie_preferences.marketing.title') }}
            </h3>
            <label class="relative inline-flex items-center cursor-pointer">
              <input
                v-model="preferences.marketing"
                type="checkbox"
                class="sr-only peer"
              >
              <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
          </div>
          <p class="text-sm text-gray-600">
            {{ t('cookie_preferences.marketing.description') }}
          </p>
        </div>
      </div>

      <div class="flex justify-end gap-3 mt-6">
        <button
          @click="closePreferences"
          class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors"
        >
          {{ t('common.cancel') }}
        </button>
        <button
          @click="savePreferences"
          class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          {{ t('cookie_preferences.save_preferences') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const showBanner = ref(false)
const showPreferences = ref(false)
const preferences = ref({
  analytics: false,
  marketing: false
})

onMounted(() => {
  // Check if user has already made a choice
  const cookieConsent = localStorage.getItem('cookie_consent')
  if (!cookieConsent) {
    showBanner.value = true
  } else {
    // Load saved preferences
    const savedPreferences = JSON.parse(cookieConsent)
    preferences.value = savedPreferences.preferences || preferences.value
  }
})

const acceptAll = () => {
  const consent = {
    necessary: true,
    analytics: true,
    marketing: true,
    preferences: {
      analytics: true,
      marketing: true
    },
    timestamp: new Date().toISOString()
  }
  
  localStorage.setItem('cookie_consent', JSON.stringify(consent))
  showBanner.value = false
  
  // Initialize analytics and marketing tools
  initializeTracking(consent)
}

const acceptNecessary = () => {
  const consent = {
    necessary: true,
    analytics: false,
    marketing: false,
    preferences: {
      analytics: false,
      marketing: false
    },
    timestamp: new Date().toISOString()
  }
  
  localStorage.setItem('cookie_consent', JSON.stringify(consent))
  showBanner.value = false
  
  // Initialize only necessary tracking
  initializeTracking(consent)
}

const openPreferences = () => {
  showPreferences.value = true
}

const closePreferences = () => {
  showPreferences.value = false
}

const savePreferences = () => {
  const consent = {
    necessary: true,
    analytics: preferences.value.analytics,
    marketing: preferences.value.marketing,
    preferences: { ...preferences.value },
    timestamp: new Date().toISOString()
  }
  
  localStorage.setItem('cookie_consent', JSON.stringify(consent))
  showBanner.value = false
  showPreferences.value = false
  
  // Initialize tracking based on preferences
  initializeTracking(consent)
}

const initializeTracking = (consent) => {
  // Initialize Google Analytics if analytics is enabled
  if (consent.analytics) {
    // Google Analytics initialization code would go here
    console.log('Analytics tracking initialized')
  }
  
  // Initialize marketing tools if marketing is enabled
  if (consent.marketing) {
    // Marketing tracking initialization code would go here
    console.log('Marketing tracking initialized')
  }
}
</script>
