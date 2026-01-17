<?php
/**
 * Database Setup Script
 * Run this ONCE to set up the database tables
 * DELETE THIS FILE after setup!
 */

// Security: Only allow if a secret token is provided
$setupToken = 'echodoc_setup_2026'; // Change this to something random
if (!isset($_GET['token']) || $_GET['token'] !== $setupToken) {
    die('Access denied. Use: ?token=' . $setupToken);
}

require_once 'includes/db_config.php';

echo "<h1>EchoDoc Database Setup</h1>";
echo "<pre>";

$pdo = getDbConnection();

if (!$pdo) {
    die("ERROR: Could not connect to database. Check your environment variables:\n" .
        "DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS");
}

echo "✓ Database connection successful!\n\n";

// Create tables in correct order (users first, then tables with foreign keys)
$tables = [
    'users' => "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL DEFAULT '',
            full_name VARCHAR(100) DEFAULT NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            google_id VARCHAR(100) DEFAULT NULL,
            is_verified TINYINT(1) DEFAULT 0,
            verification_token VARCHAR(64) DEFAULT NULL,
            reset_token VARCHAR(64) DEFAULT NULL,
            reset_token_expires DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL,
            INDEX idx_email (email),
            INDEX idx_username (username),
            INDEX idx_google_id (google_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'user_documents' => "
        CREATE TABLE IF NOT EXISTS user_documents (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_type ENUM('pdf', 'docx') NOT NULL,
            file_size INT DEFAULT 0,
            extracted_text LONGTEXT,
            char_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_id (user_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'user_settings' => "
        CREATE TABLE IF NOT EXISTS user_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL UNIQUE,
            preferred_voice VARCHAR(50) DEFAULT 'Idera',
            default_volume DECIMAL(3,2) DEFAULT 1.00,
            theme VARCHAR(20) DEFAULT 'light',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'reading_history' => "
        CREATE TABLE IF NOT EXISTS reading_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            document_id INT NOT NULL,
            last_position INT DEFAULT 0,
            completed TINYINT(1) DEFAULT 0,
            read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (document_id) REFERENCES user_documents(id) ON DELETE CASCADE,
            INDEX idx_user_doc (user_id, document_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'user_analytics' => "
        CREATE TABLE IF NOT EXISTS user_analytics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            documents_uploaded INT DEFAULT 0,
            total_characters_read INT DEFAULT 0,
            total_audio_generated INT DEFAULT 0,
            total_listening_time INT DEFAULT 0,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'analytics_daily' => "
        CREATE TABLE IF NOT EXISTS analytics_daily (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            date DATE NOT NULL,
            documents_uploaded INT DEFAULT 0,
            characters_read INT DEFAULT 0,
            audio_generated INT DEFAULT 0,
            listening_time INT DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_date (user_id, date),
            INDEX idx_date (date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'email_queue' => "
        CREATE TABLE IF NOT EXISTS email_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            to_email VARCHAR(255) NOT NULL,
            to_name VARCHAR(100) DEFAULT NULL,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            last_attempt TIMESTAMP NULL,
            error_message TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            sent_at TIMESTAMP NULL,
            INDEX idx_status (status),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'email_templates' => "
        CREATE TABLE IF NOT EXISTS email_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL UNIQUE,
            subject VARCHAR(255) NOT NULL,
            body TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

$success = 0;
$errors = 0;

foreach ($tables as $tableName => $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ Created/verified table: $tableName\n";
        $success++;
    } catch (PDOException $e) {
        echo "✗ Error creating $tableName: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n";
echo "========================================\n";
echo "Setup complete!\n";
echo "Success: $success tables\n";
echo "Errors: $errors\n";
echo "========================================\n";

// Verify tables exist
echo "\nVerifying tables:\n";
$result = $pdo->query("SHOW TABLES");
$existingTables = $result->fetchAll(PDO::FETCH_COLUMN);
foreach ($existingTables as $table) {
    echo "  ✓ $table\n";
}

echo "\n⚠️ DELETE THIS FILE NOW for security!\n";
echo "</pre>";
?>
