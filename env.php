<?php
/**
 * EchoDoc - Environment Loader
 * 
 * Simple .env file parser (no external dependencies)
 * Include this file at the top of your entry points
 */

function loadUpsunRelationshipsEnv() {
    $relationships = getenv('PLATFORM_RELATIONSHIPS');
    if (!$relationships) {
        return;
    }

    $data = json_decode($relationships, true);
    if (!is_array($data)) {
        return;
    }

    foreach ($data as $services) {
        if (!is_array($services)) {
            continue;
        }

        foreach ($services as $service) {
            if (!is_array($service)) {
                continue;
            }

            if (!isset($service['scheme']) || strpos($service['scheme'], 'mysql') === false) {
                continue;
            }

            $host = $service['host'] ?? '';
            $port = $service['port'] ?? 3306;
            $username = $service['username'] ?? '';
            $password = $service['password'] ?? '';
            $path = $service['path'] ?? '';

            if (!getenv('DB_HOST') && $host !== '') {
                putenv("DB_HOST=$host");
                $_ENV['DB_HOST'] = $host;
                $_SERVER['DB_HOST'] = $host;
            }
            if (!getenv('DB_PORT') && $port !== '') {
                putenv("DB_PORT=$port");
                $_ENV['DB_PORT'] = $port;
                $_SERVER['DB_PORT'] = $port;
            }
            if (!getenv('DB_USER') && $username !== '') {
                putenv("DB_USER=$username");
                $_ENV['DB_USER'] = $username;
                $_SERVER['DB_USER'] = $username;
            }
            if (!getenv('DB_PASS') && $password !== '') {
                putenv("DB_PASS=$password");
                $_ENV['DB_PASS'] = $password;
                $_SERVER['DB_PASS'] = $password;
            }
            if (!getenv('DB_NAME') && $path !== '') {
                putenv("DB_NAME=$path");
                $_ENV['DB_NAME'] = $path;
                $_SERVER['DB_NAME'] = $path;
            }

            return;
        }
    }
}

function loadEnv($path = null) {
    if ($path === null) {
        $path = __DIR__ . '/.env';
    }
    
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if (preg_match('/^(["\'])(.*)\\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            
            // Only set if not already defined in environment
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
    
    return true;
}

/**
 * Get environment variable with optional default
 */
function env($key, $default = null) {
    $value = getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    // Handle boolean strings
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
        case 'empty':
        case '(empty)':
            return '';
    }
    
    return $value;
}

// Auto-load Upsun relationships first, then local .env file
loadUpsunRelationshipsEnv();
loadEnv(__DIR__ . '/.env');
