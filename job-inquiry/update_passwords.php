<?php
require_once 'includes/config.php';

$conn = getDBConnection();

// Get all users
$result = $conn->query("SELECT user_id, email FROM user");
$users = $result->fetch_all(MYSQLI_ASSOC);

foreach ($users as $user) {
    $new_hash = password_hash('password123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE user SET password_hash = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_hash, $user['user_id']);
    $stmt->execute();
    
    echo "Updated password for: " . $user['email'] . " - Hash: " . $new_hash . "\n";
}

echo "All passwords updated successfully!\n";
?>