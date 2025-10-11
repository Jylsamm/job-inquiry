<?php
/**
 * WorkConnect PH - Header Template
 */
if (basename($_SERVER['PHP_SELF']) == 'home.php') {
    echo '<link rel="stylesheet" href="assets/css/home.css">';
}
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . APP_NAME : APP_NAME; ?></title>
    
    <!-- CSS -->
     
    <link rel="stylesheet" href="assets/css/main.css">
<link rel="stylesheet" href="assets/css/auth.css">  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- JavaScript -->
    <script src="assets/js/config.js"></script>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/logo.png">
</head>
<body>
    <a href="#main-content" class="skip-link">Skip to main content</a>
    
    <?php if (!isset($hideNavigation) || !$hideNavigation): ?>
    <nav>
        <div class="brand">
            <img src="assets/images/logowhite.png" alt="WorkConnect PH Logo" class="logo">
            <strong><?php echo APP_NAME; ?></strong>
        </div>
        <ul>
            <li><a href="home.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active-link' : ''; ?>">Home</a></li>
            
                <li><a href="login.php" id="loginBtn">Log in / Sign in</a></li>
        </ul>
    </nav>
    <?php endif; ?>
    
    <main id="main-content">