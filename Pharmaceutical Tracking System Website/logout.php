<?php
/**
 * logout.php
 * Session Termination Module
 * Destroys all active PHP authentication states and redirects to the login screen.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy session cookie if set
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect back to login gateway
header("Location: index.php");
exit();
?>
