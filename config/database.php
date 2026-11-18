<?php
/**
 * Database Configuration
 * Excellence Classes By Repswal
 * 
 * This file handles the PDO database connection.
 * Update credentials in this file or use environment variables.
 */

// Load environment variables if .env file exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = array_map('trim', explode('=', $line, 2));
        // Remove surrounding quotes
        $value = trim($value, '"\'');
        if (!array_key_exists($key, $_ENV)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'excellence_classes');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'Excellence Classes By Repswal');
define('APP_TAGLINE', 'Learn. Develop. Grow.');
define('APP_URL', getenv('APP_URL') ?: 'http://localhost/excellence-classes');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', getenv('APP_DEBUG') ? filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN) : true);

// Session configuration
define('SESSION_LIFETIME', 3600); // 1 hour
define('SESSION_NAME', 'ECBR_SESSION');

// Security
define('CSRF_TOKEN_LIFETIME', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_DURATION', 900); // 15 minutes in seconds

// File upload configuration
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip']);

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('CERTIFICATE_PATH', STORAGE_PATH . '/certificates');
define('DOCUMENT_PATH', STORAGE_PATH . '/documents');

/**
 * Get PDO Database Connection
 * 
 * @return PDO
 * @throws PDOException
 */
function getDBConnection(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    return $pdo;
}

/**
 * Get a website setting value from database
 * 
 * @param string $key Setting key
 * @param string|null $default Default value if not found
 * @return string|null
 */
function getSetting(string $key, ?string $default = null): ?string {
    static $settings = null;
    
    if ($settings === null) {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM website_settings");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
        } catch (PDOException $e) {
            error_log("Error loading settings: " . $e->getMessage());
            return $default;
        }
    }
    
    return $settings[$key] ?? $default;
}
