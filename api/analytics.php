<?php
/**
 * EchoDoc - Analytics API Endpoint
 * Tracks user events from JavaScript
 */

require_once '../includes/auth.php';
require_once '../includes/analytics.php';

header('Content-Type: application/json');

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
$userId = isLoggedIn() ? getCurrentUser()['id'] : null;

try {
    switch ($event) {
        case 'page_view':
            $page = $data['page'] ?? 'unknown';
            trackPageView($page, $userId);
            break;
            
        case 'upload':
            $fileType = $data['file_type'] ?? 'unknown';
            $fileSize = $data['file_size'] ?? 0;
            trackUpload($userId, $fileType, $fileSize);
            break;
            
        case 'audio_play':
            $document = $data['document'] ?? null;
            $duration = $data['duration'] ?? 0;
            trackAudioPlay($userId, $document, $duration);
            break;
            
        case 'download':
            $document = $data['document'] ?? null;
            $format = $data['format'] ?? 'mp3';
            trackDownload($userId, $document, $format);
            break;
            
        case 'tts':
            $textLength = $data['text_length'] ?? 0;
            $voice = $data['voice'] ?? 'default';
            trackEvent($userId, 'tts_generate', [
                'text_length' => $textLength,
                'voice' => $voice
            ]);
            break;
            
        case 'translate':
            $sourceLang = $data['source_lang'] ?? 'unknown';
            $targetLang = $data['target_lang'] ?? 'unknown';
            trackEvent($userId, 'translate', [
                'source_lang' => $sourceLang,
                'target_lang' => $targetLang
            ]);
            break;
            
        default:
            // Generic event tracking
            trackEvent($userId, $event, $data);
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to track event']);
}
