<?php
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear Session Arrays
$_SESSION = array();

// Destroy Session Cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), 
        '', 
        time() - 42000,
        $params["path"], 
        $params["domain"],
        $params["secure"], 
        $params["httponly"]
    );
}

// Destroy Session
session_destroy();

// Cache Disable Headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

ob_end_clean();

// Redirect to login page
header("Location: login.php?msg=logout");
exit();
?>
