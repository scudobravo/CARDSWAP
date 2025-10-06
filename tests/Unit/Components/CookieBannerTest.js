import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import CookieBanner from '../../../resources/js/components/CookieBanner.vue'

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}
Object.defineProperty(window, 'localStorage', {
  value: localStorageMock
})

// Mock IntersectionObserver
global.IntersectionObserver = vi.fn().mockImplementation(() => ({
  observe: vi.fn(),
  disconnect: vi.fn(),
}))

describe('CookieBanner', () => {
  let wrapper

  beforeEach(() => {
    localStorageMock.getItem.mockReturnValue(null)
    localStorageMock.setItem.mockClear()
    localStorageMock.getItem.mockClear()
  })

  const createWrapper = (props = {}) => {
    return mount(CookieBanner, {
      props,
      global: {
        mocks: {
          $t: (key) => key,
          $route: { path: '/' },
          $router: {
            push: vi.fn(),
            replace: vi.fn(),
          }
        }
      }
    })
  }

  it('renders cookie banner when no consent is given', () => {
    localStorageMock.getItem.mockReturnValue(null)
    
    wrapper = createWrapper()
    
    expect(wrapper.find('.lazy-image-container').exists()).toBe(false)
    expect(wrapper.text()).toContain('cookie_banner.title')
  })

  it('does not render banner when consent is already given', () => {
    const consent = JSON.stringify({
      necessary: true,
      analytics: true,
      marketing: false,
      timestamp: new Date().toISOString()
    })
    localStorageMock.getItem.mockReturnValue(consent)
    
    wrapper = createWrapper()
    
    expect(wrapper.find('.lazy-image-container').exists()).toBe(false)
  })

  it('shows preferences modal when preferences button is clicked', async () => {
    wrapper = createWrapper()
    
    const preferencesButton = wrapper.find('button:contains("cookie_banner.preferences")')
    await preferencesButton.trigger('click')
    
    expect(wrapper.vm.showPreferences).toBe(true)
    expect(wrapper.find('.fixed.inset-0').exists()).toBe(true)
  })

  it('accepts all cookies when accept all button is clicked', async () => {
    wrapper = createWrapper()
    
    const acceptAllButton = wrapper.find('button:contains("cookie_banner.accept_all")')
    await acceptAllButton.trigger('click')
    
    expect(localStorageMock.setItem).toHaveBeenCalledWith(
      'cookie_consent',
      expect.stringContaining('"analytics":true')
    )
    expect(localStorageMock.setItem).toHaveBeenCalledWith(
      'cookie_consent',
      expect.stringContaining('"marketing":true')
    )
    expect(wrapper.vm.showBanner).toBe(false)
  })

  it('accepts only necessary cookies when necessary only button is clicked', async () => {
    wrapper = createWrapper()
    
    const necessaryButton = wrapper.find('button:contains("cookie_banner.necessary_only")')
    await necessaryButton.trigger('click')
    
    expect(localStorageMock.setItem).toHaveBeenCalledWith(
      'cookie_consent',
      expect.stringContaining('"analytics":false')
    )
    expect(localStorageMock.setItem).toHaveBeenCalledWith(
      'cookie_consent',
      expect.stringContaining('"marketing":false')
    )
    expect(wrapper.vm.showBanner).toBe(false)
  })

  it('saves preferences when save preferences button is clicked', async () => {
    wrapper = createWrapper()
    
    // Open preferences modal
    const preferencesButton = wrapper.find('button:contains("cookie_banner.preferences")')
    await preferencesButton.trigger('click')
    
    // Enable analytics
    const analyticsToggle = wrapper.find('input[type="checkbox"]')
    await analyticsToggle.setChecked(true)
    
    // Save preferences
    const saveButton = wrapper.find('button:contains("cookie_preferences.save_preferences")')
    await saveButton.trigger('click')
    
    expect(localStorageMock.setItem).toHaveBeenCalledWith(
      'cookie_consent',
      expect.stringContaining('"analytics":true')
    )
    expect(wrapper.vm.showBanner).toBe(false)
    expect(wrapper.vm.showPreferences).toBe(false)
  })

  it('closes preferences modal when cancel button is clicked', async () => {
    wrapper = createWrapper()
    
    // Open preferences modal
    const preferencesButton = wrapper.find('button:contains("cookie_banner.preferences")')
    await preferencesButton.trigger('click')
    
    expect(wrapper.vm.showPreferences).toBe(true)
    
    // Close modal
    const cancelButton = wrapper.find('button:contains("common.cancel")')
    await cancelButton.trigger('click')
    
    expect(wrapper.vm.showPreferences).toBe(false)
  })

  it('closes preferences modal when clicking outside', async () => {
    wrapper = createWrapper()
    
    // Open preferences modal
    const preferencesButton = wrapper.find('button:contains("cookie_banner.preferences")')
    await preferencesButton.trigger('click')
    
    expect(wrapper.vm.showPreferences).toBe(true)
    
    // Click outside modal
    const backdrop = wrapper.find('.fixed.inset-0')
    await backdrop.trigger('click')
    
    expect(wrapper.vm.showPreferences).toBe(false)
  })

  it('does not close preferences modal when clicking inside modal', async () => {
    wrapper = createWrapper()
    
    // Open preferences modal
    const preferencesButton = wrapper.find('button:contains("cookie_banner.preferences")')
    await preferencesButton.trigger('click')
    
    expect(wrapper.vm.showPreferences).toBe(true)
    
    // Click inside modal
    const modal = wrapper.find('.bg-white.rounded-lg')
    await modal.trigger('click')
    
    expect(wrapper.vm.showPreferences).toBe(true)
  })

  it('loads saved preferences on mount', () => {
    const consent = JSON.stringify({
      necessary: true,
      analytics: true,
      marketing: false,
      preferences: {
        analytics: true,
        marketing: false
      },
      timestamp: new Date().toISOString()
    })
    localStorageMock.getItem.mockReturnValue(consent)
    
    wrapper = createWrapper()
    
    expect(wrapper.vm.preferences.analytics).toBe(true)
    expect(wrapper.vm.preferences.marketing).toBe(false)
  })

  it('initializes tracking when consent is given', async () => {
    const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => {})
    
    wrapper = createWrapper()
    
    const acceptAllButton = wrapper.find('button:contains("cookie_banner.accept_all")')
    await acceptAllButton.trigger('click')
    
    expect(consoleSpy).toHaveBeenCalledWith('Analytics tracking initialized')
    expect(consoleSpy).toHaveBeenCalledWith('Marketing tracking initialized')
    
    consoleSpy.mockRestore()
  })

  it('only initializes necessary tracking when necessary only is selected', async () => {
    const consoleSpy = vi.spyOn(console, 'log').mockImplementation(() => {})
    
    wrapper = createWrapper()
    
    const necessaryButton = wrapper.find('button:contains("cookie_banner.necessary_only")')
    await necessaryButton.trigger('click')
    
    expect(consoleSpy).not.toHaveBeenCalledWith('Analytics tracking initialized')
    expect(consoleSpy).not.toHaveBeenCalledWith('Marketing tracking initialized')
    
    consoleSpy.mockRestore()
  })
})
