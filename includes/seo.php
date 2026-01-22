<?php
// SEO helper: canonical, Open Graph, Twitter, and JSON-LD
// Safe to include from any page. Loads config if needed.
if (!defined('APP_URL')) {
    // Try to load config if not already loaded
    $cfg = __DIR__ . '/../config.php';
    if (file_exists($cfg)) require_once $cfg;
}

$siteUrl = rtrim(defined('APP_URL') ? APP_URL : (function_exists('env') ? env('APP_URL', 'http://localhost') : 'http://localhost'), '/');
$metaTitle = isset($metaTitle) ? $metaTitle : 'EchoDoc - Transform PDF to Audio';
$metaDescription = isset($metaDescription) ? $metaDescription : 'Transform PDF documents into natural-sounding audio with AI-powered voice synthesis';
$metaImage = isset($metaImage) ? $metaImage : ($siteUrl . '/assets/images/echodoc-share.png');
$canonicalPath = isset($canonicalPath) ? $canonicalPath : (isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '/');
$canonical = $siteUrl . (strpos($canonicalPath, '/') === 0 ? $canonicalPath : '/' . ltrim($canonicalPath, '/'));
$pageType = isset($pageType) ? $pageType : 'WebPage';

// Output tags
?>
<link rel="canonical" href="<?php echo htmlspecialchars($canonical, ENT_QUOTES); ?>" />
<meta name="description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES); ?>">
<meta property="og:locale" content="en_US" />
<meta property="og:type" content="<?php echo htmlspecialchars($pageType, ENT_QUOTES); ?>" />
<meta property="og:title" content="<?php echo htmlspecialchars($metaTitle, ENT_QUOTES); ?>" />
<meta property="og:description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES); ?>" />
<meta property="og:url" content="<?php echo htmlspecialchars($canonical, ENT_QUOTES); ?>" />
<meta property="og:image" content="<?php echo htmlspecialchars($metaImage, ENT_QUOTES); ?>" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="<?php echo htmlspecialchars($metaTitle, ENT_QUOTES); ?>" />
<meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES); ?>" />
<meta name="twitter:image" content="<?php echo htmlspecialchars($metaImage, ENT_QUOTES); ?>" />

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "Organization",
      "name": "EchoDoc",
      "url": "<?php echo $siteUrl; ?>",
      "logo": "<?php echo $siteUrl; ?>/assets/images/echodoc-logo.png",
      "sameAs": ["https://x.com/echodoc"]
    },
    {
      "@type": "WebSite",
      "url": "<?php echo $siteUrl; ?>",
      "name": "<?php echo htmlspecialchars($metaTitle, ENT_QUOTES); ?>",
      "description": "<?php echo htmlspecialchars($metaDescription, ENT_QUOTES); ?>"
    }
  ]
}
</script>
