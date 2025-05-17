<?php
require 'db_connection.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Process part form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $part_name = $_POST['part_name'];
    $part_number = $_POST['part_number'];
    $brand = $_POST['brand'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $car_id = !empty($_POST['car_id']) ? $_POST['car_id'] : null;
    $details = $_POST['details'];
    $is_new_arrival = isset($_POST['is_new_arrival']) ? $_POST['is_new_arrival'] : 0;
    
    // Handle image upload
    $image_path = null;
    if (isset($_FILES['part_image']) && $_FILES['part_image']['error'] == 0) {
        $upload_dir = 'uploads/parts/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['part_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('part_') . '.' . $file_extension;
        $target_file = $upload_dir . $filename;
        
        // Move uploaded file to target directory
        if (move_uploaded_file($_FILES['part_image']['tmp_name'], $target_file)) {
            $image_path = $target_file;
        }
    }
    
    // Check if part number already exists
    $check_stmt = $connection->prepare("SELECT part_id FROM Parts WHERE part_number = ?");
    $check_stmt->bind_param("s", $part_number);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $_SESSION['message'] = "❌ Error: Part number already exists in the database.";
        $_SESSION['message_type'] = "error";
        header("Location: admin_dashboard.php");
        exit();
    }
    
    // Insert part into database
    $stmt = $connection->prepare("INSERT INTO Parts (part_name, part_number, brand, price, quantity, car_id, details, image_path, is_new_arrival) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdiissi", $part_name, $part_number, $brand, $price, $quantity, $car_id, $details, $image_path, $is_new_arrival);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = "✅ Part added successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "❌ Error adding part: " . $stmt->error;
        $_SESSION['message_type'] = "error";
    }
    
    header("Location: admin_dashboard.php");
    exit();
}
?>
