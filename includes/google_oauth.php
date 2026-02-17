<?php
/**
 * Google OAuth integration has been removed from EchoDoc.
 * This file is kept as a compatibility stub for legacy includes.
 */

if (!function_exists('getGoogleAuthUrl')) {
    function getGoogleAuthUrl() {
        throw new RuntimeException('Google OAuth is disabled.');
    }
}

if (!function_exists('getGoogleAccessToken')) {
    function getGoogleAccessToken($code) {
        throw new RuntimeException('Google OAuth is disabled.');
    }
}

if (!function_exists('getGoogleUserInfo')) {
    function getGoogleUserInfo($accessToken) {
        throw new RuntimeException('Google OAuth is disabled.');
    }
}
