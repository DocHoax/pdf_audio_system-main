<?php
/**
 * EchoDoc - Authentication Helper
 * 
 * Session management and authentication functions
 */

require_once __DIR__ . '/security_headers.php';

// Configure session settings before starting
if (session_status() === PHP_SESSION_NONE) {
    // Keep sessions persistent across browser restarts (30 days)
    $sessionLifetime = 60 * 60 * 24 * 30;

    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string)$sessionLifetime);
    ini_set('session.cookie_lifetime', (string)$sessionLifetime);

    // Set session cookie parameters for better mobile compatibility
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $cookieParams = [
        'lifetime' => $sessionLifetime,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'  // 'Lax' is more compatible with mobile browsers than 'Strict'
    ];
    
    // PHP 7.3+ supports samesite in session_set_cookie_params
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($cookieParams);
    } else {
        session_set_cookie_params(
            $cookieParams['lifetime'],
            $cookieParams['path'],
            $cookieParams['domain'],
            $cookieParams['secure'],
            $cookieParams['httponly']
        );
    }
    
    session_start();
}

/**
 * Write and close session safely.
 */
function flushSession() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
}

/**
 * Redirect while safely flushing session data first.
 * @param string $location
 */
function redirectWithSessionFlush($location) {
    flushSession();
    header('Location: ' . $location);
    exit;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        return false;
    }

    if (!isset($_SESSION['user']) || !is_array($_SESSION['user'])) {
        return false;
    }

    if (!isset($_SESSION['user']['id'])) {
        return false;
    }

    return (string)$_SESSION['user']['id'] === (string)$_SESSION['user_id'];
}

/**
 * Get current logged in user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current logged in user data
 * @return array|null
 */
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

/**
 * Check if current user is an admin
 * Admin is identified by:
 * 1. is_admin flag in database
 * 2. Email matching ADMIN_EMAIL environment variable
 * @return bool
 */
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user = getCurrentUser();
    if (!$user) {
        return false;
    }
    
    // Check is_admin flag in user data
    if (isset($user['is_admin']) && $user['is_admin']) {
        return true;
    }
    
    // Check if user email matches ADMIN_EMAIL from environment
    $adminEmail = getenv('ADMIN_EMAIL') ?: ($_ENV['ADMIN_EMAIL'] ?? null);
    if ($adminEmail && isset($user['email']) && strtolower($user['email']) === strtolower($adminEmail)) {
        return true;
    }
    
    return false;
}

/**
 * Require admin access - redirect to home if not admin
 */
function requireAdmin() {
    requireAuth(); // First ensure user is logged in
    
    if (!isAdmin()) {
        redirectWithSessionFlush('index.php');
    }
}

/**
 * Set user session after login
 * @param array $user User data from database
 */
function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = $user;
    $_SESSION['logged_in_at'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Force session data to be written immediately
    // This prevents race conditions where redirects happen before data is saved
    flushSession();
}

/**
 * Clear user session (logout)
 */
function clearUserSession() {
    // Clear all session data
    $_SESSION = [];

    // Delete the session cookie if present
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'] ?? '/',
            $params['domain'] ?? '',
            $params['secure'] ?? false,
            $params['httponly'] ?? true
        );
    }

    // Destroy server-side session data
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Require authentication - redirect to login if not logged in
 * @param string $redirectUrl URL to redirect to after login
 */
function requireAuth($redirectUrl = null) {
    if (!isLoggedIn()) {
        $redirect = $redirectUrl ? '?redirect=' . urlencode($redirectUrl) : '';
        redirectWithSessionFlush('login.php' . $redirect);
    }
}

/**
 * Redirect if already logged in
 * @param string $destination Where to redirect logged-in users
 */
function redirectIfLoggedIn($destination = 'index.php') {
    if (isLoggedIn()) {
        redirectWithSessionFlush($destination);
    }
}

/**
 * Generate CSRF token
 * @return string
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF input field HTML
 * @return string
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCsrfToken()) . '">';
}
