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

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $cart_id => $new_quantity) {
        $new_quantity = intval($new_quantity);
        
        if ($new_quantity <= 0) {
            // Remove item from cart if quantity is 0 or negative
            $delete_stmt = $connection->prepare("DELETE FROM Cart WHERE cart_id = ? AND user_id = ?");
            $delete_stmt->bind_param("ii", $cart_id, $user_id);
            $delete_stmt->execute();
        } else {
            // First check if we have enough stock
            $check_stmt = $connection->prepare("
                SELECT p.quantity, c.part_id 
                FROM Cart c
                JOIN Parts p ON c.part_id = p.part_id
                WHERE c.cart_id = ? AND c.user_id = ?
            ");
            $check_stmt->bind_param("ii", $cart_id, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $part_data = $check_result->fetch_assoc();
                
                if ($new_quantity <= $part_data['quantity']) {
                    // Update cart quantity
                    $update_stmt = $connection->prepare("UPDATE Cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
                    $update_stmt->bind_param("iii", $new_quantity, $cart_id, $user_id);
                    $update_stmt->execute();
                } else {
                    $message = "❌ Some items couldn't be updated due to insufficient stock.";
                }
            }
        }
    }
    
    if (empty($message)) {
        $message = "✅ Cart updated successfully!";
    }
}

// Handle item removal
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $cart_id = $_GET['remove'];
    
    $delete_stmt = $connection->prepare("DELETE FROM Cart WHERE cart_id = ? AND user_id = ?");
    $delete_stmt->bind_param("ii", $cart_id, $user_id);
    
    if ($delete_stmt->execute()) {
        $message = "✅ Item removed from cart!";
    } else {
        $message = "❌ Error removing item from cart.";
    }
}

// Fetch cart items
$cart_query = "
    SELECT c.cart_id, c.quantity, p.part_id, p.part_name, p.brand, p.price, p.quantity as available_stock, p.image_path
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

// Set shipping fee based on subtotal
$shipping_fee = 0; // Will be calculated after subtotal is known
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shopping Cart - Project D Garage</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .cart-container {
            margin: 20px 0;
        }
        
        .cart-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .cart-table th {
            background-color: #f1f1f1;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
        }
        
        .cart-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }
        
        .cart-product {
            display: flex;
            align-items: center;
        }
        
        .cart-product img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            margin-right: 15px;
            border-radius: 4px;
        }
        
        .product-info h3 {
            margin: 0 0 5px 0;
            color: #a7001b;
        }
        
        .product-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
        }
        
        .quantity-control input {
            width: 50px;
            text-align: center;
            margin: 0 10px;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .remove-btn {
            color: #a7001b;
            text-decoration: none;
            font-weight: bold;
        }
        
        .remove-btn:hover {
            text-decoration: underline;
        }
        
        .cart-summary {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .total-row {
            font-size: 1.2rem;
            font-weight: bold;
            color: #a7001b;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #ddd;
        }
        
        .cart-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .continue-shopping {
            padding: 10px 20px;
            background-color: #555;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        
        .continue-shopping:hover {
            background-color: #333;
        }
        
        .checkout-btn {
            padding: 12px 30px;
            background-color: #a7001b;
            color: white;
            font-weight: bold;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            font-size: 1.1rem;
        }
        
        .checkout-btn:hover {
            background-color: #800015;
        }
        
        .checkout-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        
        .empty-cart {
            text-align: center;
            padding: 50px 0;
        }
        
        .empty-cart h2 {
            color: #666;
        }
        
        .empty-cart p {
            margin: 20px 0;
        }
        
        .empty-cart a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #a7001b;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        
        .message {
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
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

        .cart-form{
            margin-bottom: 20px;
            max-width: 800px;
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
                <div class="profile-icon">👤</div>
            </div>

            <h1 class="title-bar">Your Shopping Cart</h1>
            
            <?php if(!empty($message)): ?>
                <div class="message <?php echo strpos($message, '✅') !== false ? 'success-message' : 'error-message'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if($cart_result->num_rows > 0): ?>
                <div class="cart-container">
                    <form method="post" action="cart.php" class="cart-form">
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($item = $cart_result->fetch_assoc()): 
                                    $item_subtotal = $item['price'] * $item['quantity'];
                                    $subtotal += $item_subtotal;
                                    $item_count += $item['quantity'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="cart-product">
                                            <?php if(!empty($item['image_path']) && file_exists($item['image_path'])): ?>
                                                <img src="<?php echo $item['image_path']; ?>" alt="<?php echo htmlspecialchars($item['part_name']); ?>">
                                            <?php else: ?>
                                                <div style="width:80px; height:80px; background:#eee; display:flex; align-items:center; justify-content:center; margin-right:15px;">No Image</div>
                                            <?php endif; ?>
                                            <div class="product-info">
                                                <h3><?php echo htmlspecialchars($item['part_name']); ?></h3>
                                                <p>Brand: <?php echo htmlspecialchars($item['brand']); ?></p>
                                                <p class="stock-info">Available: <?php echo $item['available_stock']; ?> in stock</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td>
                                        <div class="quantity-control">
                                            <input type="number" name="quantity[<?php echo $item['cart_id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['available_stock']; ?>">
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item_subtotal, 2); ?></td>
                                    <td>
                                        <a href="cart.php?remove=<?php echo $item['cart_id']; ?>" class="remove-btn" onclick="return confirm('Remove this item from your cart?')">Remove</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <div style="text-align: right; margin-bottom: 20px;">
                            <button type="submit" name="update_cart" class="btn btn-primary">Update Cart</button>
                        </div>
                    </form>
                    
                    <?php
                    // Calculate shipping fee based on subtotal
                    if ($subtotal < 50) {
                        $shipping_fee = 9.95;
                    } else if ($subtotal < 100) {
                        $shipping_fee = 5.95;
                    } else {
                        $shipping_fee = 0; // Free shipping for orders $100+
                    }
                    
                    $total = $subtotal + $shipping_fee;
                    ?>
                    
                    <div class="cart-summary">
                        <h2>Order Summary</h2>
                        <div class="summary-row">
                            <span>Items (<?php echo $item_count; ?>):</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="summary-row">
                            <span>Shipping:</span>
                            <span><?php echo $shipping_fee > 0 ? '$' . number_format($shipping_fee, 2) : 'FREE'; ?></span>
                        </div>
                        <div class="summary-row total-row">
                            <span>Total:</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        
                        <?php if ($shipping_fee > 0): ?>
                            <p style="color:#089819; font-size:0.9rem; margin-top:10px;">
                                <?php if ($subtotal < 50): ?>
                                    Add $<?php echo number_format(50 - $subtotal, 2); ?> more to qualify for reduced shipping!
                                <?php elseif ($subtotal < 100): ?>
                                    Add $<?php echo number_format(100 - $subtotal, 2); ?> more to qualify for FREE shipping!
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cart-actions">
                        <a href="browse_parts.php" class="continue-shopping">Continue Shopping</a>
                        <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <h2>Your cart is empty</h2>
                    <p>Looks like you haven't added any parts to your cart yet.</p>
                    <a href="browse_parts.php">Browse Parts</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
