<?php
/**
 * EchoDoc - Performance Optimization Helper
 * Add resource hints for faster page loads
 * Include this file early in the <head> section
 */
?>
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-G3G5XMRFWC"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-G3G5XMRFWC');
</script>

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

<!-- PWA Manifest -->
<link rel="manifest" href="/manifest.json">

<!-- Service Worker Registration & PWA Install Popup -->
<script>
// Service Worker Registration
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(reg => console.log('[PWA] Service worker registered'))
            .catch(err => console.log('[PWA] Service worker registration failed:', err));
    });
}

// PWA Install Popup for New Users
let deferredPrompt;
const PWA_PROMPT_KEY = 'echodoc_pwa_prompted';

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    
    // Check if user has already been prompted
    if (!localStorage.getItem(PWA_PROMPT_KEY)) {
        showInstallPopup();
    }
});

function showInstallPopup() {
    // Create popup HTML
    const popup = document.createElement('div');
    popup.id = 'pwa-install-popup';
    popup.innerHTML = `
        <div class="pwa-popup-overlay"></div>
        <div class="pwa-popup-content">
            <img src="https://img.icons8.com/fluency/96/pdf.png" alt="EchoDoc" class="pwa-popup-icon">
            <h3>Install EchoDoc</h3>
            <p>Install our app for faster access and offline reading of your PDFs in Nigerian languages!</p>
            <div class="pwa-popup-buttons">
                <button id="pwa-install-btn" class="pwa-btn-install">
                    <img src="https://img.icons8.com/fluency/24/download.png" alt="">
                    Install App
                </button>
                <button id="pwa-dismiss-btn" class="pwa-btn-dismiss">Not Now</button>
            </div>
        </div>
    `;
    document.body.appendChild(popup);
    
    // Add styles
    const styles = document.createElement('style');
    styles.textContent = `
        #pwa-install-popup {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
        }
        .pwa-popup-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
        }
        .pwa-popup-content {
            position: relative;
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            max-width: 360px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.4s ease;
        }
        .pwa-popup-icon {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }
        .pwa-popup-content h3 {
            font-size: 1.5rem;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }
        .pwa-popup-content p {
            color: #6c757d;
            font-size: 0.95rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }
        .pwa-popup-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .pwa-btn-install {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #3d5a80 0%, #2c4a6e 100%);
            color: #fff;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .pwa-btn-install:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(61, 90, 128, 0.4);
        }
        .pwa-btn-install img {
            width: 20px;
            height: 20px;
            filter: brightness(0) invert(1);
        }
        .pwa-btn-dismiss {
            background: transparent;
            color: #6c757d;
            border: none;
            padding: 0.75rem;
            font-size: 0.9rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .pwa-btn-dismiss:hover {
            color: #333;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    `;
    document.head.appendChild(styles);
    
    // Handle Install button
    document.getElementById('pwa-install-btn').addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log('[PWA] Install outcome:', outcome);
            deferredPrompt = null;
        }
        localStorage.setItem(PWA_PROMPT_KEY, 'true');
        closeInstallPopup();
    });
    
    // Handle Dismiss button
    document.getElementById('pwa-dismiss-btn').addEventListener('click', () => {
        localStorage.setItem(PWA_PROMPT_KEY, 'true');
        closeInstallPopup();
    });
}

function closeInstallPopup() {
    const popup = document.getElementById('pwa-install-popup');
    if (popup) {
        popup.style.animation = 'fadeIn 0.3s ease reverse';
        setTimeout(() => popup.remove(), 280);
    }
}
</script>
