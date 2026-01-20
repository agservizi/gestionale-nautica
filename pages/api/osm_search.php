<?php
require_once __DIR__ . '/../../config/config.php';

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');
$limit = (int)($_GET['limit'] ?? 6);
$limit = max(1, min(10, $limit));

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rateWindow = 300;
$rateMax = 60;

if ($q === '') {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$cacheDir = __DIR__ . '/../../uploads/cache';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0755, true);
}

$rateFile = $cacheDir . '/osm_rate_' . sha1($ip) . '.json';
$rateData = null;
if (file_exists($rateFile)) {
    $raw = file_get_contents($rateFile);
    $rateData = json_decode($raw, true);
}
if (!is_array($rateData)) {
    $rateData = ['start' => time(), 'count' => 0];
}
if ((time() - (int)$rateData['start']) > $rateWindow) {
    $rateData = ['start' => time(), 'count' => 0];
}
$rateData['count']++;
file_put_contents($rateFile, json_encode($rateData));
if ($rateData['count'] > $rateMax) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded'], JSON_UNESCAPED_UNICODE);
    exit;
}

$cacheKey = sha1($q . '|' . $limit);
$cacheFile = $cacheDir . '/osm_' . $cacheKey . '.json';
$cacheTtl = 60 * 60 * 24;

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
    readfile($cacheFile);
    exit;
}

$url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&addressdetails=1&limit=' . $limit . '&countrycodes=it&q=' . urlencode($q);

try {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'header' => "User-Agent: NautikaPro/1.0\r\n"
        ]
    ]);
    $data = file_get_contents($url, false, $context);
    if ($data === false) {
        throw new Exception('Nominatim request failed');
    }
    file_put_contents($cacheFile, $data);
    echo $data;
} catch (Throwable $e) {
    if (file_exists($cacheFile)) {
        readfile($cacheFile);
    } else {
        echo json_encode([], JSON_UNESCAPED_UNICODE);
    }
}
