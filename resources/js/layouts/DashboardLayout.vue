<template>
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
                      <router-link
                        v-if="item.href" 
                        :to="item.href" 
                        :class="[active ? 'bg-gray-50 outline-hidden' : '', 'block px-3 py-1 text-sm/6 text-gray-900 font-gill-sans']"
                      >
                        {{ item.name }}
                      </router-link>
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
          <!-- Slot per il contenuto delle pagine -->
          <slot />
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
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

const router = useRouter()
const authStore = useAuthStore()

const user = computed(() => authStore.user)
const sidebarOpen = ref(false)

const userNavigation = [
  { name: 'Il tuo profilo', href: '/account/profile' },
  { name: 'Logout', action: 'logout' },
]

// Carica dati utente se necessario
onMounted(async () => {
  try {
    // Se c'è un token ma non c'è l'utente, caricalo
    if (authStore.token && !authStore.user) {
      await authStore.fetchUser()
    }

    // Se non è autenticato, reindirizza al login
    if (!authStore.isAuthenticated) {
      router.push('/login')
      return
    }
  } catch (error) {
    console.error('Errore nel caricamento utente:', error)
    router.push('/login')
  }
})

const getUserInitials = (name) => {
  if (!name) return 'U'
  
  const names = name.trim().split(' ')
  if (names.length === 1) {
    return names[0].charAt(0).toUpperCase()
  }
  
  return (names[0].charAt(0) + names[names.length - 1].charAt(0)).toUpperCase()
}

const handleLogout = () => {
  authStore.logout()
  router.push('/')
}
</script>
