-- EchoDoc Analytics & Email Tables
-- Run this SQL to add analytics and email features

USE echodoc_db;

-- User activity/analytics table
CREATE TABLE IF NOT EXISTS user_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(64) NOT NULL,
    event_type ENUM('page_view', 'login', 'logout', 'signup', 'upload', 'play_audio', 'download_mp3', 'translate') NOT NULL,
    event_data JSON DEFAULT NULL,
    page_url VARCHAR(500) DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_event_type (event_type),
    INDEX idx_created_at (created_at),
    INDEX idx_session (session_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Daily aggregated stats for faster dashboard queries
CREATE TABLE IF NOT EXISTS analytics_daily (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stat_date DATE NOT NULL,
    total_users INT DEFAULT 0,
    new_users INT DEFAULT 0,
    active_users INT DEFAULT 0,
    total_uploads INT DEFAULT 0,
    total_plays INT DEFAULT 0,
    total_downloads INT DEFAULT 0,
    total_page_views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY idx_date (stat_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email queue table
CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    to_email VARCHAR(255) NOT NULL,
    to_name VARCHAR(100) DEFAULT NULL,
    subject VARCHAR(255) NOT NULL,
    body_html LONGTEXT NOT NULL,
    body_text TEXT DEFAULT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    last_attempt TIMESTAMP NULL,
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Email templates table
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    subject VARCHAR(255) NOT NULL,
    body_html LONGTEXT NOT NULL,
    body_text TEXT DEFAULT NULL,
    variables TEXT DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default email templates
INSERT INTO email_templates (name, subject, body_html, body_text, variables) VALUES
('welcome', 'Welcome to EchoDoc!', 
'<!DOCTYPE html><html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
<h1 style="color: #495057;">Welcome to EchoDoc, {{username}}!</h1>
<p>Thank you for joining EchoDoc. You can now:</p>
<ul>
<li>Upload PDF and Word documents</li>
<li>Convert text to natural-sounding audio</li>
<li>Download audio as MP3 files</li>
</ul>
<p>Get started by uploading your first document!</p>
<a href="{{app_url}}" style="display: inline-block; padding: 12px 24px; background: #495057; color: white; text-decoration: none; border-radius: 5px;">Go to EchoDoc</a>
<p style="margin-top: 30px; color: #6c757d; font-size: 12px;">If you did not create this account, please ignore this email.</p>
</body></html>',
'Welcome to EchoDoc, {{username}}! Thank you for joining. Get started at {{app_url}}',
'username,app_url'),

('password_reset', 'Reset Your EchoDoc Password',
'<!DOCTYPE html><html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
<h1 style="color: #495057;">Password Reset Request</h1>
<p>Hi {{username}},</p>
<p>We received a request to reset your password. Click the button below to create a new password:</p>
<a href="{{reset_url}}" style="display: inline-block; padding: 12px 24px; background: #495057; color: white; text-decoration: none; border-radius: 5px;">Reset Password</a>
<p style="margin-top: 20px;">This link will expire in 1 hour.</p>
<p style="color: #6c757d; font-size: 12px;">If you did not request a password reset, please ignore this email.</p>
</body></html>',
'Hi {{username}}, Reset your password here: {{reset_url}} (expires in 1 hour)',
'username,reset_url'),

('contact_received', 'We Received Your Message',
'<!DOCTYPE html><html><body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
<h1 style="color: #495057;">Thank You for Contacting Us!</h1>
<p>Hi {{name}},</p>
<p>We have received your message and will get back to you within 24-48 hours.</p>
<p><strong>Your message:</strong></p>
<blockquote style="border-left: 3px solid #495057; padding-left: 15px; color: #6c757d;">{{message}}</blockquote>
<p>Best regards,<br>The EchoDoc Team</p>
</body></html>',
'Hi {{name}}, Thank you for contacting us. We will respond within 24-48 hours.',
'name,message');

-- Add email_notifications preference to user_settings
ALTER TABLE user_settings ADD COLUMN email_notifications TINYINT(1) DEFAULT 1 AFTER theme;
