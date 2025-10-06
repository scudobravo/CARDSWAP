// Script di test per verificare l'autenticazione
console.log('=== Test Autenticazione ===');

// Test 1: Verifica token in localStorage
const token = localStorage.getItem('token');
console.log('Token in localStorage:', token ? 'Presente' : 'Assente');

// Test 2: Verifica configurazione Axios
console.log('Axios headers:', window.axios.defaults.headers.common);

// Test 3: Test chiamata API
if (token) {
    console.log('Testando chiamata API...');
    window.axios.get('/api/addresses')
        .then(response => {
            console.log('✅ API Success:', response.data);
        })
        .catch(error => {
            console.error('❌ API Error:', error.response?.status, error.response?.data);
        });
} else {
    console.log('❌ Nessun token trovato - simula login...');
    
    // Simula un token per test
    const testToken = '7|Z7noXZ7JDCZYI1yAAXqwBVgIIWokCI3ZuGZPPkEA6256bd1a';
    localStorage.setItem('token', testToken);
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${testToken}`;
    
    console.log('Token di test impostato. Ricarica la pagina per testare.');
}
