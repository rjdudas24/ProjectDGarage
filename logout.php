<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $account_type = $_SESSION['account_type'];
    
    // If token exists, delete from database
    if (isset($_COOKIE['remember_token'])) {
        require 'db_connection.php';
        
        $token = $_COOKIE['remember_token'];
        $stmt = $connection->prepare("DELETE FROM user_tokens WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        
        // Remove cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Clear session variables
    $_SESSION = array();
    
    // Destroy session
    session_destroy();
    
    // logout message
    setcookie('logout_message', '✅ You have been successfully logged out!', time() + 60, '/');
}

// Redirect to login page
header("Location: login.php");
exit();
?>