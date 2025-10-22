<?php
require_once '../includes/config.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

if (!$input && $method === 'POST') {
    $input = $_POST;
}

try {
    $action = $_GET['action'] ?? $input['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Action parameter is required');
    }

    $conn = getDBConnection();

    switch ($action) {
        case 'login':
            // Add CSRF check for login
            if (isset($input['csrf_token']) && !validateCsrfToken($input['csrf_token'])) {
                throw new Exception('Invalid security token. Please refresh the page.');
            }
            handleLogin($conn, $input);
            break;
            
        case 'register':
            // Add CSRF check for registration
            if (isset($input['csrf_token']) && !validateCsrfToken($input['csrf_token'])) {
                throw new Exception('Invalid security token. Please refresh the page.');
            }
            handleRegister($conn, $input);
            break;
            
        case 'logout':
            handleLogout();
            break;
            
        case 'check':
            handleSessionCheck();
            break;
            
        case 'forgot_password':
            handleForgotPassword($conn, $input);
            break;
            
        case 'reset_password':
            handleResetPassword($conn, $input);
            break;
            
        case 'verify_email':
            handleVerifyEmail($conn, $input);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    jsonResponse(false, $e->getMessage());
}

// ... your existing handleLogin, handleRegister functions ...
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
    
    // Return structured data to match frontend expectations
    jsonResponse(true, 'Login successful', ['role' => $user['user_type']]);
}

/**
 * Check current session status for front-end pages
 */
function handleSessionCheck() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $logged_in = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    $role = $_SESSION['user_role'] ?? null;

    jsonResponse(true, $logged_in ? 'User logged in' : 'Not logged in', [
        'logged_in' => $logged_in,
        'role' => $role
    ]);
}

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
 * Handle forgot password request
 */
function handleForgotPassword($conn, $input) {
    if (!isset($input['email'])) {
        throw new Exception('Email is required');
    }
    
    $email = trim($input['email']);
    
    if (!validateEmail($email)) {
        throw new Exception('Invalid email format');
    }
    
    $result = initiatePasswordReset($email);
    jsonResponse($result['success'], $result['message'], $result['debug_url'] ?? null);
}

/**
 * Handle password reset
 */
function handleResetPassword($conn, $input) {
    if (!isset($input['token']) || !isset($input['password'])) {
        throw new Exception('Token and password are required');
    }
    
    $token = $input['token'];
    $password = $input['password'];
    
    if (!validatePassword($password)) {
        throw new Exception('Password must be at least 8 characters');
    }
    
    $result = completePasswordReset($token, $password);
    jsonResponse($result['success'], $result['message']);
}

/**
 * Handle email verification
 */
function handleVerifyEmail($conn, $input) {
    if (!isset($input['token'])) {
        throw new Exception('Verification token is required');
    }
    
    $token = $input['token'];
    $result = verifyEmail($token);
    jsonResponse($result['success'], $result['message']);
}
?>