<?php
/**
 * Database Configuration
 * Loads environment variables and establishes database connection
 */

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set environment variable if not already set
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Load .env file (look in parent directory and current directory)
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    loadEnv($envPath);
}

// Database configuration from environment variables
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'lamp_app');
define('DB_USER', getenv('DB_USER') ?: 'lamp_user');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', getenv('APP_NAME') ?: 'LAMP Stack Application');
define('APP_ENV', getenv('APP_ENV') ?: 'production');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN));

/**
 * Get database connection
 * @return PDO
 */
function getConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
        } catch (PDOException $e) {
            if (APP_DEBUG) {
                die("Database Connection Failed: " . $e->getMessage());
            } else {
                error_log("Database Connection Failed: " . $e->getMessage());
                die("Database connection error. Please contact the administrator.");
            }
        }
    }
    
    return $pdo;
}

/**
 * Test database connection
 * @return bool
 */
function testConnection() {
    try {
        $pdo = getConnection();
        $pdo->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        return false;
    }
}
