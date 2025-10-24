<?php
/**
 * WorkConnect PH - Header Template with CSRF Protection
 */
if (basename($_SERVER['PHP_SELF']) == 'index.php') {
    echo '<link rel="stylesheet" href="assets/css/home.css">';
}
require_once 'config.php';

// Generate CSRF token for forms
$csrf_token = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Add this after existing meta tags -->
    <meta name="csrf-token" content="<?php echo $csrf_token; ?>">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50:  '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        secondary: '#764ba2',
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/custom.css">
    
    <!-- JavaScript -->
    <script src="assets/js/config.js"></script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/logo.png">
</head>
<body>
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:p-4 bg-white">Skip to main content</a>
    
    <?php if (!isset($hideNavigation) || !$hideNavigation): ?>
    <nav class="bg-primary-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center space-x-3">
                    <img src="assets/images/logowhite.png" alt="WorkConnect PH Logo" class="h-8 w-auto">
                    <span class="font-semibold text-lg"><?php echo APP_NAME; ?></span>
                </div>
                <div class="flex space-x-4">
                    <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' 
                        ? 'bg-primary-700 text-white' 
                        : 'text-white hover:bg-primary-500'; ?> px-3 py-2 rounded-md text-sm font-medium transition">
                        Home
                    </a>
                    
                    <?php if (isLoggedIn()): ?>
                        <a href="dashboard.php" class="text-white hover:bg-primary-500 px-3 py-2 rounded-md text-sm font-medium transition">
                            Dashboard
                        </a>
                        <a href="logout.php" class="text-white hover:bg-primary-500 px-3 py-2 rounded-md text-sm font-medium transition">
                            Logout (<?php echo $_SESSION['user_name'] ?? 'User'; ?>)
                        </a>
                    <?php else: ?>
                        <a href="login.php" id="loginBtn" class="bg-white text-primary-600 hover:bg-gray-100 px-4 py-2 rounded-md text-sm font-medium transition">
                            Log in / Sign in
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    
    <main id="main-content">