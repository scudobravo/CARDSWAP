<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <!-- Breadcrumbs -->
      <nav class="pt-20 pb-4">
        <ol class="flex items-center space-x-2 text-sm font-gill-sans">
          <li><a href="/" class="text-primary hover:text-secondary">Home</a></li>
          <li class="text-gray-500">></li>
          <li><a :href="`/category/${getCategorySlug()}`" class="text-primary hover:text-secondary">{{ getCategoryDisplayName() }}</a></li>
          <li class="text-gray-500">></li>
          <li class="text-gray-500">{{ subcategoryName }}</li>
        </ol>
      </nav>

      <!-- Page Header -->
      <div class="flex items-center justify-between border-b border-gray-200 pb-6">
        <!-- Mobile Layout: H1 con icona su una riga -->
        <div class="flex items-center space-x-3 lg:space-x-4">
          <img :src="subcategoryIcon" :alt="subcategoryName" class="w-8 h-8 lg:w-12 lg:h-12" />
          <h1 class="text-2xl lg:text-4xl font-futura-bold text-primary">{{ subcategoryName }} - {{ categoryName }}</h1>
        </div>

        <!-- Desktop Controls -->
        <div class="hidden lg:flex items-center space-x-4">
          <!-- View Toggle -->
          <div class="flex rounded-md shadow-sm">
            <button 
              type="button" 
              :class="[viewMode === 'grid' ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:text-gray-900', 'px-3 py-2 text-sm font-medium border border-gray-300 rounded-l-md focus:z-10 focus:ring-1 focus:ring-primary focus:border-primary']"
              @click="viewMode = 'grid'"
            >
              <Squares2X2Icon class="size-4" />
            </button>
            <button 
              type="button" 
              :class="[viewMode === 'list' ? 'bg-primary text-white' : 'bg-white text-gray-700 hover:text-gray-900', 'px-3 py-2 text-sm font-medium border border-gray-300 rounded-r-md focus:z-10 focus:ring-1 focus:ring-primary focus:border-primary']"
              @click="viewMode = 'list'"
            >
              <svg class="size-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>

          <!-- Sort Menu -->
          <Menu as="div" class="relative inline-block text-left">
            <MenuButton class="group inline-flex justify-center text-sm font-medium text-gray-700 hover:text-gray-900">
              {{ currentSortOption.name }}
              <ChevronDownIcon class="-mr-1 ml-1 size-5 shrink-0 text-gray-400 group-hover:text-gray-500" aria-hidden="true" />
            </MenuButton>

            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform scale-100" leave-to-class="transform opacity-0 scale-95">
              <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-2xl ring-1 ring-black/5 focus:outline-hidden">
                <div class="py-1">
                  <MenuItem v-for="option in sortOptions" :key="option.value" v-slot="{ active }">
                    <button 
                      @click="setSortOption(option)"
                      :class="[option.value === currentSortOption.value ? 'font-medium text-gray-900' : 'text-gray-500', active ? 'bg-gray-100 outline-hidden' : '', 'block w-full text-left px-4 py-2 text-sm']"
                    >
                      {{ option.name }}
                    </button>
                  </MenuItem>
                </div>
              </MenuItems>
            </transition>
          </Menu>
        </div>

        <!-- Mobile Sort Menu -->
        <div class="lg:hidden">
          <Menu as="div" class="relative inline-block text-left">
            <MenuButton class="group inline-flex justify-center text-sm font-medium text-gray-700 hover:text-gray-900">
              {{ currentSortOption.name }}
              <ChevronDownIcon class="-mr-1 ml-1 size-5 shrink-0 text-gray-400 group-hover:text-gray-500" aria-hidden="true" />
            </MenuButton>

            <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform scale-100" leave-to-class="transform opacity-0 scale-95">
              <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white shadow-2xl ring-1 ring-black/5 focus:outline-hidden">
                <div class="py-1">
                  <MenuItem v-for="option in sortOptions" :key="option.value" v-slot="{ active }">
                    <button 
                      @click="setSortOption(option)"
                      :class="[option.value === currentSortOption.value ? 'font-medium text-gray-900' : 'text-gray-500', active ? 'bg-gray-100 outline-hidden' : '', 'block w-full text-left px-4 py-2 text-sm']"
                    >
                      {{ option.name }}
                    </button>
                  </MenuItem>
                </div>
              </MenuItems>
            </transition>
          </Menu>
        </div>
      </div>


      <section aria-labelledby="products-heading" class="pt-6 pb-24">
        <h2 id="products-heading" class="sr-only">Prodotti</h2>

        <!-- Mobile Filters Section (Collapsible) -->
        <div class="lg:hidden mb-6">
          <Disclosure as="div" class="bg-white rounded-lg shadow-md" v-slot="{ open }">
            <DisclosureButton class="flex w-full items-center justify-between px-4 py-3 text-left text-sm font-medium text-gray-900 hover:bg-gray-50 focus:outline-none focus-visible:ring focus-visible:ring-primary focus-visible:ring-opacity-75">
              <span>Filtri</span>
              <span class="ml-6 flex items-center">
                <PlusIcon v-if="!open" class="size-5" aria-hidden="true" />
                <MinusIcon v-else class="size-5" aria-hidden="true" />
              </span>
            </DisclosureButton>
            <DisclosurePanel class="px-4 pb-4 pt-4 text-sm text-gray-500">
              <AdvancedFilters
                :filters="filters"
                :category="category"
                :subcategory="subcategory"
                :grading-companies="gradingCompanies"
                :condition-options="conditionOptions"
                :numbered-presets="numberedPresets"
                :multi-player-options="multiPlayerOptions"
                :multi-autograph-options="multiAutographOptions"
                @apply-filters="applyFilters"
                @clear-filters="clearFilters"
                @filter-changed="handleFilterChange"
                class="space-y-4"
              />
            </DisclosurePanel>
          </Disclosure>
        </div>

        <div class="grid grid-cols-1 gap-x-8 gap-y-10 lg:grid-cols-4">
          <!-- Filters (Desktop) -->
          <form class="hidden lg:block">
            <!-- Subcategories Navigation -->
            <h3 class="sr-only">Sotto categorie</h3>
            <!-- <ul role="list" class="space-y-4 border-b border-gray-200 pb-6 text-sm font-medium text-gray-900">
              <li v-for="subcategory in subCategories" :key="subcategory.name">
                <a :href="subcategory.href" :class="subcategory.name === subcategoryName ? 'text-primary font-bold' : ''">{{ subcategory.name }}</a>
              </li>
            </ul> -->

            <!-- Advanced Filters Component -->
            <AdvancedFilters
              :filters="filters"
              :category="category"
              :subcategory="subcategory"
              :grading-companies="gradingCompanies"
              :condition-options="conditionOptions"
              :numbered-presets="numberedPresets"
              :multi-player-options="multiPlayerOptions"
              :multi-autograph-options="multiAutographOptions"
              @apply-filters="applyFilters"
              @clear-filters="clearFilters"
              @filter-changed="handleFilterChange"
              class=" bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden p-6"
            />
          </form>

          <!-- Product grid/list -->
          <div class="lg:col-span-3">
            <!-- Loading state -->
            <div v-if="loading" class="flex justify-center items-center py-12">
              <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>

            <!-- Desktop Grid View -->
            <div v-if="viewMode === 'grid'" class="hidden lg:grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3 lg:gap-x-8">
              <ProductCard 
                v-for="product in displayedProducts" 
                :key="product.id" 
                :product="product"
                @add-to-cart="handleAddToCart"
                class="cursor-pointer"
                @click="goToProduct(product)"
              />
            </div>

            <!-- Mobile Horizontal Cards -->
            <div class="lg:hidden space-y-3">
              <div v-for="product in displayedProducts" :key="product.id" class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden">
                <div class="flex">
                  <!-- Small Image Area -->
                  <div class="w-20 min-h-full bg-gray-100 overflow-hidden flex-shrink-0 relative">
                    <img 
                      v-if="product.imageUrl" 
                      :src="product.imageUrl" 
                      :alt="product.name || 'Carta'"
                      class="w-full h-full object-cover"
                      @error="handleImageError"
                    />
                    <div :class="['w-full h-full flex items-center justify-center bg-gray-200', product.imageUrl ? 'hidden' : '']">
                      <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                  
                  <!-- Product Details -->
                  <div class="flex-1 p-3 flex flex-col justify-between min-w-0">
                    <div class="min-w-0">
                      <router-link :to="getCardUrl(product)" class="text-base font-bold text-gray-900 hover:text-primary line-clamp-2">
                        {{ product.name }}
                      </router-link>
                      
                      <!-- Compact Badges Row -->
                      <div class="flex flex-wrap gap-1 mt-2">
                        <!-- Numbered (solo card_number_in_set, nessun fallback) -->
                        <div v-if="isValidNumbered(product)" class="bg-gray-100 px-2 py-1 rounded text-xs font-bold text-primary">
                          /{{ product.card_number_in_set }}
                        </div>
                        <!-- Autograph -->
                        <div v-if="product.is_autograph" class="bg-gray-100 px-2 py-1 rounded text-xs font-bold text-primary">
                          AUTO
                        </div>
                        <!-- Relic -->
                        <div v-if="product.is_relic" class="bg-gray-100 px-2 py-1 rounded text-xs font-bold text-primary">
                          RELIC
                        </div>
                        <!-- Rookie (solo per categorie sportive) -->
                        <div v-if="(category === 'football' || category === 'basketball') && product.is_rookie" class="bg-gray-100 px-2 py-1 rounded text-xs font-bold text-primary">
                          RC
                        </div>
                        <!-- Sketch (solo per Disney e Spongebob) -->
                        <div v-if="(category === 'disney' || category === 'spongebob') && product.is_sketch" class="bg-gray-100 px-2 py-1 rounded text-xs font-bold text-primary">
                          SKETCH
                        </div>
                        <!-- Condition -->
                        <div class="bg-gray-100 px-2 py-1 rounded text-xs font-bold text-primary">
                          {{ formatCondition(product) }}
                        </div>
                      </div>
                      
                      <!-- Compact Details -->
                      <div class="mt-2 text-xs text-gray-600 space-y-1">
                        <div class="flex justify-between">
                          <span>Team:</span>
                          <span class="font-medium truncate ml-2">{{ product.team && product.team !== 'Team' && product.team !== 'Team Name' && product.team !== 'Unknown Team' ? product.team : '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span>Set:</span>
                          <span class="font-medium truncate ml-2">{{ product.set && product.set !== 'Set' && product.set !== 'Set Name' && product.set !== 'Unknown Set' ? product.set : '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span>Rarity:</span>
                          <span class="font-medium">{{ product.rarity ? (product.rarity + (product.rarity_variation ? ` (${product.rarity_variation})` : '')) : '-' }}</span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Price and Add to Cart -->
                    <div class="mt-3 flex items-center justify-between">
                      <p class="text-lg font-bold text-gray-900">€{{ formatPrice(product.price) }}</p>
                      <button 
                        @click="handleAddToCart(product, $event)"
                        class="w-8 h-8 bg-blue-600 rounded-md flex items-center justify-center hover:bg-blue-700 transition-colors"
                        aria-label="Aggiungi al carrello"
                      >
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Desktop List View -->
            <div v-if="viewMode === 'list'" class="hidden lg:block space-y-4">
              <div v-for="product in displayedProducts" :key="product.id" class="bg-white rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300 overflow-hidden">
                <div class="flex">
                  <!-- Image Area -->
                  <div class="w-48 h-screen max-h-64 bg-gray-100 overflow-hidden flex-shrink-0 relative">
                    <img 
                      v-if="product.imageUrl" 
                      :src="product.imageUrl" 
                      :alt="product.name || 'Carta'"
                      class="w-full h-full object-cover"
                      @error="handleImageError"
                    />
                    <div :class="['w-full h-full flex items-center justify-center bg-gray-200', product.imageUrl ? 'hidden' : '']">
                      <svg class="w-8 h-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                  
                  <!-- Product Details -->
                  <div class="flex-1 p-4 flex flex-col justify-between">
                    <div>
                      <router-link :to="getCardUrl(product)" class="text-lg font-bold text-gray-900 hover:text-primary">
                        {{ product.name }}
                      </router-link>
                      
                      <!-- Badges Row -->
                      <div class="flex flex-wrap gap-2 mt-2">
                        <!-- Numbered - Mostra solo se presente e valido (solo card_number_in_set) -->
                        <div v-if="isValidNumbered(product)" class="relative group">
                          <div class="bg-gray-100 p-2 rounded-lg flex items-center justify-center min-w-[40px] min-h-[40px]">
                            <span class="text-primary font-futura-bold text-sm">{{ product.card_number_in_set }}</span>
                          </div>
                          <!-- Tooltip -->
                          <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                            NUMBERED
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                          </div>
                        </div>

                        <!-- Autograph - Mostra solo se is_autograph è true -->
                        <div v-if="product.is_autograph" class="relative group">
                          <div class="bg-gray-100 p-2 rounded-lg flex items-center justify-center min-w-[40px] min-h-[40px]">
                            <svg class="w-5 h-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                            </svg>
                          </div>
                          <!-- Tooltip -->
                          <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                            AUTOGRAPH
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                          </div>
                        </div>

                        <!-- Relic - Mostra solo se is_relic è true -->
                        <div v-if="product.is_relic" class="relative group">
                          <div class="bg-gray-100 p-2 rounded-lg flex items-center justify-center min-w-[40px] min-h-[40px]">
                            <svg class="w-5 h-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3zM3.31 9.397L5 10.12v4.102a8.969 8.969 0 00-1.05-.174 1 1 0 01-.89-.89 11.115 11.115 0 01.25-3.762zM9.3 16.573A9.026 9.026 0 007 14.935v-3.957l1.818.78a3 3 0 002.429 3.658L9.3 16.573z"></path>
                            </svg>
                          </div>
                          <!-- Tooltip -->
                          <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                            RELIC
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                          </div>
                        </div>

                        <!-- Rookie - Mostra solo se is_rookie è true (solo per categorie sportive) -->
                        <div v-if="(category === 'football' || category === 'basketball') && product.is_rookie" class="relative group">
                          <div class="bg-gray-100 p-2 rounded-lg flex items-center justify-center min-w-[40px] min-h-[40px]">
                            <span class="text-primary font-futura-bold text-sm">RC</span>
                          </div>
                          <!-- Tooltip -->
                          <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                            ROOKIE
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                          </div>
                        </div>

                        <!-- Sketch - Mostra solo se is_sketch è true (solo per Disney e Spongebob) -->
                        <div v-if="(category === 'disney' || category === 'spongebob') && product.is_sketch" class="relative group">
                          <div class="bg-gray-100 p-2 rounded-lg flex items-center justify-center min-w-[40px] min-h-[40px]">
                            <span class="text-primary font-futura-bold text-sm">SKETCH</span>
                          </div>
                          <!-- Tooltip -->
                          <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                            SKETCH
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                          </div>
                        </div>

                        <!-- Star - Mostra solo se is_star è true -->
                        <div v-if="product.is_star" class="relative group">
                          <div class="bg-gray-100 p-2 rounded-lg flex items-center justify-center min-w-[40px] min-h-[40px]">
                            <svg class="w-5 h-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                          </div>
                          <!-- Tooltip -->
                          <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                            STAR
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                          </div>
                        </div>

                        <!-- Legend - Mostra solo se is_legend è true -->
                        <div v-if="product.is_legend" class="relative group">
                          <div class="bg-gray-100 p-2 rounded-lg flex items-center justify-center min-w-[40px] min-h-[40px]">
                            <svg class="w-5 h-5 text-primary" fill="currentColor" viewBox="0 0 20 20">
                              <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                          </div>
                          <!-- Tooltip -->
                          <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                            LEGEND
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                          </div>
                        </div>

                        <!-- Condition -->
                        <div class="relative group">
                          <div class="bg-gray-100 p-2 rounded-lg flex items-center justify-center min-w-[40px] min-h-[40px]">
                            <span class="text-primary font-futura-bold text-sm">{{ formatCondition(product) }}</span>
                          </div>
                          <!-- Tooltip -->
                          <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-primary text-white text-sm font-futura-bold rounded-lg shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                            {{ formatCondition(product) }}
                            <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-primary"></div>
                          </div>
                        </div>
                      </div>
                      
                      <!-- Details -->
                      <div class="mt-2 text-sm text-gray-600 space-y-1">
                        <div class="flex justify-between">
                          <span>Team:</span>
                          <span class="font-medium">{{ product.team || 'Team' }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span>Set:</span>
                          <span class="font-medium">{{ product.set && product.set !== 'Set' && product.set !== 'Set Name' && product.set !== 'Unknown Set' ? product.set : '-' }}</span>
                        </div>
                        <div class="flex justify-between">
                          <span>Rarity:</span>
                          <span class="font-medium">{{ product.rarity ? (product.rarity + (product.rarity_variation ? ` (${product.rarity_variation})` : '')) : '-' }}</span>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Price and Add to Cart -->
                    <div class="mt-4 flex items-center justify-between">
                      <p class="text-lg font-bold text-gray-900">€{{ formatPrice(product.price) }}</p>
                      <button 
                        @click="handleAddToCart(product, $event)"
                        class="w-10 h-10 bg-blue-600 rounded-md flex items-center justify-center hover:bg-blue-700 transition-colors"
                        aria-label="Aggiungi al carrello"
                      >
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Infinite scroll trigger -->
            <div v-if="hasMoreProducts" ref="loadingMoreTrigger" class="flex justify-center py-8">
              <div v-if="loadingMore" class="animate-spin rounded-full h-6 w-6 border-b-2 border-primary"></div>
            </div>
          </div>
        </div>
      </section>
    </main>
    
    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'
import { useRoute } from 'vue-router'
import { formatPriceItaliana } from '../utils/priceFormatter'
import { formatCondition } from '../utils/conditionFormatter'
import {
  Disclosure,
  DisclosureButton,
  DisclosurePanel,
  Menu,
  MenuButton,
  MenuItem,
  MenuItems,
} from '@headlessui/vue'
import { ChevronDownIcon, MinusIcon, PlusIcon, Squares2X2Icon } from '@heroicons/vue/20/solid'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'
import AdvancedFilters from '../components/AdvancedFilters.vue'
import ProductCard from '../components/ProductCard.vue'
import { useCartStore } from '../stores/cart.js'

const route = useRoute()
const cartStore = useCartStore()

// Reactive data
const viewMode = ref('grid')
const loading = ref(false)
const loadingMore = ref(false)
const hasMoreProducts = ref(true)

// Computed properties per ottenere i parametri dalla route
const category = computed(() => route.params.category)
const subcategory = computed(() => route.params.subcategory)

const categoryName = computed(() => {
  switch(category.value) {
    case 'football': return 'Calcio'
    case 'basketball': return 'Basket'
    case 'pokemon': return 'Pokemon'
    default: return 'Categoria'
  }
})

const subcategoryName = computed(() => {
  switch(subcategory.value) {
    case 'singles': return 'Singles'
    case 'sealed-packs': return 'Sealed Packs'
    case 'sealed-boxes': return 'Sealed Boxes'
    case 'lot': return 'Lot'
    default: return 'Sotto categoria'
  }
})

const subcategoryIcon = computed(() => {
  // Mappa i valori delle sottocategorie ai nomi corretti dei file
  const subcategoryMap = {
    'singles': 'singles',
    'sealed-packs': 'sealed packs',
    'sealed-boxes': 'sealed boxes',
    'lot': 'lot'
  }
  
  const fileName = subcategoryMap[subcategory.value] || subcategory.value
  return `/images/icons/sottocategoria ${fileName}.png`
})

// Sort options
const sortOptions = [
  { name: 'Più popolari', value: 'created_at', order: 'desc' },
  { name: 'Più recenti', value: 'id', order: 'desc' },
  { name: 'Prezzo: dal più basso', value: 'price', order: 'asc' },
  { name: 'Prezzo: dal più alto', value: 'price', order: 'desc' },
  { name: 'Anno: più recenti', value: 'year', order: 'desc' },
  { name: 'Anno: più vecchi', value: 'year', order: 'asc' },
  { name: 'Nome: A-Z', value: 'player_name', order: 'asc' },
  { name: 'Nome: Z-A', value: 'player_name', order: 'desc' },
  { name: 'Squadra: A-Z', value: 'team_name', order: 'asc' },
  { name: 'Squadra: Z-A', value: 'team_name', order: 'desc' },
]

const currentSortOption = ref(sortOptions[0])

// Filters state
const filters = ref({
  // Main filters
  playerSearch: '',
  selectedPlayers: [],
  team: '',
  set: '',
  rarity: '',
  year: '',
  brand: '',
  numberedMin: null,
  numberedMax: null,
  
  // Extra filters
  autograph: '',
  relic: '',
  onCardAuto: '',
  jewel: '',
  booklet: '',
  rookie: '',
  sketch: '',
  multiPlayer: [],
  multiAutograph: [],
  
  // Grading filters
  grading: '',
  gradingScoreMin: null,
  gradingScoreMax: null,
  gradingCompanies: [],
  conditions: []
})

// Data for filters - REMOVED: These are now managed by AdvancedFilters component

// Numbered presets
const numberedPresets = [
  { label: '1-50', min: 1, max: 50 },
  { label: '51-150', min: 51, max: 150 },
  { label: '151-300', min: 151, max: 300 },
  { label: '301+', min: 301, max: 999 }
]

// Multi player/autograph options (booklet rimosso perché è già presente nei Filtri Extra)
const multiPlayerOptions = [
  { label: 'Dual', value: 'dual' },
  { label: 'Triple', value: 'triple' },
  { label: 'Quad', value: 'quad' }
]

// Multi autograph usa le stesse opzioni di multi player
const multiAutographOptions = multiPlayerOptions

// Grading companies - caricati dall'API
const gradingCompanies = ref([])

// Condition options based on CSV data
const conditionOptions = [
  { label: 'Poor', value: 'poor' },
  { label: 'Played', value: 'played' },
  { label: 'Light Played', value: 'light_played' },
  { label: 'Good', value: 'good' },
  { label: 'Excellent', value: 'excellent' },
  { label: 'Near Mint', value: 'near_mint' },
  { label: 'Mint', value: 'mint' }
]

// Subcategories per la navigazione laterale
const subCategories = computed(() => [
  { name: 'Singles', href: `/categories/${category.value}/singles` },
  { name: 'Sealed Packs', href: `/categories/${category.value}/sealed-packs` },
  { name: 'Sealed Boxes', href: `/categories/${category.value}/sealed-boxes` },
  { name: 'Lot', href: `/categories/${category.value}/lot` },
])

// Products data
const products = ref([])
const currentPage = ref(1)
const productsPerPage = 20

// Computed properties
const displayedProducts = computed(() => {
  // I filtri vengono applicati direttamente dall'API
  return products.value
})

// Functions

// Verifica se card_number_in_set è valido (non è un identificatore errato)
const isValidNumbered = (product) => {
  if (!product?.card_number_in_set) return false
  
  const numbered = product.card_number_in_set.toString().trim()
  
  // Se è uguale a card_number, è un errore di importazione
  if (product.card_number && numbered === product.card_number.toString().trim()) {
    return false
  }
  
  // Se contiene solo lettere e trattini (senza numeri), probabilmente è un identificatore errato
  if (!/\d/.test(numbered)) {
    return false
  }
  
  return true
}

const getApiEndpoint = (category) => {
  switch (category) {
    case 'football':
      return '/api/football/filters/products'
    case 'basketball':
      return '/api/basketball/filters/products'
    case 'pokemon':
      return '/api/pokemon/filters/products'
    case 'disney':
      return '/api/disney/filters/products'
    case 'spongebob':
      return '/api/spongebob/filters/products'
    default:
      return '/api/football/filters/products' // Fallback
  }
}

const getCardUrl = (product) => {
  // Usa il listing_id se disponibile per creare URL SEO-friendly e univoco
  // Formato: /categoria/:listingId/nome-carta
  if (product.listing_id) {
    // Genera lo slug dal nome del giocatore/carta
    const slug = (product.name || 'carta')
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '') // Rimuove caratteri speciali
      .replace(/\s+/g, '-') // Sostituisce spazi con trattini
      .replace(/-+/g, '-') // Rimuove trattini multipli
      .replace(/^-+|-+$/g, '') // Rimuove trattini all'inizio e alla fine
    
    return `/${category.value}/${product.listing_id}/${slug}`
  }
  
  // Fallback: genera lo slug dal nome della carta (comportamento legacy)
  const slug = product.name
    .toLowerCase()
    .replace(/[^a-z0-9\s-]/g, '') // Rimuove caratteri speciali
    .replace(/\s+/g, '-') // Sostituisce spazi con trattini
    .replace(/-+/g, '-') // Rimuove trattini multipli
    .replace(/^-+|-+$/g, '') // Rimuove trattini all'inizio e alla fine
  
  return `/${category.value}/${slug}`
}

const getCategorySlug = () => {
  return category.value
}

const getCategoryDisplayName = () => {
  const categoryMap = {
    'football': 'Calcio',
    'basketball': 'Basketball',
    'pokemon': 'Pokemon',
    'disney': 'Disney',
    'spongebob': 'Spongebob'
  }
  return categoryMap[category.value] || 'Categoria'
}

const setSortOption = (option) => {
  currentSortOption.value = option
  // Aggiorna la query per il nuovo ordinamento
  loadProducts(true)
}

// REMOVED: Player search functions - now handled by AdvancedFilters component

// REMOVED: setNumberedRange - now handled by AdvancedFilters component

// REMOVED: handleGradingChange - now handled by AdvancedFilters component

const applyFilters = () => {
  currentPage.value = 1
  // Ricarica i prodotti con i nuovi filtri
  loadProducts(true)
  console.log('Filtri applicati:', filters.value)
}

// Simple debounce function
const debounce = (func, wait) => {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

// Debounced function for loading products
const debouncedLoadProducts = debounce((reset) => {
  loadProducts(reset)
}, 300)

// Funzione per gestire i cambiamenti dei filtri senza ricorsione
const handleFilterChange = (newFilters) => {
  filters.value = newFilters
  currentPage.value = 1
  debouncedLoadProducts(true)
}

const clearFilters = () => {
  // Reset all filters
  filters.value = {
    playerSearch: '',
    selectedPlayers: [],
    team: '',
    set: '',
    rarity: '',
    year: '',
    brand: '',
    numberedMin: null,
    numberedMax: null,
    autograph: '',
    relic: '',
    onCardAuto: '',
    jewel: '',
    booklet: '',
    rookie: '',
    sketch: '',
    multiPlayer: [],
    multiAutograph: [],
    grading: '',
    gradingScoreMin: null,
    gradingScoreMax: null,
    gradingCompanies: [],
    conditions: []
  }
  currentPage.value = 1
  // Ricarica i prodotti senza filtri
  loadProducts(true)
  console.log('Filtri resettati')
}

const loadProducts = async (reset = false) => {
  if (loading.value || loadingMore.value) return // Prevent multiple simultaneous loads
  
  if (reset) {
    products.value = []
    currentPage.value = 1
    loading.value = true // Show initial loader
    disconnectObserver() // Disconnect observer during full reset
  } else {
    loadingMore.value = true // Show "loading more" loader
  }
  
  try {
    // Prepara i parametri per la chiamata API
    const params = new URLSearchParams({
      page: currentPage.value.toString(),
      per_page: productsPerPage.toString(),
      sort_by: currentSortOption.value.value,
      sort_order: currentSortOption.value.order || 'desc'
    })

    // Aggiungi i filtri attivi
    if (filters.value.selectedPlayers && filters.value.selectedPlayers.length > 0) {
      filters.value.selectedPlayers.forEach(playerId => {
        params.append('player_id[]', playerId.toString())
      })
    }

    if (filters.value.team) {
      params.append('team_id', filters.value.team)
    }

    if (filters.value.set) {
      params.append('set_id', filters.value.set)
    }

    if (filters.value.year) {
      params.append('year', filters.value.year)
    }

    if (filters.value.brand) {
      params.append('brand', filters.value.brand)
    }

    if (filters.value.rarity) {
      params.append('rarity', filters.value.rarity)
    }

    if (filters.value.numberedMin !== null) {
      params.append('numbered_min', filters.value.numberedMin.toString())
    }

    if (filters.value.numberedMax !== null) {
      params.append('numbered_max', filters.value.numberedMax.toString())
    }

    // Filtri extra
    if (filters.value.autograph && filters.value.autograph !== '') {
      params.append('autograph', filters.value.autograph)
    }

    if (filters.value.relic && filters.value.relic !== '') {
      params.append('relic', filters.value.relic)
    }

    if (filters.value.onCardAuto && filters.value.onCardAuto !== '') {
      params.append('onCardAuto', filters.value.onCardAuto)
    }

    // Jewel e Rookie solo per categorie sportive
    if ((category.value === 'football' || category.value === 'basketball') && filters.value.jewel && filters.value.jewel !== '') {
      params.append('jewel', filters.value.jewel)
    }

    if (filters.value.booklet && filters.value.booklet !== '') {
      params.append('booklet', filters.value.booklet)
    }

    if ((category.value === 'football' || category.value === 'basketball') && filters.value.rookie && filters.value.rookie !== '') {
      params.append('rookie', filters.value.rookie)
    }

    // Sketch solo per Disney e Spongebob
    if ((category.value === 'disney' || category.value === 'spongebob') && filters.value.sketch && filters.value.sketch !== '') {
      params.append('sketch', filters.value.sketch)
    }

    // Filtri multi player/autograph
    if (filters.value.multiPlayer && filters.value.multiPlayer.length > 0) {
      filters.value.multiPlayer.forEach(value => {
        params.append('multi_player[]', value)
      })
    }

    if (filters.value.multiAutograph && filters.value.multiAutograph.length > 0) {
      filters.value.multiAutograph.forEach(value => {
        params.append('multi_autograph[]', value)
      })
    }

    // Filtri grading
    if (filters.value.grading && filters.value.grading !== '') {
      params.append('grading', filters.value.grading)
    }

    if (filters.value.gradingScoreMin !== null) {
      params.append('grading_score_min', filters.value.gradingScoreMin.toString())
    }

    if (filters.value.gradingScoreMax !== null) {
      params.append('grading_score_max', filters.value.gradingScoreMax.toString())
    }

    if (filters.value.gradingCompanies && filters.value.gradingCompanies.length > 0) {
      filters.value.gradingCompanies.forEach(companyId => {
        params.append('grading_companies[]', companyId.toString())
      })
    }

    if (filters.value.conditions && filters.value.conditions.length > 0) {
      filters.value.conditions.forEach(condition => {
        params.append('conditions[]', condition)
      })
    }

    // Aggiungi filtro per sottocategoria
    if (subcategory.value) {
      params.append('subcategory', subcategory.value)
    }

    // Chiamata API dinamica in base alla categoria
    const apiEndpoint = getApiEndpoint(category.value)
    const response = await fetch(`${apiEndpoint}?${params.toString()}`)
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`)
    }
    
    const data = await response.json()
    
    console.log('API Response:', data)
    
    if (reset) {
      products.value = data.data || []
    } else {
      products.value.push(...(data.data || []))
    }
    
    // Aggiorna stato paginazione
    hasMoreProducts.value = data.has_more_pages || false
    
    console.log('Prodotti caricati:', data.data?.length || 0, 'di', data.total || 0, 'hasMore:', hasMoreProducts.value)
    
  } catch (error) {
    console.error('Errore nel caricamento prodotti:', error)
    // Fallback ai dati mock in caso di errore
    if (reset) {
      products.value = []
    }
  } finally {
    loading.value = false
    loadingMore.value = false
    // Reconnect observer only if there are more products to load and we're not resetting
    if (!reset && hasMoreProducts.value) {
      nextTick(() => {
        connectObserver()
      })
    }
  }
}

// loadMoreProducts is now handled directly by the IntersectionObserver

const goToProduct = (product) => {
  // Naviga alla pagina del prodotto usando URL SEO-friendly
  window.location.href = getCardUrl(product)
}

const handleAddToCart = async (product, event) => {
  // Previeni la propagazione dell'evento per evitare che il click vada al router-link
  if (event) {
    event.preventDefault()
    event.stopPropagation()
  }
  
  // Verifica che il product abbia un listing_id (obbligatorio per aggiungere al carrello)
  if (!product.listing_id) {
    console.error('Product senza listing_id:', product)
    alert('Errore: Impossibile aggiungere al carrello. Inserzione non disponibile.')
    return
  }
  
  // Crea un oggetto listing minimale per il cart store
  // Il backend recupererà tutti i dati necessari tramite listing_id
  // IMPORTANTE: listing_id deve essere una stringa come richiesto dal backend
  const listing = {
    id: String(product.listing_id), // Converti in stringa come richiesto dal backend
    card_model_id: product.id,
    seller_id: product.seller_id || 1, // Fallback temporaneo, il backend dovrebbe fornirlo
    price: parseFloat(String(product.price || 0).replace(/€/g, '').replace(/,/g, '')) || 0,
    condition: formatCondition(product),
    description: product.description || '',
    images: product.images || (product.imageUrl ? [product.imageUrl] : []),
    seller: product.seller || null,
    card_model: product.card_model || null,
    shipping_zones: product.shipping_zones || []
  }
  
  try {
    const result = await cartStore.addToCart(listing, 1)
    
    if (result.success) {
      alert(`${product.name || 'Carta'} aggiunta al carrello!`)
    } else {
      alert(result.message || 'Errore nell\'aggiunta al carrello')
    }
  } catch (error) {
    console.error('Errore nell\'aggiunta al carrello:', error)
    // Mostra messaggio più dettagliato se disponibile
    if (error.response?.data?.errors) {
      const errorMessages = Object.values(error.response.data.errors).flat().join(', ')
      alert(`Errore: ${errorMessages}`)
    } else if (error.response?.data?.message) {
      alert(`Errore: ${error.response.data.message}`)
    } else {
      alert('Errore nell\'aggiunta al carrello. Controlla la console per i dettagli.')
    }
  }
}

const handleImageError = (event) => {
  // Gestisci errori di caricamento immagini in modo sicuro
  try {
    if (!event || !event.target) {
      return
    }
    
    const img = event.target
    if (!img) {
      return
    }
    
    // Nascondi l'immagine che ha fallito usando classi CSS invece di style
    img.classList.add('hidden')
    
    // Cerca il placeholder nel parent (il div contenitore)
    const parent = img.parentElement
    if (!parent) {
      return
    }
    
    // Cerca un div placeholder nel parent (ora sempre presente nel DOM)
    const placeholder = Array.from(parent.children).find(
      el => el !== img && el && el.classList && el.classList.contains('bg-gray-200')
    )
    
    // Mostra il placeholder se esiste, usando solo classi CSS
    if (placeholder && placeholder.classList) {
      placeholder.classList.remove('hidden')
    }
  } catch (error) {
    // Silenziosamente ignora errori per evitare di bloccare l'app
    console.warn('Errore nella gestione del fallimento immagine:', error)
  }
}

const formatPrice = (price) => {
  return formatPriceItaliana(price, false)
}

// Intersection Observer setup
const observer = ref(null)
const loadingMoreTrigger = ref(null)

const disconnectObserver = () => {
  if (observer.value) {
    observer.value.disconnect()
    observer.value = null // Clear the observer instance
  }
}

const connectObserver = () => {
  // Only connect if there's a trigger element and potentially more products
  if (loadingMoreTrigger.value && hasMoreProducts.value && !observer.value) {
    observer.value = new IntersectionObserver(
      (entries) => {
        const entry = entries[0]
        // Only load more if the trigger is intersecting, there are more products, and no other loading is in progress
        if (entry.isIntersecting && hasMoreProducts.value && !loading.value && !loadingMore.value) {
          console.log('Loading more products, page:', currentPage.value + 1)
          currentPage.value++
          loadProducts(false) // Load more products
        }
      },
      { threshold: 0.5 }
    )
    observer.value.observe(loadingMoreTrigger.value)
    console.log('IntersectionObserver connected')
  } else {
    console.log('IntersectionObserver not connected:', {
      hasTrigger: !!loadingMoreTrigger.value,
      hasMoreProducts: hasMoreProducts.value,
      hasObserver: !!observer.value
    })
  }
}

// Carica i grading companies dall'API
const loadGradingCompanies = async () => {
  try {
    const response = await fetch(`/api/${category.value}/filters/options`)
    const data = await response.json()
    
    if (data.grading_companies && Array.isArray(data.grading_companies)) {
      gradingCompanies.value = data.grading_companies.map(company => ({
        id: company.id,
        name: company.name,
        slug: company.slug
      }))
    }
  } catch (error) {
    console.error('Errore nel caricamento grading companies:', error)
    // Fallback ai valori di default se l'API fallisce
    gradingCompanies.value = []
  }
}

// Carica i dati quando il componente viene montato
onMounted(() => {
  loadProducts(true)
  loadGradingCompanies()
  // REMOVED: loadFilterData() - now handled by AdvancedFilters component
  // Initial observer connection will be handled after products are loaded
})

// Cleanup observer when component is unmounted
onUnmounted(() => {
  disconnectObserver()
})

// REMOVED: loadFilterData function - now handled by AdvancedFilters component

// Rimuovo i watcher che causano loop infiniti
// I filtri verranno applicati manualmente quando necessario
</script>
