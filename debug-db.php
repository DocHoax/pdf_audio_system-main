<?php
/**
 * Database Debug Script
 * Shows environment variables and tests connection
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Debug</h1>";
echo "<pre>";

// Check if env.php exists and load it
echo "=== Step 1: Loading environment ===\n";
if (file_exists(__DIR__ . '/env.php')) {
    require_once __DIR__ . '/env.php';
    echo "✓ env.php loaded\n";
} else {
    echo "✗ env.php not found\n";
}

// Check environment variables
echo "\n=== Step 2: Environment Variables ===\n";

// Check multiple ways to get env vars
$dbHost = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? null));
$dbPort = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? ($_SERVER['DB_PORT'] ?? null));
$dbName = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($_SERVER['DB_NAME'] ?? null));
$dbUser = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($_SERVER['DB_USER'] ?? null));
$dbPass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ($_SERVER['DB_PASS'] ?? null));

echo "DB_HOST: " . ($dbHost ? $dbHost : "NOT SET") . "\n";
echo "DB_PORT: " . ($dbPort ? $dbPort : "NOT SET") . "\n";
echo "DB_NAME: " . ($dbName ? $dbName : "NOT SET") . "\n";
echo "DB_USER: " . ($dbUser ? $dbUser : "NOT SET") . "\n";
echo "DB_PASS: " . ($dbPass ? "SET (hidden)" : "NOT SET") . "\n";

// Check using env() function if available
if (function_exists('env')) {
    echo "\n=== Using env() function ===\n";
    echo "DB_HOST via env(): " . env('DB_HOST', 'NOT SET') . "\n";
    echo "DB_PORT via env(): " . env('DB_PORT', 'NOT SET') . "\n";
    echo "DB_NAME via env(): " . env('DB_NAME', 'NOT SET') . "\n";
    echo "DB_USER via env(): " . env('DB_USER', 'NOT SET') . "\n";
    echo "DB_PASS via env(): " . (env('DB_PASS', '') ? "SET (hidden)" : "NOT SET") . "\n";
}

// Check for .env file
echo "\n=== Step 3: .env file check ===\n";
if (file_exists(__DIR__ . '/.env')) {
    echo "✓ .env file exists\n";
    echo "First few lines:\n";
    $lines = file(__DIR__ . '/.env');
    foreach (array_slice($lines, 0, 10) as $line) {
        $line = trim($line);
        if (strpos($line, 'PASS') !== false || strpos($line, 'SECRET') !== false || strpos($line, 'KEY') !== false) {
            echo preg_replace('/=.*/', '=***HIDDEN***', $line) . "\n";
        } else {
            echo $line . "\n";
        }
    }
} else {
    echo "✗ .env file NOT found\n";
    echo "  Production apps should use environment variables instead\n";
}

// Check $_ENV and $_SERVER
echo "\n=== Step 4: All DB_ variables in \$_ENV ===\n";
foreach ($_ENV as $key => $value) {
    if (strpos($key, 'DB_') === 0) {
        if (strpos($key, 'PASS') !== false) {
            echo "$key = ***HIDDEN***\n";
        } else {
            echo "$key = $value\n";
        }
    }
}

echo "\n=== Step 5: All DB_ variables in \$_SERVER ===\n";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'DB_') === 0) {
        if (strpos($key, 'PASS') !== false) {
            echo "$key = ***HIDDEN***\n";
        } else {
            echo "$key = $value\n";
        }
    }
}

// Try to connect
echo "\n=== Step 6: Database Connection Test ===\n";

if ($dbHost && $dbUser) {
    $port = $dbPort ?: '25060';
    $database = $dbName ?: 'defaultdb';
    
    echo "Attempting connection to:\n";
    echo "  Host: $dbHost\n";
    echo "  Port: $port\n";
    echo "  Database: $database\n";
    echo "  User: $dbUser\n";
    
    try {
        // DigitalOcean requires SSL
        $dsn = "mysql:host=$dbHost;port=$port;dbname=$database;charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ];
        
        // Try with SSL
        echo "\nTrying with SSL...\n";
        $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        echo "✓ CONNECTION SUCCESSFUL!\n";
        
        // Test query
        $result = $pdo->query("SELECT 1 as test")->fetch();
        echo "✓ Query test passed\n";
        
    } catch (PDOException $e) {
        echo "✗ Connection FAILED: " . $e->getMessage() . "\n";
        
        // Try without specifying database
        echo "\nTrying without database name...\n";
        try {
            $dsn2 = "mysql:host=$dbHost;port=$port;charset=utf8mb4";
            $pdo2 = new PDO($dsn2, $dbUser, $dbPass, $options);
            echo "✓ Connected without database name\n";
            echo "  You may need to create the database first\n";
        } catch (PDOException $e2) {
            echo "✗ Still failed: " . $e2->getMessage() . "\n";
        }
    }
} else {
    echo "✗ Cannot test - missing DB_HOST or DB_USER\n";
}

echo "\n=== Step 7: PHP Info ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";

echo "</pre>";
?>
