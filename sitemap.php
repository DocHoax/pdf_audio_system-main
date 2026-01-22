<?php
require_once __DIR__ . '/config.php';

// Base URL
$base = rtrim(defined('APP_URL') ? APP_URL : (env('APP_URL', '') ?: ''), "/");
if (empty($base)) {
    // Fallback to localhost if APP_URL not set
    $base = 'http://localhost/pdf_audio_system-main';
}

// List of pages to include in sitemap
$pages = [
    '/',
    '/index.php',
    '/about.php',
    '/help.php',
    '/contact.php',
    '/signup.php',
    '/login.php',
    '/profile.php',
    '/recent.php',
    '/analytics.php'
];

// Additional dynamic pages could be added here (e.g., uploaded documents)

header('Content-Type: application/xml; charset=utf-8');
$lastmod = date('Y-m-d');

// Emit XML declaration and root element with proper newlines
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

foreach ($pages as $p) {
    $url = $base . (strpos($p, '/') === 0 ? $p : '/' . ltrim($p, '/'));
    // Try to get last modified time of file
    $filePath = __DIR__ . ($p === '/' ? '/index.php' : $p);
    $lm = $lastmod;
    if (file_exists($filePath)) {
        $lm = date('Y-m-d', filemtime($filePath));
    }
    echo "  <url>\n";
    echo "    <loc>" . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . "</loc>\n";
    echo "    <lastmod>$lm</lastmod>\n";
    echo "    <changefreq>weekly</changefreq>\n";
    echo "    <priority>0.6</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
