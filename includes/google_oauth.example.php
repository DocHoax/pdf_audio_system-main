<?php
/**
 * EchoDoc - Google OAuth Configuration (EXAMPLE)
 * 
 * Copy this file to google_oauth.php and configure with your credentials
 * 
 * To get your Google OAuth credentials:
 * 1. Go to https://console.cloud.google.com/
 * 2. Create a new project (or select existing)
 * 3. Go to "APIs & Services" > "Credentials"
 * 4. Click "Create Credentials" > "OAuth client ID"
 * 5. Select "Web application"
 * 6. Add Authorized redirect URI: http://localhost/pdf_audio_system-main/google-callback.php
 * 7. Copy the Client ID and Client Secret below
 */

// Google OAuth Credentials
// In production, set these as environment variables in Digital Ocean
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'your_google_client_id_here');
define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'your_google_client_secret_here');
define('GOOGLE_REDIRECT_URI', getenv('GOOGLE_REDIRECT_URI') ?: 'http://localhost/pdf_audio_system-main/google-callback.php');

// Google OAuth URLs
define('GOOGLE_AUTH_URL', 'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USER_INFO_URL', 'https://www.googleapis.com/oauth2/v2/userinfo');

/**
 * Generate Google OAuth URL
 * @return string
 */
function getGoogleAuthUrl() {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];
    
    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}

/**
 * Exchange authorization code for access token
 * @param string $code
 * @return array|null
 */
function getGoogleAccessToken($code) {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code',
        'code' => $code
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    error_log("Google token error: " . $response);
    return null;
}

/**
 * Get user info from Google
 * @param string $accessToken
 * @return array|null
 */
function getGoogleUserInfo($accessToken) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GOOGLE_USER_INFO_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    error_log("Google user info error: " . $response);
    return null;
}
