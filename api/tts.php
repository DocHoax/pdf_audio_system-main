<?php
/**
 * YarnGPT Text-to-Speech API Endpoint
 * Handles requests to convert text to speech using YarnGPT API
 */

header('Content-Type: application/json');

// Include configuration
require_once __DIR__ . '/../config.php';

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

// Initialize cURL
$ch = curl_init($apiUrl);

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($requestData),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_CONNECTTIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
$errno = curl_errno($ch);

curl_close($ch);

// Check for cURL errors
if ($errno || $error) {
    http_response_code(500);
    echo json_encode(['error' => 'API request failed: ' . $error . ' (Code: ' . $errno . ')']);
    exit;
}

// Check HTTP response code
if ($httpCode !== 200) {
    http_response_code($httpCode);
    // Try to parse error message from response
    $errorData = json_decode($response, true);
    if ($errorData && isset($errorData['error'])) {
        echo json_encode(['error' => $errorData['error']]);
    } else {
        echo json_encode(['error' => 'API request failed with status: ' . $httpCode]);
    }
    exit;
}

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
        echo json_encode([
            'success' => true,
            'data' => $responseData
        ]);
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
