<?php
require 'db_connection.php';
session_start();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
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
                
                <button type="submit">Login</button>
                
                <a href="register.php" class="register-link">Don't have an account? Register here</a>
                <a href="index.php" class="back-link">Back to Home</a>
            </form>
        </div>
    </div>
</body>
</html>