<?php
/**
 * EchoDoc - Database Configuration
 * 
 * Database connection settings for MySQL
 */

// Prevent multiple includes
if (defined('DB_CONFIG_LOADED')) {
    return;
}
define('DB_CONFIG_LOADED', true);

// Load environment if not already loaded
if (!function_exists('env')) {
    require_once __DIR__ . '/../env.php';
}

// Database credentials from .env
if (!defined('DB_HOST')) define('DB_HOST', env('DB_HOST', 'localhost'));
if (!defined('DB_NAME')) define('DB_NAME', env('DB_NAME', 'echodoc_db'));
if (!defined('DB_USER')) define('DB_USER', env('DB_USER', 'root'));
if (!defined('DB_PASS')) define('DB_PASS', env('DB_PASS', ''));
if (!defined('DB_PORT')) define('DB_PORT', env('DB_PORT', '3306'));

// PDO options
if (!defined('DB_OPTIONS')) {
    define('DB_OPTIONS', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
}

/**
 * Get database connection
 * @return PDO|null
 */
if (!function_exists('getDbConnection')) {
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
}

/**
 * Check if database connection is available
 * @return bool
 */
if (!function_exists('isDatabaseAvailable')) {
    function isDatabaseAvailable() {
        $pdo = getDbConnection();
        return $pdo !== null;
    }
}
