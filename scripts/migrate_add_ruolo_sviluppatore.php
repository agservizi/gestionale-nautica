<?php
require __DIR__ . '/../config/config.php';

$db = getDB();

$sql = "ALTER TABLE utenti MODIFY ruolo ENUM('admin','operatore','sviluppatore') DEFAULT 'operatore'";

try {
    $db->exec($sql);
    echo "Migrazione completata: ruolo sviluppatore aggiunto.\n";
} catch (Exception $e) {
    echo "Errore migrazione: " . $e->getMessage() . "\n";
    exit(1);
}
