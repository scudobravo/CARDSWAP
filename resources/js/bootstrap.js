import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configurazione per CSRF token di Laravel
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
}

// Configurazione automatica del token di autenticazione
const authToken = localStorage.getItem('token');
if (authToken) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${authToken}`;
}

// Interceptor per aggiornare automaticamente il token quando cambia
window.axios.interceptors.request.use(
    config => {
        const currentToken = localStorage.getItem('token');
        if (currentToken) {
            config.headers.Authorization = `Bearer ${currentToken}`;
        }
        return config;
    },
    error => {
        return Promise.reject(error);
    }
);

// Interceptor per gestire errori di autenticazione
window.axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Token scaduto o non valido, rimuovi i dati di autenticazione
            localStorage.removeItem('token');
            delete window.axios.defaults.headers.common['Authorization'];
            
            // NON reindirizzare automaticamente al login
            // Lascia che sia l'applicazione a decidere come gestire l'errore 401
            // Solo se siamo su una pagina protetta (dashboard, account, etc)
            const protectedPaths = ['/dashboard', '/account/', '/purchases/', '/sales/', '/settings/', '/checkout', '/orders'];
            const isProtectedPath = protectedPaths.some(path => window.location.pathname.startsWith(path));
            
            if (isProtectedPath && window.location.pathname !== '/login') {
                window.location.href = '/login';
            }
        }
        return Promise.reject(error);
    }
);
