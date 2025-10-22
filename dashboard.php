<?php
/**
 * Redirect to role-specific dashboard
 */
require_once 'includes/config.php';

if (!isLoggedIn()) {
    // Not logged in, go to login page
    redirect('login.php');
}

// Determine the preferred target for each role. If the file does not exist, fall back to index.php
$role = getCurrentUserRole();
$targets = [
    'employer' => 'employer.html',
    'job_seeker' => 'jobseeker.html',
    'admin' => 'admin.php'
];

$target = $targets[$role] ?? 'index.php';
$fullPath = __DIR__ . DIRECTORY_SEPARATOR . $target;
if (!file_exists($fullPath)) {
    // Try alternative extension for dashboards
    $alt = preg_replace('/\.html$/', '.php', $target);
    if ($alt !== $target && file_exists(__DIR__ . DIRECTORY_SEPARATOR . $alt)) {
        $target = $alt;
    } else {
        // final fallback
        $target = 'index.php';
    }
}

redirect($target);
