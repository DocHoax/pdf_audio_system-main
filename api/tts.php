<?php
/**
 * YarnGPT Text-to-Speech API Endpoint
 * Handles requests to convert text to speech using YarnGPT API
 */

// Buffer all output to prevent stray PHP errors/warnings from corrupting JSON response
ob_start();

// Set JSON content type early
header('Content-Type: application/json');

// Custom error handler to prevent HTML error output
set_error_handler(function($severity, $message, $file, $line) {
    error_log("TTS API Error [$severity]: $message in $file on line $line");
    return true; // Prevent default PHP error handler (which may output HTML)
});

try {
    // Include configuration
    require_once __DIR__ . '/../config.php';
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['error' => 'Server configuration error']);
    error_log('TTS config error: ' . $e->getMessage());
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
if (empty($input['text'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Text is required']);
    exit;
}

$text = $input['text'];
$voice = $input['voice'] ?? 'Idera';
$responseFormat = $input['response_format'] ?? 'mp3';

// Validate text length (max 2000 characters as per API limit)
if (strlen($text) > 2000) {
    http_response_code(400);
    echo json_encode(['error' => 'Text exceeds 2000 character limit']);
    exit;
}

// Valid voices from YarnGPT
$validVoices = [
    'Idera', 'Emma', 'Zainab', 'Osagie', 'Wura', 'Jude', 'Chinenye', 
    'Tayo', 'Regina', 'Femi', 'Adaora', 'Umar', 'Mary', 'Nonso', 'Remi', 'Adam'
];

if (!in_array($voice, $validVoices)) {
    $voice = 'Idera'; // Default to Idera if invalid voice
}

// Valid response formats
$validFormats = ['mp3', 'wav', 'opus', 'flac'];
if (!in_array($responseFormat, $validFormats)) {
    $responseFormat = 'mp3';
}

// Prepare API request
$apiUrl = 'https://yarngpt.ai/api/v1/tts';
$apiKey = YARNGPT_API_KEY;

$requestData = [
    'text' => $text,
    'voice' => $voice,
    'response_format' => $responseFormat
];

// Call upstream with small retry window for transient failures
$response = false;
$httpCode = 0;
$contentType = '';
$error = '';
$errno = 0;
$maxAttempts = 2;

for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    $ch = curl_init($apiUrl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => true
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    $errno = curl_errno($ch);

    curl_close($ch);

    $hasCurlError = ($errno || $error);
    $transientCurlError = in_array($errno, [7, 28, 52, 56], true);
    $transientHttpCode = in_array($httpCode, [429, 500, 502, 503, 504], true);

    if (!$hasCurlError && $httpCode === 200) {
        break;
    }

    if ($attempt < $maxAttempts && (($hasCurlError && $transientCurlError) || $transientHttpCode)) {
        usleep(250000);
        continue;
    }

    break;
}

// Check for cURL errors
if ($errno || $error) {
    ob_end_clean();
    http_response_code(503);
    echo json_encode(['error' => 'TTS service is temporarily unavailable. Please try again in a moment.']);
    exit;
}

// Check HTTP response code
if ($httpCode !== 200) {
    ob_end_clean();
    $statusCode = in_array($httpCode, [429, 500, 502, 503, 504], true) ? 503 : $httpCode;
    http_response_code($statusCode);
    // Try to parse error message from response
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['error'])) {
        echo json_encode(['error' => $errorData['error']]);
    } else {
        if (in_array($httpCode, [429, 500, 502, 503, 504], true)) {
            echo json_encode(['error' => 'TTS service is currently busy. Please try again shortly.']);
        } else {
            echo json_encode(['error' => 'API request failed with status: ' . $httpCode]);
        }
    }
    exit;
}

// Discard any buffered warnings/notices before sending JSON response
ob_end_clean();

// Re-set content type in case it was overwritten by an include
header('Content-Type: application/json');

// Check if response is audio
if (strpos($contentType, 'audio/') !== false) {
    // Return audio as base64 encoded data
    $base64Audio = base64_encode($response);
    $mimeType = $contentType;
    
    echo json_encode([
        'success' => true,
        'audio' => $base64Audio,
        'mime_type' => $mimeType,
        'format' => $responseFormat
    ]);
} else {
    // Response might be JSON with URL or other format
    $responseData = json_decode($response, true);
    if ($responseData) {
        // Check if the JSON contains an audio URL we can fetch
        $audioUrl = $responseData['audio_url'] ?? $responseData['url'] ?? $responseData['audio'] ?? null;
        
        if ($audioUrl && filter_var($audioUrl, FILTER_VALIDATE_URL)) {
            // Fetch the actual audio from the URL
            $audioCh = curl_init($audioUrl);
            curl_setopt_array($audioCh, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2
            ]);
            $audioResponse = curl_exec($audioCh);
            $audioContentType = curl_getinfo($audioCh, CURLINFO_CONTENT_TYPE);
            $audioHttpCode = curl_getinfo($audioCh, CURLINFO_HTTP_CODE);
            $audioError = curl_error($audioCh);
            curl_close($audioCh);
            
            if ($audioHttpCode === 200 && $audioResponse && !$audioError) {
                $base64Audio = base64_encode($audioResponse);
                $mimeType = (strpos($audioContentType, 'audio/') !== false) 
                    ? $audioContentType 
                    : 'audio/' . $responseFormat;
                echo json_encode([
                    'success' => true,
                    'audio' => $base64Audio,
                    'mime_type' => $mimeType,
                    'format' => $responseFormat
                ]);
            } else {
                http_response_code(502);
                echo json_encode(['error' => 'Failed to fetch audio from URL: ' . ($audioError ?: 'HTTP ' . $audioHttpCode)]);
            }
        } else {
            // No usable audio URL in JSON response
            http_response_code(502);
            echo json_encode(['error' => 'API returned unexpected response format (no audio data)']);
        }
    } else {
        // Assume it's binary audio data
        $base64Audio = base64_encode($response);
        echo json_encode([
            'success' => true,
            'audio' => $base64Audio,
            'mime_type' => 'audio/' . $responseFormat,
            'format' => $responseFormat
        ]);
    }
}
