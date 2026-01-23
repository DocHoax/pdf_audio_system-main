<?php
/**
 * EchoDoc - SEO Helper
 * Provides canonical URLs, Open Graph, Twitter Cards, JSON-LD, and meta tags
 * 
 * Variables that can be set before including this file:
 * - $metaTitle: Page title (default: 'EchoDoc - Transform PDF to Audio')
 * - $metaDescription: Page description
 * - $metaKeywords: Comma-separated keywords
 * - $metaImage: OG/Twitter image URL
 * - $canonicalPath: Override canonical path
 * - $pageType: OG type (default: 'WebPage')
 * - $noIndex: Set to true to add noindex meta tag
 * - $faqSchema: Array of FAQ items for FAQPage schema
 */

// Load config if needed
if (!defined('APP_URL')) {
    $cfg = __DIR__ . '/../config.php';
    if (file_exists($cfg)) require_once $cfg;
}

// Set defaults
$siteUrl = rtrim(defined('APP_URL') ? APP_URL : (function_exists('env') ? env('APP_URL', 'http://localhost') : 'http://localhost'), '/');
$metaTitle = isset($metaTitle) ? $metaTitle : 'EchoDoc - Nigerian Language PDF Reader | Yoruba, Hausa, Igbo Text to Speech';
$metaDescription = isset($metaDescription) ? $metaDescription : 'EchoDoc is the #1 Nigerian language PDF reader. Convert PDF documents to audio in Yoruba, Hausa, and Igbo. Free AI-powered text to speech for accessibility and learning.';
$metaKeywords = isset($metaKeywords) ? $metaKeywords : 'Nigerian language PDF reader, Yoruba text to speech, Hausa PDF reader, Igbo audio converter, Nigerian TTS, PDF to audio Nigeria, African language accessibility';
$metaImage = isset($metaImage) ? $metaImage : ($siteUrl . '/assets/images/echodoc-share.png');
$canonicalPath = isset($canonicalPath) ? $canonicalPath : (isset($_SERVER['REQUEST_URI']) ? strtok($_SERVER['REQUEST_URI'], '?') : '/');
$canonical = $siteUrl . (strpos($canonicalPath, '/') === 0 ? $canonicalPath : '/' . ltrim($canonicalPath, '/'));
$pageType = isset($pageType) ? $pageType : 'WebPage';
$noIndex = isset($noIndex) ? $noIndex : false;

// Output meta tags
?>
<?php if ($noIndex): ?>
<meta name="robots" content="noindex, nofollow">
<?php endif; ?>

<!-- Favicon for Google Search Results -->
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo $siteUrl; ?>/assets/images/favicon.png">
<link rel="icon" type="image/png" sizes="16x16" href="<?php echo $siteUrl; ?>/assets/images/favicon.png">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $siteUrl; ?>/assets/images/favicon.png">
<link rel="shortcut icon" href="<?php echo $siteUrl; ?>/assets/images/favicon.png">
<meta name="msapplication-TileImage" content="<?php echo $siteUrl; ?>/assets/images/favicon.png">
<meta name="theme-color" content="#3d5a80">

<link rel="canonical" href="<?php echo htmlspecialchars($canonical, ENT_QUOTES); ?>" />
<meta name="description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES); ?>">
<meta name="keywords" content="<?php echo htmlspecialchars($metaKeywords, ENT_QUOTES); ?>">
<meta property="og:locale" content="en_US" />
<meta property="og:type" content="<?php echo htmlspecialchars($pageType, ENT_QUOTES); ?>" />
<meta property="og:site_name" content="EchoDoc" />
<meta property="og:title" content="<?php echo htmlspecialchars($metaTitle, ENT_QUOTES); ?>" />
<meta property="og:description" content="<?php echo htmlspecialchars($metaDescription, ENT_QUOTES); ?>" />
<meta property="og:url" content="<?php echo htmlspecialchars($canonical, ENT_QUOTES); ?>" />
<meta property="og:image" content="<?php echo htmlspecialchars($metaImage, ENT_QUOTES); ?>" />
<meta property="og:image:alt" content="EchoDoc - PDF to Audio Converter" />
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:site" content="@echodoc" />
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
      "logo": {
        "@type": "ImageObject",
        "url": "<?php echo $siteUrl; ?>/assets/images/favicon.png"
      },
      "sameAs": ["https://x.com/echodoc"],
      "contactPoint": {
        "@type": "ContactPoint",
        "email": "infoechodoc@gmail.com",
        "contactType": "customer support"
      }
    },
    {
      "@type": "WebSite",
      "url": "<?php echo $siteUrl; ?>",
      "name": "EchoDoc",
      "description": "<?php echo htmlspecialchars($metaDescription, ENT_QUOTES); ?>",
      "publisher": {
        "@type": "Organization",
        "name": "EchoDoc"
      }
    },
    {
      "@type": "WebPage",
      "@id": "<?php echo htmlspecialchars($canonical, ENT_QUOTES); ?>",
      "url": "<?php echo htmlspecialchars($canonical, ENT_QUOTES); ?>",
      "name": "<?php echo htmlspecialchars($metaTitle, ENT_QUOTES); ?>",
      "description": "<?php echo htmlspecialchars($metaDescription, ENT_QUOTES); ?>",
      "isPartOf": {
        "@type": "WebSite",
        "url": "<?php echo $siteUrl; ?>"
      }
    }
<?php if (isset($faqSchema) && is_array($faqSchema) && count($faqSchema) > 0): ?>,
    {
      "@type": "FAQPage",
      "mainEntity": [
<?php foreach ($faqSchema as $i => $faq): ?>
        {
          "@type": "Question",
          "name": <?php echo json_encode($faq['question']); ?>,
          "acceptedAnswer": {
            "@type": "Answer",
            "text": <?php echo json_encode($faq['answer']); ?>
          }
        }<?php echo ($i < count($faqSchema) - 1) ? ',' : ''; ?>

<?php endforeach; ?>
      ]
    }
<?php endif; ?>
  ]
}
</script>
