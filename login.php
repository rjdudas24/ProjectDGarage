<?php
require 'db_connection.php';
session_start();

$message = "";

// Check if a token cookie exists and try to auto-login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    
    // Find token in the database
    $token_stmt = $connection->prepare("SELECT user_id, account_type, expires_at FROM user_tokens WHERE token = ?");
    $token_stmt->bind_param("s", $token);
    $token_stmt->execute();
    $token_stmt->store_result();
    
    if ($token_stmt->num_rows === 1) {
        $token_stmt->bind_result($user_id, $account_type, $expires_at);
        $token_stmt->fetch();
        
        // Check if token has expired
        if (strtotime($expires_at) > time()) {
            // Token is valid, set session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['account_type'] = $account_type;
            
            // Update the expiration date to extend the session
            $new_expiry = date('Y-m-d H:i:s', strtotime('+30 days'));
            $update_stmt = $connection->prepare("UPDATE user_tokens SET expires_at = ? WHERE token = ?");
            $update_stmt->bind_param("ss", $new_expiry, $token);
            $update_stmt->execute();
            
            // Redirect based on account type
            if ($account_type === 'admin') {
                header("Location: admin_dashboard.php");
                exit();
            } else {
                header("Location: index.php");
                exit();
            }
        } else {
            // Token has expired, delete it
            $delete_stmt = $connection->prepare("DELETE FROM user_tokens WHERE token = ?");
            $delete_stmt->bind_param("s", $token);
            $delete_stmt->execute();
            
            // Remove the cookie
            setcookie('remember_token', '', time() - 3600, '/');
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    // First try to find the user in the Users table
    $user_stmt = $connection->prepare("SELECT user_id, password FROM Users WHERE email = ?");
    $user_stmt->bind_param("s", $email);
    $user_stmt->execute();
    $user_stmt->store_result();
    
    // Check if user found in Users table
    if ($user_stmt->num_rows === 1) {
        $user_stmt->bind_result($id, $hashed_password);
        $user_stmt->fetch();
        
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['account_type'] = 'user';
            
            // If remember me is checked, create a token
            if ($remember_me) {
                create_remember_token($id, 'user', $connection);
            }
            
            $message = "✅ Login successful! Redirecting...";
            
            echo "<script>
                setTimeout(function(){
                    window.location.href = 'index.php';
                }, 1500);
            </script>";
        } else {
            $message = "❌ Incorrect password.";
        }
    } else {
        // If not found in Users, try in Admins table
        $admin_stmt = $connection->prepare("SELECT admin_id, password FROM Admins WHERE email = ?");
        $admin_stmt->bind_param("s", $email);
        $admin_stmt->execute();
        $admin_stmt->store_result();
        
        if ($admin_stmt->num_rows === 1) {
            $admin_stmt->bind_result($id, $hashed_password);
            $admin_stmt->fetch();
            
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['account_type'] = 'admin';
                
                // If remember me is checked, create a token
                if ($remember_me) {
                    create_remember_token($id, 'admin', $connection);
                }
                
                $message = "✅ Login successful! Redirecting...";
                
                echo "<script>
                    setTimeout(function(){
                        window.location.href = 'admin_dashboard.php';
                    }, 1500);
                </script>";
            } else {
                $message = "❌ Incorrect password.";
            }
        } else {
            $message = "❌ Account not found.";
        }
    }
}

// Function to create and store a remember token
function create_remember_token($user_id, $account_type, $connection) {
    // Generate a secure random token
    $token = bin2hex(random_bytes(32));
    
    // Set expiration date (30 days)
    $expires_at = date('Y-m-d H:i:s', strtotime('+30 days'));
    
    // Delete any existing tokens for this user
    $delete_stmt = $connection->prepare("DELETE FROM user_tokens WHERE user_id = ? AND account_type = ?");
    $delete_stmt->bind_param("is", $user_id, $account_type);
    $delete_stmt->execute();
    
    // Store the new token
    $token_stmt = $connection->prepare("INSERT INTO user_tokens (user_id, account_type, token, expires_at) VALUES (?, ?, ?, ?)");
    $token_stmt->bind_param("isss", $user_id, $account_type, $token, $expires_at);
    $token_stmt->execute();
    
    // Set the cookie to expire in 30 days
    setcookie('remember_token', $token, time() + (86400 * 30), '/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Project D Garage</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #a7001b;
        }
        .message {
            text-align: center;
            font-weight: bold;
            margin: 20px;
            color: <?php echo strpos($message, "✅") !== false ? "#089819" : "red"; ?>;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #555;
            text-decoration: none;
        }
        .back-link:hover {
            color: #a7001b;
        }
        .register-link {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #089819;
            text-decoration: none;
        }
        .register-link:hover {
            text-decoration: underline;
        }
        .remember-me {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .remember-me input {
            margin-right: 8px;
            width: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <a href="index.php">
                <img class="name-plate" src="./assets/nameplate.png" alt="Project D Garage">
            </a>
        </div>
        
        <div class="main-content">

            <?php if(isset($_COOKIE['logout_message'])): ?>
                <div class="message success-message">
                    <?php echo htmlspecialchars($_COOKIE['logout_message']); ?>
                    <?php setcookie('logout_message', '', time() - 3600, '/'); // clear the cookie after displaying ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
            <form action="login.php" method="POST">
                <h2>Login to Project D Garage</h2>
                
                <label>Email:</label>
                <input type="email" name="email" required>
                
                <label>Password:</label>
                <input type="password" name="password" required>
                
                <div class="remember-me">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    <label for="remember_me">Remember me</label>
                </div>
                
                <button type="submit">Login</button>
                
                <a href="register.php" class="register-link">Don't have an account? Register here</a>
                <a href="index.php" class="back-link">Back to Home</a>
            </form>
        </div>
    </div>
</body>
</html>