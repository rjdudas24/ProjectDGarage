<?php
require 'db_connection.php';
session_start();

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $account_type = $_POST['account_type'];

    if ($account_type === 'admin') {
        $stmt = $connection->prepare("SELECT id, password FROM Admins WHERE email = ?");
    } else {
        $stmt = $connection->prepare("SELECT id, password FROM Users WHERE email = ?");
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['account_type'] = $account_type;
            $message = "✅ Login successful! Redirecting...";

            // Redirect depending on user type
            echo "<script>
                setTimeout(function(){
                    window.location.href = '" . ($account_type === 'admin' ? 'admin_dashboard.php' : 'index.php') . "';
                }, 1500);
            </script>";
        } else {
            $message = "❌ Incorrect password.";
        }
    } else {
        $message = "❌ Account not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
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
            color: red;
        }
    </style>
</head>
<body>
    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
    <form action="login.php" method="POST">
        <h2>User / Admin Login</h2>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Account Type:</label>
        <select name="account_type" required>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>

        <button type="submit">Login</button>
    </form>
</body>
</html>
