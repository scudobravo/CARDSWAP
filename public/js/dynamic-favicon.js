/**
 * Dynamic Favicon Manager
 * Cambia automaticamente il favicon in base al dark mode del browser
 */

class DynamicFavicon {
    constructor() {
        this.lightFavicon16 = '/favicon-light-16x16.png';
        this.lightFavicon32 = '/favicon-light-32x32.png';
        this.darkFavicon16 = '/favicon-dark-16x16.png';
        this.darkFavicon32 = '/favicon-dark-32x32.png';
        
        this.favicon16 = null;
        this.favicon32 = null;
        
        this.init();
    }
    
    init() {
        // Crea gli elementi link per i favicon se non esistono
        this.createFaviconElements();
        
        // Imposta il favicon iniziale
        this.updateFavicon();
        
        // Ascolta i cambiamenti del dark mode
        this.watchDarkMode();
    }
    
    createFaviconElements() {
        // Cerca se esistono già gli elementi favicon
        this.favicon16 = document.querySelector('link[rel="icon"][sizes="16x16"]');
        this.favicon32 = document.querySelector('link[rel="icon"][sizes="32x32"]');
        
        // Se non esistono, li crea
        if (!this.favicon16) {
            this.favicon16 = document.createElement('link');
            this.favicon16.rel = 'icon';
            this.favicon16.sizes = '16x16';
            this.favicon16.type = 'image/png';
            document.head.appendChild(this.favicon16);
        }
        
        if (!this.favicon32) {
            this.favicon32 = document.createElement('link');
            this.favicon32.rel = 'icon';
            this.favicon32.sizes = '32x32';
            this.favicon32.type = 'image/png';
            document.head.appendChild(this.favicon32);
        }
    }
    
    isDarkMode() {
        // Controlla se il browser è in dark mode
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }
    
    updateFavicon() {
        const isDark = this.isDarkMode();
        
        if (isDark) {
            this.favicon16.href = this.darkFavicon16;
            this.favicon32.href = this.darkFavicon32;
        } else {
            this.favicon16.href = this.lightFavicon16;
            this.favicon32.href = this.lightFavicon32;
        }
    }
    
    watchDarkMode() {
        // Ascolta i cambiamenti del dark mode
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // Funzione callback per i cambiamenti
            const handleChange = (e) => {
                this.updateFavicon();
            };
            
            // Aggiungi il listener
            if (mediaQuery.addEventListener) {
                mediaQuery.addEventListener('change', handleChange);
            } else {
                // Fallback per browser più vecchi
                mediaQuery.addListener(handleChange);
            }
        }
    }
}

// Inizializza il favicon dinamico quando il DOM è pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        new DynamicFavicon();
    });
} else {
    new DynamicFavicon();
}
