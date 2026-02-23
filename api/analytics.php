<?php
/**
 * EchoDoc - Analytics API Endpoint
 * Tracks user events from JavaScript
 */

// Buffer all output to prevent stray PHP errors/warnings from corrupting JSON response
ob_start();

header('Content-Type: application/json');

// Custom error handler to prevent HTML error output
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Analytics API Error [$severity]: $message in $file on line $line");
    return true;
});

try {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/analytics.php';
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error']);
    error_log('Analytics config error: ' . $e->getMessage());
    exit;
}

// Discard any buffered output from includes
ob_end_clean();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['event'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing event type']);
    exit;
}

$event = $input['event'];
$data = $input['data'] ?? [];

// Get user ID if logged in
$userId = isLoggedIn() ? getCurrentUserId() : null;

try {
    switch ($event) {
        case 'page_view':
            $page = $data['page'] ?? 'unknown';
            trackEvent('page_view', ['page' => $page], $userId);
            break;
            
        case 'upload':
            $fileType = $data['file_type'] ?? 'unknown';
            $fileSize = $data['file_size'] ?? 0;
            trackEvent('upload', [
                'file_type' => $fileType,
                'file_size' => $fileSize
            ], $userId);
            break;
            
        case 'audio_play':
            $document = $data['document'] ?? null;
            $voice = $data['voice'] ?? 'default';
            $duration = $data['duration'] ?? 0;
            trackEvent('play_audio', [
                'document' => $document,
                'voice' => $voice,
                'duration' => $duration
            ], $userId);
            break;
            
        case 'download':
            $document = $data['document'] ?? null;
            $format = $data['format'] ?? 'mp3';
            trackEvent('download_mp3', [
                'document' => $document,
                'format' => $format
            ], $userId);
            break;
            
        case 'tts':
            $textLength = $data['text_length'] ?? 0;
            $voice = $data['voice'] ?? 'default';
            trackEvent('tts_generate', [
                'text_length' => $textLength,
                'voice' => $voice
            ], $userId);
            break;
            
        case 'translate':
            $sourceLang = $data['source_lang'] ?? 'unknown';
            $targetLang = $data['target_lang'] ?? 'unknown';
            trackEvent('translate', [
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang
            ], $userId);
            break;
            
        default:
            // Generic event tracking
            trackEvent($event, $data, $userId);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to track event']);
}
