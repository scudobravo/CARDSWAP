// Test delle notifiche migliorate
console.log('=== Test Sistema Notifiche ===');

// Funzione per testare le notifiche
function testNotifications() {
    if (!window.$notification) {
        console.error('Sistema notifiche non disponibile');
        return;
    }

    console.log('Testando notifiche...');

    // Test notifica di successo
    setTimeout(() => {
        window.$notification.success(
            'Operazione Completata', 
            'L\'indirizzo è stato salvato con successo nel tuo account.'
        );
    }, 1000);

    // Test notifica di errore
    setTimeout(() => {
        window.$notification.error(
            'Errore di Validazione', 
            'Alcuni campi non sono stati compilati correttamente. Controlla e riprova.'
        );
    }, 2000);

    // Test notifica di warning
    setTimeout(() => {
        window.$notification.warning(
            'Attenzione', 
            'Stai per eliminare un indirizzo. Questa azione non può essere annullata.'
        );
    }, 3000);

    // Test modal di conferma
    setTimeout(() => {
        window.$notification.confirm({
            title: 'Elimina Indirizzo',
            message: 'Sei sicuro di voler eliminare l\'indirizzo "Casa"? Questa azione non può essere annullata.',
            type: 'danger',
            confirmText: 'Elimina',
            cancelText: 'Annulla',
            onConfirm: () => {
                console.log('Confermato!');
                window.$notification.success('Eliminato', 'Indirizzo eliminato con successo');
            }
        });
    }, 4000);
}

// Esegui il test quando la pagina è pronta
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', testNotifications);
} else {
    testNotifications();
}

// Esporta la funzione per uso manuale
window.testNotifications = testNotifications;
