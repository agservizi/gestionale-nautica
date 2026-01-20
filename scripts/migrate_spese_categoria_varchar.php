<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDB();
    $db->exec("ALTER TABLE spese MODIFY categoria VARCHAR(100) NOT NULL");
    echo "OK";
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}
