<?php
/**
 * EchoDoc - About Page
 */
require_once 'config.php';

// Page-specific SEO
$metaTitle = 'About EchoDoc - Our Mission & Technology';
$metaDescription = 'Learn about EchoDoc\'s mission to improve accessibility through AI-powered PDF to audio conversion. Discover our technology stack and objectives.';
$metaKeywords = 'about EchoDoc, PDF accessibility, text to speech technology, reading disabilities, document reader mission';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include 'includes/performance.php'; ?>
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <title><?php echo htmlspecialchars($metaTitle); ?></title>
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
                <li><a href="about.php" class="nav-link active"><img src="https://img.icons8.com/fluency/48/info.png" alt="About"> About</a></li>
                <li><a href="help.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/help.png" alt="Help"> Help</a></li>
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
            <h1><img src="https://img.icons8.com/fluency/48/info.png" alt="About" style="width: 48px; height: 48px; vertical-align: middle;"> About EchoDoc</h1>
            <p>Learn about EchoDoc and our mission to improve accessibility</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <section class="about-section">
                <!-- Background -->
                <div class="about-content">
                    <h2><img src="https://img.icons8.com/fluency/48/clock--v1.png" alt="History" style="width: 32px; height: 32px; vertical-align: middle;"> Background of the Project</h2>
                    <p>
                        In 1991, Adobe co-founder Dr. John Warnock propelled the paper-to-digital revolution with an idea 
                        he called "The Camelot Project." The objective was to enable the growing digital users the ability 
                        to capture documents from any application, send electronic renditions of these documents anywhere, 
                        and view and print them on any machine. By 1992, Camelot had developed into PDF.
                    </p>
                    <p>
                        Today, PDF (Portable Document Format) is a document format trusted by businesses and organizations 
                        around the globe. It was the first file format of its kind to have the ability to store and offer 
                        content and images in a way that would protect the formatting of the original document, regardless 
                        of which software, hardware, or platform it is being viewed on.
                    </p>
                    <p>
                        With PDF being the most used document format globally, there is a need to convert text in PDF formats 
                        into audio signals. This technology can be utilized for various purposes, including educational systems, 
                        car navigation, announcements, response services in telecommunications, and email reading. Furthermore, 
                        people with vision disabilities often cannot view or read PDF files, and this system addresses that challenge.
                    </p>
                </div>

                <!-- Mission & Objectives -->
                <div class="about-content">
                    <h2><img src="https://img.icons8.com/fluency/48/goal--v1.png" alt="Goals" style="width: 32px; height: 32px; vertical-align: middle;"> Aims and Objectives</h2>
                    <p>
                        This project aims at the Design and Implementation of EchoDoc to aid accessibility 
                        and easy text-to-voice assimilation of documents in PDF format.
                    </p>
                    <h3>Specific Objectives:</h3>
                    <ul class="objectives-list">
                        <li>Develop a system that will convert PDF text to audio for easy assimilation of documents</li>
                        <li>Create a system to easily detect PDF files and convert them to audio</li>
                        <li>Design a system that will assist people with reading disabilities to easily convert PDF text to audio files</li>
                        <li>Implement a system that will assist students' reading comprehension skills</li>
                    </ul>
                </div>

                <!-- Motivation -->
                <div class="about-content">
                    <h2><img src="https://img.icons8.com/fluency/48/light-on--v1.png" alt="Motivation" style="width: 32px; height: 32px; vertical-align: middle;"> Motivation</h2>
                    <p>
                        Presenting reading material orally in addition to a traditional paper presentation format increases 
                        the ability of users to decode reading material. This has the potential to help students with reading 
                        disabilities better comprehend written texts.
                    </p>
                    <p>
                        There are several different technologies for presenting oral materials (e.g., text-to-speech, reading pens, 
                        audiobooks). Text has already been accessible orally through books-on-tape and through human readers. 
                        However, there is a need to develop and implement a text-to-speech system that can be used widely in 
                        educational settings from elementary school through universities.
                    </p>
                    <p>
                        With the implementation of EchoDoc, there will be improved effects of text-to-speech 
                        and related tools for oral presentation of material on reading comprehension for students with reading disabilities.
                    </p>
                </div>

                <!-- Technology Stack -->
                <div class="about-content">
                    <h2><img src="https://img.icons8.com/fluency/48/source-code.png" alt="Code" style="width: 32px; height: 32px; vertical-align: middle;"> Technology Stack</h2>
                    <p>EchoDoc is developed using modern web technologies:</p>
                    <div class="tech-stack">
                        <div class="tech-item">
                            <img src="https://img.icons8.com/color/48/html-5--v1.png" alt="HTML5" style="width: 40px; height: 40px;">
                            <div>
                                <strong>HTML5</strong>
                                <p>Structure & Semantics</p>
                            </div>
                        </div>
                        <div class="tech-item">
                            <img src="https://img.icons8.com/color/48/css3.png" alt="CSS3" style="width: 40px; height: 40px;">
                            <div>
                                <strong>CSS3</strong>
                                <p>Styling & Animations</p>
                            </div>
                        </div>
                        <div class="tech-item">
                            <img src="https://img.icons8.com/color/48/javascript--v1.png" alt="JavaScript" style="width: 40px; height: 40px;">
                            <div>
                                <strong>JavaScript</strong>
                                <p>Interactivity & TTS</p>
                            </div>
                        </div>
                        <div class="tech-item">
                            <img src="https://img.icons8.com/officel/48/php-logo.png" alt="PHP" style="width: 40px; height: 40px;">
                            <div>
                                <strong>PHP</strong>
                                <p>Backend & PDF Processing</p>
                            </div>
                        </div>
                        <div class="tech-item">
                            <img src="https://img.icons8.com/fluency/48/high-volume--v1.png" alt="Audio" style="width: 40px; height: 40px;">
                            <div>
                                <strong>YarnGPT API</strong>
                                <p>Text-to-Speech Engine</p>
                            </div>
                        </div>
                        <div class="tech-item">
                            <img src="https://img.icons8.com/fluency/48/iphone-x.png" alt="Mobile" style="width: 40px; height: 40px;">
                            <div>
                                <strong>Responsive Design</strong>
                                <p>Mobile Friendly</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Features -->
                <div class="about-content">
                    <h2><img src="https://img.icons8.com/fluency/48/star--v1.png" alt="Features" style="width: 32px; height: 32px; vertical-align: middle;"> Key Features</h2>
                    <div class="features-grid">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <img src="https://img.icons8.com/fluency/48/upload--v1.png" alt="Upload" style="width: 48px; height: 48px;">
                            </div>
                            <h3>Easy Upload</h3>
                            <p>Drag and drop or click to upload PDF documents</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <img src="https://img.icons8.com/fluency/48/wizard.png" alt="Magic" style="width: 48px; height: 48px;">
                            </div>
                            <h3>Auto Extraction</h3>
                            <p>Automatic text extraction from PDF files</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <img src="https://img.icons8.com/fluency/48/language.png" alt="Language" style="width: 48px; height: 48px;">
                            </div>
                            <h3>Multi-Language</h3>
                            <p>Support for multiple languages and voices</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <img src="https://img.icons8.com/fluency/48/settings.png" alt="Settings" style="width: 48px; height: 48px;">
                            </div>
                            <h3>Customizable</h3>
                            <p>Adjust speed, pitch, and volume settings</p>
                        </div>
                    </div>
                </div>

                <!-- Scope -->
                <div class="about-content">
                    <h2><img src="https://img.icons8.com/fluency/48/search--v1.png" alt="Search" style="width: 32px; height: 32px; vertical-align: middle;"> Scope of the Project</h2>
                    <p>
                        The scope of this research is focused on implementing EchoDoc to improve the usage 
                        of PDF documents and achieve a more flexible audio speech system. The system is designed to:
                    </p>
                    <ul class="objectives-list">
                        <li>Accept PDF file uploads from users</li>
                        <li>Extract text content from PDF documents</li>
                        <li>Convert extracted text to speech using browser-native APIs</li>
                        <li>Provide customizable speech settings for user preference</li>
                        <li>Support multiple languages based on browser capabilities</li>
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
