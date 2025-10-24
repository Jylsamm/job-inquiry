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
    
    $stmt = db_prepare_or_error($conn, "SELECT user_id, email, password_hash, first_name, last_name, user_type, is_active FROM user WHERE email = ?");
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
                $updateStmt = db_prepare_or_error($conn, "UPDATE user SET last_login = NOW() WHERE user_id = ?");
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
    $checkStmt = db_prepare_or_error($conn, "SELECT user_id FROM user WHERE email = ?");
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
if (!function_exists('hasRole')) {
    function hasRole($role) {
        return isLoggedIn() && getCurrentUserRole() === $role;
    }
}

/**
 * Require specific role for access
 * @param string $role
 */
if (!function_exists('requireRole')) {
    function requireRole($role) {
        if (!hasRole($role)) {
            header('HTTP/1.0 403 Forbidden');
            die('Access denied. Required role: ' . $role);
        }
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
function initiatePasswordReset($email) {
    $conn = getDBConnection();
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM user WHERE email = ? AND is_active = TRUE");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Delete any existing tokens for this user
        $deleteStmt = $conn->prepare("DELETE FROM password_reset WHERE user_id = ?");
        $deleteStmt->bind_param("i", $user['user_id']);
        $deleteStmt->execute();
        
        // Insert new token
        $insertStmt = $conn->prepare("INSERT INTO password_reset (user_id, token, expires_at) VALUES (?, ?, ?)");
        $insertStmt->bind_param("iss", $user['user_id'], $token, $expires);
        
        if ($insertStmt->execute()) {
            // In a real application, send email here
            $reset_url = APP_URL . "/reset-password.php?token=" . $token;
            
            // For development, return the URL
            return [
                'success' => true, 
                'message' => 'Password reset link has been sent to your email.',
                'debug_url' => $reset_url // Remove this in production
            ];
        }
    }
    
    // Always return success to prevent email enumeration
    return ['success' => true, 'message' => 'If the email exists, a reset link has been sent.'];
}

/**
 * Complete password reset
 */
function completePasswordReset($token, $new_password) {
    $conn = getDBConnection();
    
    // Validate token
    $stmt = $conn->prepare("
        SELECT pr.user_id, u.email 
        FROM password_reset pr 
        JOIN user u ON pr.user_id = u.user_id 
        WHERE pr.token = ? AND pr.expires_at > NOW() AND u.is_active = TRUE
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        
        // Update password
        $passwordHash = password_hash($new_password, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE user SET password_hash = ? WHERE user_id = ?");
        $updateStmt->bind_param("si", $passwordHash, $data['user_id']);
        
        if ($updateStmt->execute()) {
            // Delete used token
            $deleteStmt = $conn->prepare("DELETE FROM password_reset WHERE token = ?");
            $deleteStmt->bind_param("s", $token);
            $deleteStmt->execute();
            
            return ['success' => true, 'message' => 'Password has been reset successfully.'];
        }
    }
    
    return ['success' => false, 'message' => 'Invalid or expired reset token.'];
}

/**
 * Email verification
 */
function sendVerificationEmail($user_id, $email) {
    $conn = getDBConnection();
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Delete any existing tokens
    $deleteStmt = $conn->prepare("DELETE FROM email_verification WHERE user_id = ?");
    $deleteStmt->bind_param("i", $user_id);
    $deleteStmt->execute();
    
    // Insert new token
    $insertStmt = $conn->prepare("INSERT INTO email_verification (user_id, token, expires_at) VALUES (?, ?, ?)");
    $insertStmt->bind_param("iss", $user_id, $token, $expires);
    
    if ($insertStmt->execute()) {
        $verification_url = APP_URL . "/verify-email.php?token=" . $token;
        
        // In production, send actual email
        // For now, return the URL for testing
        return [
            'success' => true,
            'debug_url' => $verification_url // Remove in production
        ];
    }
    
    return ['success' => false, 'message' => 'Failed to generate verification email.'];
}

function verifyEmail($token) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        SELECT ev.user_id 
        FROM email_verification ev 
        JOIN user u ON ev.user_id = u.user_id 
        WHERE ev.token = ? AND ev.expires_at > NOW()
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $data = $result->fetch_assoc();
        
        // Mark email as verified
        $updateStmt = $conn->prepare("UPDATE user SET email_verified = TRUE WHERE user_id = ?");
        $updateStmt->bind_param("i", $data['user_id']);
        
        if ($updateStmt->execute()) {
            // Delete used token
            $deleteStmt = $conn->prepare("DELETE FROM email_verification WHERE token = ?");
            $deleteStmt->bind_param("s", $token);
            $deleteStmt->execute();
            
            return ['success' => true, 'message' => 'Email verified successfully.'];
        }
    }
    
    return ['success' => false, 'message' => 'Invalid or expired verification token.'];
}
?>