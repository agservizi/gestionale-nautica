<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();
requireAdmin();

$sql = buildSqlBackup();

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="backup_' . date('Ymd_His') . '.sql"');

echo $sql;
