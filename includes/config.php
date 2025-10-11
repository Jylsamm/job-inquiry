<?php
/**
 * WorkConnect PH - Database Configuration
 * MySQLi Connection for XAMPP Environment
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

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Database connection function
 * @return mysqli
 * @throws Exception if connection fails
 */
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                // If the connection fails, it throws an exception to handle the error cleanly below.
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            $conn->set_charset(DB_CHARSET);
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            
            // --- FIX: Robust API Detection for JSON Error Handling ---
            // Check if the script is being called via the API directory structure.
            $is_api_call = isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false;

            if ($is_api_call) {
                // For API calls, return a JSON error response (HTTP 500) and terminate cleanly.
                header('Content-Type: application/json');
                http_response_code(500);
                die(json_encode([
                    'success' => false,
                    'message' => 'Internal Server Error: Database connection failed. Please ensure MySQL is running.'
                ]));
            }
            // --- END FIX ---
            
            // Show user-friendly HTML error for non-API pages
            if (php_sapi_name() !== 'cli') {
                // This die outputs the HTML that was causing the parsing error on the API side.
                // It is kept for standard PHP pages.
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
 * Secure input data
 * @param string $data
 * @return string
 */
function sanitizeInput($data) {
    // Calling getDBConnection() here ensures the connection attempt happens early.
    $conn = getDBConnection();
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * JSON response helper
 * @param bool $success
 * @param string $message
 * @param mixed $data
 */
function jsonResponse($success, $message = '', $data = null) {
    // This is defined here because api/auth.php uses it to ensure clean JSON output.
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current user role
 * @return string|null
 */
function getCurrentUserRole() {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Get current user ID
 * @return int|null
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current job seeker ID
 * @return int|null
 */
function getCurrentJobSeekerId() {
    if (!isLoggedIn() || getCurrentUserRole() !== 'job_seeker') return null;
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT job_seeker_id FROM job_seeker WHERE user_id = ?");
    $stmt->bind_param("i", getCurrentUserId());
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 1 ? $result->fetch_assoc()['job_seeker_id'] : null;
}

/**
 * Get current employer ID
 * @return int|null
 */
function getCurrentEmployerId() {
    if (!isLoggedIn() || getCurrentUserRole() !== 'employer') return null;
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT employer_id FROM employer WHERE user_id = ?");
    $stmt->bind_param("i", getCurrentUserId());
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->num_rows === 1 ? $result->fetch_assoc()['employer_id'] : null;
}

/**
 * Redirect to specified page
 * @param string $page
 */
function redirect($page) {
    header("Location: " . APP_URL . "/$page");
    exit;
}

/**
 * Check if user has specific role
 * @param string $role
 * @return bool
 */
function hasRole($role) {
    return isLoggedIn() && getCurrentUserRole() === $role;
}

/**
 * Require specific role for access
 * @param string $role
 */
function requireRole($role) {
    if (!hasRole($role)) {
        header('HTTP/1.0 403 Forbidden');
        die('Access denied. Required role: ' . $role);
    }
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}
