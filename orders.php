<?php
require 'db_connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$is_admin = isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'admin';
$message = "";

// Handle order status update (admin only)
if ($is_admin && isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['new_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($connection, $_POST['new_status']);
    
    $update_query = "UPDATE Orders SET status = ? WHERE order_id = ?";
    $update_stmt = $connection->prepare($update_query);
    $update_stmt->bind_param("si", $new_status, $order_id);
    
    if ($update_stmt->execute()) {
        $message = "✅ Order #" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . " status updated to " . ucfirst($new_status);
    } else {
        $message = "❌ Error updating order status";
    }
}

// Fetch orders based on account type
if ($is_admin) {
    // Admin sees all orders
    $orders_query = "
        SELECT o.order_id, o.user_id, CONCAT(u.first_name, ' ', u.last_name) AS customer_name, o.order_date, o.status, 
               o.shipping_address, o.contact_number, o.subtotal, o.shipping_fee, 
               o.total_amount, o.payment_method
        FROM Orders o
        JOIN Users u ON o.user_id = u.user_id
        ORDER BY o.order_date DESC
    ";
    $orders_stmt = $connection->prepare($orders_query);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
} else {
    // Regular user sees only their orders
    $orders_query = "
        SELECT order_id, order_date, status, shipping_address, 
               contact_number, subtotal, shipping_fee, total_amount, payment_method
        FROM Orders
        WHERE user_id = ?
        ORDER BY order_date DESC
    ";
    $orders_stmt = $connection->prepare($orders_query);
    $orders_stmt->bind_param("i", $user_id);
    $orders_stmt->execute();
    $orders_result = $orders_stmt->get_result();
}

// Function to get order items for a specific order
function getOrderItems($connection, $order_id) {
    $items_query = "
        SELECT oi.item_id, oi.part_id, oi.quantity, oi.price, oi.total,
               p.part_name, p.brand, p.part_number
        FROM Order_Items oi
        JOIN Parts p ON oi.part_id = p.part_id
        WHERE oi.order_id = ?
        ORDER BY oi.item_id
    ";
    
    $items_stmt = $connection->prepare($items_query);
    $items_stmt->bind_param("i", $order_id);
    $items_stmt->execute();
    return $items_stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_admin ? 'Manage Orders' : 'My Orders'; ?> - Project D Garage</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #a7001b;
            font-size: 2.5rem;
            margin-bottom: 10px;
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
        
        .no-orders {
            text-align: center;
            padding: 40px 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .order-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .order-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .order-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .order-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .order-meta-item {
            margin-right: 20px;
        }
        
        .order-meta-label {
            font-size: 0.85rem;
            color: #666;
            display: block;
        }
        
        .order-meta-value {
            font-weight: bold;
        }
        
        .order-status {
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .status-shipped {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        
        .status-delivered {
            background-color: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .order-body {
            padding: 20px;
        }
        
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .order-items th {
            text-align: left;
            padding: 10px;
            background-color: #f8f9fa;
            border-bottom: 2px solid #ddd;
        }
        
        .order-items td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
        }
        
        .order-items tr:last-child td {
            border-bottom: none;
        }
        
        .order-footer {
            padding: 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .order-totals {
            flex: 1;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding-right: 20px;
        }
        
        .total-label {
            color: #666;
        }
        
        .order-total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #a7001b;
            margin-top: 10px;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .shipping-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .shipping-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .admin-controls {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .admin-controls h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .status-form {
            display: flex;
            gap: 10px;
        }
        
        .status-select {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .update-btn {
            background-color: #089819;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .update-btn:hover {
            background-color: #067f14;
        }
        
        .empty-orders {
            text-align: center;
            padding: 40px;
        }
        
        .empty-orders p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }
        
        .shop-btn {
            display: inline-block;
            background-color: #a7001b;
            color: white;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .shop-btn:hover {
            background-color: #800015;
        }
        
        .user-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .user-info h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.1rem;
        }
        
        .filter-controls {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
        }
        
        .filter-controls label {
            font-weight: bold;
            margin-right: 5px;
        }
        
        .filter-controls select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-right: 10px;
        }
        
        @media (max-width: 768px) {
            .order-meta {
                flex-direction: column;
                gap: 10px;
            }
            
            .order-footer {
                flex-direction: column;
                gap: 20px;
            }
            
            .status-form {
                flex-direction: column;
            }
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
                <?php if(isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'admin'): ?>
                    <a href="admin_dashboard.php">Admin Dashboard</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="profile-icon">👤</div>
            </div>

            <div class="page-header">
                <h1><?php echo $is_admin ? 'Manage All Orders' : 'My Orders'; ?></h1>
                <p><?php echo $is_admin ? 'View and manage all customer orders on the platform' : 'View your order history and track your purchases'; ?></p>
                
                <?php if(!empty($message)): ?>
                    <div class="message <?php echo strpos($message, '✅') !== false ? 'success-message' : 'error-message'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($is_admin): ?>
            <div class="filter-controls">
                <div>
                    <label for="status-filter">Filter by Status:</label>
                    <select id="status-filter" onchange="filterOrders()">
                        <option value="all">All Orders</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label for="date-filter">Sort by:</label>
                    <select id="date-filter" onchange="filterOrders()">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                    </select>
                </div>
            </div>
            <?php endif; ?>

            <?php if($orders_result->num_rows > 0): ?>
                <?php while($order = $orders_result->fetch_assoc()): ?>
                    <div class="order-card" data-status="<?php echo htmlspecialchars($order['status']); ?>">
                        <div class="order-header">
                            <div>
                                <div class="order-title">Order #<?php echo str_pad($order['order_id'], 6, '0', STR_PAD_LEFT); ?></div>
                                <div class="order-date"><?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></div>
                            </div>
                            <div>
                                <span class="order-status status-<?php echo htmlspecialchars($order['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="order-body">
                            <?php if($is_admin): ?>
                            <div class="user-info">
                                <h3>Customer Information</h3>
                                <p><strong>Customer Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                <p><strong>User ID:</strong> <?php echo $order['user_id']; ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <div class="shipping-info">
                                <h3>Shipping Information</h3>
                                <p><strong>Address:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                                <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['contact_number']); ?></p>
                                <p><strong>Payment Method:</strong> <?php echo ucwords(str_replace('_', ' ', htmlspecialchars($order['payment_method']))); ?></p>
                            </div>
                            
                            <h3>Order Items</h3>
                            <table class="order-items">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $items_result = getOrderItems($connection, $order['order_id']);
                                    while($item = $items_result->fetch_assoc()):
                                    ?>
                                    <tr>
                                        <td>
                                            <div><strong><?php echo htmlspecialchars($item['part_name']); ?></strong></div>
                                            <div><?php echo htmlspecialchars($item['brand']); ?> - <?php echo htmlspecialchars($item['part_number']); ?></div>
                                        </td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($item['total'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            
                            <?php if($is_admin): ?>
                            <div class="admin-controls">
                                <h3>Update Order Status</h3>
                                <form class="status-form" method="post" action="orders.php">
                                    <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                    <select name="new_status" class="status-select">
                                        <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="update-btn">Update Status</button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="order-footer">
                            <div class="order-totals">
                                <div class="total-row">
                                    <span class="total-label">Subtotal:</span>
                                    <span>$<?php echo number_format($order['subtotal'], 2); ?></span>
                                </div>
                                <div class="total-row">
                                    <span class="total-label">Shipping:</span>
                                    <span><?php echo $order['shipping_fee'] > 0 ? '$' . number_format($order['shipping_fee'], 2) : 'FREE'; ?></span>
                                </div>
                                <div class="total-row order-total">
                                    <span>Total:</span>
                                    <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-orders">
                    <?php if($is_admin): ?>
                        <h2>No orders found</h2>
                        <p>There are currently no orders in the system.</p>
                    <?php else: ?>
                        <h2>You haven't placed any orders yet</h2>
                        <p>Browse our products and place your first order today!</p>
                        <a href="browse_parts.php" class="shop-btn">Shop Now</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function filterOrders() {
            const statusFilter = document.getElementById('status-filter').value;
            const dateFilter = document.getElementById('date-filter').value;
            const orders = document.querySelectorAll('.order-card');
            
            orders.forEach(order => {
                // Apply status filter
                if (statusFilter === 'all' || order.getAttribute('data-status') === statusFilter) {
                    order.style.display = 'block';
                } else {
                    order.style.display = 'none';
                }
            });
            
            // Apply date sorting if needed
            if (dateFilter === 'oldest') {
                const ordersList = document.querySelector('.main-content');
                const ordersArray = Array.from(orders);
                ordersArray.reverse().forEach(order => {
                    if (order.style.display !== 'none') {
                        ordersList.appendChild(order);
                    }
                });
            }
        }
    </script>
</body>
</html>