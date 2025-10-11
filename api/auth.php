<?php
/**
 * WorkConnect PH - Authentication API
 * Handles login, register, logout, and session check
 */

require_once '../includes/config.php';

// Set JSON header
header('Content-Type: application/json');

// Enable error reporting but don't display to users
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Get request method and input data
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// If no JSON input, try form data as fallback
if (!$input && $method === 'POST') {
    $input = $_POST;
}

try {
    // Get action from query string or input
    $action = $_GET['action'] ?? $input['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Action parameter is required');
    }

    $conn = getDBConnection();

    switch ($action) {
        case 'login':
            handleLogin($conn, $input);
            break;
            
        case 'register':
            handleRegister($conn, $input);
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        case 'check':
            handleSessionCheck();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage());
}

/**
 * Handle user login
 */
function handleLogin($conn, $input) {
    // Validate required fields
    if (!isset($input['email']) || !isset($input['password'])) {
        throw new Exception('Email and password are required');
    }
    
    $email = trim($input['email']);
    $password = $input['password'];
    
    // Basic validation
    if (empty($email) || empty($password)) {
        throw new Exception('Email and password are required');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    // Get user from database
    $stmt = $conn->prepare("
        SELECT user_id, email, password_hash, first_name, last_name, user_type, is_active 
        FROM user 
        WHERE email = ? AND is_active = TRUE
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception('Invalid email or password');
    }
    
    // Password verification
    if (!password_verify($password, $user['password_hash'])) {
        throw new Exception('Invalid email or password');
    }
    
    // Start session and set user data
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_type'] = $user['user_type'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['user_role'] = $user['user_type'];
    
    // Update last login
    $updateStmt = $conn->prepare("UPDATE user SET last_login = NOW() WHERE user_id = ?");
    $updateStmt->bind_param("i", $user['user_id']);
    $updateStmt->execute();
    
    jsonResponse(true, 'Login successful', $user['user_type']);
}

/**
 * Handle user registration
 */
/**
 * Handle user registration
 */
function handleRegister($conn, $input) {
    // Debug: Log the received input
    error_log("Registration input: " . print_r($input, true));
    
    // Required fields
    $required = ['email', 'password', 'first_name', 'last_name', 'user_type'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            throw new Exception("Field {$field} is required");
        }
    }
    
    $email = trim($input['email']);
    $password = $input['password'];
    $first_name = trim($input['first_name']);
    $last_name = trim($input['last_name']);
    $user_type = $input['user_type'];
    $phone = isset($input['phone']) ? trim($input['phone']) : null;
    $company_name = isset($input['company_name']) ? trim($input['company_name']) : null;
    
    // Validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }
    
    if (strlen($password) < 8) {
        throw new Exception('Password must be at least 8 characters');
    }
    
    if ($user_type === 'employer' && empty($company_name)) {
        throw new Exception('Company name is required for employers');
    }
    
    // Check if email already exists
    $checkStmt = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception('Email already exists');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user - WITHOUT company_name in user table
    $stmt = $conn->prepare("
        INSERT INTO user (email, password_hash, first_name, last_name, phone, user_type, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("ssssss", $email, $hashedPassword, $first_name, $last_name, $phone, $user_type);
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        
        // For employers, create employer record with company_name
        if ($user_type === 'employer') {
            if (empty($company_name)) {
                throw new Exception('Company name is required for employers');
            }
            
            $employerStmt = $conn->prepare("INSERT INTO employer (user_id, company_name) VALUES (?, ?)");
            $employerStmt->bind_param("is", $user_id, $company_name);
            
            if (!$employerStmt->execute()) {
                // If employer creation fails, delete the user to maintain data consistency
                $conn->query("DELETE FROM user WHERE user_id = $user_id");
                throw new Exception('Failed to create employer profile: ' . $conn->error);
            }
        }
        
        // For job seekers, create job_seeker record
        if ($user_type === 'job_seeker') {
            $jobSeekerStmt = $conn->prepare("INSERT INTO job_seeker (user_id) VALUES (?)");
            $jobSeekerStmt->bind_param("i", $user_id);
            
            if (!$jobSeekerStmt->execute()) {
                // If job seeker creation fails, delete the user to maintain data consistency
                $conn->query("DELETE FROM user WHERE user_id = $user_id");
                throw new Exception('Failed to create job seeker profile: ' . $conn->error);
            }
        }
        
        jsonResponse(true, 'Registration successful');
    } else {
        throw new Exception('Registration failed: ' . $conn->error);
    }
}

/**
 * Handle user logout
 */
function handleLogout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear all session variables
    $_SESSION = [];
    
    // Destroy session
    if (session_destroy()) {
        jsonResponse(true, 'Logout successful');
    } else {
        throw new Exception('Logout failed');
    }
}

/**
 * Check current session status
 */
function handleSessionCheck() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $logged_in = isset($_SESSION['user_id']);
    
    jsonResponse(true, 'Session check complete', [
        'logged_in' => $logged_in,
        'user_id' => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['user_type'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? null
    ]);
}
?>