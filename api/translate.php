<?php
/**
 * Translation API Handler
 * Translates text to Nigerian local languages using Google Translate API (free tier)
 */

// Prevent direct access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['text']) || !isset($input['target_language'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$text = $input['text'];
$targetLang = $input['target_language'];

// Supported languages
$supportedLanguages = [
    'yo' => 'Yoruba',
    'ha' => 'Hausa', 
    'ig' => 'Igbo',
    'en' => 'English'
];

if (!array_key_exists($targetLang, $supportedLanguages)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Unsupported language']);
    exit;
}

// If target is English, just return the original text
if ($targetLang === 'en') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'translated_text' => $text,
        'source_language' => 'en',
        'target_language' => 'en',
        'language_name' => 'English'
    ]);
    exit;
}

try {
    $translatedText = translateText($text, $targetLang);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'translated_text' => $translatedText,
        'source_language' => 'en',
        'target_language' => $targetLang,
        'language_name' => $supportedLanguages[$targetLang]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Translate text using Google Translate (free method)
 * 
 * @param string $text Text to translate
 * @param string $targetLang Target language code
 * @return string Translated text
 */
function translateText($text, $targetLang) {
    // Split text into chunks if too long (Google has limits)
    $maxLength = 5000;
    
    if (strlen($text) <= $maxLength) {
        return translateChunk($text, $targetLang);
    }
    
    // Split into paragraphs and translate each
    $paragraphs = explode("\n", $text);
    $translatedParagraphs = [];
    $currentChunk = '';
    
    foreach ($paragraphs as $paragraph) {
        if (strlen($currentChunk) + strlen($paragraph) + 1 > $maxLength) {
            if (!empty($currentChunk)) {
                $translatedParagraphs[] = translateChunk($currentChunk, $targetLang);
            }
            $currentChunk = $paragraph;
        } else {
            $currentChunk .= ($currentChunk ? "\n" : '') . $paragraph;
        }
    }
    
    if (!empty($currentChunk)) {
        $translatedParagraphs[] = translateChunk($currentChunk, $targetLang);
    }
    
    return implode("\n", $translatedParagraphs);
}

/**
 * Translate a single chunk of text
 * 
 * @param string $text Text chunk to translate
 * @param string $targetLang Target language code
 * @return string Translated text
 */
function translateChunk($text, $targetLang) {
    $url = 'https://translate.googleapis.com/translate_a/single';
    
    $params = [
        'client' => 'gtx',
        'sl' => 'en',           // Source language (auto-detect or English)
        'tl' => $targetLang,    // Target language
        'dt' => 't',            // Return translated text
        'q' => $text
    ];
    
    $fullUrl = $url . '?' . http_build_query($params);
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $fullUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Translation request failed: ' . $error);
    }
    
    if ($httpCode !== 200) {
        throw new Exception('Translation service returned error code: ' . $httpCode);
    }
    
    // Parse the response (it's a nested array)
    $result = json_decode($response, true);
    
    if (!$result || !isset($result[0])) {
        throw new Exception('Invalid response from translation service');
    }
    
    // Extract translated text from the nested array structure
    $translatedText = '';
    foreach ($result[0] as $segment) {
        if (isset($segment[0])) {
            $translatedText .= $segment[0];
        }
    }
    
    return $translatedText;
}
