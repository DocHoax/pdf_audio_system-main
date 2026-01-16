<?php
/**
 * EchoDoc - Configuration File
 * 
 * Modify these settings according to your needs
 */

// Load environment variables from .env file
require_once __DIR__ . '/env.php';

// Error reporting (disabled for production)
$isProduction = env('APP_ENV') === 'production';
error_reporting($isProduction ? 0 : E_ALL);
ini_set('display_errors', $isProduction ? 0 : 1);

// YarnGPT API Configuration
define('YARNGPT_API_KEY', env('YARNGPT_API_KEY', ''));

// Available YarnGPT Voices
define('YARNGPT_VOICES', [
    'Idera' => 'Melodic, gentle',
    'Emma' => 'Authoritative, deep',
    'Zainab' => 'Soothing, gentle',
    'Osagie' => 'Smooth, calm',
    'Wura' => 'Young, sweet',
    'Jude' => 'Warm, confident',
    'Chinenye' => 'Engaging, warm',
    'Tayo' => 'Upbeat, energetic',
    'Regina' => 'Mature, warm',
    'Femi' => 'Rich, reassuring',
    'Adaora' => 'Warm, Engaging',
    'Umar' => 'Calm, smooth',
    'Mary' => 'Energetic, youthful',
    'Nonso' => 'Bold, resonant',
    'Remi' => 'Melodious, warm',
    'Adam' => 'Deep, Clear'
]);

// Upload settings
define('UPLOAD_MAX_SIZE', 10 * 1024 * 1024); // 10MB in bytes
define('UPLOAD_DIRECTORY', __DIR__ . '/uploads/');
define('ALLOWED_EXTENSIONS', ['pdf']);

// Session settings (only set if session not already active)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 3600); // 1 hour
    ini_set('session.cookie_lifetime', 3600);
}

// Text extraction settings
define('MAX_TEXT_LENGTH', 100000); // Maximum characters to extract
define('CHUNK_SIZE', 200); // Characters per speech chunk

// Create uploads directory if it doesn't exist
if (!file_exists(UPLOAD_DIRECTORY)) {
    mkdir(UPLOAD_DIRECTORY, 0755, true);;
}

// Timezone setting
date_default_timezone_set('UTC');

/**
 * Helper function to format file sizes
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, $precision) . ' ' . $units[$pow];
}

/**
 * Helper function to validate file upload
 */
function validateUpload($file) {
    $errors = [];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Upload failed with error code: ' . $file['error'];
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > UPLOAD_MAX_SIZE) {
        $errors[] = 'File size exceeds limit of ' . formatBytes(UPLOAD_MAX_SIZE);
    }
    
    // Check extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        $errors[] = 'Invalid file type. Allowed: ' . implode(', ', ALLOWED_EXTENSIONS);
    }
    
    return $errors;
}

/**
 * Helper function to clean old uploads
 */
function cleanOldUploads($maxAge = 3600) {
    $files = glob(UPLOAD_DIRECTORY . '*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
            unlink($file);
        }
    }
}

/**
 * Security: Sanitize filename
 */
function sanitizeFilename($filename) {
    // Remove any character that isn't alphanumeric, dash, underscore, or dot
    $filename = preg_replace('/[^a-zA-Z0-9\-\_\.]/', '', $filename);
    
    // Remove any runs of dots
    $filename = preg_replace('/\.+/', '.', $filename);
    
    return $filename;
}
