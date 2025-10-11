<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

// Simple API test endpoint
$data = [
    'status' => 'API is working',
    'timestamp' => date('Y-m-d H:i:s'),
    'database' => [
        'connected' => getDBConnection() ? true : false,
        'host' => DB_HOST,
        'name' => DB_NAME
    ],
    'session' => [
        'logged_in' => isLoggedIn(),
        'user_id' => $_SESSION['user_id'] ?? null
    ]
];

echo json_encode([
    'success' => true,
    'message' => 'API test successful',
    'data' => $data
]);
?>