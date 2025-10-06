<template>
    <!--
      This example requires updating your template:
  
      ```
      <html class="h-full bg-white">
      <body class="h-full">
      ```
    -->
    <div class="h-full bg-gray-50">
      <TransitionRoot as="template" :show="sidebarOpen">
        <Dialog class="relative z-50 lg:hidden" @close="sidebarOpen = false">
          <TransitionChild as="template" enter="transition-opacity ease-linear duration-300" enter-from="opacity-0" enter-to="" leave="transition-opacity ease-linear duration-300" leave-from="" leave-to="opacity-0">
            <div class="fixed inset-0 bg-gray-900/80" />
          </TransitionChild>
  
          <div class="fixed inset-0 flex">
            <TransitionChild as="template" enter="transition ease-in-out duration-300 transform" enter-from="-translate-x-full" enter-to="translate-x-0" leave="transition ease-in-out duration-300 transform" leave-from="translate-x-0" leave-to="-translate-x-full">
              <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
                <TransitionChild as="template" enter="ease-in-out duration-300" enter-from="opacity-0" enter-to="" leave="ease-in-out duration-300" leave-from="" leave-to="opacity-0">
                  <div class="absolute top-0 left-full flex w-16 justify-center pt-5">
                    <button type="button" class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                      <span class="sr-only">Close sidebar</span>
                      <XMarkIcon class="size-6 text-white" aria-hidden="true" />
                    </button>
                  </div>
                </TransitionChild>
  
                <!-- Unified Sidebar -->
                <UnifiedSidebar />
              </DialogPanel>
            </TransitionChild>
          </div>
        </Dialog>
      </TransitionRoot>
  
      <!-- Static sidebar for desktop -->
      <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
        <!-- Unified Sidebar -->
        <div class="flex grow flex-col gap-y-5 overflow-y-auto border-r border-gray-200 bg-white">
          <UnifiedSidebar />
        </div>
      </div>
  
      <div class="lg:pl-72">
        <div class="sticky top-0 z-40">
          <div class="flex h-16 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-xs sm:gap-x-6 sm:px-6 lg:px-0 lg:shadow-none">
            <button type="button" class="-m-2.5 p-2.5 text-gray-700 hover:text-gray-900 lg:hidden" @click="sidebarOpen = true">
              <span class="sr-only">Open sidebar</span>
              <Bars3Icon class="size-6" aria-hidden="true" />
            </button>
  
            <!-- Separator -->
            <div class="h-6 w-px bg-gray-200 lg:hidden" aria-hidden="true" />
  
            <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
              <!-- Campo ricerca rimosso -->
              <div class="flex items-center gap-x-4 lg:gap-x-6 ml-auto">
                <button type="button" class="-m-2.5 p-2.5 text-gray-400 hover:text-gray-500">
                  <span class="sr-only">View notifications</span>
                  <BellIcon class="size-6" aria-hidden="true" />
                </button>
  
                <!-- Separator -->
                <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200" aria-hidden="true" />
  
                <!-- Profile dropdown -->
                <Menu as="div" class="relative">
                  <MenuButton class="relative flex items-center">
                    <span class="absolute -inset-1.5" />
                    <span class="sr-only">Open user menu</span>
                    <div class="size-8 rounded-full bg-primary flex items-center justify-center outline -outline-offset-1 outline-black/5">
                      <span class="text-white text-sm font-gill-sans-semibold">
                        {{ getUserInitials(user?.name) }}
                      </span>
                    </div>
                    <span class="hidden lg:flex lg:items-center">
                      <span class="ml-4 text-sm/6 font-gill-sans-semibold text-gray-900" aria-hidden="true">{{ user?.name || 'Utente' }}</span>
                      <ChevronDownIcon class="ml-2 size-5 text-gray-400" aria-hidden="true" />
                    </span>
                  </MenuButton>
                  <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform scale-100" leave-to-class="transform opacity-0 scale-95">
                    <MenuItems class="absolute right-0 z-10 mt-2.5 w-32 origin-top-right rounded-md bg-white py-2 shadow-lg outline-1 outline-gray-900/5">
                      <MenuItem v-for="item in userNavigation" :key="item.name" v-slot="{ active }">
                        <a 
                          v-if="item.href" 
                          :href="item.href" 
                          :class="[active ? 'bg-gray-50 outline-hidden' : '', 'block px-3 py-1 text-sm/6 text-gray-900 font-gill-sans']"
                        >
                          {{ item.name }}
                        </a>
                        <button 
                          v-else-if="item.action === 'logout'"
                          @click="handleLogout"
                          :class="[active ? 'bg-gray-50 outline-hidden' : '', 'block w-full text-left px-3 py-1 text-sm/6 text-gray-900 font-gill-sans']"
                        >
                          {{ item.name }}
                        </button>
                      </MenuItem>
                    </MenuItems>
                  </transition>
                </Menu>
              </div>
            </div>
          </div>
        </div>
  
        <main class="py-10">
          <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Dashboard content -->
            <div class="mb-8">
              <h1 class="text-3xl font-futura-bold text-gray-900 mb-2">
                {{ user?.role === 'admin' ? 'Dashboard Amministrazione' : 'Dashboard' }}
              </h1>
              <p class="text-gray-600 font-gill-sans">
                {{ user?.role === 'admin' ? 'Panoramica completa della piattaforma' : 'Benvenuto nella tua dashboard CARDSWAP' }}
              </p>
            </div>


            <!-- Loading state -->
            <div v-if="loading" class="flex justify-center items-center py-12">
              <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"></div>
            </div>
            
            <!-- Stats cards dinamiche -->
            <div v-else-if="dashboardData" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
              <!-- Admin stats -->
              <template v-if="user?.role === 'admin'">
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                  <div class="flex items-center">
                    <div class="p-2 bg-primary rounded-lg">
                      <UsersIcon class="w-6 h-6 text-white" />
                    </div>
                    <div class="ml-4">
                      <p class="text-sm font-gill-sans text-gray-600">Utenti Totali</p>
                      <p class="text-2xl font-futura-bold text-gray-900">{{ dashboardData.stats?.users?.total || 0 }}</p>
                    </div>
                  </div>
                </div>
                
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                  <div class="flex items-center">
                    <div class="p-2 bg-secondary rounded-lg">
                      <DocumentDuplicateIcon class="w-6 h-6 text-primary" />
                    </div>
                    <div class="ml-4">
                      <p class="text-sm font-gill-sans text-gray-600">Ordini Totali</p>
                      <p class="text-2xl font-futura-bold text-gray-900">{{ dashboardData.stats?.orders?.total || 0 }}</p>
                    </div>
                  </div>
                </div>
                
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                  <div class="flex items-center">
                    <div class="p-2 bg-accent rounded-lg">
                      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                      </svg>
                    </div>
                    <div class="ml-4">
                      <p class="text-sm font-gill-sans text-gray-600">Ricavi Totali</p>
                      <p class="text-2xl font-futura-bold text-gray-900">€{{ (dashboardData.stats?.orders?.total_revenue || 0).toLocaleString() }}</p>
                    </div>
                  </div>
                </div>
                
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                  <div class="flex items-center">
                    <div class="p-2 bg-accent-red rounded-lg">
                      <ExclamationTriangleIcon class="w-6 h-6 text-white" />
                    </div>
                    <div class="ml-4">
                      <p class="text-sm font-gill-sans text-gray-600">KYC in Attesa</p>
                      <p class="text-2xl font-futura-bold text-gray-900">{{ dashboardData.stats?.kyc?.pending || 0 }}</p>
                    </div>
                  </div>
                </div>
              </template>

              <!-- User/Seller stats -->
              <template v-else>
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                  <div class="flex items-center">
                    <div class="p-2 bg-primary rounded-lg">
                      <DocumentDuplicateIcon class="w-6 h-6 text-white" />
                    </div>
                    <div class="ml-4">
                      <p class="text-sm font-gill-sans text-gray-600">Ordini Totali</p>
                      <p class="text-2xl font-futura-bold text-gray-900">{{ dashboardData.stats?.orders?.total || 0 }}</p>
                    </div>
                  </div>
                </div>
                
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                  <div class="flex items-center">
                    <div class="p-2 bg-secondary rounded-lg">
                      <ChartPieIcon class="w-6 h-6 text-primary" />
                    </div>
                    <div class="ml-4">
                      <p class="text-sm font-gill-sans text-gray-600">Wishlist</p>
                      <p class="text-2xl font-futura-bold text-gray-900">{{ dashboardData.stats?.wishlist?.total_items || 0 }}</p>
                    </div>
                  </div>
                </div>
                
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                  <div class="flex items-center">
                    <div class="p-2 bg-accent rounded-lg">
                      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                      </svg>
                    </div>
                    <div class="ml-4">
                      <p class="text-sm font-gill-sans text-gray-600">Inserzioni Attive</p>
                      <p class="text-2xl font-futura-bold text-gray-900">{{ dashboardData.stats?.listings?.active || 0 }}</p>
                    </div>
                  </div>
                </div>
                
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-lg transition-shadow">
                  <div class="flex items-center">
                    <div class="p-2 bg-accent-red rounded-lg">
                      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                    </div>
                    <div class="ml-4">
                      <p class="text-sm font-gill-sans text-gray-600">{{ user?.role === 'seller' ? 'Carte Vendute' : 'Carte Acquistate' }}</p>
                      <p class="text-2xl font-futura-bold text-gray-900">{{ dashboardData.stats?.listings?.sold || 0 }}</p>
                    </div>
                  </div>
                </div>
              </template>
            </div>
            
            <!-- Recent activity -->
            <div v-if="!loading" class="bg-white rounded-lg border border-gray-200 p-6">
              <h2 class="text-xl font-futura-bold text-gray-900 mb-4">
                {{ user?.role === 'admin' ? 'Attività Piattaforma' : 'Attività Recenti' }}
              </h2>
              <div class="space-y-4">
                <!-- Admin activity -->
                <template v-if="user?.role === 'admin'">
                  <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                    <div class="w-2 h-2 bg-primary rounded-full"></div>
                    <div class="flex-1">
                      <p class="text-sm font-gill-sans-semibold text-gray-900">Nuovi utenti registrati</p>
                      <p class="text-xs font-gill-sans text-gray-600">{{ dashboardData.stats?.users?.new_this_week || 0 }} questa settimana</p>
                    </div>
                    <span class="text-xs font-gill-sans text-gray-500">Oggi</span>
                  </div>
                  
                  <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                    <div class="w-2 h-2 bg-secondary rounded-full"></div>
                    <div class="flex-1">
                      <p class="text-sm font-gill-sans-semibold text-gray-900">Ordini in attesa</p>
                      <p class="text-xs font-gill-sans text-gray-600">{{ dashboardData.stats?.orders?.pending || 0 }} ordini da gestire</p>
                    </div>
                    <span class="text-xs font-gill-sans text-gray-500">Oggi</span>
                  </div>
                  
                  <div class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg">
                    <div class="w-2 h-2 bg-accent-red rounded-full"></div>
                    <div class="flex-1">
                      <p class="text-sm font-gill-sans-semibold text-gray-900">KYC da verificare</p>
                      <p class="text-xs font-gill-sans text-gray-600">{{ dashboardData.stats?.kyc?.pending || 0 }} documenti in attesa</p>
                    </div>
                    <span class="text-xs font-gill-sans text-gray-500">Oggi</span>
                  </div>
                </template>

                <!-- User/Seller activity -->
                <template v-else>
                  <div v-if="dashboardData?.activities?.length > 0" class="space-y-3">
                    <div 
                      v-for="activity in dashboardData.activities" 
                      :key="`${activity.type}-${activity.date}`"
                      class="flex items-center space-x-4 p-3 bg-gray-50 rounded-lg"
                    >
                      <div 
                        class="w-2 h-2 rounded-full"
                        :class="{
                          'bg-primary': activity.type === 'order',
                          'bg-secondary': activity.type === 'listing',
                          'bg-accent': activity.type === 'notification'
                        }"
                      ></div>
                      <div class="flex-1">
                        <p class="text-sm font-gill-sans-semibold text-gray-900">{{ activity.title }}</p>
                        <p class="text-xs font-gill-sans text-gray-600">{{ activity.description }}</p>
                      </div>
                      <span class="text-xs font-gill-sans text-gray-500">
                        {{ formatRelativeTime(activity.date) }}
                      </span>
                    </div>
                  </div>
                  
                  <div v-else class="text-center py-8">
                    <p class="text-gray-500 font-gill-sans">Nessuna attività recente</p>
                  </div>
                </template>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  </template>
  
  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import { useRouter } from 'vue-router'
  import {
    Dialog,
    DialogPanel,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    TransitionChild,
    TransitionRoot,
  } from '@headlessui/vue'
  import {
    Bars3Icon,
    BellIcon,
    XMarkIcon,
  } from '@heroicons/vue/24/outline'
  import { ChevronDownIcon } from '@heroicons/vue/20/solid'
  import UnifiedSidebar from '@/components/UnifiedSidebar.vue'
  import { useAuthStore } from '@/stores/auth'
  
  const router = useRouter()
  const authStore = useAuthStore()
  const user = ref(null)
  const dashboardData = ref(null)
  const loading = ref(true)

  const userNavigation = [
    { name: 'Il tuo profilo', href: '#' },
    { name: 'Logout', action: 'logout' },
  ]
  
  const sidebarOpen = ref(false)

  // Carica dati utente e dashboard
  onMounted(async () => {
    try {
      // Se c'è un token ma non c'è l'utente, caricalo
      if (authStore.token && !authStore.user) {
        await authStore.fetchUser()
      }

      // Usa l'auth store per ottenere i dati utente
      if (!authStore.isAuthenticated) {
        router.push('/login')
        return
      }

      // Carica i dati utente dall'auth store
      user.value = authStore.user

      // Carica dati dashboard in base al ruolo
      if (user.value.role === 'admin') {
        await loadAdminDashboard()
      } else {
        await loadUserDashboard()
      }

    } catch (error) {
      console.error('Errore nel caricamento dashboard:', error)
      // Fallback ai dati statici
      user.value = { role: 'buyer', name: 'Utente' }
    } finally {
      loading.value = false
    }
  })

  const loadAdminDashboard = async () => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch('/api/admin/dashboard', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        }
      })

      if (response.ok) {
        const data = await response.json()
        dashboardData.value = data.data
      }
    } catch (error) {
      console.error('Errore nel caricamento dashboard admin:', error)
    }
  }

  const loadUserDashboard = async () => {
    try {
      const token = localStorage.getItem('token')
      const response = await fetch('/api/dashboard', {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Accept': 'application/json',
        }
      })

      if (response.ok) {
        const data = await response.json()
        dashboardData.value = data.data
      } else {
        console.error('Errore nel caricamento dashboard utente:', response.statusText)
        // Fallback ai dati reali dal database invece di dati statici
        dashboardData.value = {
          stats: {
            orders: { total: 0, as_buyer: 0, as_seller: 0, pending: 0, completed: 0 },
            listings: { total: 0, active: 0, draft: 0, sold: 0 },
            wishlist: { total_items: 0, recent_additions: 0 },
            notifications: { unread: 0, total_today: 0, total_week: 0 },
            kyc: { status: user.value?.kyc_status || 'pending', can_sell: user.value?.canSell?.() || false, needs_kyc: user.value?.needsKyc?.() || true }
          },
          activities: []
        }
      }
    } catch (error) {
      console.error('Errore nel caricamento dashboard utente:', error)
      // Fallback ai dati reali dal database invece di dati statici
      dashboardData.value = {
        stats: {
          orders: { total: 0, as_buyer: 0, as_seller: 0, pending: 0, completed: 0 },
          listings: { total: 0, active: 0, draft: 0, sold: 0 },
          wishlist: { total_items: 0, recent_additions: 0 },
          notifications: { unread: 0, total_today: 0, total_week: 0 },
          kyc: { status: user.value?.kyc_status || 'pending', can_sell: user.value?.canSell?.() || false, needs_kyc: user.value?.needsKyc?.() || true }
        },
        activities: []
      }
    }
  }

  const getUserInitials = (name) => {
    if (!name) return 'U'
    
    const names = name.trim().split(' ')
    if (names.length === 1) {
      return names[0].charAt(0).toUpperCase()
    }
    
    return (names[0].charAt(0) + names[names.length - 1].charAt(0)).toUpperCase()
  }

  const formatRelativeTime = (dateString) => {
    if (!dateString) return 'N/A'
    
    const date = new Date(dateString)
    const now = new Date()
    const diffInSeconds = Math.floor((now - date) / 1000)
    
    if (diffInSeconds < 60) {
      return 'Ora'
    } else if (diffInSeconds < 3600) {
      const minutes = Math.floor(diffInSeconds / 60)
      return `${minutes} min fa`
    } else if (diffInSeconds < 86400) {
      const hours = Math.floor(diffInSeconds / 3600)
      return `${hours} ore fa`
    } else if (diffInSeconds < 604800) {
      const days = Math.floor(diffInSeconds / 86400)
      return `${days} giorni fa`
    } else {
      return date.toLocaleDateString('it-IT')
    }
  }

  const handleLogout = () => {
    // Usa l'auth store per il logout
    authStore.logout()
    
    console.log('Logout effettuato')
    
    // Reindirizzamento alla home
    router.push('/')
  }
  </script>