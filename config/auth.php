<?php
/**
 * NautikaPro
 * Sistema di autenticazione
 */

require_once __DIR__ . '/config.php';

// Session cookie hardening
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

// Funzione per verificare se l'utente è loggato
function isLogged() {
    return isset($_SESSION['user_logged']) && $_SESSION['user_logged'] === true;
}

function currentUser() {
    if (!isLogged()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'ruolo' => $_SESSION['ruolo'] ?? null,
    ];
}

function isAdmin() {
    return isLogged() && ($_SESSION['ruolo'] ?? '') === 'admin';
}

// Funzione per richiedere il login
function requireLogin() {
    if(!isLogged()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: /pages/dashboard.php');
        exit;
    }
}

// Funzione per effettuare il login
function doLogin($username, $password) {
    $db = getDB();

    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (isLockedOut($username, $ip)) {
        return false;
    }

    $stmt = $db->prepare("SELECT id, username, password_hash, ruolo, attivo FROM utenti WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || (int)$user['attivo'] !== 1) {
        registerLoginFailure($username, $ip);
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        registerLoginFailure($username, $ip);
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user_logged'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['ruolo'] = $user['ruolo'];
    $_SESSION['login_time'] = time();
    clearLoginFailures($username, $ip);
    if (function_exists('logAudit')) {
        logAudit('login', 'utente', $user['id']);
    }
    return true;
}

// Funzione per effettuare il logout
function doLogout() {
    if (function_exists('logAudit') && isset($_SESSION['user_id'])) {
        logAudit('logout', 'utente', $_SESSION['user_id']);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    header('Location: /login.php');
    exit;
}

// Rate limit / lockout
function getLoginAttempt($username, $ip) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM auth_attempts WHERE username = ? AND ip_address = ? LIMIT 1");
    $stmt->execute([$username, $ip]);
    return $stmt->fetch();
}

function isLockedOut($username, $ip) {
    $attempt = getLoginAttempt($username, $ip);
    if (!$attempt || empty($attempt['locked_until'])) {
        return false;
    }
    return strtotime($attempt['locked_until']) > time();
}

function registerLoginFailure($username, $ip) {
    $db = getDB();
    $attempt = getLoginAttempt($username, $ip);
    $attempts = $attempt ? ((int)$attempt['attempts'] + 1) : 1;
    $lockedUntil = null;
    if ($attempts >= 5) {
        $lockedUntil = date('Y-m-d H:i:s', time() + 15 * 60);
    }

    if ($attempt) {
        $stmt = $db->prepare("UPDATE auth_attempts SET attempts = ?, last_attempt = NOW(), locked_until = ? WHERE id = ?");
        $stmt->execute([$attempts, $lockedUntil, $attempt['id']]);
    } else {
        $stmt = $db->prepare("INSERT INTO auth_attempts (username, ip_address, attempts, last_attempt, locked_until)
                              VALUES (?, ?, ?, NOW(), ?)");
        $stmt->execute([$username, $ip, $attempts, $lockedUntil]);
    }
}

function clearLoginFailures($username, $ip) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM auth_attempts WHERE username = ? AND ip_address = ?");
    $stmt->execute([$username, $ip]);
}
