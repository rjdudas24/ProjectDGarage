<?php
// Place this file in your project directory to debug the issue
require 'db_connection.php';
session_start();

echo "<h1>User ID Debug</h1>";

// Check session user ID
echo "<p>Session user_id: ";
if (isset($_SESSION['user_id'])) {
    echo $_SESSION['user_id'] . "</p>";
    
    // Verify user exists in database
    $stmt = $connection->prepare("SELECT * FROM Users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo "<p>User found in database:</p>";
        echo "<pre>";
        print_r($user);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'><strong>ERROR: User ID exists in session but not in database!</strong></p>";
    }
} else {
    echo "Not set!</p>";
    echo "<p style='color: red;'><strong>ERROR: No user_id in session</strong></p>";
}

// Show all session data
echo "<h2>All Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Display users table structure
echo "<h2>Users Table Structure:</h2>";
$result = $connection->query("DESCRIBE Users");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $key => $value) {
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Display cart table structure
echo "<h2>Cart Table Structure:</h2>";
$result = $connection->query("DESCRIBE Cart");
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $key => $value) {
        echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Show foreign key constraints
echo "<h2>Foreign Key Constraints:</h2>";
$result = $connection->query("
    SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE REFERENCED_TABLE_SCHEMA = DATABASE()
    AND REFERENCED_TABLE_NAME = 'Users'
    AND TABLE_NAME = 'Cart'
");

echo "<table border='1'>";
echo "<tr><th>Table</th><th>Column</th><th>Constraint</th><th>Referenced Table</th><th>Referenced Column</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    foreach ($row as $key => $value) {
        echo "<td>" . htmlspecialchars($value) . "</td>";
    }
    echo "</tr>";
}
echo "</table>";
?>