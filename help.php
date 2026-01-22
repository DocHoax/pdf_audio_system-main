<?php
/**
 * EchoDoc - Help Page
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Help and Documentation for EchoDoc">
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <title>Help - EchoDoc</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <?php include 'includes/seo.php'; ?>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-logo">
                <img src="https://img.icons8.com/fluency/48/pdf.png" alt="EchoDoc">
                <span>EchoDoc</span>
            </a>
            <ul class="nav-menu">
                <li><a href="index.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/home.png" alt="Home"> Home</a></li>
                <li><a href="recent.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/time-machine.png" alt="Recent"> Recent</a></li>
                <li><a href="about.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/info.png" alt="About"> About</a></li>
                <li><a href="help.php" class="nav-link active"><img src="https://img.icons8.com/fluency/48/help.png" alt="Help"> Help</a></li>
                <li><a href="contact.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/email.png" alt="Contact"> Contact</a></li>
            </ul>
            <button class="nav-toggle" id="navToggle">
                <img src="https://img.icons8.com/fluency/48/menu.png" alt="Menu" style="width:24px;height:24px;">
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-content">
            <h1><img src="https://img.icons8.com/fluency/48/help.png" alt="Help" style="width: 48px; height: 48px; vertical-align: middle;"> Help & Documentation</h1>
            <p>Learn how to use EchoDoc effectively</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <section class="help-section">
                <!-- Step-by-Step Guide -->
                <div class="step-guide">
                    <h2><img src="https://img.icons8.com/fluency/48/list.png" alt="Guide" style="width: 32px; height: 32px; vertical-align: middle;"> How to Use EchoDoc</h2>
                    
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Upload Your PDF</h3>
                            <p>
                                Click on the upload area or drag and drop your PDF file. The system accepts PDF files 
                                up to 10MB in size. Make sure your PDF contains selectable text (not scanned images).
                            </p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Wait for Text Extraction</h3>
                            <p>
                                After uploading, the system will automatically extract text from your PDF document. 
                                This may take a few seconds depending on the document size.
                            </p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Review Extracted Text</h3>
                            <p>
                                Once extraction is complete, you'll see the text displayed on screen. You can scroll 
                                through the text to verify it was extracted correctly.
                            </p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3>Configure Audio Settings</h3>
                            <p>
                                Before playing, you can customize the audio experience:
                                <br><strong>Voice:</strong> Select from available voices in different languages
                                <br><strong>Speed:</strong> Adjust reading speed (0.5x to 2x)
                                <br><strong>Pitch:</strong> Change the voice pitch
                                <br><strong>Volume:</strong> Set the audio volume level
                            </p>
                        </div>
                    </div>
                    
                    <div class="step">
                        <div class="step-number">5</div>
                        <div class="step-content">
                            <h3>Play the Audio</h3>
                            <p>
                                Click the "Play" button to start listening. You can pause, resume, or stop 
                                playback at any time using the control buttons. A progress bar shows your 
                                current position in the document.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- FAQ Section -->
                <div class="about-content">
                    <h2><img src="https://img.icons8.com/fluency/48/question-mark.png" alt="FAQ" style="width: 32px; height: 32px; vertical-align: middle;"> Frequently Asked Questions</h2>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>What types of PDF files are supported?</span>
                            <img src="https://img.icons8.com/fluency/48/chevron-down.png" alt="Expand" style="width: 20px; height: 20px;" class="faq-chevron">
                        </div>
                        <div class="faq-answer">
                            <p>
                                The system supports PDF files that contain selectable text. Scanned documents 
                                (image-based PDFs) may not work properly as they don't contain actual text data. 
                                For best results, use PDFs that were created digitally rather than scanned from paper.
                            </p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>What is the maximum file size allowed?</span>
                            <img src="https://img.icons8.com/fluency/48/chevron-down.png" alt="Expand" style="width: 20px; height: 20px;" class="faq-chevron">
                        </div>
                        <div class="faq-answer">
                            <p>
                                The maximum file size is 10MB. This is sufficient for most text documents. 
                                If your PDF is larger, consider splitting it into smaller sections or 
                                removing embedded images to reduce file size.
                            </p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Why can't I hear any audio?</span>
                            <img src="https://img.icons8.com/fluency/48/chevron-down.png" alt="Expand" style="width: 20px; height: 20px;" class="faq-chevron">
                        </div>
                        <div class="faq-answer">
                            <p>
                                Make sure your device's volume is turned up and not muted. Also check that 
                                your browser supports the Web Speech API (Chrome, Edge, Safari, and Firefox 
                                are supported). Try refreshing the page and uploading your PDF again.
                            </p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>What languages are supported?</span>
                            <img src="https://img.icons8.com/fluency/48/chevron-down.png" alt="Expand" style="width: 20px; height: 20px;" class="faq-chevron">
                        </div>
                        <div class="faq-answer">
                            <p>
                                The available languages depend on your browser and operating system. Most systems 
                                include English, Spanish, French, German, and other major languages. The voice 
                                selector shows all available options on your device.
                            </p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Why is the extracted text different from my PDF?</span>
                            <img src="https://img.icons8.com/fluency/48/chevron-down.png" alt="Expand" style="width: 20px; height: 20px;" class="faq-chevron">
                        </div>
                        <div class="faq-answer">
                            <p>
                                PDF text extraction may not preserve exact formatting like tables, columns, 
                                or special layouts. The system extracts readable text in a linear format. 
                                Some special characters or fonts may also not be extracted correctly.
                            </p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Is my PDF stored on the server?</span>
                            <img src="https://img.icons8.com/fluency/48/chevron-down.png" alt="Expand" style="width: 20px; height: 20px;" class="faq-chevron">
                        </div>
                        <div class="faq-answer">
                            <p>
                                PDF files are temporarily uploaded for text extraction and then stored 
                                in the uploads folder. You can clear your session data at any time by 
                                clicking "Clear & Upload New PDF" button. We recommend not uploading 
                                sensitive or confidential documents.
                            </p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Can I download the audio file?</span>
                            <img src="https://img.icons8.com/fluency/48/chevron-down.png" alt="Expand" style="width: 20px; height: 20px;" class="faq-chevron">
                        </div>
                        <div class="faq-answer">
                            <p>
                                Currently, the audio is played directly in your browser using the Web Speech API 
                                and is not saved as a downloadable file. The text-to-speech conversion happens 
                                in real-time on your device.
                            </p>
                        </div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">
                            <span>Does it work on mobile devices?</span>
                            <img src="https://img.icons8.com/fluency/48/chevron-down.png" alt="Expand" style="width: 20px; height: 20px;" class="faq-chevron">
                        </div>
                        <div class="faq-answer">
                            <p>
                                Yes! EchoDoc is fully responsive and works on smartphones 
                                and tablets. However, text-to-speech capabilities may vary depending on 
                                your mobile browser and device settings.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Keyboard Shortcuts -->
                <div class="about-content">
                    <h2><img src="https://img.icons8.com/fluency/48/keyboard.png" alt="Keyboard" style="width: 32px; height: 32px; vertical-align: middle;"> Keyboard Shortcuts</h2>
                    <div class="tech-stack">
                        <div class="tech-item">
                            <img src="https://img.icons8.com/fluency/48/keyboard.png" alt="Keyboard" style="width: 40px; height: 40px;">
                            <div>
                                <strong>Space Bar</strong>
                                <p>Play / Pause audio</p>
                            </div>
                        </div>
                        <div class="tech-item">
                            <img src="https://img.icons8.com/fluency/48/keyboard.png" alt="Keyboard" style="width: 40px; height: 40px;">
                            <div>
                                <strong>Escape</strong>
                                <p>Stop audio playback</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Browser Support -->
                <div class="about-content">
                    <h2><img src="https://img.icons8.com/fluency/48/web.png" alt="Browser" style="width: 32px; height: 32px; vertical-align: middle;"> Browser Support</h2>
                    <p>EchoDoc works best with modern browsers that support the Web Speech API:</p>
                    <div class="tech-stack">
                        <div class="tech-item">
                            <img src="https://img.icons8.com/color/48/chrome--v1.png" alt="Chrome" style="width: 40px; height: 40px;">
                            <div>
                                <strong>Google Chrome</strong>
                                <p>Fully supported (recommended)</p>
                            </div>
                        </div>
                        <div class="tech-item">
                            <img src="https://img.icons8.com/color/48/ms-edge-new.png" alt="Edge" style="width: 40px; height: 40px;">
                            <div>
                                <strong>Microsoft Edge</strong>
                                <p>Fully supported</p>
                            </div>
                        </div>
                        <div class="tech-item">
                            <img src="https://img.icons8.com/color/48/firefox--v1.png" alt="Firefox" style="width: 40px; height: 40px;">
                            <div>
                                <strong>Mozilla Firefox</strong>
                                <p>Supported with limitations</p>
                            </div>
                        </div>
                        <div class="tech-item">
                            <img src="https://img.icons8.com/color/48/safari--v1.png" alt="Safari" style="width: 40px; height: 40px;">
                            <div>
                                <strong>Apple Safari</strong>
                                <p>Supported</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Troubleshooting -->
                <div class="about-content">
                    <h2><img src="https://img.icons8.com/fluency/48/maintenance.png" alt="Tools" style="width: 32px; height: 32px; vertical-align: middle;"> Troubleshooting</h2>
                    <ul class="objectives-list">
                        <li><strong>No voices available:</strong> Refresh the page or try a different browser</li>
                        <li><strong>Text extraction failed:</strong> Try a different PDF file with selectable text</li>
                        <li><strong>Audio stops unexpectedly:</strong> Long documents may pause; click Resume to continue</li>
                        <li><strong>Slow performance:</strong> Close other browser tabs and applications</li>
                        <li><strong>Upload fails:</strong> Check file size (max 10MB) and ensure it's a valid PDF</li>
                    </ul>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><img src="https://img.icons8.com/fluency/48/pdf.png" alt="EchoDoc" style="width: 24px; height: 24px; vertical-align: middle;"> EchoDoc</h3>
                    <p>Transform your documents into audio with AI-powered voice synthesis for accessibility and convenience.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php"><img src="https://img.icons8.com/fluency/48/home.png" alt="Home" style="width: 16px; height: 16px;"> Home</a></li>
                        <li><a href="recent.php"><img src="https://img.icons8.com/fluency/48/time-machine.png" alt="Recent" style="width: 16px; height: 16px;"> Recent</a></li>
                        <li><a href="about.php"><img src="https://img.icons8.com/fluency/48/info.png" alt="About" style="width: 16px; height: 16px;"> About</a></li>
                        <li><a href="help.php"><img src="https://img.icons8.com/fluency/48/help.png" alt="Help" style="width: 16px; height: 16px;"> Help</a></li>
                        <li><a href="contact.php"><img src="https://img.icons8.com/fluency/48/email.png" alt="Contact" style="width: 16px; height: 16px;"> Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Technology</h3>
                    <ul>
                        <li><img src="https://img.icons8.com/color/48/html-5--v1.png" alt="HTML" style="width: 16px; height: 16px;"> HTML5 & CSS3</li>
                        <li><img src="https://img.icons8.com/officel/48/php-logo.png" alt="PHP" style="width: 16px; height: 16px;"> PHP 7+</li>
                        <li><img src="https://img.icons8.com/color/48/javascript--v1.png" alt="JS" style="width: 16px; height: 16px;"> JavaScript</li>
                        <li><img src="https://img.icons8.com/fluency/48/high-volume--v1.png" alt="Audio" style="width: 16px; height: 16px;"> YarnGPT API</li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Connect</h3>
                    <ul>
                        <li><a href="https://x.com/echodoc" target="_blank" rel="noopener noreferrer"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" style="vertical-align: middle;"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg> @echodoc</a></li>
                        <li><a href="mailto:infoechodoc@gmail.com"><img src="https://img.icons8.com/fluency/48/email.png" alt="Email" style="width: 16px; height: 16px;"> infoechodoc@gmail.com</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> EchoDoc. All rights reserved. — <a href="contact.php?subject=Bug%20Report" rel="noopener" onclick="if(typeof EchoAnalytics!=='undefined'){EchoAnalytics.track('report_bug_click');}">Report a bug</a></p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/analytics.js?v=1"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
