<?php
/**
 * WorkConnect PH - Enhanced Database Configuration with Security
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'workconnect_ph');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'WorkConnect PH');
define('APP_URL', 'http://localhost/job-inquiry');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/job-inquiry/uploads/');

// Security configuration
define('CSRF_TOKEN_EXPIRY', 3600); // 1 hour

// Enhanced session security
// Configure session cookie params. Strip any port from the host to avoid invalid domain values.
$host = $_SERVER['HTTP_HOST'] ?? '';
$cookie_domain = $host ? preg_replace('/:\d+$/', '', $host) : '';
$is_secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
    'lifetime' => 86400, // 24 hours
    'path' => '/',
    // Use host without port (empty string will result in host-only cookie)
    'domain' => $cookie_domain,
    'secure' => $is_secure, // Only over HTTPS when available
    'httponly' => true, // Prevent JavaScript access
    // Use Lax to allow top-level navigations to carry the cookie while still providing CSRF protection
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID to prevent fixation
if (!isset($_SESSION['created'])) {
    session_regenerate_id(true);
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) {
    // Regenerate every 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// Error reporting (disable in production)
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Production
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

/**
 * Database connection function with enhanced error handling
 */
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            $conn->set_charset(DB_CHARSET);
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            
            // Robust API Detection for JSON Error Handling
            $is_api_call = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false;

            if ($is_api_call) {
                header('Content-Type: application/json');
                http_response_code(500);
                die(json_encode([
                    'success' => false,
                    'message' => 'Internal Server Error: Database connection failed.'
                ]));
            }
            
            // Show user-friendly HTML error for non-API pages
            if (php_sapi_name() !== 'cli') {
                die("
                    <div style='padding: 20px; background: #f8d7da; color: #721c24; border-radius: 5px; margin: 20px;'>
                        <h3>Database Connection Error</h3>
                        <p>Please ensure:</p>
                        <ol>
                            <li>XAMPP MySQL is running</li>
                            <li>Database 'workconnect_ph' exists</li>
                            <li>You've imported database/workconnect_ph.sql</li>
                        </ol>
                        <p><a href='database/workconnect_ph.sql' download>Download Database File</a></p>
                    </div>
                ");
            } else {
                die("Database connection error: " . $e->getMessage());
            }
        }
    }
    
    return $conn;
}

/**
 * CSRF Token Generation and Validation
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || 
        (time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['csrf_token_time'] = time();
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Check token expiration
    if ((time() - $_SESSION['csrf_token_time']) > CSRF_TOKEN_EXPIRY) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Secure input data with prepared statement compatibility
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Enhanced input validation
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= 8;
}

function validatePhone($phone) {
    return preg_match('/^\+?[\d\s\-\(\)]{10,}$/', $phone);
}

/**
 * JSON response helper
 */
function jsonResponse($success, $message = '', $data = null, $httpCode = 200) {
    if (!headers_sent()) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
    }
    
    $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => time()
    ];
    
    // Include CSRF token for forms if requested
    if ($success && isset($_GET['include_csrf'])) {
        $response['csrf_token'] = generateCsrfToken();
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Authentication functions
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentJobSeekerId() {
    if (!isLoggedIn() || getCurrentUserRole() !== 'job_seeker') return null;
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT job_seeker_id FROM job_seeker WHERE user_id = ?");
    $stmt->bind_param("i", getCurrentUserId());
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 1 ? $result->fetch_assoc()['job_seeker_id'] : null;
}

function getCurrentEmployerId() {
    if (!isLoggedIn() || getCurrentUserRole() !== 'employer') return null;
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT employer_id FROM employer WHERE user_id = ?");
    $stmt->bind_param("i", getCurrentUserId());
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 1 ? $result->fetch_assoc()['employer_id'] : null;
}

function redirect($page) {
    header("Location: " . APP_URL . "/$page");
    exit;
}

function hasRole($role) {
    return isLoggedIn() && getCurrentUserRole() === $role;
}

function requireRole($role) {
    if (!hasRole($role)) {
        header('HTTP/1.0 403 Forbidden');
        die('Access denied. Required role: ' . $role);
    }
}

function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    
    if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
        // Show errors in development
        return false;
    }
    
    // Don't show errors to users in production
    return true;
}
set_error_handler('customErrorHandler');

// Custom exception handler
function customExceptionHandler($exception) {
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    
    if (strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'An internal server error occurred.'
        ]);
    } else {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1>";
        if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
            echo "<p><strong>Error:</strong> " . $exception->getMessage() . "</p>";
            echo "<p><strong>File:</strong> " . $exception->getFile() . "</p>";
            echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
        }
    }
    exit;
}
set_exception_handler('customExceptionHandler');
?>