<?php
/**
 * Clear Session Script
 * Clears the current user's extracted text and redirects to the main page
 */

require_once 'includes/auth.php';

// Only allow logged in users to clear session
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Get user-specific session keys
$userId = getCurrentUserId();
$textKey = 'user_' . $userId . '_extracted_text';
$fileKey = 'user_' . $userId . '_file_name';

// Clear user-specific extracted text from session
if (isset($_SESSION[$textKey])) {
    unset($_SESSION[$textKey]);
}

if (isset($_SESSION[$fileKey])) {
    unset($_SESSION[$fileKey]);
}

// Redirect to main page
header('Location: index.php');
exit;
