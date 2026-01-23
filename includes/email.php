<?php
/**
 * EchoDoc - Email Helper
 * 
 * Send emails using SMTP with PHPMailer
 * Supports email templates and queue
 */

require_once __DIR__ . '/db_config.php';
require_once __DIR__ . '/../env.php';

// Include PHPMailer
require_once __DIR__ . '/../vendor/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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
 * Send email using SMTP with PHPMailer
 */
function sendEmailSMTP($to, $subject, $bodyHtml, $bodyText, $toName, $config) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'];
        $mail->Password   = $config['smtp_pass'];
        $mail->SMTPSecure = $config['smtp_secure'] === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = (int)$config['smtp_port'];
        
        // Recipients
        $mail->setFrom($config['from_email'], $config['from_name']);
        $mail->addAddress($to, $toName ?? '');
        $mail->addReplyTo($config['from_email'], $config['from_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody = $bodyText ?? strip_tags($bodyHtml);
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("PHPMailer Error: " . $mail->ErrorInfo);
        // Fall back to queue on failure
        return queueEmail($to, $subject, $bodyHtml, $bodyText, $toName);
    }
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
        // Beautiful fallback template
        $template = [
            'subject' => '🎉 Welcome to EchoDoc - Your Audio Journey Begins!',
            'body_html' => getWelcomeEmailHtml($username, $config['app_url']),
            'body_text' => "Welcome to EchoDoc, $username!\n\nThank you for joining us. You can now convert your PDFs and documents into high-quality audio files.\n\nGet started: {$config['app_url']}\n\nHappy listening!\nThe EchoDoc Team"
        ];
    }
    
    return sendEmail($userEmail, $template['subject'], $template['body_html'], $template['body_text'], $username);
}

/**
 * Get beautiful welcome email HTML
 */
function getWelcomeEmailHtml($username, $appUrl) {
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background-color:#f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);padding:40px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:32px;">🎧 Welcome to EchoDoc!</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:40px;">
                            <h2 style="color:#333;margin-top:0;">Hey {$username}! 👋</h2>
                            <p style="color:#666;font-size:16px;line-height:1.6;">
                                Thank you for joining EchoDoc! We're excited to help you transform your documents into audio.
                            </p>
                            <p style="color:#666;font-size:16px;line-height:1.6;">
                                With EchoDoc, you can:
                            </p>
                            <ul style="color:#666;font-size:16px;line-height:2;">
                                <li>📄 Convert PDFs and documents to audio</li>
                                <li>🎤 Choose from 16+ natural voices</li>
                                <li>🌍 Translate documents to different languages</li>
                                <li>⬇️ Download MP3 files for offline listening</li>
                            </ul>
                            <div style="text-align:center;margin:30px 0;">
                                <a href="{$appUrl}" style="display:inline-block;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#ffffff;text-decoration:none;padding:15px 40px;border-radius:30px;font-weight:bold;font-size:16px;">
                                    Start Converting →
                                </a>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8f9fa;padding:20px;text-align:center;">
                            <p style="color:#999;font-size:14px;margin:0;">
                                Happy listening! 🎵<br>
                                <strong>The EchoDoc Team</strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * Send MP3 ready notification email
 */
function sendMp3ReadyEmail($userEmail, $username, $documentName, $downloadUrl = null) {
    $config = getEmailConfig();
    
    $template = getEmailTemplate('mp3_ready', [
        'username' => $username,
        'document_name' => $documentName,
        'download_url' => $downloadUrl,
        'app_url' => $config['app_url']
    ]);
    
    if (!$template) {
        // Beautiful fallback template
        $template = [
            'subject' => "🎧 Your audio is ready: $documentName",
            'body_html' => getMp3ReadyEmailHtml($username, $documentName, $downloadUrl, $config['app_url']),
            'body_text' => "Hi $username,\n\nGreat news! Your document \"$documentName\" has been converted to audio and is ready to listen.\n\nVisit EchoDoc to download: {$config['app_url']}\n\nHappy listening!\nThe EchoDoc Team"
        ];
    }
    
    return sendEmail($userEmail, $template['subject'], $template['body_html'], $template['body_text'], $username);
}

/**
 * Get MP3 ready email HTML
 */
function getMp3ReadyEmailHtml($username, $documentName, $downloadUrl, $appUrl) {
    $downloadButton = $downloadUrl 
        ? "<a href=\"{$downloadUrl}\" style=\"display:inline-block;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:#ffffff;text-decoration:none;padding:15px 40px;border-radius:30px;font-weight:bold;font-size:16px;margin:10px;\">⬇️ Download MP3</a>"
        : "";
    
    return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background-color:#f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#11998e 0%,#38ef7d 100%);padding:40px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:32px;">🎧 Your Audio is Ready!</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:40px;">
                            <h2 style="color:#333;margin-top:0;">Hi {$username}! 🎉</h2>
                            <p style="color:#666;font-size:16px;line-height:1.6;">
                                Great news! Your document has been successfully converted to audio:
                            </p>
                            <div style="background-color:#f8f9fa;border-radius:10px;padding:20px;margin:20px 0;text-align:center;">
                                <p style="color:#333;font-size:18px;font-weight:bold;margin:0;">
                                    📄 {$documentName}
                                </p>
                            </div>
                            <p style="color:#666;font-size:16px;line-height:1.6;">
                                You can now listen to your document or download the MP3 file for offline listening.
                            </p>
                            <div style="text-align:center;margin:30px 0;">
                                {$downloadButton}
                                <a href="{$appUrl}" style="display:inline-block;background-color:#f8f9fa;color:#667eea;text-decoration:none;padding:15px 40px;border-radius:30px;font-weight:bold;font-size:16px;margin:10px;border:2px solid #667eea;">
                                    Open EchoDoc →
                                </a>
                            </div>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8f9fa;padding:20px;text-align:center;">
                            <p style="color:#999;font-size:14px;margin:0;">
                                Happy listening! 🎵<br>
                                <strong>The EchoDoc Team</strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
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

/**
 * Send notification to admin when new user signs up
 */
function sendNewUserNotification($username, $userEmail, $fullName = '') {
    $config = getEmailConfig();
    $adminEmail = env('ADMIN_EMAIL', $config['from_email']);
    
    // Don't send if admin email is not configured
    if (empty($adminEmail) || $adminEmail === 'noreply@echodoc.app') {
        error_log("[EchoDoc] New user signup notification skipped - ADMIN_EMAIL not configured");
        return false;
    }
    
    $registrationTime = date('F j, Y \a\t g:i A');
    $displayName = $fullName ?: $username;
    
    $subject = "🎉 New User Signup: $displayName";
    
    $bodyHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background-color:#f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5;padding:40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 6px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg,#11998e 0%,#38ef7d 100%);padding:30px;text-align:center;">
                            <h1 style="color:#ffffff;margin:0;font-size:24px;">🎉 New User Registration</h1>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding:30px;">
                            <p style="color:#333;font-size:16px;line-height:1.6;margin-bottom:20px;">
                                A new user has just signed up for EchoDoc!
                            </p>
                            <table width="100%" style="background-color:#f8f9fa;border-radius:10px;padding:5px;">
                                <tr>
                                    <td style="padding:15px;border-bottom:1px solid #e9ecef;">
                                        <strong style="color:#666;">👤 Username:</strong>
                                        <span style="color:#333;float:right;">{$username}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:15px;border-bottom:1px solid #e9ecef;">
                                        <strong style="color:#666;">📧 Email:</strong>
                                        <span style="color:#333;float:right;">{$userEmail}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:15px;border-bottom:1px solid #e9ecef;">
                                        <strong style="color:#666;">📝 Full Name:</strong>
                                        <span style="color:#333;float:right;">{$displayName}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:15px;">
                                        <strong style="color:#666;">🕐 Registered:</strong>
                                        <span style="color:#333;float:right;">{$registrationTime}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8f9fa;padding:15px;text-align:center;">
                            <p style="color:#999;font-size:12px;margin:0;">
                                This is an automated notification from EchoDoc
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

    $bodyText = "New User Signup on EchoDoc\n\n"
              . "Username: $username\n"
              . "Email: $userEmail\n"
              . "Full Name: $displayName\n"
              . "Registered: $registrationTime\n";
    
    return sendEmail($adminEmail, $subject, $bodyHtml, $bodyText, 'Admin');
}
