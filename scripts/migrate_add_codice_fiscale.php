<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDB();
    $db->exec("ALTER TABLE clienti ADD COLUMN codice_fiscale VARCHAR(16) NULL AFTER email");
    echo "OK";
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}
