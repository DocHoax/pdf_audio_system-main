<?php
/**
 * EchoDoc - Performance Optimization Helper
 * Add resource hints for faster page loads
 * Include this file early in the <head> section
 */
?>
<!-- DNS Prefetch for external resources -->
<link rel="dns-prefetch" href="//img.icons8.com">
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//fonts.gstatic.com">

<!-- Preconnect for critical resources -->
<link rel="preconnect" href="https://img.icons8.com" crossorigin>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<!-- Preload critical fonts -->
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<link rel="preload" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Lato:wght@400;700&family=Outfit:wght@400;500;600;700&display=swap" as="style" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Lato:wght@400;700&family=Outfit:wght@400;500;600;700&display=swap">
</noscript>
