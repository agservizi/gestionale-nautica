<?php
require_once __DIR__ . '/../config/config.php';

try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS app_settings (\n        setting_key VARCHAR(100) PRIMARY KEY,\n        setting_value TEXT NOT NULL,\n        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP\n    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "OK";
} catch (Throwable $e) {
    echo $e->getMessage();
    exit(1);
}
