<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$backupDir = getenv('BACKUP_DIR') ?: (__DIR__ . '/../backups');
$retentionDays = (int)(getenv('BACKUP_RETENTION_DAYS') ?: 14);
$maxFiles = (int)(getenv('BACKUP_MAX_FILES') ?: 30);

if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

$filename = 'backup_' . date('Ymd_His') . '.sql';
$path = rtrim($backupDir, '/') . '/' . $filename;

$sql = buildSqlBackup();
file_put_contents($path, $sql);

// Rotazione per giorni
$files = glob(rtrim($backupDir, '/') . '/backup_*.sql');
$now = time();
foreach ($files as $file) {
    if (filemtime($file) < ($now - ($retentionDays * 86400))) {
        unlink($file);
    }
}

// Rotazione per numero massimo
$files = glob(rtrim($backupDir, '/') . '/backup_*.sql');
rsort($files);
if (count($files) > $maxFiles) {
    $toDelete = array_slice($files, $maxFiles);
    foreach ($toDelete as $f) {
        unlink($f);
    }
}

echo "Backup creato: $path\n";
