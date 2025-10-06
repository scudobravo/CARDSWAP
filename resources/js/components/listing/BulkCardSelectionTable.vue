<template>
  <div class="space-y-4">
    <div v-if="cards.length > 0" class="flex items-center justify-between">
      <h5 class="text-lg font-semibold text-gray-900">
        Carte Trovate ({{ cards.length }})
      </h5>
      <div class="flex items-center space-x-2">
        <button 
          @click="selectAll"
          class="text-sm text-primary hover:text-primary-dark font-medium"
        >
          Seleziona Tutto
        </button>
        <button 
          @click="clearSelection"
          class="text-sm text-gray-500 hover:text-gray-700 font-medium"
        >
          Deseleziona Tutto
        </button>
      </div>
    </div>

    <div v-if="cards.length > 0" class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              <input 
                type="checkbox"
                :checked="allSelected"
                @change="toggleAllSelection"
                class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
              />
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Player
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Team
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Set
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Year
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Rarity
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Brand
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr 
            v-for="card in cards" 
            :key="card.id"
            class="hover:bg-gray-50 cursor-pointer"
            @click="toggleCardSelection(card)"
          >
            <td class="px-6 py-4 whitespace-nowrap">
              <input 
                type="checkbox"
                :checked="isCardSelected(card)"
                @click.stop="toggleCardSelection(card)"
                class="h-4 w-4 text-primary focus:ring-primary border-gray-300 rounded"
              />
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ card.player?.name || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ card.team?.name || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ card.card_set?.name || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ card.year || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ card.rarity || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
              {{ card.card_set?.brand || '-' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="hasSearched" class="text-center py-8">
      <p class="text-gray-500">Nessuna carta trovata con i filtri selezionati</p>
    </div>

    <div v-if="selectedCards.length > 0" class="mt-6 p-4 bg-blue-50 rounded-lg">
      <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-blue-900">
          {{ selectedCards.length }} carte selezionate
        </span>
        <button 
          @click="proceedToBulkEdit"
          class="px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary"
        >
          Procedi con {{ selectedCards.length }} carte
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

// Props
const props = defineProps({
  cards: {
    type: Array,
    default: () => []
  },
  hasSearched: {
    type: Boolean,
    default: false
  }
})

// Emits
const emit = defineEmits(['cards-selected', 'proceed-to-bulk-edit'])

// State
const selectedCards = ref([])

// Computed
const allSelected = computed(() => {
  return props.cards.length > 0 && selectedCards.value.length === props.cards.length
})

// Methods
const isCardSelected = (card) => {
  return selectedCards.value.some(selected => selected.id === card.id)
}

const toggleCardSelection = (card) => {
  const index = selectedCards.value.findIndex(selected => selected.id === card.id)
  if (index > -1) {
    selectedCards.value.splice(index, 1)
  } else {
    selectedCards.value.push(card)
  }
  emit('cards-selected', selectedCards.value)
}

const selectAll = () => {
  selectedCards.value = [...cards]
  emit('cards-selected', selectedCards.value)
}

const clearSelection = () => {
  selectedCards.value = []
  emit('cards-selected', selectedCards.value)
}

const toggleAllSelection = () => {
  if (allSelected.value) {
    clearSelection()
  } else {
    selectAll()
  }
}

const proceedToBulkEdit = () => {
  emit('proceed-to-bulk-edit', selectedCards.value)
}
</script>
