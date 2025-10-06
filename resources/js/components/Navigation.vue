<template>
  <div class="bg-white">
    <!-- Mobile menu -->
    <TransitionRoot as="template" :show="mobileMenuOpen">
      <Dialog class="relative z-40 lg:hidden" @close="mobileMenuOpen = false">
        <TransitionChild as="template" enter="transition-opacity ease-linear duration-300" enter-from="opacity-0" enter-to="opacity-100" leave="transition-opacity ease-linear duration-300" leave-from="opacity-100" leave-to="opacity-0">
          <div class="fixed inset-0 bg-black/25" />
        </TransitionChild>
        <div class="fixed inset-0 z-40 flex">
          <TransitionChild as="template" enter="transition ease-in-out duration-300 transform" enter-from="-translate-x-full" enter-to="translate-x-0" leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0" leave-to="-translate-x-full">
            <DialogPanel class="relative flex w-full max-w-xs flex-col overflow-y-auto bg-white pb-12 shadow-xl">
              <div class="flex px-4 pt-5 pb-2">
                <button type="button" class="relative -m-2 inline-flex items-center justify-center rounded-md p-2 text-gray-400" @click="mobileMenuOpen = false">
                  <span class="absolute -inset-0.5" />
                  <span class="sr-only">Chiudi menu</span>
                  <XMarkIcon class="size-6" aria-hidden="true" />
                </button>
              </div>

              <!-- Links -->
              <TabGroup as="div" class="mt-2">
                <div class="border-b border-gray-200">
                  <TabList class="-mb-px flex space-x-8 px-4">
                    <Tab as="template" v-for="category in navigation.categories" :key="category.name" v-slot="{ selected }">
                      <button :class="[selected ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-900', 'flex-1 border-b-2 px-1 py-4 text-base font-medium whitespace-nowrap']">{{ category.name }}</button>
                    </Tab>
                  </TabList>
                </div>
                <TabPanels as="template">
                  <TabPanel v-for="category in navigation.categories" :key="category.name" class="space-y-12 px-4 py-6">
                    <div class="grid grid-cols-2 gap-x-4 gap-y-10">
                      <div v-for="item in category.featured" :key="item.name" class="group relative">
                        <img :src="item.imageSrc" :alt="item.imageAlt" class="aspect-square w-full rounded-md bg-gray-100 object-cover group-hover:opacity-75" />
                        <router-link :to="item.href" class="mt-6 block text-sm font-medium text-gray-900">
                          <span class="absolute inset-0 z-10" aria-hidden="true" />
                          {{ item.name }}
                        </router-link>
                        <p aria-hidden="true" class="mt-1 text-sm text-gray-500">Visualizza</p>
                      </div>
                    </div>
                  </TabPanel>
                </TabPanels>
              </TabGroup>

              <div class="space-y-6 border-t border-gray-200 px-4 py-6">
                <div v-for="page in navigation.pages" :key="page.name" class="flow-root">
                  <router-link :to="page.href" class="-m-2 block p-2 font-medium text-gray-900">{{ page.name }}</router-link>
                </div>
              </div>

              <div class="space-y-6 border-t border-gray-200 px-4 py-6">
                <div class="flow-root">
                  <router-link to="/register" class="-m-2 block p-2 font-medium text-gray-900">Crea un account</router-link>
                </div>
                <div class="flow-root">
                  <router-link to="/login" class="-m-2 block p-2 font-medium text-gray-900">Accedi</router-link>
                </div>
              </div>

              <div class="space-y-6 border-t border-gray-200 px-4 py-6">
                <!-- Currency selector -->
                <form>
                  <div class="-ml-2 inline-grid grid-cols-1">
                    <select id="mobile-currency" name="currency" aria-label="Valuta" class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-white py-0.5 pr-7 pl-2 text-base font-medium text-gray-700 group-hover:text-gray-800 focus:outline-2 sm:text-sm/6">
                      <option v-for="currency in currencies" :key="currency">{{ currency }}</option>
                    </select>
                    <ChevronDownIcon class="pointer-events-none col-start-1 row-start-1 mr-1 size-5 self-center justify-self-end fill-gray-500" aria-hidden="true" />
                  </div>
                </form>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </Dialog>
    </TransitionRoot>

    <!-- Hero section -->
    <div class="relative bg-gray-900">
      <!-- Decorative image and overlay -->
      <div aria-hidden="true" class="absolute inset-0 overflow-hidden">
        <img src="https://images.unsplash.com/photo-1551698618-1dfe5d97d256?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=2850&q=80" alt="" class="size-full object-cover" />
      </div>
      <div aria-hidden="true" class="absolute inset-0 bg-gray-900 opacity-50" />

      <!-- Navigation -->
      <header class="relative z-10">
        <nav aria-label="Top">
          <!-- Top navigation -->
          <div class="bg-gray-900">
            <div class="mx-auto flex h-10 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
              <!-- Currency selector -->
              <form>
                <div class="-ml-2 inline-grid grid-cols-1">
                  <select id="desktop-currency" name="currency" aria-label="Valuta" class="col-start-1 row-start-1 w-full appearance-none rounded-md bg-gray-900 py-0.5 pr-7 pl-2 text-left text-base font-medium text-white focus:outline-2 focus:-outline-offset-1 focus:outline-white sm:text-sm/6">
                    <option v-for="currency in currencies" :key="currency">{{ currency }}</option>
                  </select>
                  <ChevronDownIcon class="pointer-events-none col-start-1 row-start-1 mr-1 size-5 self-center justify-self-end fill-gray-300" aria-hidden="true" />
                </div>
              </form>

              <div class="flex items-center space-x-6">
                <router-link to="/login" class="text-sm font-medium text-white hover:text-gray-100">Accedi</router-link>
                <router-link to="/register" class="text-sm font-medium text-white hover:text-gray-100">Crea un account</router-link>
              </div>
            </div>
          </div>

          <!-- Secondary navigation -->
          <div class="bg-white/10 backdrop-blur-md backdrop-filter">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
              <div>
                <div class="flex h-16 items-center justify-between">
                  <!-- Logo (lg+) -->
                  <div class="hidden lg:flex lg:flex-1 lg:items-center">
                    <router-link to="/">
                      <span class="sr-only">CARDSWAP</span>
                      <span class="text-2xl font-bold text-white">CARDSWAP</span>
                    </router-link>
                  </div>

                  <div class="hidden h-full lg:flex">
                    <!-- Flyout menus -->
                    <PopoverGroup class="inset-x-0 bottom-0 px-4">
                      <div class="flex h-full justify-center space-x-8">
                        <Popover v-for="category in navigation.categories" :key="category.name" class="flex" v-slot="{ open }">
                          <div class="relative flex">
                            <PopoverButton class="relative flex items-center justify-center text-sm font-medium text-white transition-colors duration-200 ease-out">
                              {{ category.name }}
                              <span :class="[open ? 'bg-white' : '', 'absolute inset-x-0 -bottom-px z-30 h-0.5 transition duration-200 ease-out']" aria-hidden="true" />
                            </PopoverButton>
                          </div>
                          <transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                            <PopoverPanel class="absolute inset-x-0 top-full z-20 w-full bg-white text-sm text-gray-500">
                              <!-- Presentational element used to render the bottom shadow, if we put the shadow on the actual panel it pokes out the top, so we use this shorter element to hide the top of the shadow -->
                              <div class="absolute inset-0 top-1/2 bg-white shadow-sm" aria-hidden="true" />
                              <div class="relative bg-white">
                                <div class="mx-auto max-w-7xl px-8">
                                  <div class="grid grid-cols-4 gap-x-8 gap-y-10 py-16">
                                    <div v-for="item in category.featured" :key="item.name" class="group relative">
                                      <img :src="item.imageSrc" :alt="item.imageAlt" class="aspect-square w-full rounded-md bg-gray-100 object-cover group-hover:opacity-75" />
                                      <router-link :to="item.href" class="mt-4 block font-medium text-gray-900">
                                        <span class="absolute inset-0 z-10" aria-hidden="true" />
                                        {{ item.name }}
                                      </router-link>
                                      <p aria-hidden="true" class="mt-1">Visualizza</p>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </PopoverPanel>
                          </transition>
                        </Popover>
                        <router-link v-for="page in navigation.pages" :key="page.name" :to="page.href" class="flex items-center text-sm font-medium text-white">{{ page.name }}</router-link>
                      </div>
                    </PopoverGroup>
                  </div>

                  <!-- Mobile menu and search (lg-) -->
                  <div class="flex flex-1 items-center lg:hidden">
                    <button type="button" class="-ml-2 p-2 text-white" @click="mobileMenuOpen = true">
                      <span class="sr-only">Apri menu</span>
                      <Bars3Icon class="size-6" aria-hidden="true" />
                    </button>

                    <!-- Search -->
                    <router-link to="/search" class="ml-2 p-2 text-white">
                      <span class="sr-only">Cerca</span>
                      <MagnifyingGlassIcon class="size-6" aria-hidden="true" />
                    </router-link>
                  </div>

                  <!-- Logo (lg-) -->
                  <router-link to="/" class="lg:hidden">
                    <span class="sr-only">CARDSWAP</span>
                    <span class="text-2xl font-bold text-white">CARDSWAP</span>
                  </router-link>

                  <div class="flex flex-1 items-center justify-end">
                    <router-link to="/search" class="hidden text-sm font-medium text-white lg:block">Cerca</router-link>

                    <div class="flex items-center lg:ml-8">
                      <!-- Help -->
                      <router-link to="/help" class="p-2 text-white lg:hidden">
                        <span class="sr-only">Aiuto</span>
                        <QuestionMarkCircleIcon class="size-6" aria-hidden="true" />
                      </router-link>
                      <router-link to="/help" class="hidden text-sm font-medium text-white lg:block">Aiuto</router-link>

                      <!-- Cart -->
                      <div class="ml-4 flow-root lg:ml-8">
                        <router-link to="/cart" class="group -m-2 flex items-center p-2">
                          <ShoppingBagIcon class="size-6 shrink-0 text-white" aria-hidden="true" />
                          <span class="ml-2 text-sm font-medium text-white">0</span>
                          <span class="sr-only">articoli nel carrello, visualizza carrello</span>
                        </router-link>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </nav>
      </header>

      <!-- Hero content slot -->
      <slot name="hero-content">
        <div class="relative mx-auto flex max-w-3xl flex-col items-center px-6 py-32 text-center sm:py-64 lg:px-0">
          <h1 class="text-4xl font-bold tracking-tight text-white lg:text-6xl">Nuove carte disponibili</h1>
          <p class="mt-4 text-xl text-white">Le nuove carte sono arrivate. Controlla le ultime opzioni dalla nostra collezione estiva mentre sono ancora disponibili.</p>
          <router-link to="/categories" class="mt-8 inline-block rounded-md border border-transparent bg-white px-8 py-3 text-base font-medium text-gray-900 hover:bg-gray-100">Sfoglia Nuove Carte</router-link>
        </div>
      </slot>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import {
  Dialog,
  DialogPanel,
  Popover,
  PopoverButton,
  PopoverGroup,
  PopoverPanel,
  Tab,
  TabGroup,
  TabList,
  TabPanel,
  TabPanels,
  TransitionChild,
  TransitionRoot,
} from '@headlessui/vue'
import {
  Bars3Icon,
  MagnifyingGlassIcon,
  QuestionMarkCircleIcon,
  ShoppingBagIcon,
  XMarkIcon,
} from '@heroicons/vue/24/outline'
import { ChevronDownIcon } from '@heroicons/vue/20/solid'

const currencies = ['EUR', 'USD', 'GBP', 'JPY', 'CAD']
const navigation = {
  categories: [
    {
      name: 'Calcio',
      icon: '/images/icons/categoria football.png',
      featured: [
        {
          name: 'Singles',
          href: '/categories/football/singles',
          imageSrc: '/images/icons/sottocategoria singles.svg',
          imageAlt: 'Carte singole di calcio',
        },
        {
          name: 'Sealed Packs',
          href: '/categories/football/sealed-packs',
          imageSrc: '/images/icons/sottocategoria sealed packs.svg',
          imageAlt: 'Buste sigillate di calcio',
        },
        {
          name: 'Sealed Boxes',
          href: '/categories/football/sealed-boxes',
          imageSrc: '/images/icons/sottocategoria sealed boxes.svg',
          imageAlt: 'Scatole sigillate di calcio',
        },
        {
          name: 'Lot',
          href: '/categories/football/lot',
          imageSrc: '/images/icons/sottocategoria lot.svg',
          imageAlt: 'Lotti di carte di calcio',
        },
      ],
    },
    {
      name: 'Basket',
      icon: '/images/icons/categoria basketball.png',
      featured: [
        {
          name: 'Singles',
          href: '/categories/basketball/singles',
          imageSrc: '/images/icons/sottocategoria singles.svg',
          imageAlt: 'Carte singole di basket',
        },
        {
          name: 'Sealed Packs',
          href: '/categories/basketball/sealed-packs',
          imageSrc: '/images/icons/sottocategoria sealed packs.svg',
          imageAlt: 'Buste sigillate di basket',
        },
        {
          name: 'Sealed Boxes',
          href: '/categories/basketball/sealed-boxes',
          imageSrc: '/images/icons/sottocategoria sealed boxes.svg',
          imageAlt: 'Scatole sigillate di basket',
        },
        {
          name: 'Lot',
          href: '/categories/basketball/lot',
          imageSrc: '/images/icons/sottocategoria lot.svg',
          imageAlt: 'Lotti di carte di basket',
        },
      ],
    },
    {
      name: 'Pokemon',
      icon: '/images/icons/categoria pokemon.png',
      featured: [
        {
          name: 'Singles',
          href: '/categories/pokemon/singles',
          imageSrc: '/images/icons/sottocategoria singles.svg',
          imageAlt: 'Carte singole Pokemon',
        },
        {
          name: 'Sealed Packs',
          href: '/categories/pokemon/sealed-packs',
          imageSrc: '/images/icons/sottocategoria sealed packs.svg',
          imageAlt: 'Buste sigillate Pokemon',
        },
        {
          name: 'Sealed Boxes',
          href: '/categories/pokemon/sealed-boxes',
          imageSrc: '/images/icons/sottocategoria sealed boxes.svg',
          imageAlt: 'Scatole sigillate Pokemon',
        },
        {
          name: 'Lot',
          href: '/categories/pokemon/lot',
          imageSrc: '/images/icons/sottocategoria lot.svg',
          imageAlt: 'Lotti di carte Pokemon',
        },
      ],
    },
  ],
  pages: [
    { name: 'Azienda', href: '/about' },
    { name: 'Negozi', href: '/stores' },
  ],
}

const mobileMenuOpen = ref(false)
</script>
