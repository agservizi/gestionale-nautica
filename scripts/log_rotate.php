<?php
require_once __DIR__ . '/../config/config.php';

$db = getDB();
$days = (int)(getenv('AUDIT_RETENTION_DAYS') ?: 180);

$stmt = $db->prepare("DELETE FROM audit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
$stmt->execute([$days]);

echo "Audit log pulito (oltre $days giorni).\n";
