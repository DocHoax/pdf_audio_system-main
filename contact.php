<?php
/**
 * EchoDoc - Contact Page
 */

require_once 'includes/email.php';
require_once 'config.php';

$contactMessage = '';
$contactType = '';

// Handle form submission and allow prefill via GET (e.g., ?subject=Bug%20Report)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
} else {
    // Prefill subject from GET while keeping other fields empty
    $name = '';
    $email = '';
    $subject = trim($_GET['subject'] ?? '');
    $message = '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $contactMessage = 'Please fill in all fields.';
        $contactType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $contactMessage = 'Please enter a valid email address.';
        $contactType = 'error';
    } else {
        // Send confirmation email to user
        sendContactConfirmation($email, $name);
        
        // Send notification to admin
        sendContactToAdmin($name, $email, $subject, $message);
        
        $contactMessage = 'Thank you for your message! We will get back to you soon.';
        $contactType = 'success';
        
        // Log the contact for backup
        $logEntry = date('Y-m-d H:i:s') . " | Name: $name | Email: $email | Subject: $subject | Message: $message\n";
        @file_put_contents('contact_log.txt', $logEntry, FILE_APPEND);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Contact EchoDoc - Get in touch with us">
    <link rel="icon" type="image/png" href="https://img.icons8.com/fluency/48/pdf.png">
    <title>Contact - EchoDoc</title>
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
                <li><a href="help.php" class="nav-link"><img src="https://img.icons8.com/fluency/48/help.png" alt="Help"> Help</a></li>
                <li><a href="contact.php" class="nav-link active"><img src="https://img.icons8.com/fluency/48/email.png" alt="Contact"> Contact</a></li>
            </ul>
            <button class="nav-toggle" id="navToggle">
                <img src="https://img.icons8.com/fluency/48/menu.png" alt="Menu" style="width:24px;height:24px;">
            </button>
        </div>
    </nav>

    <!-- Hero Section -->
    <header class="hero">
        <div class="hero-content">
            <h1><img src="https://img.icons8.com/fluency/48/email.png" alt="Contact" style="width: 48px; height: 48px; vertical-align: middle;"> Contact Us</h1>
            <p>Have questions or feedback? We'd love to hear from you!</p>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <section class="contact-section">
                <!-- Message Display -->
                <?php if (!empty($contactMessage)): ?>
                <div class="alert alert-<?php echo htmlspecialchars($contactType); ?>">
                    <img src="https://img.icons8.com/fluency/48/<?php echo $contactType === 'success' ? 'checkmark--v1' : 'cancel--v1'; ?>.png" alt="<?php echo $contactType; ?>" style="width: 24px; height: 24px; vertical-align: middle;">
                    <?php echo htmlspecialchars($contactMessage); ?>
                </div>
                <?php endif; ?>

                <!-- Contact Form -->
                <div class="contact-form">
                    <h2><img src="https://img.icons8.com/fluency/48/send.png" alt="Send" style="width: 32px; height: 32px; vertical-align: middle;"> Send us a Message</h2>
                    <form action="contact.php" method="POST">
                        <div class="form-group">
                            <label for="name"><img src="https://img.icons8.com/fluency/48/user-male-circle--v1.png" alt="User" style="width: 20px; height: 20px; vertical-align: middle;"> Your Name</label>
                            <input type="text" id="name" name="name" placeholder="Enter your full name" required value="<?php echo htmlspecialchars($name); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email"><img src="https://img.icons8.com/fluency/48/email.png" alt="Email" style="width: 20px; height: 20px; vertical-align: middle;"> Email Address</label>
                            <input type="email" id="email" name="email" placeholder="Enter your email address" required value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="subject"><img src="https://img.icons8.com/fluency/48/price-tag--v1.png" alt="Tag" style="width: 20px; height: 20px; vertical-align: middle;"> Subject</label>
                            <input type="text" id="subject" name="subject" placeholder="What is this about?" required value="<?php echo htmlspecialchars($subject); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="message"><img src="https://img.icons8.com/fluency/48/speech-bubble--v1.png" alt="Message" style="width: 20px; height: 20px; vertical-align: middle;"> Message</label>
                            <textarea id="message" name="message" rows="6" placeholder="Write your message here..." required><?php echo htmlspecialchars($message); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block">
                            <img src="https://img.icons8.com/fluency/48/send.png" alt="Send" style="width: 20px; height: 20px; vertical-align: middle;"> Send Message
                        </button>
                    </form>
                </div>

                <!-- Contact Information -->
                <div class="contact-info">
                    <h2><img src="https://img.icons8.com/fluency/48/contacts.png" alt="Contact" style="width: 32px; height: 32px; vertical-align: middle;"> Contact Information</h2>
                    
                    <div class="contact-item">
                        <img src="https://img.icons8.com/fluency/48/email.png" alt="Email" style="width: 32px; height: 32px;">
                        <div>
                            <strong>Email</strong>
                            <p><a href="mailto:infoechodoc@gmail.com">infoechodoc@gmail.com</a></p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <img src="https://img.icons8.com/fluency/48/domain.png" alt="Website" style="width: 32px; height: 32px;">
                        <div>
                            <strong>Website</strong>
                            <p><a href="https://echodoc-5vpfq.ondigitalocean.app/" target="_blank" rel="noopener noreferrer">echodoc-5vpfq.ondigitalocean.app</a></p>
                        </div>
                    </div>
                    
                    <div class="contact-item">
                        <img src="https://img.icons8.com/fluency/48/clock--v1.png" alt="Clock" style="width: 32px; height: 32px;">
                        <div>
                            <strong>Support Hours</strong>
                            <p>Monday - Friday: 9:00 AM - 5:00 PM</p>
                        </div>
                    </div>
                </div>
                
                <script>
                document.addEventListener('DOMContentLoaded', function(){
                    try {
                        const urlParams = new URLSearchParams(window.location.search);
                        if (urlParams.has('subject')) {
                            const msg = document.getElementById('message');
                            if (msg) msg.focus();
                        }
                    } catch (e) {
                        // ignore
                    }
                });
                </script>

                <!-- Social Media Links -->
                <div class="about-content">
                    <h2><img src="https://img.icons8.com/fluency/48/share--v1.png" alt="Share" style="width: 32px; height: 32px; vertical-align: middle;"> Connect With Us</h2>
                    <div class="tech-stack">
                        <div class="tech-item">
                            <a href="https://x.com/echodoc" target="_blank" rel="noopener noreferrer" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                                <div>
                                    <strong>X (Twitter)</strong>
                                    <p>@echodoc</p>
                                </div>
                            </a>
                        </div>
                        <div class="tech-item">
                            <a href="mailto:infoechodoc@gmail.com" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit;">
                                <img src="https://img.icons8.com/fluency/48/email.png" alt="Email" style="width: 40px; height: 40px;">
                                <div>
                                    <strong>Email</strong>
                                    <p>infoechodoc@gmail.com</p>
                                </div>
                            </a>
                        </div>
                    </div>
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
