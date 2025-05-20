<?php
require 'db_connection.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get category from URL parameter and validate it
$category = isset($_GET['category']) ? $_GET['category'] : '';
$valid_categories = [
    'brake_pads', 'oil_filters', 'spark_plugs', 'air_filters', 
    'headlight_bulbs', 'tailight_bulbs', 'suspensions',
    'signal_light_bulbs', 'fuel_filters', 'rims', 'tires', 'tools', 'other'
];

// Default to 'other' if invalid category
if (!in_array($category, $valid_categories)) {
    $category = 'other';
}

// Get category display name
$category_display_names = [
    'brake_pads' => 'Brake Pads',
    'oil_filters' => 'Oil Filters',
    'spark_plugs' => 'Spark Plugs',
    'air_filters' => 'Air Filters',
    'headlight_bulbs' => 'Headlight Bulbs',
    'tailight_bulbs' => 'Tailight Bulbs',
    'suspensions' => 'Suspensions',
    'signal_light_bulbs' => 'Signal Light Bulbs',
    'fuel_filters' => 'Fuel Filters',
    'rims' => 'Rims',
    'tires' => 'Tires',
    'tools' => 'Tools',
    'other' => 'Other Parts'
];

$category_name = $category_display_names[$category];

// Fetch parts for this category
$stmt = $connection->prepare("SELECT p.part_id, p.part_name, p.part_number, p.brand, p.price, 
                            p.quantity, p.details, p.image_path, c.brand AS car_brand, 
                            c.model AS car_model
                            FROM Parts p 
                            LEFT JOIN Cars c ON p.car_id = c.car_id 
                            WHERE p.category = ?
                            ORDER BY p.part_name");
$stmt->bind_param("s", $category);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($category_name); ?> - Project D Garage</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="add-to-cart-styles.css">
    <script src="add-to-cart.js"></script>
    <style>
        .parts-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        
        .part-card {
            width: 300px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .part-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .part-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        
        .part-name {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #a7001b;
        }
        
        .part-details {
            margin-bottom: 5px;
            color: #333;
        }
        
        .part-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: #089819;
            margin: 10px 0;
        }
        
        .stock-status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .in-stock {
            background-color: #d4edda;
            color: #155724;
        }
        
        .low-stock {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .out-of-stock {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .add-to-cart-btn {
            width: 100%;
            padding: 8px;
            background-color: #a7001b;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .add-to-cart-btn:hover {
            background-color: #800015;
        }
        
        .add-to-cart-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        
        .no-parts {
            text-align: center;
            padding: 40px;
            font-size: 1.2rem;
            color: #666;
        }
        
        .breadcrumb {
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        
        .breadcrumb a {
            color: #a7001b;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
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
                <button class="header-btn">Search</button>
                <?php if(isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'user'): ?>
                    <a href="cart.php" class="header-btn">View Cart</a>
                <?php endif; ?>
                <div class="profile-icon">👤</div>
            </div>

            <div class="breadcrumb">
                <a href="index.php">Home</a> &gt; 
                <a href="browse_parts.php">Browse Parts</a> &gt; 
                <?php echo htmlspecialchars($category_name); ?>
            </div>

            <h1 class="title-bar"><?php echo htmlspecialchars($category_name); ?></h1>
            
            <?php if($result->num_rows > 0): ?>
                <div class="parts-container">
                    <?php while($part = $result->fetch_assoc()): ?>
                        <div class="part-card">
                            <?php if(!empty($part['image_path']) && file_exists($part['image_path'])): ?>
                                <img src="<?php echo $part['image_path']; ?>" alt="<?php echo htmlspecialchars($part['part_name']); ?>" class="part-image">
                            <?php else: ?>
                                <div class="part-image" style="display:flex; align-items:center; justify-content:center; background:#eee;">No Image</div>
                            <?php endif; ?>
                            
                            <div class="part-name"><?php echo htmlspecialchars($part['part_name']); ?></div>
                            
                            <div class="part-details">
                                <strong>Brand:</strong> <?php echo htmlspecialchars($part['brand']); ?><br>
                                <strong>Part Number:</strong> <?php echo htmlspecialchars($part['part_number']); ?>
                                <?php if(!empty($part['car_brand']) && !empty($part['car_model'])): ?>
                                    <br><strong>Compatible with:</strong> <?php echo htmlspecialchars($part['car_brand'] . ' ' . $part['car_model']); ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="part-price">$<?php echo number_format($part['price'], 2); ?></div>
                            
                            <?php
                            if($part['quantity'] > 10) {
                                echo '<div class="stock-status in-stock">In Stock</div>';
                            } elseif($part['quantity'] > 0) {
                                echo '<div class="stock-status low-stock">Low Stock: ' . $part['quantity'] . ' left</div>';
                            } else {
                                echo '<div class="stock-status out-of-stock">Out of Stock</div>';
                            }
                            ?>
                            
                            <button class="add-to-cart-btn" 
                                    data-part-id="<?php echo $part['part_id']; ?>" 
                                    <?php echo $part['quantity'] <= 0 ? 'disabled' : ''; ?>>
                                <?php echo $part['quantity'] > 0 ? 'Add to Cart' : 'Out of Stock'; ?>
                            </button>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="no-parts">
                    <p>No parts found in this category.</p>
                    <p>Please check back later or <a href="browse_parts.php">browse other categories</a>.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>