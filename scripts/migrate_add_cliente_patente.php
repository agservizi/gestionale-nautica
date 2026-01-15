<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDB();
    $db->exec("ALTER TABLE clienti ADD COLUMN tipo_pratica ENUM('Patente entro 12 miglia', 'Patente oltre 12 miglia', 'Patente D1', 'Rinnovo', 'Duplicato', 'Altro') NOT NULL DEFAULT 'Altro' AFTER email");
    $db->exec("ALTER TABLE clienti ADD COLUMN numero_patente VARCHAR(50) NULL AFTER tipo_pratica");
    $db->exec("ALTER TABLE clienti ADD COLUMN data_conseguimento_patente DATE NULL AFTER numero_patente");
    $db->exec("ALTER TABLE clienti ADD COLUMN data_scadenza_patente DATE NULL AFTER data_conseguimento_patente");
    echo "OK";
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}
