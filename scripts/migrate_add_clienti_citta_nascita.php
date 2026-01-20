<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDB();
    $db->exec("ALTER TABLE clienti ADD COLUMN citta_nascita VARCHAR(100) NULL AFTER data_nascita");
    echo "OK";
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}
