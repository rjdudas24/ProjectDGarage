<?php
// Start the session to access session variables
session_start();

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Set a logout message in a temporary cookie (will be shown in login page)
setcookie('logout_message', 'You have been successfully logged out.', time() + 60, '/');

// Redirect to login page
header("Location: login.php");
exit();
?>