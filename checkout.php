<?php
require 'db_connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$order_placed = false;
$order_id = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Get user information
    $street = mysqli_real_escape_string($connection, $_POST['address']);
    $city = mysqli_real_escape_string($connection, $_POST['city']);
    $state = mysqli_real_escape_string($connection, $_POST['state']);
    $zipcode = mysqli_real_escape_string($connection, $_POST['zipcode']);
    $contact_number = mysqli_real_escape_string($connection, $_POST['phone']);
    $payment_method = mysqli_real_escape_string($connection, $_POST['payment_method']);
    
    // Combine address components into one field
    $shipping_address = $street . ", " . $city . ", " . $state . " " . $zipcode;
    
    // Validate basic input
    if (empty($street) || empty($city) || empty($state) || empty($zipcode) || empty($contact_number) || empty($payment_method)) {
        $message = "❌ Please fill out all required fields";
    } else {
        // Start transaction
        $connection->begin_transaction();
        
        try {
            // First get cart items to calculate totals
            $cart_query = "
                SELECT c.cart_id, c.part_id, c.quantity, p.price, p.quantity as available_stock 
                FROM Cart c
                JOIN Parts p ON c.part_id = p.part_id
                WHERE c.user_id = ?
            ";
            
            $cart_stmt = $connection->prepare($cart_query);
            $cart_stmt->bind_param("i", $user_id);
            $cart_stmt->execute();
            $cart_result = $cart_stmt->get_result();
            
            if ($cart_result->num_rows === 0) {
                throw new Exception("Your cart is empty");
            }
            
            $subtotal = 0;
            $items = [];
            
            // Check stock and calculate total
            while ($item = $cart_result->fetch_assoc()) {
                // Check if item is still in stock
                if ($item['quantity'] > $item['available_stock']) {
                    throw new Exception("Some items in your cart are no longer available in the requested quantity");
                }
                
                $item_total = $item['quantity'] * $item['price'];
                $subtotal += $item_total;
                
                $items[] = [
                    'part_id' => $item['part_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item_total
                ];
            }
            
            // Calculate shipping
            if ($subtotal < 50) {
                $shipping_fee = 9.95;
            } else if ($subtotal < 100) {
                $shipping_fee = 5.95;
            } else {
                $shipping_fee = 0; // Free shipping for orders $100+
            }
            
            $total = $subtotal + $shipping_fee;
            
            // Create order - modified to match the orders table schema
            $order_stmt = $connection->prepare("
                INSERT INTO Orders (user_id, order_date, status, shipping_address, contact_number, subtotal, shipping_fee, total_amount, payment_method)
                VALUES (?, NOW(), 'pending', ?, ?, ?, ?, ?, ?)
            ");
            
            $order_stmt->bind_param("issddds", 
                $user_id, $shipping_address, $contact_number, $subtotal, $shipping_fee, $total, $payment_method
            );
            
            $order_stmt->execute();
            $order_id = $connection->insert_id;
            
            // Add order items
            foreach ($items as $item) {
                $item_stmt = $connection->prepare("
                    INSERT INTO Order_Items (order_id, part_id, quantity, price, total)
                    VALUES (?, ?, ?, ?, ?)
                ");
                
                $item_stmt->bind_param("iiidd", 
                    $order_id, $item['part_id'], $item['quantity'], $item['price'], $item['total']
                );
                
                $item_stmt->execute();
                
                // Update inventory
                $update_stock = $connection->prepare("
                    UPDATE Parts SET quantity = quantity - ? WHERE part_id = ?
                ");
                
                $update_stock->bind_param("ii", $item['quantity'], $item['part_id']);
                $update_stock->execute();
            }
            
            // Clear cart
            $clear_cart = $connection->prepare("DELETE FROM Cart WHERE user_id = ?");
            $clear_cart->bind_param("i", $user_id);
            $clear_cart->execute();
            
            // Commit transaction
            $connection->commit();
            $order_placed = true;
            $message = "✅ Order placed successfully!";
            
        } catch (Exception $e) {
            // Roll back the transaction in case of error
            $connection->rollback();
            $message = "❌ Error: " . $e->getMessage();
        }
    }
}

// If no order has been placed, fetch current cart items
if (!$order_placed) {
    // Fetch cart items
    $cart_query = "
        SELECT c.cart_id, c.quantity, p.part_id, p.part_name, p.brand, p.price, p.quantity as available_stock
        FROM Cart c
        JOIN Parts p ON c.part_id = p.part_id
        WHERE c.user_id = ?
        ORDER BY c.date_added DESC
    ";

    $cart_stmt = $connection->prepare($cart_query);
    $cart_stmt->bind_param("i", $user_id);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();

    // Calculate cart totals
    $subtotal = 0;
    $item_count = 0;

    while ($item = $cart_result->fetch_assoc()) {
        $subtotal += $item['price'] * $item['quantity'];
        $item_count += $item['quantity'];
    }

    // Calculate shipping fee based on subtotal
    if ($subtotal < 50) {
        $shipping_fee = 9.95;
    } else if ($subtotal < 100) {
        $shipping_fee = 5.95;
    } else {
        $shipping_fee = 0; // Free shipping for orders $100+
    }
    
    $total = $subtotal + $shipping_fee;
    
    // Reset the result pointer
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
}

// Fetch user info for pre-filling the form
if (!$order_placed) {
    $user_query = $connection->prepare("SELECT * FROM Users WHERE user_id = ?");
    $user_query->bind_param("i", $user_id);
    $user_query->execute();
    $user_data = $user_query->get_result()->fetch_assoc();
    
    // Map contact_number to phone if needed
    if (isset($user_data['contact_number']) && !isset($user_data['phone'])) {
        $user_data['phone'] = $user_data['contact_number'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout - Project D Garage</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .checkout-container {
            display: flex;
            gap: 30px;
            margin: 20px 0;
        }
        
        .checkout-form {
            flex: 3;
            background-color: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .order-summary {
            flex: 2;
            background-color: #f9f9f9;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            align-self: flex-start;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .section-title {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #a7001b;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        
        .place-order-btn {
            background-color: #a7001b;
            color: white;
            border: none;
            padding: 12px 25px;
            font-size: 1.1rem;
            font-weight: bold;
            border-radius: 50px;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        
        .place-order-btn:hover {
            background-color: #800015;
        }
        
        .place-order-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .order-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .item-meta {
            color: #666;
            font-size: 0.9rem;
        }
        
        .item-price {
            text-align: right;
            font-weight: bold;
        }
        
        .order-totals {
            margin-top: 20px;
        }
        
        .totals-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        
        .order-total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #a7001b;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }
        
        .payment-options label {
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            cursor: pointer;
        }
        
        .payment-options input[type="radio"] {
            margin-right: 10px;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .order-success {
            text-align: center;
            padding: 40px 20px;
        }
        
        .order-success h2 {
            color: #155724;
            margin-bottom: 20px;
        }
        
        .order-success p {
            margin-bottom: 15px;
            font-size: 1.1rem;
        }
        
        .order-id {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 1.2rem;
            margin: 15px 0;
            display: inline-block;
        }
        
        .continue-btn {
            display: inline-block;
            background-color: #a7001b;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 20px;
        }
        
        .continue-btn:hover {
            background-color: #800015;
        }
        
        .empty-cart-message {
            text-align: center;
            padding: 40px 20px;
        }
        
        .empty-cart-message p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <a href="index.php">
                <img class="name-plate" src="./assets/nameplate.png" alt="Project D Garage">
            </a>
            <div class="menu">
                <a href="index.php">Home</a>
                <a href="browse_parts.php">Browse Parts</a>
                <a href="new_arrivals.php">New Arrivals</a>
                <a href="orders.php">Orders</a>
                <?php if(isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'admin'): ?>
                    <a href="admin_dashboard.php">Admin Dashboard</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <a href="search_parts.php" class="header-btn">Search</a>
                <a href="cart.php" class="header-btn">View Cart</a>
                <div class="profile-icon">👤</div>
            </div>

            <h1 class="title-bar">Checkout</h1>
            
            <?php if(!empty($message)): ?>
                <div class="message <?php echo strpos($message, '✅') !== false ? 'success-message' : 'error-message'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if($order_placed): ?>
                <div class="order-success">
                    <h2>Thank you for your order!</h2>
                    <p>Your order has been placed successfully and is being processed.</p>
                    <p>Your order number is: <span class="order-id">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></span></p>
                    <p>We've sent a confirmation email with your order details.</p>
                    <a href="orders.php" class="continue-btn">View Orders</a>
                    <a href="browse_parts.php" class="continue-btn">Continue Shopping</a>
                </div>
            <?php elseif(isset($cart_result) && $cart_result->num_rows > 0): ?>
                <div class="checkout-container">
                    <div class="checkout-form">
                        <h2 class="section-title">Shipping Information</h2>
                        <form method="post" action="checkout.php">
                            <div class="form-group">
                                <label for="fullname">Full Name *</label>
                                <input type="text" id="fullname" name="fullname" value="<?php echo isset($user_data['first_name']) && isset($user_data['last_name']) ? htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']) : ''; ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" value="<?php echo isset($user_data['email']) ? htmlspecialchars($user_data['email']) : ''; ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label for="phone">Contact Number *</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo isset($user_data['phone']) ? htmlspecialchars($user_data['phone']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Street Address *</label>
                                <input type="text" id="address" name="address" value="<?php echo isset($user_data['address']) ? htmlspecialchars($user_data['address']) : ''; ?>" required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city">City *</label>
                                    <input type="text" id="city" name="city" value="<?php echo isset($user_data['city']) ? htmlspecialchars($user_data['city']) : ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="state">State *</label>
                                    <input type="text" id="state" name="state" value="<?php echo isset($user_data['state']) ? htmlspecialchars($user_data['state']) : ''; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="zipcode">ZIP Code *</label>
                                    <input type="text" id="zipcode" name="zipcode" value="<?php echo isset($user_data['zipcode']) ? htmlspecialchars($user_data['zipcode']) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <h2 class="section-title">Payment Method</h2>
                            <div class="form-group payment-options">
                                <label>
                                    <input type="radio" name="payment_method" value="credit_card" checked>
                                    Credit Card
                                </label>
                                <label>
                                    <input type="radio" name="payment_method" value="paypal">
                                    PayPal
                                </label>
                                <label>
                                    <input type="radio" name="payment_method" value="bank_transfer">
                                    Bank Transfer
                                </label>
                                <label>
                                    <input type="radio" name="payment_method" value="cash_on_delivery">
                                    Cash on Delivery
                                </label>
                            </div>
                            
                            <button type="submit" name="place_order" class="place-order-btn">Place Order</button>
                        </form>
                    </div>
                    
                    <div class="order-summary">
                        <h2 class="section-title">Order Summary</h2>
                        
                        <?php 
                        // Reset result pointer
                        $cart_stmt->execute();
                        $cart_result = $cart_stmt->get_result();
                        
                        while($item = $cart_result->fetch_assoc()): 
                            $item_total = $item['price'] * $item['quantity'];
                        ?>
                        <div class="order-item">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['part_name']); ?></div>
                                <div class="item-meta">
                                    <?php echo htmlspecialchars($item['brand']); ?> • 
                                    Qty: <?php echo $item['quantity']; ?>
                                </div>
                            </div>
                            <div class="item-price">$<?php echo number_format($item_total, 2); ?></div>
                        </div>
                        <?php endwhile; ?>
                        
                        <div class="order-totals">
                            <div class="totals-row">
                                <span>Subtotal:</span>
                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="totals-row">
                                <span>Shipping:</span>
                                <span><?php echo $shipping_fee > 0 ? '$' . number_format($shipping_fee, 2) : 'FREE'; ?></span>
                            </div>
                            <div class="totals-row order-total">
                                <span>Total:</span>
                                <span>$<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-cart-message">
                    <h2>Your cart is empty</h2>
                    <p>You need to add some items to your cart before checking out.</p>
                    <a href="orders.php" class="continue-btn">View Orders</a>
                    <a href="browse_parts.php" class="continue-btn">Browse Parts</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>