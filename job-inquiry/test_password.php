<?php
require_once 'includes/config.php';

$test_password = 'password123';
$test_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

echo "Testing password verification:\n";
echo "Password: " . $test_password . "\n";
echo "Hash: " . $test_hash . "\n";
echo "Password verify result: " . (password_verify($test_password, $test_hash) ? 'TRUE' : 'FALSE') . "\n";

// Test creating a new hash
$new_hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "New hash: " . $new_hash . "\n";
echo "Verify new hash: " . (password_verify($test_password, $new_hash) ? 'TRUE' : 'FALSE') . "\n";
?>