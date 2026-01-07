<?php
/**
 * EchoDoc - Google OAuth Callback Handler
 */

require_once 'includes/auth.php';
require_once 'includes/google_oauth.php';
require_once 'includes/User.php';
require_once 'includes/db_config.php';

// Check for error from Google
if (isset($_GET['error'])) {
    header('Location: login.php?error=google_denied');
    exit;
}

// Check for authorization code
if (!isset($_GET['code'])) {
    header('Location: login.php?error=google_failed');
    exit;
}

$code = $_GET['code'];

// Exchange code for access token
$tokenData = getGoogleAccessToken($code);

if (!$tokenData || !isset($tokenData['access_token'])) {
    header('Location: login.php?error=google_token_failed');
    exit;
}

// Get user info from Google
$googleUser = getGoogleUserInfo($tokenData['access_token']);

if (!$googleUser || !isset($googleUser['email'])) {
    header('Location: login.php?error=google_user_failed');
    exit;
}

// Extract user info
$email = $googleUser['email'];
$fullName = $googleUser['name'] ?? '';
$googleId = $googleUser['id'];
$avatar = $googleUser['picture'] ?? null;

// Check if user exists with this email
$pdo = getDbConnection();

if (!$pdo) {
    header('Location: login.php?error=database_error');
    exit;
}

try {
    // Check if user exists by email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute([':email' => strtolower($email)]);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        // User exists - update google_id if not set and log them in
        if (empty($existingUser['google_id'])) {
            $updateStmt = $pdo->prepare("UPDATE users SET google_id = :google_id, avatar = :avatar WHERE id = :id");
            $updateStmt->execute([
                ':google_id' => $googleId,
                ':avatar' => $avatar,
                ':id' => $existingUser['id']
            ]);
        }
        
        // Update last login
        $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")->execute([':id' => $existingUser['id']]);
        
        // Set session
        $userData = [
            'id' => $existingUser['id'],
            'username' => $existingUser['username'],
            'email' => $existingUser['email'],
            'full_name' => $existingUser['full_name'] ?: $fullName,
            'avatar' => $avatar ?: $existingUser['avatar']
        ];
        
        setUserSession($userData);
        header('Location: index.php');
        exit;
        
    } else {
        // New user - create account
        // Generate unique username from email
        $baseUsername = strtolower(explode('@', $email)[0]);
        $username = $baseUsername;
        $counter = 1;
        
        // Ensure username is unique
        while (true) {
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
            $checkStmt->execute([':username' => $username]);
            if (!$checkStmt->fetch()) {
                break;
            }
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        // Create user (no password for Google-only accounts)
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, google_id, avatar, is_verified, created_at)
            VALUES (:username, :email, :password, :full_name, :google_id, :avatar, 1, NOW())
        ");
        
        $stmt->execute([
            ':username' => $username,
            ':email' => strtolower($email),
            ':password' => '', // No password for Google accounts
            ':full_name' => $fullName,
            ':google_id' => $googleId,
            ':avatar' => $avatar
        ]);
        
        $userId = $pdo->lastInsertId();
        
        // Create default settings
        $settingsStmt = $pdo->prepare("
            INSERT INTO user_settings (user_id, preferred_voice, default_volume, theme)
            VALUES (:user_id, 'Idera', 1.00, 'light')
        ");
        $settingsStmt->execute([':user_id' => $userId]);
        
        // Set session
        $userData = [
            'id' => $userId,
            'username' => $username,
            'email' => $email,
            'full_name' => $fullName,
            'avatar' => $avatar
        ];
        
        setUserSession($userData);
        header('Location: index.php?welcome=1');
        exit;
    }
    
} catch (PDOException $e) {
    error_log("Google OAuth DB Error: " . $e->getMessage());
    header('Location: login.php?error=database_error');
    exit;
}
