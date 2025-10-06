<template>
  <div id="app">
    <router-view />
    <CookieBanner />
    <NotificationSystem />
  </div>
</template>

<script>
import CookieBanner from './components/CookieBanner.vue'
import NotificationSystem from './components/NotificationSystem.vue'

export default {
  name: 'App',
  components: {
    CookieBanner,
    NotificationSystem
  }
}
</script>

<style>
/* Stili globali */
</style>