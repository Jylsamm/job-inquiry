<?php
require_once 'includes/config.php';

try {
    $db = getDBConnection();
    echo "<div style='
        margin:40px auto;
        width:fit-content;
        padding:20px;
        border:2px solid #28a745;
        border-radius:10px;
        background:#e8f5e9;
        color:#155724;
        font-family:Arial, sans-serif;
        text-align:center;
    '>
        ✅ <strong>Database connected successfully!</strong><br>
        Database: <b>" . DB_NAME . "</b><br>
        Host: <b>" . DB_HOST . "</b>
    </div>";
} catch (Exception $e) {
    echo "<div style='
        margin:40px auto;
        width:fit-content;
        padding:20px;
        border:2px solid #dc3545;
        border-radius:10px;
        background:#f8d7da;
        color:#721c24;
        font-family:Arial, sans-serif;
        text-align:center;
    '>
        ❌ <strong>Database connection failed!</strong><br>
        " . $e->getMessage() . "
    </div>";
}
?>
