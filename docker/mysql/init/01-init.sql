-- Inizializzazione database CARDSWAP
-- Questo script viene eseguito automaticamente all'avvio del container MySQL

USE cardswap;

-- Imposta il charset corretto
ALTER DATABASE cardswap CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Crea utente per l'applicazione (opzionale)
-- CREATE USER IF NOT EXISTS 'cardswap'@'%' IDENTIFIED BY 'cardswap';
-- GRANT ALL PRIVILEGES ON cardswap.* TO 'cardswap'@'%';
-- FLUSH PRIVILEGES;

-- Messaggio di conferma
SELECT 'Database CARDSWAP inizializzato con successo!' as message;
