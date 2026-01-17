<?php
/**
 * EchoDoc - Authentication Helper
 * 
 * Session management and authentication functions
 */

// Configure session settings before starting
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters for better mobile compatibility
    $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $cookieParams = [
        'lifetime' => 0,
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
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
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
 * Set user session after login
 * @param array $user User data from database
 */
function setUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user'] = $user;
    $_SESSION['logged_in_at'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
}

/**
 * Clear user session (logout)
 */
function clearUserSession() {
    // Clear user-specific session data
    unset($_SESSION['user_id']);
    unset($_SESSION['user']);
    unset($_SESSION['logged_in_at']);
    
    // Optionally destroy entire session
    // session_destroy();
}

/**
 * Require authentication - redirect to login if not logged in
 * @param string $redirectUrl URL to redirect to after login
 */
function requireAuth($redirectUrl = null) {
    if (!isLoggedIn()) {
        $redirect = $redirectUrl ? '?redirect=' . urlencode($redirectUrl) : '';
        header('Location: login.php' . $redirect);
        exit;
    }
}

/**
 * Redirect if already logged in
 * @param string $destination Where to redirect logged-in users
 */
function redirectIfLoggedIn($destination = 'index.php') {
    if (isLoggedIn()) {
        header('Location: ' . $destination);
        exit;
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
