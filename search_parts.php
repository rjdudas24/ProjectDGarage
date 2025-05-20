<?php
require 'db_connection.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Initialize variables
$search_query = '';
$search_results = [];
$message = '';

// Process search when form is submitted
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = trim($_GET['search']);
    
    // Prepare the SQL query to search for parts by name, brand, or part number
    $sql = "SELECT p.part_id, p.part_name, p.part_number, p.brand, p.price, 
                   p.quantity, p.image_path, c.brand AS car_brand, c.model AS car_model
            FROM Parts p 
            LEFT JOIN Cars c ON p.car_id = c.car_id 
            WHERE p.part_name LIKE ? 
               OR p.brand LIKE ? 
               OR p.part_number LIKE ?
            ORDER BY p.part_name ASC";
    
    $stmt = $connection->prepare($sql);
    $search_param = "%" . $search_query . "%";
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $search_results[] = $row;
        }
    } else {
        $message = "No parts found matching your search criteria.";
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Parts - Project D Garage</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .search-container {
            margin: 20px 0;
            display: flex;
            justify-content: center;
        }
        
        .search-form {
            width: 100%;
            max-width: 600px;
            display: flex;
        }
        
        .search-input {
            flex: 1;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 16px;
        }
        
        .search-btn {
            background-color: #a7001b;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        .search-btn:hover {
            background-color: #800015;
        }
        
        .search-results {
            margin-top: 30px;
        }
        
        .result-count {
            margin-bottom: 20px;
            font-size: 18px;
            color: #555;
        }
        
        .no-results {
            text-align: center;
            padding: 40px;
            background-color: #f9f9f9;
            border-radius: 8px;
            color: #666;
        }
        
        .parts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .part-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            background-color: white;
        }
        
        .part-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .part-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background-color: #f1f1f1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .part-image img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .part-details {
            padding: 15px;
        }
        
        .part-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        
        .part-brand {
            color: #666;
            margin-bottom: 5px;
        }
        
        .part-number {
            color: #888;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .part-compatible {
            color: #444;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .part-price {
            font-weight: bold;
            color: #a7001b;
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .part-actions {
            display: flex;
            justify-content: space-between;
        }
        
        .view-details-btn, .add-to-cart-btn {
            padding: 8px 12px;
            border-radius: 4px;
            text-decoration: none;
            text-align: center;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .view-details-btn {
            background-color: #f1f1f1;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .view-details-btn:hover {
            background-color: #ddd;
        }
        
        .add-to-cart-btn {
            background-color: #089819;
            color: white;
            border: none;
        }
        
        .add-to-cart-btn:hover {
            background-color: #067f14;
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
                <a href="search_parts.php" class="header-btn">Search</a>
                <?php if(isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'user'): ?>
                    <a href="cart.php" class="header-btn">View Cart</a>
                <?php endif; ?>
                <div class="profile-icon">👤</div>
            </div>

            <h1 class="title-bar">Search Parts</h1>
            
            <div class="search-container">
                <form class="search-form" action="search_parts.php" method="GET">
                    <input type="text" name="search" class="search-input" placeholder="Search by part name, brand, or part number..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="search-btn">Search</button>
                </form>
            </div>
            
            <?php if (!empty($search_query)): ?>
                <div class="search-results">
                    <?php if (!empty($search_results)): ?>
                        <div class="result-count">
                            Found <?php echo count($search_results); ?> result<?php echo count($search_results) !== 1 ? 's' : ''; ?> for "<?php echo htmlspecialchars($search_query); ?>"
                        </div>
                        
                        <div class="parts-grid">
                            <?php foreach ($search_results as $part): ?>
                                <div class="part-card">
                                    <div class="part-image">
                                        <?php if(!empty($part['image_path']) && file_exists($part['image_path'])): ?>
                                            <img src="<?php echo $part['image_path']; ?>" alt="<?php echo htmlspecialchars($part['part_name']); ?>">
                                        <?php else: ?>
                                            <div style="color: #aaa;">No Image Available</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="part-details">
                                        <div class="part-name"><?php echo htmlspecialchars($part['part_name']); ?></div>
                                        <div class="part-brand">Brand: <?php echo htmlspecialchars($part['brand']); ?></div>
                                        <div class="part-number">Part #: <?php echo htmlspecialchars($part['part_number']); ?></div>
                                        <div class="part-compatible">
                                            Compatible with: 
                                            <?php 
                                            if (!empty($part['car_brand']) && !empty($part['car_model'])) {
                                                echo htmlspecialchars($part['car_brand'] . ' ' . $part['car_model']);
                                            } else {
                                                echo "Universal";
                                            }
                                            ?>
                                        </div>
                                        <div class="part-price">$<?php echo number_format($part['price'], 2); ?></div>
                                        <div class="part-actions">
                                            <a href="part_details.php?id=<?php echo $part['part_id']; ?>" class="view-details-btn">View Details</a>
                                            <?php if(isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'user'): ?>
                                                <?php if ($part['quantity'] > 0): ?>
                                                    <a href="add_to_cart.php?part_id=<?php echo $part['part_id']; ?>" class="add-to-cart-btn">Add to Cart</a>
                                                <?php else: ?>
                                                    <span class="add-to-cart-btn" style="background-color: #ccc;">Out of Stock</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <h3>No results found</h3>
                            <p>We couldn't find any parts matching "<?php echo htmlspecialchars($search_query); ?>". Please try a different search term.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="search-instructions" style="text-align: center; margin-top: 50px; color: #666;">
                    <h3>Start your search</h3>
                    <p>Enter a part name, brand, or part number in the search box above.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
