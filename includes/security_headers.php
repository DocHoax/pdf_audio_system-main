<?php
/**
 * EchoDoc - Security Headers Middleware
 */

if (!function_exists('applySecurityHeaders')) {
    function applySecurityHeaders() {
        if (headers_sent()) {
            return;
        }

        $csp = "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' https:; "
            . "style-src 'self' 'unsafe-inline' https:; "
            . "img-src 'self' data: https:; "
            . "font-src 'self' data: https:; "
            . "connect-src 'self' https:; "
            . "media-src 'self' blob:; "
            . "base-uri 'self'; "
            . "form-action 'self'; "
            . "frame-ancestors 'none'";

        header('Content-Security-Policy: ' . $csp);
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Prevent browsers from caching dynamic PHP pages
        // This ensures logged-in/logged-out state is always fresh
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
    }
}

applySecurityHeaders();
