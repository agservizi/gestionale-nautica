<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDB();
    $db->exec("ALTER TABLE clienti 
        ADD COLUMN data_nascita DATE NULL AFTER codice_fiscale,
        ADD COLUMN documento_tipo VARCHAR(50) NULL AFTER occhiali,
        ADD COLUMN documento_data_emissione DATE NULL AFTER documento_tipo,
        ADD COLUMN documento_data_scadenza DATE NULL AFTER documento_data_emissione");
    echo "OK";
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}
