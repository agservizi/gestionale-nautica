<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDB();
    $db->exec("ALTER TABLE clienti 
        ADD COLUMN indirizzo VARCHAR(255) NULL AFTER codice_fiscale,
        ADD COLUMN citta VARCHAR(100) NULL AFTER indirizzo,
        ADD COLUMN numero_registro_iscrizione VARCHAR(50) NULL AFTER data_scadenza_patente,
        ADD COLUMN data_iscrizione DATE NULL AFTER numero_registro_iscrizione,
        ADD COLUMN occhiali TINYINT(1) NOT NULL DEFAULT 0 AFTER data_iscrizione");
    echo "OK";
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}
