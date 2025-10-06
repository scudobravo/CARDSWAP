import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import LazyImage from '../../../resources/js/components/LazyImage.vue'

// Mock IntersectionObserver
const mockIntersectionObserver = vi.fn()
mockIntersectionObserver.mockReturnValue({
  observe: vi.fn(),
  disconnect: vi.fn(),
})
global.IntersectionObserver = mockIntersectionObserver

describe('LazyImage', () => {
  let wrapper

  beforeEach(() => {
    vi.clearAllMocks()
  })

  const createWrapper = (props = {}) => {
    return mount(LazyImage, {
      props: {
        src: 'https://example.com/image.jpg',
        alt: 'Test image',
        ...props
      }
    })
  }

  it('renders placeholder initially', () => {
    wrapper = createWrapper()
    
    expect(wrapper.find('.lazy-image-placeholder').exists()).toBe(true)
    expect(wrapper.find('.lazy-image-container img').exists()).toBe(false)
    expect(wrapper.find('.lazy-image-error').exists()).toBe(false)
  })

  it('shows loading icon in placeholder by default', () => {
    wrapper = createWrapper()
    
    const placeholder = wrapper.find('.lazy-image-placeholder')
    expect(placeholder.find('svg').exists()).toBe(true)
  })

  it('hides loading icon when showIcon is false', () => {
    wrapper = createWrapper({ showIcon: false })
    
    const placeholder = wrapper.find('.lazy-image-placeholder')
    expect(placeholder.find('svg').exists()).toBe(false)
  })

  it('applies custom container class', () => {
    wrapper = createWrapper({ containerClass: 'custom-container' })
    
    expect(wrapper.find('.lazy-image-container').classes()).toContain('custom-container')
  })

  it('applies custom placeholder class', () => {
    wrapper = createWrapper({ placeholderClass: 'custom-placeholder' })
    
    expect(wrapper.find('.lazy-image-placeholder').classes()).toContain('custom-placeholder')
  })

  it('applies custom image class', () => {
    wrapper = createWrapper({ imageClass: 'custom-image' })
    
    // Image is not loaded yet, so we need to trigger the load
    wrapper.vm.loaded = true
    wrapper.vm.$nextTick()
    
    expect(wrapper.find('.lazy-image-container img').classes()).toContain('custom-image')
  })

  it('applies custom error class', () => {
    wrapper = createWrapper({ errorClass: 'custom-error' })
    
    // Trigger error state
    wrapper.vm.error = true
    wrapper.vm.$nextTick()
    
    expect(wrapper.find('.lazy-image-error').classes()).toContain('custom-error')
  })

  it('shows custom error text', () => {
    wrapper = createWrapper({ errorText: 'Custom error message' })
    
    // Trigger error state
    wrapper.vm.error = true
    wrapper.vm.$nextTick()
    
    expect(wrapper.find('.lazy-image-error').text()).toContain('Custom error message')
  })

  it('sets up intersection observer on mount', () => {
    wrapper = createWrapper()
    
    expect(mockIntersectionObserver).toHaveBeenCalledWith(
      expect.any(Function),
      expect.objectContaining({
        threshold: 0.1,
        rootMargin: '50px'
      })
    )
  })

  it('uses custom threshold and rootMargin', () => {
    wrapper = createWrapper({ 
      threshold: 0.5, 
      rootMargin: '100px' 
    })
    
    expect(mockIntersectionObserver).toHaveBeenCalledWith(
      expect.any(Function),
      expect.objectContaining({
        threshold: 0.5,
        rootMargin: '100px'
      })
    )
  })

  it('loads image when intersection observer triggers', () => {
    wrapper = createWrapper()
    
    // Get the callback function passed to IntersectionObserver
    const callback = mockIntersectionObserver.mock.calls[0][0]
    
    // Simulate intersection
    callback([{ isIntersecting: true }])
    
    expect(wrapper.vm.loaded).toBe(true)
    expect(wrapper.find('.lazy-image-container img').exists()).toBe(true)
  })

  it('does not load image if already loaded', () => {
    wrapper = createWrapper()
    wrapper.vm.loaded = true
    
    const callback = mockIntersectionObserver.mock.calls[0][0]
    callback([{ isIntersecting: true }])
    
    // Should not change loaded state
    expect(wrapper.vm.loaded).toBe(true)
  })

  it('handles image load event', async () => {
    wrapper = createWrapper()
    wrapper.vm.loaded = true
    await wrapper.vm.$nextTick()
    
    const img = wrapper.find('.lazy-image-container img')
    await img.trigger('load')
    
    expect(wrapper.vm.loaded).toBe(true)
  })

  it('handles image error event', async () => {
    wrapper = createWrapper()
    wrapper.vm.loaded = true
    await wrapper.vm.$nextTick()
    
    const img = wrapper.find('.lazy-image-container img')
    await img.trigger('error')
    
    expect(wrapper.vm.error).toBe(true)
    expect(wrapper.find('.lazy-image-error').exists()).toBe(true)
  })

  it('emits load event when image loads', async () => {
    wrapper = createWrapper()
    wrapper.vm.loaded = true
    await wrapper.vm.$nextTick()
    
    const img = wrapper.find('.lazy-image-container img')
    await img.trigger('load')
    
    expect(wrapper.emitted('load')).toBeTruthy()
  })

  it('emits error event when image fails to load', async () => {
    wrapper = createWrapper()
    wrapper.vm.loaded = true
    await wrapper.vm.$nextTick()
    
    const img = wrapper.find('.lazy-image-container img')
    await img.trigger('error')
    
    expect(wrapper.emitted('error')).toBeTruthy()
  })

  it('shows error state when image fails to load', () => {
    wrapper = createWrapper()
    wrapper.vm.error = true
    wrapper.vm.$nextTick()
    
    expect(wrapper.find('.lazy-image-error').exists()).toBe(true)
    expect(wrapper.find('.lazy-image-placeholder').exists()).toBe(false)
    expect(wrapper.find('.lazy-image-container img').exists()).toBe(false)
  })

  it('shows loaded image when image loads successfully', () => {
    wrapper = createWrapper()
    wrapper.vm.loaded = true
    wrapper.vm.$nextTick()
    
    expect(wrapper.find('.lazy-image-container img').exists()).toBe(true)
    expect(wrapper.find('.lazy-image-placeholder').exists()).toBe(false)
    expect(wrapper.find('.lazy-image-error').exists()).toBe(false)
  })

  it('disconnects intersection observer on unmount', () => {
    const mockDisconnect = vi.fn()
    mockIntersectionObserver.mockReturnValue({
      observe: vi.fn(),
      disconnect: mockDisconnect
    })
    
    wrapper = createWrapper()
    wrapper.unmount()
    
    expect(mockDisconnect).toHaveBeenCalled()
  })

  it('falls back to immediate load when IntersectionObserver is not available', () => {
    // Mock IntersectionObserver as undefined
    const originalIntersectionObserver = global.IntersectionObserver
    delete global.IntersectionObserver
    
    wrapper = createWrapper()
    
    // Should immediately load the image
    expect(wrapper.vm.loaded).toBe(true)
    
    // Restore IntersectionObserver
    global.IntersectionObserver = originalIntersectionObserver
  })

  it('sets loading attribute on img element', () => {
    wrapper = createWrapper()
    wrapper.vm.loaded = true
    wrapper.vm.$nextTick()
    
    const img = wrapper.find('.lazy-image-container img')
    expect(img.attributes('loading')).toBe('lazy')
  })

  it('sets correct src and alt attributes', () => {
    wrapper = createWrapper({
      src: 'https://example.com/test.jpg',
      alt: 'Test alt text'
    })
    wrapper.vm.loaded = true
    wrapper.vm.$nextTick()
    
    const img = wrapper.find('.lazy-image-container img')
    expect(img.attributes('src')).toBe('https://example.com/test.jpg')
    expect(img.attributes('alt')).toBe('Test alt text')
  })
})
