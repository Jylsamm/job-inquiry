<?php
/**
 * WorkConnect PH - Authentication Functions
 */

// config.php is included first, so this require_once should be clean.
require_once 'config.php';

/**
 * User login function
 * @param string $email
 * @param string $password
 * @return array
 */
function loginUser($email, $password) {
    // Force a connection check early to trigger robust error handling in config.php if DB is down
    $conn = getDBConnection();
    $email = sanitizeInput($email);
    
    $stmt = $conn->prepare("SELECT user_id, email, password_hash, first_name, last_name, user_type, is_active FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password_hash'])) {
            if ($user['is_active']) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['user_role'] = $user['user_type'];
                $_SESSION['logged_in'] = true;
                
                // Update last login
                $updateStmt = $conn->prepare("UPDATE user SET last_login = NOW() WHERE user_id = ?");
                $updateStmt->bind_param("i", $user['user_id']);
                $updateStmt->execute();
                
                return ['success' => true, 'message' => 'Login successful!', 'role' => $user['user_type']];
            } else {
                return ['success' => false, 'message' => 'Account is deactivated.'];
            }
        }
    }
    
    return ['success' => false, 'message' => 'Invalid email or password.'];
}

/**
 * User registration function
 * @param array $userData
 * @return array
 */
function registerUser($userData) {
    $conn = getDBConnection();
    
    // Validate required fields
    $required = ['email', 'password', 'first_name', 'last_name', 'user_type'];
    foreach ($required as $field) {
        if (empty($userData[$field])) {
            return ['success' => false, 'message' => "Missing required field: $field"];
        }
    }
    
    // Validate email
    if (!filter_var($userData['email'], FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format.'];
    }
    
    // Check if email exists
    $checkStmt = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
    $checkStmt->bind_param("s", $userData['email']);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already registered.'];
    }
    
    // Hash password
    $passwordHash = password_hash($userData['password'], PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO user (email, password_hash, first_name, last_name, phone, user_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", 
        $userData['email'], 
        $passwordHash, 
        $userData['first_name'], 
        $userData['last_name'], 
        $userData['phone'] ?? null, 
        $userData['user_type']
    );
    
    if ($stmt->execute()) {
        $user_id = $conn->insert_id;
        
        // Create profile based on user type
        if ($userData['user_type'] === 'job_seeker') {
            $profileStmt = $conn->prepare("INSERT INTO job_seeker (user_id) VALUES (?)");
            $profileStmt->bind_param("i", $user_id);
            $profileStmt->execute();
        } elseif ($userData['user_type'] === 'employer') {
            $profileStmt = $conn->prepare("INSERT INTO employer (user_id, company_name) VALUES (?, ?)");
            $companyName = $userData['company_name'] ?? $userData['first_name'] . ' ' . $userData['last_name'];
            $profileStmt->bind_param("is", $user_id, $companyName);
            $profileStmt->execute();
        }
        
        return ['success' => true, 'message' => 'Registration successful!', 'user_id' => $user_id];
    } else {
        return ['success' => false, 'message' => 'Registration failed. Please try again.'];
    }
}

/**
 * Logout user
 */
function logoutUser() {
    session_destroy();
    session_start();
    return ['success' => true, 'message' => 'Logged out successfully.'];
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
 * Get current user data
 * @return array|null
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    
    $conn = getDBConnection();
    $user_id = $_SESSION['user_id'];
    
    $stmt = $conn->prepare("SELECT user_id, email, first_name, last_name, phone, user_type, profile_picture FROM user WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}
// Note: Removed closing PHP tag to prevent accidental whitespace output.
