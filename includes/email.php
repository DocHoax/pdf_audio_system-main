<?php
/**
 * EchoDoc - Email Helper
 * 
 * Send emails using SMTP or PHP mail()
 * Supports email templates and queue
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/../env.php';

/**
 * Get email configuration
 */
function getEmailConfig() {
    return [
        'smtp_enabled' => env('SMTP_ENABLED', false),
        'smtp_host' => env('SMTP_HOST', 'smtp.gmail.com'),
        'smtp_port' => env('SMTP_PORT', 587),
        'smtp_user' => env('SMTP_USER', ''),
        'smtp_pass' => env('SMTP_PASS', ''),
        'smtp_secure' => env('SMTP_SECURE', 'tls'),
        'from_email' => env('MAIL_FROM_EMAIL', 'noreply@echodoc.app'),
        'from_name' => env('MAIL_FROM_NAME', 'EchoDoc'),
        'app_url' => env('APP_URL', 'http://localhost/pdf_audio_system-main')
    ];
}

/**
 * Send email (queues if SMTP not configured)
 */
function sendEmail($to, $subject, $bodyHtml, $bodyText = null, $toName = null) {
    $config = getEmailConfig();
    
    // If SMTP is configured, send directly
    if ($config['smtp_enabled'] && !empty($config['smtp_user'])) {
        return sendEmailSMTP($to, $subject, $bodyHtml, $bodyText, $toName, $config);
    }
    
    // Otherwise, queue the email
    return queueEmail($to, $subject, $bodyHtml, $bodyText, $toName);
}

/**
 * Send email using PHP mail() with proper headers
 */
function sendEmailBasic($to, $subject, $bodyHtml, $bodyText = null, $toName = null) {
    $config = getEmailConfig();
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
        'Reply-To: ' . $config['from_email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $headerString = implode("\r\n", $headers);
    
    return mail($to, $subject, $bodyHtml, $headerString);
}

/**
 * Send email using SMTP (requires PHPMailer or similar)
 */
function sendEmailSMTP($to, $subject, $bodyHtml, $bodyText, $toName, $config) {
    // For production, you would use PHPMailer here
    // For now, fall back to basic mail
    return sendEmailBasic($to, $subject, $bodyHtml, $bodyText, $toName);
}

/**
 * Queue email for later sending
 */
function queueEmail($to, $subject, $bodyHtml, $bodyText = null, $toName = null) {
    $pdo = getDbConnection();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO email_queue (to_email, to_name, subject, body_html, body_text)
            VALUES (:to_email, :to_name, :subject, :body_html, :body_text)
        ");
        
        $stmt->execute([
            ':to_email' => $to,
            ':to_name' => $toName,
            ':subject' => $subject,
            ':body_html' => $bodyHtml,
            ':body_text' => $bodyText
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Email queue error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get email template and replace variables
 */
function getEmailTemplate($templateName, $variables = []) {
    $pdo = getDbConnection();
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE name = :name AND is_active = 1");
        $stmt->execute([':name' => $templateName]);
        $template = $stmt->fetch();
        
        if (!$template) return null;
        
        $subject = $template['subject'];
        $bodyHtml = $template['body_html'];
        $bodyText = $template['body_text'];
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $bodyHtml = str_replace($placeholder, $value, $bodyHtml);
            $bodyText = str_replace($placeholder, $value, $bodyText);
        }
        
        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText
        ];
        
    } catch (PDOException $e) {
        error_log("Email template error: " . $e->getMessage());
        return null;
    }
}

/**
 * Send welcome email to new user
 */
function sendWelcomeEmail($userEmail, $username) {
    $config = getEmailConfig();
    
    $template = getEmailTemplate('welcome', [
        'username' => $username,
        'app_url' => $config['app_url']
    ]);
    
    if (!$template) {
        // Fallback if template doesn't exist
        $template = [
            'subject' => 'Welcome to EchoDoc!',
            'body_html' => "<h1>Welcome, $username!</h1><p>Thank you for joining EchoDoc.</p>",
            'body_text' => "Welcome, $username! Thank you for joining EchoDoc."
        ];
    }
    
    return sendEmail($userEmail, $template['subject'], $template['body_html'], $template['body_text']);
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($userEmail, $username, $resetToken) {
    $config = getEmailConfig();
    $resetUrl = $config['app_url'] . '/reset-password.php?token=' . $resetToken;
    
    $template = getEmailTemplate('password_reset', [
        'username' => $username,
        'reset_url' => $resetUrl
    ]);
    
    if (!$template) {
        $template = [
            'subject' => 'Reset Your EchoDoc Password',
            'body_html' => "<h1>Password Reset</h1><p>Click <a href='$resetUrl'>here</a> to reset your password.</p>",
            'body_text' => "Reset your password: $resetUrl"
        ];
    }
    
    return sendEmail($userEmail, $template['subject'], $template['body_html'], $template['body_text']);
}

/**
 * Send contact form confirmation
 */
function sendContactConfirmation($userEmail, $userName, $message) {
    $template = getEmailTemplate('contact_received', [
        'name' => $userName,
        'message' => htmlspecialchars($message)
    ]);
    
    if (!$template) {
        $template = [
            'subject' => 'We Received Your Message',
            'body_html' => "<p>Hi $userName, thank you for contacting us!</p>",
            'body_text' => "Hi $userName, thank you for contacting us!"
        ];
    }
    
    return sendEmail($userEmail, $template['subject'], $template['body_html'], $template['body_text']);
}

/**
 * Send contact form to admin
 */
function sendContactToAdmin($fromEmail, $fromName, $subject, $message) {
    $config = getEmailConfig();
    $adminEmail = env('ADMIN_EMAIL', $config['from_email']);
    
    $bodyHtml = "
        <h2>New Contact Form Submission</h2>
        <p><strong>From:</strong> $fromName &lt;$fromEmail&gt;</p>
        <p><strong>Subject:</strong> $subject</p>
        <p><strong>Message:</strong></p>
        <blockquote>" . nl2br(htmlspecialchars($message)) . "</blockquote>
    ";
    
    return sendEmail($adminEmail, "Contact Form: $subject", $bodyHtml, $message);
}

/**
 * Process email queue (call via cron job)
 */
function processEmailQueue($limit = 10) {
    $pdo = getDbConnection();
    if (!$pdo) return 0;
    
    $processed = 0;
    
    try {
        // Get pending emails
        $stmt = $pdo->prepare("
            SELECT * FROM email_queue 
            WHERE status = 'pending' AND attempts < 3
            ORDER BY created_at ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $emails = $stmt->fetchAll();
        
        foreach ($emails as $email) {
            $success = sendEmailBasic(
                $email['to_email'],
                $email['subject'],
                $email['body_html'],
                $email['body_text'],
                $email['to_name']
            );
            
            if ($success) {
                $updateStmt = $pdo->prepare("
                    UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = :id
                ");
                $updateStmt->execute([':id' => $email['id']]);
                $processed++;
            } else {
                $updateStmt = $pdo->prepare("
                    UPDATE email_queue SET attempts = attempts + 1, last_attempt = NOW(), 
                    status = CASE WHEN attempts >= 2 THEN 'failed' ELSE 'pending' END
                    WHERE id = :id
                ");
                $updateStmt->execute([':id' => $email['id']]);
            }
        }
        
    } catch (PDOException $e) {
        error_log("Email queue processing error: " . $e->getMessage());
    }
    
    return $processed;
}

/**
 * Check if user wants email notifications
 */
function userWantsEmailNotifications($userId) {
    $pdo = getDbConnection();
    if (!$pdo) return true; // Default to yes
    
    try {
        $stmt = $pdo->prepare("SELECT email_notifications FROM user_settings WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        
        return $result ? (bool)$result['email_notifications'] : true;
    } catch (PDOException $e) {
        return true;
    }
}
