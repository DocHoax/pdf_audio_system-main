<?php
/**
 * EchoDoc - Audio Ready Notification API
 * 
 * Sends email notification when MP3 conversion is complete
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email.php';
require_once __DIR__ . '/../includes/db_config.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$documentName = $input['document_name'] ?? 'Your document';
$downloadUrl = $input['download_url'] ?? null;

// Get current user info
$userId = $_SESSION['user_id'] ?? null;
$userEmail = $_SESSION['user_email'] ?? null;
$username = $_SESSION['user_name'] ?? $_SESSION['username'] ?? 'User';

if (!$userEmail) {
    // Try to get email from database
    $pdo = getDbConnection();
    if ($pdo && $userId) {
        try {
            $stmt = $pdo->prepare("SELECT email, username, full_name FROM users WHERE id = :id");
            $stmt->execute([':id' => $userId]);
            $user = $stmt->fetch();
            if ($user) {
                $userEmail = $user['email'];
                $username = $user['full_name'] ?: $user['username'];
            }
        } catch (PDOException $e) {
            error_log("Error fetching user for notification: " . $e->getMessage());
        }
    }
}

if (!$userEmail) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User email not found']);
    exit;
}

// Check if user wants email notifications
if (!userWantsEmailNotifications($userId)) {
    echo json_encode(['success' => true, 'message' => 'Notifications disabled by user']);
    exit;
}

// Send the notification
$result = sendMp3ReadyEmail($userEmail, $username, $documentName, $downloadUrl);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'Notification sent']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send notification']);
}
