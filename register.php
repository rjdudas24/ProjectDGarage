<?php
require 'db_connection.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $address = $_POST['address'];
    $birthday = $_POST['birthday'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $account_type = $_POST['account_type'];

    if ($account_type === 'admin') {
        $stmt = $connection->prepare("INSERT INTO Admins (first_name, last_name, address, birthday, contact_number, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    } else {
        $stmt = $connection->prepare("INSERT INTO Users (first_name, last_name, address, birthday, contact_number, email, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
    }

    $stmt->bind_param("sssssss", $first_name, $last_name, $address, $birthday, $contact_number, $email, $password);

    if ($stmt->execute()) {
        $message = "✅ Registration successful! <a href='login.php'>Login here</a>";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
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
            color: #089819;
        }
    </style>
</head>
<body>
    <?php if (!empty($message)) echo "<p class='message'>$message</p>"; ?>
    <form action="register.php" method="POST">
        <h2>User / Admin Registration</h2>

        <label>First Name:</label>
        <input type="text" name="first_name" required>

        <label>Last Name:</label>
        <input type="text" name="last_name" required>

        <label>Address:</label>
        <input type="text" name="address" required>

        <label>Birthday:</label>
        <input type="date" name="birthday" required>

        <label>Contact Number:</label>
        <input type="text" name="contact_number" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Account Type:</label>
        <select name="account_type" required>
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>

        <button type="submit" >Register</button>
    </form>
</body>
</html>
