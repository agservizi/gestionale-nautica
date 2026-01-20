<?php
/**
 * Header comune per tutte le pagine
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/functions.php';

// Security Headers
$cspNonce = base64_encode(random_bytes(16));
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net 'nonce-$cspNonce'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net; connect-src 'self'");

// Richiedi login per tutte le pagine
requireLogin();

// Scheduler interno (no cron)
if (function_exists('runScheduledJobs')) {
    runScheduledJobs();
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$notifications = function_exists('getNotificationSummary') ? getNotificationSummary() : null;

$defaultTheme = [
    'color_primary' => '#1e3a5f',
    'color_secondary' => '#4a90d9',
    'color_accent' => '#f4c430',
    'color_success' => '#28a745',
    'color_danger' => '#dc3545',
    'color_warning' => '#ffc107',
    'color_info' => '#17a2b8',
    'color_light' => '#f8f9fa',
    'color_dark' => '#343a40',
    'color_white' => '#ffffff',
    'color_gray' => '#6c757d',
];

$theme = [];
foreach ($defaultTheme as $key => $value) {
    $stored = getSetting('theme_' . $key, $value);
    $theme[$key] = $stored ?: $value;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - <?php echo ucfirst($current_page); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/custom.css">
    <link rel="icon" type="image/svg+xml" href="/assets/icons/favicon.svg">
    <style>
        :root {
            --color-primary: <?php echo htmlspecialchars($theme['color_primary']); ?>;
            --color-secondary: <?php echo htmlspecialchars($theme['color_secondary']); ?>;
            --color-accent: <?php echo htmlspecialchars($theme['color_accent']); ?>;
            --color-success: <?php echo htmlspecialchars($theme['color_success']); ?>;
            --color-danger: <?php echo htmlspecialchars($theme['color_danger']); ?>;
            --color-warning: <?php echo htmlspecialchars($theme['color_warning']); ?>;
            --color-info: <?php echo htmlspecialchars($theme['color_info']); ?>;
            --color-light: <?php echo htmlspecialchars($theme['color_light']); ?>;
            --color-dark: <?php echo htmlspecialchars($theme['color_dark']); ?>;
            --color-white: <?php echo htmlspecialchars($theme['color_white']); ?>;
            --color-gray: <?php echo htmlspecialchars($theme['color_gray']); ?>;
        }
    </style>
    <script>
        (function() {
            try {
                var collapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                if (collapsed) {
                    document.documentElement.classList.add('sidebar-collapsed');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body>
    <div class="wrapper">
        <div id="sidebarOverlay" class="sidebar-overlay"></div>
