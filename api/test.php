<?php
/**
 * Test YarnGPT API connectivity
 */

header('Content-Type: text/html');

require_once __DIR__ . '/../config.php';

echo "<h2>YarnGPT API Test</h2>";

// Check if cURL is enabled
echo "<h3>1. PHP Configuration</h3>";
echo "<p><strong>cURL enabled:</strong> " . (function_exists('curl_init') ? '<span style="color:green">YES</span>' : '<span style="color:red">NO</span>') . "</p>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>OpenSSL:</strong> " . (extension_loaded('openssl') ? '<span style="color:green">YES</span>' : '<span style="color:red">NO</span>') . "</p>";

// Test basic connectivity
echo "<h3>2. Basic Connectivity Test</h3>";
$testUrl = 'https://www.google.com';
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$testResult = curl_exec($ch);
$testError = curl_error($ch);
curl_close($ch);
echo "<p><strong>Can reach Google:</strong> " . ($testResult ? '<span style="color:green">YES</span>' : '<span style="color:red">NO - ' . $testError . '</span>') . "</p>";

// Test YarnGPT connectivity
echo "<h3>3. YarnGPT API Test</h3>";

$apiUrl = 'https://yarngpt.ai/api/v1/tts';
$apiKey = YARNGPT_API_KEY;

echo "<p><strong>API URL:</strong> $apiUrl</p>";
echo "<p><strong>API Key:</strong> " . substr($apiKey, 0, 15) . "..." . "</p>";

// Test data
$requestData = [
    'text' => 'Hello, this is a test.',
    'voice' => 'Idera',
    'response_format' => 'mp3'
];

echo "<p><strong>Request Data:</strong> " . json_encode($requestData) . "</p>";

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
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true
]);

echo "<p><strong>Making request...</strong> (this may take up to 30 seconds)</p>";
flush();

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$error = curl_error($ch);
$errno = curl_errno($ch);
$totalTime = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

curl_close($ch);

echo "<h3>Results:</h3>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Content Type:</strong> $contentType</p>";
echo "<p><strong>Total Time:</strong> {$totalTime}s</p>";
echo "<p><strong>cURL Error Code:</strong> $errno</p>";
echo "<p><strong>cURL Error:</strong> " . ($error ?: 'None') . "</p>";

if ($response) {
    $responseLength = strlen($response);
    echo "<p><strong>Response Length:</strong> $responseLength bytes</p>";
    
    if (strpos($contentType, 'audio/') !== false) {
        echo "<p style='color: green; font-size: 1.5em;'><strong>✓ SUCCESS!</strong> Received audio data.</p>";
        
        // Save audio for testing
        $audioFile = __DIR__ . '/test_audio.mp3';
        file_put_contents($audioFile, $response);
        echo "<p><audio controls><source src='test_audio.mp3' type='audio/mpeg'>Your browser does not support audio.</audio></p>";
    } else {
        echo "<p><strong>Response:</strong></p>";
        echo "<pre style='background:#f5f5f5;padding:10px;'>" . htmlspecialchars($response) . "</pre>";
    }
} else {
    echo "<p style='color: red; font-size: 1.2em;'><strong>✗ No response received</strong></p>";
    
    if ($errno == 28) {
        echo "<p style='color: orange;'><strong>Timeout Error:</strong> The request took too long. This usually means:</p>";
        echo "<ul>";
        echo "<li>The YarnGPT API server might be slow or unresponsive</li>";
        echo "<li>Your network/firewall is blocking the connection</li>";
        echo "<li>There might be DNS resolution issues</li>";
        echo "</ul>";
    }
}
?>

