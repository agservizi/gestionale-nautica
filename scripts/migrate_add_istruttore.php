<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDB();
    $db->exec("ALTER TABLE agenda_guide ADD COLUMN istruttore VARCHAR(100) NOT NULL AFTER orario_fine");
    echo "OK";
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}
