<template>
  <nav class="flex" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
      <li class="inline-flex items-center">
        <router-link
          to="/"
          class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600"
        >
          <svg
            class="w-4 h-4 mr-2"
            fill="currentColor"
            viewBox="0 0 20 20"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"
            />
          </svg>
          {{ t('navigation.home') }}
        </router-link>
      </li>
      
      <li v-for="(item, index) in breadcrumbs" :key="index">
        <div class="flex items-center">
          <svg
            class="w-6 h-6 text-gray-400"
            fill="currentColor"
            viewBox="0 0 20 20"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              fill-rule="evenodd"
              d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
              clip-rule="evenodd"
            />
          </svg>
          
          <router-link
            v-if="item.to && index < breadcrumbs.length - 1"
            :to="item.to"
            class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2"
          >
            {{ item.label }}
          </router-link>
          
          <span
            v-else
            class="ml-1 text-sm font-medium text-gray-500 md:ml-2"
            :aria-current="index === breadcrumbs.length - 1 ? 'page' : undefined"
          >
            {{ item.label }}
          </span>
        </div>
      </li>
    </ol>
  </nav>
</template>

<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const route = useRoute()

const breadcrumbs = computed(() => {
  const crumbs = []
  const pathSegments = route.path.split('/').filter(segment => segment)
  
  let currentPath = ''
  
  pathSegments.forEach((segment, index) => {
    currentPath += `/${segment}`
    
    // Skip API routes
    if (segment === 'api') return
    
    // Skip numeric IDs (assume they are resource IDs)
    if (/^\d+$/.test(segment)) return
    
    const isLast = index === pathSegments.length - 1
    
    // Generate breadcrumb based on path
    const breadcrumb = generateBreadcrumb(segment, currentPath, isLast)
    
    if (breadcrumb) {
      crumbs.push(breadcrumb)
    }
  })
  
  return crumbs
})

const generateBreadcrumb = (segment, path, isLast) => {
  // Map common segments to labels
  const segmentMap = {
    'catalog': t('navigation.catalog'),
    'categories': t('navigation.categories'),
    'sets': t('navigation.sets'),
    'players': t('navigation.players'),
    'teams': t('navigation.teams'),
    'sell': t('navigation.sell'),
    'account': t('navigation.account'),
    'profile': t('navigation.profile'),
    'listings': t('navigation.listings'),
    'orders': t('navigation.orders'),
    'wishlist': t('navigation.wishlist'),
    'login': t('navigation.login'),
    'register': t('navigation.register'),
    'privacy-policy': t('common.privacy_policy'),
    'terms-of-service': t('common.terms_of_service'),
    'help': t('common.help'),
    'contact': t('common.contact'),
  }
  
  // Check if it's a known segment
  if (segmentMap[segment]) {
    return {
      label: segmentMap[segment],
      to: isLast ? null : path
    }
  }
  
  // Handle dynamic segments
  if (segment === 'category' && route.params.category) {
    return {
      label: route.params.category,
      to: isLast ? null : path
    }
  }
  
  if (segment === 'set' && route.params.set) {
    return {
      label: route.params.set,
      to: isLast ? null : path
    }
  }
  
  if (segment === 'player' && route.params.player) {
    return {
      label: route.params.player,
      to: isLast ? null : path
    }
  }
  
  if (segment === 'team' && route.params.team) {
    return {
      label: route.params.team,
      to: isLast ? null : path
    }
  }
  
  if (segment === 'card' && route.params.card) {
    return {
      label: route.params.card,
      to: isLast ? null : path
    }
  }
  
  // Default: capitalize first letter
  return {
    label: segment.charAt(0).toUpperCase() + segment.slice(1),
    to: isLast ? null : path
  }
}
</script>
