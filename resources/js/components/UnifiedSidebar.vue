<template>
  <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-white px-6 pb-4">
    <div class="flex h-16 shrink-0 items-center">
      <router-link to="/" class="flex items-center">
        <img class="h-8 w-auto" src="/images/logos/logo-blu.svg" alt="CARDSWAP" />
      </router-link>
    </div>
    
    <nav class="flex flex-1 flex-col">
      <ul role="list" class="flex flex-1 flex-col gap-y-7">
        <!-- Dashboard -->
        <li>
          <ul role="list" class="-mx-2 space-y-1">
            <li>
              <router-link 
                to="/dashboard" 
                :class="[
                  $route.path === '/dashboard' ? 'bg-gray-50 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary',
                  'group flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold'
                ]"
              >
                <HomeIcon :class="[
                  $route.path === '/dashboard' ? 'text-primary' : 'text-gray-400 group-hover:text-primary',
                  'size-6 shrink-0'
                ]" aria-hidden="true" />
                Dashboard
              </router-link>
            </li>
          </ul>
        </li>

        <!-- Il mio Account -->
        <li>
          <div class="text-xs/6 font-futura-bold text-gray-400 uppercase tracking-wider">Il mio Account</div>
          <ul role="list" class="-mx-2 mt-2 space-y-1">
            <li>
              <router-link 
                to="/account/profile" 
                :class="[
                  $route.path.startsWith('/account') ? 'bg-gray-50 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary',
                  'group flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold'
                ]"
              >
                <UserIcon :class="[
                  $route.path.startsWith('/account') ? 'text-primary' : 'text-gray-400 group-hover:text-primary',
                  'size-6 shrink-0'
                ]" aria-hidden="true" />
                Profilo Personale
              </router-link>
            </li>
            <li>
              <router-link 
                to="/account/addresses" 
                :class="[
                  $route.path === '/account/addresses' ? 'bg-gray-50 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary',
                  'group flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold'
                ]"
              >
                <MapPinIcon :class="[
                  $route.path === '/account/addresses' ? 'text-primary' : 'text-gray-400 group-hover:text-primary',
                  'size-6 shrink-0'
                ]" aria-hidden="true" />
                Indirizzi
              </router-link>
            </li>
            <li>
              <router-link 
                to="/account/security" 
                :class="[
                  $route.path === '/account/security' ? 'bg-gray-50 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary',
                  'group flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold'
                ]"
              >
                <ShieldCheckIcon :class="[
                  $route.path === '/account/security' ? 'text-primary' : 'text-gray-400 group-hover:text-primary',
                  'size-6 shrink-0'
                ]" aria-hidden="true" />
                Sicurezza
              </router-link>
            </li>
          </ul>
        </li>

        <!-- Acquisti -->
        <li>
          <div class="text-xs/6 font-futura-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
            Acquisti
            <span v-if="!kycCompleted" class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">
              KYC Richiesto
            </span>
            <span v-else class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
              Attivo
            </span>
          </div>
          <ul role="list" class="-mx-2 mt-2 space-y-1">
            <li>
              <router-link 
                to="/purchases/orders" 
                :class="[
                  $route.path.startsWith('/purchases') ? 'bg-gray-50 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary',
                  'group flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold'
                ]"
              >
                <ShoppingBagIcon :class="[
                  $route.path.startsWith('/purchases') ? 'text-primary' : 'text-gray-400 group-hover:text-primary',
                  'size-6 shrink-0'
                ]" aria-hidden="true" />
                I miei Ordini
              </router-link>
            </li>
            <li>
              <router-link 
                to="/purchases/wishlist" 
                :class="[
                  $route.path === '/purchases/wishlist' ? 'bg-gray-50 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary',
                  'group flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold'
                ]"
              >
                <HeartIcon :class="[
                  $route.path === '/purchases/wishlist' ? 'text-primary' : 'text-gray-400 group-hover:text-primary',
                  'size-6 shrink-0'
                ]" aria-hidden="true" />
                Wishlist
              </router-link>
            </li>
          </ul>
        </li>

        <!-- Vendite -->
        <li>
          <div class="text-xs/6 font-futura-bold text-gray-400 uppercase tracking-wider flex items-center gap-2">
            Vendite
            <span v-if="user?.kyc_status === 'pending'" class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-medium text-yellow-800">
              In Verifica
            </span>
            <span v-else-if="user?.kyc_status === 'approved'" class="inline-flex items-center rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">
              Verificato
            </span>
            <span v-else-if="user?.kyc_status === 'rejected'" class="inline-flex items-center rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-800">
              Verifica Rifiutata
            </span>
            <span v-else class="inline-flex items-center rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800">
              Non Verificato
            </span>
          </div>
          <ul role="list" class="-mx-2 mt-2 space-y-1">
            <li>
              <router-link 
                to="/sales/create" 
                :class="[
                  $route.path === '/sales/create' ? 'bg-gray-50 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary',
                  'group flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold'
                ]"
              >
                <PlusIcon :class="[
                  $route.path === '/sales/create' ? 'text-primary' : 'text-gray-400 group-hover:text-primary',
                  'size-6 shrink-0'
                ]" aria-hidden="true" />
                Crea Inserzione
              </router-link>
            </li>
            <li>
              <router-link 
                to="/sales/cards" 
                :class="[
                  $route.path.startsWith('/sales') && $route.path !== '/sales/create' ? 'bg-gray-50 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary',
                  'group flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold'
                ]"
              >
                <FolderIcon :class="[
                  $route.path.startsWith('/sales') && $route.path !== '/sales/create' ? 'text-primary' : 'text-gray-400 group-hover:text-primary',
                  'size-6 shrink-0'
                ]" aria-hidden="true" />
                Le mie Carte
              </router-link>
            </li>
            <li>
              <router-link 
                to="/sales/orders" 
                :class="[
                  $route.path === '/sales/orders' ? 'bg-gray-50 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary',
                  'group flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold'
                ]"
              >
                <DocumentDuplicateIcon :class="[
                  $route.path === '/sales/orders' ? 'text-primary' : 'text-gray-400 group-hover:text-primary',
                  'size-6 shrink-0'
                ]" aria-hidden="true" />
                Ordini da Preparare
              </router-link>
            </li>
            <li>
              <router-link 
                to="/sales/statistics" 
                :class="[
                  $route.path === '/sales/statistics' ? 'bg-gray-50 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary',
                  'group flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold'
                ]"
              >
                <ChartBarIcon :class="[
                  $route.path === '/sales/statistics' ? 'text-primary' : 'text-gray-400 group-hover:text-primary',
                  'size-6 shrink-0'
                ]" aria-hidden="true" />
                Statistiche Vendite
              </router-link>
            </li>
            <li>
              <router-link 
                to="/sales/feedback" 
                :class="[
                  $route.path === '/sales/feedback' ? 'bg-gray-50 text-primary' : 'text-gray-700 hover:bg-gray-50 hover:text-primary',
                  'group flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold'
                ]"
              >
                <StarIcon :class="[
                  $route.path === '/sales/feedback' ? 'text-primary' : 'text-gray-400 group-hover:text-primary',
                  'size-6 shrink-0'
                ]" aria-hidden="true" />
                Feedback Ricevuti
              </router-link>
            </li>
          </ul>
        </li>


        <!-- Logout -->
        <li class="mt-auto">
          <button 
            @click="handleLogout" 
            class="group -mx-2 flex gap-x-3 rounded-md p-2 text-sm/6 font-gill-sans-semibold text-gray-700 hover:bg-gray-50 hover:text-primary w-full text-left"
          >
            <ArrowRightOnRectangleIcon class="size-6 shrink-0 text-gray-400 group-hover:text-primary" aria-hidden="true" />
            Logout
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import {
  HomeIcon,
  UserIcon,
  MapPinIcon,
  CreditCardIcon,
  ShieldCheckIcon,
  ShoppingBagIcon,
  HeartIcon,
  ClockIcon,
  FolderIcon,
  DocumentDuplicateIcon,
  ChartBarIcon,
  StarIcon,
  BellIcon,
  LockClosedIcon,
  GlobeAltIcon,
  ArrowRightOnRectangleIcon,
  PlusIcon
} from '@heroicons/vue/24/outline'

const router = useRouter()
const authStore = useAuthStore()

const user = computed(() => authStore.user)
const kycCompleted = computed(() => user.value?.kyc_status === 'approved')

const handleLogout = () => {
  authStore.logout()
  router.push('/')
}
</script>
