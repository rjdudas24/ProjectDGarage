<?php
require 'db_connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to add items to cart']);
    exit();
}

// Check if we received the required parameters
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['part_id']) && isset($_POST['quantity'])) {
    $user_id = $_SESSION['user_id'];
    $part_id = $_POST['part_id'];
    $quantity = intval($_POST['quantity']);
    
    // Validate quantity
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
        exit();
    }
    
    // Check if part exists and has enough stock
    $part_stmt = $connection->prepare("SELECT quantity, part_name FROM Parts WHERE part_id = ?");
    $part_stmt->bind_param("i", $part_id);
    $part_stmt->execute();
    $part_result = $part_stmt->get_result();
    
    if ($part_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Part not found']);
        exit();
    }
    
    $part_data = $part_result->fetch_assoc();
    
    if ($part_data['quantity'] < $quantity) {
        echo json_encode([
            'success' => false, 
            'message' => 'Not enough stock available. Only ' . $part_data['quantity'] . ' units available.'
        ]);
        exit();
    }
    
    // Check if item already exists in cart
    $check_stmt = $connection->prepare("SELECT cart_id, quantity FROM Cart WHERE user_id = ? AND part_id = ?");
    $check_stmt->bind_param("ii", $user_id, $part_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Update existing cart item
        $cart_data = $check_result->fetch_assoc();
        $new_quantity = $cart_data['quantity'] + $quantity;
        
        // Check if the new quantity exceeds available stock
        if ($new_quantity > $part_data['quantity']) {
            echo json_encode([
                'success' => false, 
                'message' => 'Cannot add more items. Cart would exceed available stock.'
            ]);
            exit();
        }
        
        $update_stmt = $connection->prepare("UPDATE Cart SET quantity = ? WHERE cart_id = ?");
        $update_stmt->bind_param("ii", $new_quantity, $cart_data['cart_id']);
        
        if ($update_stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => 'Cart updated! Added ' . $quantity . ' more ' . htmlspecialchars($part_data['part_name']) . ' to your cart.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating cart']);
        }
    } else {
        // Insert new cart item
        $insert_stmt = $connection->prepare("INSERT INTO Cart (user_id, part_id, quantity) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iii", $user_id, $part_id, $quantity);
        
        if ($insert_stmt->execute()) {
            echo json_encode([
                'success' => true, 
                'message' => htmlspecialchars($part_data['part_name']) . ' added to cart!'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error adding item to cart']);
        }
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
