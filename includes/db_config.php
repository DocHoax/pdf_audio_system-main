<?php
/**
 * EchoDoc - Database Configuration
 * 
 * Database connection settings for MySQL
 */

// Check if running on Digital Ocean (environment variables set)
$isProduction = getenv('APP_ENV') === 'production';

if ($isProduction) {
    // Production - Use environment variables from Digital Ocean
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'echodoc_db');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
    define('DB_PORT', getenv('DB_PORT') ?: '3306');
} else {
    // Local development - Use local XAMPP settings
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'echodoc_db');
    define('DB_USER', 'root');
    define('DB_PASS', ''); // Default XAMPP has no password
    define('DB_PORT', '3306');
}

// PDO options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
]);

/**
 * Get database connection
 * @return PDO|null
 */
function getDbConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            return null;
        }
    }
    
    return $pdo;
}

/**
 * Check if database connection is available
 * @return bool
 */
function isDatabaseAvailable() {
    $pdo = getDbConnection();
    return $pdo !== null;
}
