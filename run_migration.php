<?php
/**
 * EchoDoc - Analytics Migration Script
 * 
 * Run this script once to update the database for enhanced analytics
 * Access via: https://echodoc-5vpfq.ondigitalocean.app/run_migration.php
 * 
 * DELETE THIS FILE AFTER RUNNING!
 */

require_once 'includes/db_config.php';

// Simple security - require a token
$token = $_GET['token'] ?? '';
$expectedToken = 'echodoc_migrate_2026'; // Change this for security

if ($token !== $expectedToken) {
    die('Access denied. Add ?token=echodoc_migrate_2026 to the URL');
}

echo "<pre style='font-family: monospace; background: #1e1e1e; color: #00ff00; padding: 20px; border-radius: 8px;'>";
echo "========================================\n";
echo "EchoDoc Analytics Migration\n";
echo "========================================\n\n";

$pdo = getDbConnection();

if (!$pdo) {
    die("ERROR: Could not connect to database!\n");
}

echo "✓ Connected to database\n\n";

$migrations = [
    [
        'name' => 'Alter event_type to VARCHAR',
        'sql' => "ALTER TABLE user_analytics MODIFY COLUMN event_type VARCHAR(50) NOT NULL",
        'check' => "SHOW COLUMNS FROM user_analytics LIKE 'event_type'"
    ],
    [
        'name' => 'Create voice_statistics table',
        'sql' => "CREATE TABLE IF NOT EXISTS voice_statistics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            voice_name VARCHAR(50) NOT NULL,
            stat_date DATE NOT NULL,
            use_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_voice_date (voice_name, stat_date),
            INDEX idx_date (stat_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'check' => "SHOW TABLES LIKE 'voice_statistics'"
    ],
    [
        'name' => 'Create language_statistics table',
        'sql' => "CREATE TABLE IF NOT EXISTS language_statistics (
            id INT AUTO_INCREMENT PRIMARY KEY,
            language_code VARCHAR(10) NOT NULL,
            stat_date DATE NOT NULL,
            translation_count INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY idx_lang_date (language_code, stat_date),
            INDEX idx_date (stat_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        'check' => "SHOW TABLES LIKE 'language_statistics'"
    ]
];

$success = 0;
$failed = 0;

foreach ($migrations as $migration) {
    echo "Running: {$migration['name']}...\n";
    
    try {
        $pdo->exec($migration['sql']);
        echo "  ✓ Success\n\n";
        $success++;
    } catch (PDOException $e) {
        // Check if it's just a "already exists" type error
        if (strpos($e->getMessage(), 'Duplicate') !== false || 
            strpos($e->getMessage(), 'already exists') !== false) {
            echo "  ⚠ Already applied (skipped)\n\n";
            $success++;
        } else {
            echo "  ✗ Error: " . $e->getMessage() . "\n\n";
            $failed++;
        }
    }
}

echo "========================================\n";
echo "Migration Complete!\n";
echo "========================================\n";
echo "Successful: $success\n";
echo "Failed: $failed\n\n";

if ($failed === 0) {
    echo "✓ All migrations applied successfully!\n\n";
    echo "<span style='color: #ff6b6b;'>⚠ IMPORTANT: Delete this file (run_migration.php) now!</span>\n";
} else {
    echo "⚠ Some migrations failed. Check the errors above.\n";
}

echo "</pre>";

// Show current table structure
echo "<pre style='font-family: monospace; background: #2d2d2d; color: #ddd; padding: 20px; border-radius: 8px; margin-top: 20px;'>";
echo "Current user_analytics table structure:\n";
echo "----------------------------------------\n";

try {
    $stmt = $pdo->query("DESCRIBE user_analytics");
    $columns = $stmt->fetchAll();
    foreach ($columns as $col) {
        echo sprintf("%-20s %-30s %s\n", $col['Field'], $col['Type'], $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
    }
} catch (PDOException $e) {
    echo "Could not describe table: " . $e->getMessage();
}

echo "</pre>";
?>
