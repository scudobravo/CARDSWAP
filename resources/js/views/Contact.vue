<template>
  <div class="bg-gray-light min-h-screen">
    <!-- Header -->
    <Header />
    
    <!-- Contact Form Section -->
    <div class="bg-white px-6 py-24 sm:py-32 lg:px-8">
      <div class="mx-auto max-w-2xl text-center">
        <h2 class="text-4xl font-futura-bold tracking-tight text-primary sm:text-5xl">Contattaci</h2>
        <p class="mt-2 text-lg text-gray-600 font-gill-sans">Hai domande? Siamo qui per aiutarti. Inviaci un messaggio e ti risponderemo il prima possibile.</p>
      </div>
      
      <form @submit.prevent="submitForm" class="mx-auto mt-16 max-w-xl sm:mt-20">
        <div class="grid grid-cols-1 gap-x-8 gap-y-6 sm:grid-cols-2">
          <!-- Nome -->
          <div>
            <label for="first-name" class="block text-sm font-gill-sans-semibold text-primary">Nome</label>
            <div class="mt-2.5">
              <input 
                type="text" 
                name="first-name" 
                id="first-name" 
                v-model="form.firstName"
                autocomplete="given-name" 
                required
                class="block w-full rounded-lg bg-white px-3.5 py-2 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent font-gill-sans" 
              />
            </div>
          </div>
          
          <!-- Cognome -->
          <div>
            <label for="last-name" class="block text-sm font-gill-sans-semibold text-primary">Cognome</label>
            <div class="mt-2.5">
              <input 
                type="text" 
                name="last-name" 
                id="last-name" 
                v-model="form.lastName"
                autocomplete="family-name" 
                required
                class="block w-full rounded-lg bg-white px-3.5 py-2 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent font-gill-sans" 
              />
            </div>
          </div>
          
          <!-- Email -->
          <div class="sm:col-span-2">
            <label for="email" class="block text-sm font-gill-sans-semibold text-primary">Email</label>
            <div class="mt-2.5">
              <input 
                type="email" 
                name="email" 
                id="email" 
                v-model="form.email"
                autocomplete="email" 
                required
                class="block w-full rounded-lg bg-white px-3.5 py-2 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent font-gill-sans" 
              />
            </div>
          </div>
          
          <!-- Telefono -->
          <div class="sm:col-span-2">
            <label for="phone-number" class="block text-sm font-gill-sans-semibold text-primary">Numero di telefono</label>
            <div class="mt-2.5">
              <div class="flex rounded-lg bg-white border border-gray-300 focus-within:ring-2 focus-within:ring-secondary focus-within:border-transparent">
                <div class="grid shrink-0 grid-cols-1 focus-within:relative">
                  <select 
                    id="country" 
                    name="country" 
                    v-model="form.country"
                    autocomplete="country" 
                    aria-label="Paese" 
                    class="col-start-1 row-start-1 w-full appearance-none rounded-l-lg py-2 pr-7 pl-3.5 text-base text-gray-500 placeholder:text-gray-400 focus:outline-none sm:text-sm font-gill-sans"
                  >
                    <option value="IT">IT</option>
                    <option value="US">US</option>
                    <option value="CA">CA</option>
                    <option value="EU">EU</option>
                  </select>
                  <svg class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end text-gray-500 sm:size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                  </svg>
                </div>
                <input 
                  type="tel" 
                  name="phone-number" 
                  id="phone-number" 
                  v-model="form.phone"
                  class="block min-w-0 grow py-1.5 pr-3 pl-1 text-base text-gray-900 placeholder:text-gray-400 focus:outline-none sm:text-sm font-gill-sans rounded-r-lg" 
                  placeholder="123-456-7890" 
                />
              </div>
            </div>
          </div>
          
          <!-- Oggetto -->
          <div class="sm:col-span-2">
            <label for="subject" class="block text-sm font-gill-sans-semibold text-primary">Oggetto</label>
            <div class="mt-2.5">
              <select 
                id="subject" 
                name="subject" 
                v-model="form.subject"
                required
                class="block w-full rounded-lg bg-white px-3.5 py-2 text-base text-gray-900 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent font-gill-sans"
              >
                <option value="">Seleziona un argomento</option>
                <option value="support">Supporto tecnico</option>
                <option value="sales">Vendite e acquisti</option>
                <option value="account">Account e profilo</option>
                <option value="shipping">Spedizioni</option>
                <option value="payment">Pagamenti</option>
                <option value="other">Altro</option>
              </select>
            </div>
          </div>
          
          <!-- Messaggio -->
          <div class="sm:col-span-2">
            <label for="message" class="block text-sm font-gill-sans-semibold text-primary">Messaggio</label>
            <div class="mt-2.5">
              <textarea 
                name="message" 
                id="message" 
                rows="4" 
                v-model="form.message"
                required
                class="block w-full rounded-lg bg-white px-3.5 py-2 text-base text-gray-900 border border-gray-300 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-secondary focus:border-transparent font-gill-sans" 
                placeholder="Descrivi il tuo problema o la tua domanda..."
              />
            </div>
          </div>
          
          <!-- Privacy Policy -->
          <div class="flex gap-x-4 sm:col-span-2">
            <div class="flex h-6 items-center">
              <div class="group relative inline-flex w-8 shrink-0 rounded-full bg-gray-200 p-px transition-colors duration-200 ease-in-out" :class="{ 'bg-secondary': form.agreeToPrivacy }">
                <span 
                  class="size-4 rounded-full bg-white shadow-xs ring-1 ring-gray-900/5 transition-transform duration-200 ease-in-out" 
                  :class="{ 'translate-x-3.5': form.agreeToPrivacy }"
                />
                <input 
                  type="checkbox" 
                  v-model="form.agreeToPrivacy"
                  class="absolute inset-0 appearance-none focus:outline-hidden" 
                  aria-label="Accetta le politiche" 
                  id="agree-to-policies" 
                  name="agree-to-policies" 
                  required
                />
              </div>
            </div>
            <label class="text-sm text-gray-600 font-gill-sans" for="agree-to-policies">
              Accettando questo, accetti la nostra
              {{ ' ' }}
              <router-link to="/privacy-policy" class="font-gill-sans-semibold whitespace-nowrap text-secondary hover:underline">privacy policy</router-link>.
            </label>
          </div>
        </div>
        
        <!-- Submit Button -->
        <div class="mt-10">
          <button 
            type="submit" 
            :disabled="isSubmitting"
            class="block w-full rounded-lg bg-secondary text-primary px-3.5 py-2.5 text-center text-sm font-futura-bold shadow-sm hover:bg-opacity-90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-secondary disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            <span v-if="!isSubmitting">Invia messaggio</span>
            <span v-else class="flex items-center justify-center">
              <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Invio in corso...
            </span>
          </button>
        </div>
      </form>
    </div>
    
    <!-- Footer -->
    <Footer />
  </div>
</template>

<script setup>
import { ref } from 'vue'
import Header from '../components/Header.vue'
import Footer from '../components/Footer.vue'

// Form data
const form = ref({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  country: 'IT',
  subject: '',
  message: '',
  agreeToPrivacy: false
})

const isSubmitting = ref(false)

// Submit form
const submitForm = async () => {
  if (!form.value.agreeToPrivacy) {
    alert('Devi accettare la privacy policy per continuare')
    return
  }

  isSubmitting.value = true

  try {
    const response = await fetch('/api/contact', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: JSON.stringify(form.value)
    })

    if (response.ok) {
      alert('Messaggio inviato con successo! Ti risponderemo presto.')
      // Reset form
      form.value = {
        firstName: '',
        lastName: '',
        email: '',
        phone: '',
        country: 'IT',
        subject: '',
        message: '',
        agreeToPrivacy: false
      }
    } else {
      throw new Error('Errore durante l\'invio del messaggio')
    }
  } catch (error) {
    console.error('Errore:', error)
    alert('Si è verificato un errore durante l\'invio del messaggio. Riprova più tardi.')
  } finally {
    isSubmitting.value = false
  }
}
</script>
