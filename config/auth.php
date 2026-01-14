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

// Remember me settings
define('REMEMBER_ME_COOKIE', 'nautikapro_remember');
define('REMEMBER_ME_DAYS', 30);

function setAuthSession($user) {
    session_regenerate_id(true);
    $_SESSION['user_logged'] = true;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['ruolo'] = $user['ruolo'];
    $_SESSION['login_time'] = time();
}

function setRememberMeCookie($value, $expires) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie(REMEMBER_ME_COOKIE, $value, [
        'expires' => $expires,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}

function clearRememberMeCookie() {
    setRememberMeCookie('', time() - 3600);
}

function setRememberMeToken($userId) {
    try {
        $db = getDB();
        $selector = bin2hex(random_bytes(12));
        $validator = bin2hex(random_bytes(32));
        $hash = hash('sha256', $validator);
        $expires = date('Y-m-d H:i:s', time() + (REMEMBER_ME_DAYS * 86400));

        $stmt = $db->prepare("INSERT INTO auth_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $selector, $hash, $expires]);

        setRememberMeCookie($selector . ':' . $validator, time() + (REMEMBER_ME_DAYS * 86400));
    } catch (Exception $e) {
        // Fail silently to avoid breaking login flow
        clearRememberMeCookie();
    }
}

function clearRememberMeToken($userId = null) {
    try {
        $db = getDB();
        if (!empty($_COOKIE[REMEMBER_ME_COOKIE])) {
            $parts = explode(':', $_COOKIE[REMEMBER_ME_COOKIE], 2);
            $selector = $parts[0] ?? '';
            if ($selector !== '') {
                $stmt = $db->prepare("DELETE FROM auth_tokens WHERE selector = ?");
                $stmt->execute([$selector]);
            }
        }
        if ($userId) {
            $stmt = $db->prepare("DELETE FROM auth_tokens WHERE user_id = ?");
            $stmt->execute([$userId]);
        }
    } catch (Exception $e) {
        // Ignore token cleanup failures
    }
    clearRememberMeCookie();
}

function loginFromRememberMe() {
    if (isLogged() || empty($_COOKIE[REMEMBER_ME_COOKIE])) {
        return;
    }

    $parts = explode(':', $_COOKIE[REMEMBER_ME_COOKIE], 2);
    if (count($parts) !== 2) {
        clearRememberMeCookie();
        return;
    }

    [$selector, $validator] = $parts;
    if ($selector === '' || $validator === '') {
        clearRememberMeCookie();
        return;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM auth_tokens WHERE selector = ? LIMIT 1");
        $stmt->execute([$selector]);
        $token = $stmt->fetch();
    } catch (Exception $e) {
        clearRememberMeCookie();
        return;
    }

    if (!$token || strtotime($token['expires_at']) < time()) {
        if ($token) {
            $db->prepare("DELETE FROM auth_tokens WHERE id = ?")->execute([$token['id']]);
        }
        clearRememberMeCookie();
        return;
    }

    $calc = hash('sha256', $validator);
    if (!hash_equals($token['token_hash'], $calc)) {
        $db->prepare("DELETE FROM auth_tokens WHERE id = ?")->execute([$token['id']]);
        clearRememberMeCookie();
        return;
    }

    $stmt = $db->prepare("SELECT id, username, ruolo, attivo FROM utenti WHERE id = ? LIMIT 1");
    $stmt->execute([$token['user_id']]);
    $user = $stmt->fetch();

    if (!$user || (int)$user['attivo'] !== 1) {
        $db->prepare("DELETE FROM auth_tokens WHERE id = ?")->execute([$token['id']]);
        clearRememberMeCookie();
        return;
    }

    setAuthSession($user);
    $db->prepare("DELETE FROM auth_tokens WHERE id = ?")->execute([$token['id']]);
    setRememberMeToken($user['id']);
}

loginFromRememberMe();

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
function doLogin($username, $password, $remember = false) {
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

    setAuthSession($user);
    clearLoginFailures($username, $ip);
    if ($remember) {
        setRememberMeToken($user['id']);
    }
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
    if (isset($_SESSION['user_id'])) {
        clearRememberMeToken($_SESSION['user_id']);
    } else {
        clearRememberMeToken();
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
