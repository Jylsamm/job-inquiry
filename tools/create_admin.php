<?php
/**
 * One-off admin creation tool (LOCAL USE ONLY)
 * Usage (browser/localhost only):
 *   http://localhost/job-inquiry/tools/create_admin.php?email=you@example.com&password=YourPass123&first=Your&last=Name
 * After use, DELETE this file.
 */

// Restrict to localhost for safety
$allowed_ips = ['127.0.0.1', '::1'];
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, $allowed_ips)) {
    http_response_code(403);
    echo "Forbidden: this tool can only be used from localhost. Your IP: " . htmlspecialchars($remote);
    exit;
}

require_once __DIR__ . '/../includes/config.php';

$email = $_GET['email'] ?? null;
$password = $_GET['password'] ?? null;
$first = $_GET['first'] ?? 'Admin';
$last = $_GET['last'] ?? 'User';

if (!$email || !$password) {
    echo "Usage: ?email=you@example.com&password=YourPass123 (optional: &first=First&last=Last)";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid email format.";
    exit;
}

if (strlen($password) < 6) {
    echo "Password must be at least 6 characters.";
    exit;
}

$conn = getDBConnection();

// Check if user exists
$check = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
$check->bind_param('s', $email);
$check->execute();
$res = $check->get_result();

$hash = password_hash($password, PASSWORD_DEFAULT);

if ($res && $res->num_rows === 1) {
    $row = $res->fetch_assoc();
    $user_id = $row['user_id'];
    // Update user to admin and set password
    $stmt = $conn->prepare("UPDATE user SET password_hash = ?, first_name = ?, last_name = ?, user_type = 'admin', is_active = TRUE, email_verified = TRUE WHERE user_id = ?");
    $stmt->bind_param('sssi', $hash, $first, $last, $user_id);
    if ($stmt->execute()) {
        echo "Success: Updated existing user (ID: $user_id) to admin and set password.\n";
    } else {
        echo "Failed to update user: " . htmlspecialchars($conn->error);
    }
} else {
    // Insert new admin user
    $stmt = $conn->prepare("INSERT INTO user (email, password_hash, first_name, last_name, user_type, is_active, email_verified, created_at) VALUES (?, ?, ?, ?, 'admin', TRUE, TRUE, NOW())");
    $stmt->bind_param('ssss', $email, $hash, $first, $last);
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        echo "Success: Created admin user (ID: $user_id, email: $email).\n";
    } else {
        echo "Failed to create user: " . htmlspecialchars($conn->error);
    }
}

echo "\nIMPORTANT: Delete tools/create_admin.php after use to avoid security risk.";

?>
