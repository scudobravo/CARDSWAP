<template>
  <div id="app">
    <router-view />
    <NotificationSystem />
  </div>
</template>

<script>
import NotificationSystem from './components/NotificationSystem.vue'

export default {
  name: 'App',
  components: {
    NotificationSystem
  }
}
</script>

<style>
/* Stili globali */
</style>