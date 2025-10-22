<?php
/**
 * One-off admin password reset tool (LOCAL USE ONLY)
 * Usage (browser/localhost only):
 *   http://localhost/job-inquiry/tools/reset_admin_password.php?password=newpass
 * After use, delete this file.
 */

// Only allow requests from localhost for safety
$allowed_ips = ['127.0.0.1', '::1'];
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, $allowed_ips)) {
    http_response_code(403);
    echo "Forbidden: this tool can only be used from localhost. Your IP: " . htmlspecialchars($remote);
    exit;
}

require_once __DIR__ . '/../includes/config.php';

if (!isset($_GET['password']) || strlen($_GET['password']) < 6) {
    echo "Usage: ?password=NEWPASSWORD (min length 6).";
    exit;
}

$new = $_GET['password'];
$hash = password_hash($new, PASSWORD_DEFAULT);

$conn = getDBConnection();
$email = 'admin@workconnect.ph';

$stmt = $conn->prepare("UPDATE user SET password_hash = ? WHERE email = ?");
$stmt->bind_param('ss', $hash, $email);
if ($stmt->execute()) {
    echo "Success: admin password updated for {$email}. Please delete this file after use.";
} else {
    http_response_code(500);
    echo "Failed to update password: " . htmlspecialchars($conn->error);
}

?>
