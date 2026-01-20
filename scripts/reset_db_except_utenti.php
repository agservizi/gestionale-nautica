<?php
require_once __DIR__ . '/../config/config.php';

$tablesToKeep = ['utenti'];

try {
    $db = getDB();
    $db->exec('SET FOREIGN_KEY_CHECKS=0');

    $stmt = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE()");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        if (in_array($table, $tablesToKeep, true)) {
            continue;
        }
        $db->exec("TRUNCATE TABLE `{$table}`");
    }

    $db->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "OK";
} catch (Throwable $e) {
    try {
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
    } catch (Throwable $ignore) {}
    echo $e->getMessage();
    exit(1);
}
