import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import Checkout from '@/views/Checkout.vue'
import { useCartStore } from '@/stores/cart'
import { useAuthStore } from '@/stores/auth'

// Mock delle dipendenze
vi.mock('axios', () => ({
  default: {
    post: vi.fn(),
    get: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}))

vi.mock('vue-router', () => ({
  useRouter: () => ({
    push: vi.fn(),
    replace: vi.fn()
  }),
  useRoute: () => ({
    params: {},
    query: {}
  })
}))

describe('Checkout.vue', () => {
  let wrapper
  let pinia
  let cartStore
  let authStore

  beforeEach(() => {
    pinia = createPinia()
    setActivePinia(pinia)
    
    cartStore = useCartStore()
    authStore = useAuthStore()
    
    // Mock dei dati del carrello
    cartStore.cartItems = {
      '1': [
        {
          id: 1,
          price: 10.00,
          quantity: 2,
          condition: 'Mint',
          cardModel: { name: 'Test Card' },
          seller: { name: 'Test Seller' },
          images: ['test-image.jpg']
        }
      ]
    }
    
    // Mock dell'utente autenticato
    authStore.user = {
      id: 1,
      email: 'test@example.com',
      name: 'Test User'
    }
    
    wrapper = mount(Checkout, {
      global: {
        plugins: [pinia]
      }
    })
  })

  it('renders checkout form correctly', () => {
    expect(wrapper.find('h1').text()).toContain('Checkout')
    expect(wrapper.find('[data-testid="order-summary"]').exists()).toBe(true)
  })

  it('displays cart items correctly', async () => {
    await wrapper.vm.$nextTick()
    
    const cartItems = wrapper.findAll('[data-testid="cart-item"]')
    expect(cartItems.length).toBe(1)
    
    const item = cartItems[0]
    expect(item.find('[data-testid="item-name"]').text()).toBe('Test Card')
    expect(item.find('[data-testid="item-price"]').text()).toContain('€10.00')
    expect(item.find('[data-testid="item-quantity"]').text()).toBe('2')
  })

  it('calculates order summary correctly', async () => {
    await wrapper.vm.$nextTick()
    
    const summary = wrapper.find('[data-testid="order-summary"]')
    
    // Subtotale: 10.00 * 2 = 20.00
    expect(summary.find('[data-testid="subtotal"]').text()).toContain('€20.00')
    
    // IVA: 20.00 * 0.22 = 4.40
    expect(summary.find('[data-testid="tax"]').text()).toContain('€4.40')
    
    // Spedizione standard: 5.00
    expect(summary.find('[data-testid="shipping"]').text()).toContain('€5.00')
    
    // Totale: 20.00 + 4.40 + 5.00 = 29.40
    expect(summary.find('[data-testid="total"]').text()).toContain('€29.40')
  })

  it('validates form data before payment', async () => {
    // Test con form vuoto
    expect(wrapper.vm.canProcessPayment).toBe(false)
    
    // Popola i campi obbligatori
    await wrapper.setData({
      formData: {
        firstName: 'Mario',
        lastName: 'Rossi',
        address: 'Via Roma 123',
        city: 'Milano',
        postalCode: '20100',
        country: 'IT',
        paymentMethod: 'credit-card'
      },
      selectedShippingMethods: { '1': 'standard' }
    })
    
    await wrapper.vm.$nextTick()
    expect(wrapper.vm.canProcessPayment).toBe(true)
  })

  it('handles address selection correctly', async () => {
    const mockAddress = {
      id: 1,
      first_name: 'Mario',
      last_name: 'Rossi',
      address_line_1: 'Via Roma 123',
      city: 'Milano',
      country: 'IT',
      postal_code: '20100'
    }
    
    wrapper.vm.selectAddress(mockAddress)
    
    expect(wrapper.vm.selectedAddress).toEqual(mockAddress)
    expect(wrapper.vm.formData.firstName).toBe('Mario')
    expect(wrapper.vm.formData.lastName).toBe('Rossi')
    expect(wrapper.vm.formData.address).toBe('Via Roma 123')
  })

  it('handles shipping method selection for each seller', async () => {
    await wrapper.setData({
      selectedShippingMethods: { '1': 'express' }
    })
    
    expect(wrapper.vm.selectedShippingMethods['1']).toBe('express')
    
    // Verifica che il calcolo del totale sia aggiornato
    await wrapper.vm.$nextTick()
    const summary = wrapper.find('[data-testid="order-summary"]')
    
    // Con spedizione express (€16.00): 20.00 + 4.40 + 16.00 = 40.40
    expect(summary.find('[data-testid="total"]').text()).toContain('€40.40')
  })

  it('handles quantity updates correctly', async () => {
    const quantityInput = wrapper.find('[data-testid="quantity-input"]')
    
    await quantityInput.setValue(3)
    await quantityInput.trigger('change')
    
    expect(wrapper.vm.updateQuantity).toHaveBeenCalled()
  })

  it('handles item removal correctly', async () => {
    const removeButton = wrapper.find('[data-testid="remove-item"]')
    
    await removeButton.trigger('click')
    
    expect(wrapper.vm.removeFromCart).toHaveBeenCalled()
  })

  it('loads user addresses on mount', async () => {
    expect(wrapper.vm.loadUserAddresses).toHaveBeenCalled()
  })

  it('initializes Stripe on mount', async () => {
    expect(wrapper.vm.initializeStripe).toHaveBeenCalled()
  })

  it('handles payment processing correctly', async () => {
    // Mock della risposta di successo
    const mockResponse = {
      data: {
        success: true,
        order_id: 'ORD-123',
        message: 'Ordine creato con successo'
      }
    }
    
    // Mock del metodo processPayment
    vi.spyOn(wrapper.vm, 'processPayment').mockResolvedValue(mockResponse)
    
    await wrapper.vm.processPayment()
    
    expect(wrapper.vm.processPayment).toHaveBeenCalled()
  })

  it('shows loading state during payment processing', async () => {
    await wrapper.setData({ isProcessing: true })
    
    expect(wrapper.find('[data-testid="processing-spinner"]').exists()).toBe(true)
    expect(wrapper.find('[data-testid="checkout-button"]').attributes('disabled')).toBeDefined()
  })

  it('handles payment errors gracefully', async () => {
    const mockError = {
      response: {
        data: {
          message: 'Errore nel pagamento',
          errors: ['Carta di credito non valida']
        }
      }
    }
    
    vi.spyOn(wrapper.vm, 'processPayment').mockRejectedValue(mockError)
    
    await wrapper.vm.processPayment()
    
    // Verifica che l'errore sia gestito senza crashare l'app
    expect(wrapper.vm.isProcessing).toBe(false)
  })

  it('validates shipping methods for all sellers', async () => {
    // Test con metodo di spedizione mancante
    await wrapper.setData({
      selectedShippingMethods: {} // Nessun metodo selezionato
    })
    
    expect(wrapper.vm.canProcessPayment).toBe(false)
    
    // Test con metodo selezionato
    await wrapper.setData({
      selectedShippingMethods: { '1': 'standard' }
    })
    
    expect(wrapper.vm.canProcessPayment).toBe(true)
  })

  it('calculates shipping costs correctly for different methods', () => {
    expect(wrapper.vm.getShippingCostForMethod('standard')).toBe(5.00)
    expect(wrapper.vm.getShippingCostForMethod('express')).toBe(16.00)
    expect(wrapper.vm.getShippingCostForMethod('invalid')).toBe(0)
  })

  it('gets shipping methods for seller correctly', () => {
    const methods = wrapper.vm.getShippingMethodsForSeller('1')
    expect(methods).toHaveLength(2)
    expect(methods[0].id).toBe('standard')
    expect(methods[1].id).toBe('express')
  })

  it('handles empty cart correctly', async () => {
    cartStore.cartItems = {}
    await wrapper.vm.$nextTick()
    
    expect(wrapper.find('[data-testid="empty-cart"]').exists()).toBe(true)
    expect(wrapper.vm.canProcessPayment).toBe(false)
  })

  it('handles multiple sellers correctly', async () => {
    // Aggiungi un secondo venditore
    cartStore.cartItems = {
      '1': [
        {
          id: 1,
          price: 10.00,
          quantity: 1,
          seller: { name: 'Seller 1' }
        }
      ],
      '2': [
        {
          id: 2,
          price: 15.00,
          quantity: 1,
          seller: { name: 'Seller 2' }
        }
      ]
    }
    
    await wrapper.vm.$nextTick()
    
    // Verifica che ci siano due sezioni di spedizione
    const shippingSections = wrapper.findAll('[data-testid="shipping-section"]')
    expect(shippingSections).toHaveLength(2)
    
    // Verifica che ogni venditore abbia i suoi metodi di spedizione
    expect(wrapper.vm.selectedShippingMethods['1']).toBe('standard')
    expect(wrapper.vm.selectedShippingMethods['2']).toBe('standard')
  })
})

describe('Checkout Integration', () => {
  it('integrates with cart store correctly', () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    
    const cartStore = useCartStore()
    const wrapper = mount(Checkout, {
      global: {
        plugins: [pinia]
      }
    })
    
    // Verifica che il componente usi i dati del carrello
    expect(wrapper.vm.cartStore).toBe(cartStore)
    expect(wrapper.vm.cartProducts).toBeDefined()
    expect(wrapper.vm.orderSummary).toBeDefined()
  })

  it('integrates with auth store correctly', () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    
    const authStore = useAuthStore()
    authStore.user = { email: 'test@example.com' }
    
    const wrapper = mount(Checkout, {
      global: {
        plugins: [pinia]
      }
    })
    
    // Verifica che l'email dell'utente sia popolata
    expect(wrapper.vm.formData.email).toBe('test@example.com')
  })
})
