<template>
  <!-- Badge numero vendite -->
  <div class="bg-primary text-white px-3 py-1 rounded-lg text-sm font-futura-bold whitespace-nowrap flex-shrink-0">
      {{ totalSales }} Numero di vendite
  </div>
  <!-- Stelle rating -->
  <div class="flex items-center space-x-1 flex-shrink-0">
    <svg
      v-for="(star, index) in 5"
      :key="index"
      :class="['w-5 h-5', index < rating ? 'text-yellow-400' : 'text-gray-300']"
      fill="currentColor"
      viewBox="0 0 20 20"
    >
      <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
    </svg>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import cardService from '../services/cardService.js'

const props = defineProps({
  sellerId: { type: [Number, String], default: null }
})

const totalSales = ref(0)
const rating = ref(0)

async function fetchStats () {
  if (!props.sellerId) return
  try {
    const res = await cardService.getSellerStats(Number(props.sellerId))
    if (res?.success) {
      totalSales.value = res.total_sales ?? 0
      rating.value = parseFloat(res.rating) || 0
    }
  } catch (_) {}
}

watch(() => props.sellerId, (id) => {
  if (id) fetchStats()
  else {
    totalSales.value = 0
    rating.value = 0
  }
}, { immediate: true })

onMounted(() => {
  if (props.sellerId) fetchStats()
})
</script>
