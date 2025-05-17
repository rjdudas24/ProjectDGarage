<?php
require 'db_connection.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Handle part deletion if requested
if (isset($_GET['delete_part']) && is_numeric($_GET['delete_part'])) {
    $part_id = $_GET['delete_part'];
    
    // Get image filename before deleting the part
    $stmt = $connection->prepare("SELECT image_path FROM Parts WHERE part_id = ?");
    $stmt->bind_param("i", $part_id);
    $stmt->execute();
    $stmt->bind_result($image_path);
    $stmt->fetch();
    $stmt->close();
    
    // Delete the part from database
    $stmt = $connection->prepare("DELETE FROM Parts WHERE part_id = ?");
    $stmt->bind_param("i", $part_id);
    $result = $stmt->execute();
    
    if ($result) {
        // Delete the image file if it exists
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        $message = "✅ Part deleted successfully!";
    } else {
        $message = "❌ Error deleting part.";
    }
}

// Handle car deletion if requested
if (isset($_GET['delete_car']) && is_numeric($_GET['delete_car'])) {
    $car_id = $_GET['delete_car'];
    
    // Get image filename before deleting the car
    $stmt = $connection->prepare("SELECT image_path FROM Cars WHERE car_id = ?");
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $stmt->bind_result($image_path);
    $stmt->fetch();
    $stmt->close();
    
    // Delete the car from database
    $stmt = $connection->prepare("DELETE FROM Cars WHERE car_id = ?");
    $stmt->bind_param("i", $car_id);
    $result = $stmt->execute();
    
    if ($result) {
        // Delete the image file if it exists
        if ($image_path && file_exists($image_path)) {
            unlink($image_path);
        }
        $message = "✅ Car deleted successfully!";
    } else {
        $message = "❌ Error deleting car.";
    }
}

// Fetch all car parts
$parts_query = "SELECT p.part_id, p.part_name, p.part_number, p.brand, p.price, 
                       p.quantity, p.details, p.date_added, c.brand AS car_brand, 
                       c.model AS car_model, p.image_path 
                FROM Parts p 
                LEFT JOIN Cars c ON p.car_id = c.car_id 
                ORDER BY p.date_added DESC";
$parts_result = $connection->query($parts_query);

// Fetch all cars
$cars_query = "SELECT car_id, brand, model, year, image_path FROM Cars ORDER BY brand, model";
$cars_result = $connection->query($cars_query);

// For part creation/editing, fetch cars for dropdown
$car_options_query = "SELECT car_id, brand, model, year FROM Cars ORDER BY brand, model";
$car_options_result = $connection->query($car_options_query);
$car_options = [];
while ($car = $car_options_result->fetch_assoc()) {
    $car_options[$car['car_id']] = "{$car['brand']} {$car['model']} ({$car['year']})";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Project D Garage</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Dashboard specific styles */
        .dashboard-header {
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
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
        
        .tab-container {
            margin-bottom: 30px;
            display: flex;
            border-bottom: 1px solid #ccc;
        }
        
        .tab {
            padding: 12px 24px;
            cursor: pointer;
            margin-right: 5px;
            border: 1px solid #ccc;
            border-bottom: none;
            border-radius: 5px 5px 0 0;
            background-color: #f1f1f1;
            transition: all 0.3s;
        }
        
        .tab:hover {
            background-color: #ddd;
        }
        
        .tab.active {
            background-color: #a7001b;
            color: white;
            border-color: #a7001b;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .btn {
            padding:4px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background-color: #089819;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #067f14;
        }
        
        .btn-edit {
            background-color: #089819;
            color: white;
        }
        
        .btn-edit:hover {
            background-color:rgb(48, 201, 66);
        }
        
        .btn-delete {
            background-color: #a7001b;
            color: white;
        }
        
        .btn-delete:hover {
            background-color:rgb(218, 31, 62);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .data-table th {
            background-color: #f1f1f1;
            padding: 12px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #ddd;
        }
        
        .data-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            vertical-align: middle;
        }

        .data-table .btn{
            padding: 8px 16px;
            line-height: 1;
            font-size: 1rem;
            width: 80px;
            margin-right: 10px;
        }
        
        .data-table tr:hover {
            background-color: #f5f5f5;
        }

        .data-table td.action-buttons{
            vertical-align: middle;
            padding:10px;
        }
        
        .data-table td.action-buttons :hover{
            scale: 1.1;
            transition: all 0.3s;
            cursor: pointer;
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-grid .form-group:last-child {
            grid-column: span 2;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .form-group textarea {
            min-height: 100px;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-50px);}
            to {opacity: 1; transform: translateY(0);}
        }
        
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close-modal:hover {
            color: #555;
        }
        
        .modal-title {
            color: #a7001b;
            margin-bottom: 20px;
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
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="profile-icon">👤</div>
            </div>

            <div class="dashboard-header">
                <h1>Admin Dashboard</h1>
                <p>Manage cars and parts inventory for Project D Garage</p>
                
                <?php if(isset($message)): ?>
                    <div class="message <?php echo strpos($message, '✅') !== false ? 'success-message' : 'error-message'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tab-container">
                <div class="tab active" onclick="openTab('parts')">Manage Parts</div>
                <div class="tab" onclick="openTab('cars')">Manage Cars</div>
                <div class="tab" onclick="openTab('add-part')">Add New Part</div>
                <div class="tab" onclick="openTab('add-car')">Add New Car</div>
            </div>

            <!-- Parts Management Tab -->
            <div id="parts" class="tab-content active">
                <h2>Car Parts Inventory</h2>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Part Number</th>
                            <th>Brand</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Compatible With</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($parts_result && $parts_result->num_rows > 0): ?>
                            <?php while($part = $parts_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if(!empty($part['image_path']) && file_exists($part['image_path'])): ?>
                                            <img src="<?php echo $part['image_path']; ?>" alt="<?php echo $part['part_name']; ?>" class="thumbnail">
                                        <?php else: ?>
                                            <div class="thumbnail" style="display:flex; align-items:center; justify-content:center; background:#eee;">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($part['part_name']); ?></td>
                                    <td><?php echo htmlspecialchars($part['part_number']); ?></td>
                                    <td><?php echo htmlspecialchars($part['brand']); ?></td>
                                    <td>$<?php echo number_format($part['price'], 2); ?></td>
                                    <td><?php echo $part['quantity']; ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($part['car_brand']) && !empty($part['car_model'])) {
                                            echo htmlspecialchars($part['car_brand'] . ' ' . $part['car_model']);
                                        } else {
                                            echo "Universal";
                                        }
                                        ?>
                                    </td>
                                    <td class="action-buttons">
                                        <a href="edit_part.php?id=<?php echo $part['part_id']; ?>" class="btn btn-edit">Edit</a>
                                        <a href="admin_dashboard.php?delete_part=<?php echo $part['part_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this part?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No parts found in the inventory.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cars Management Tab -->
            <div id="cars" class="tab-content">
                <h2>Car Models Inventory</h2>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th>Year</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($cars_result && $cars_result->num_rows > 0): ?>
                            <?php while($car = $cars_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <?php if(!empty($part['image_path']) && file_exists("../" . $part['image_path'])): ?>
                                            <img src="../<?php echo $part['image_path']; ?>" alt="<?php echo $part['part_name']; ?>" class="thumbnail">
                                        <?php else: ?>
                                            <div class="thumbnail" style="display:flex; align-items:center; justify-content:center; background:#eee;">No Image</div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($car['brand']); ?></td>
                                    <td><?php echo htmlspecialchars($car['model']); ?></td>
                                    <td><?php echo $car['year']; ?></td>
                                    <td class="action-buttons">
                                        <a href="edit_car.php?id=<?php echo $car['car_id']; ?>" class="btn btn-edit">Edit</a>
                                        <a href="admin_dashboard.php?delete_car=<?php echo $car['car_id']; ?>" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this car?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No cars found in the inventory.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Add Part Tab -->
            <div id="add-part" class="tab-content">
                <h2>Add New Part</h2>
                
                <form action="process_part.php" method="POST" enctype="multipart/form-data">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="part_name">Part Name:</label>
                            <input type="text" id="part_name" name="part_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="part_number">Part Number:</label>
                            <input type="text" id="part_number" name="part_number" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="brand">Brand:</label>
                            <input type="text" id="brand" name="brand" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price ($):</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">Quantity in Stock:</label>
                            <input type="number" id="quantity" name="quantity" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="car_id">Compatible Car (optional):</label>
                            <select id="car_id" name="car_id">
                                <option value="">Universal (fits all cars)</option>
                                <?php foreach($car_options as $id => $name): ?>
                                    <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <select id="category" name="category" required>
                                <option value="">Select a category</option>
                                <option value="brake_pads">Brake Pads</option>
                                <option value="oil_filters">Oil Filters</option>
                                <option value="spark_plugs">Spark Plugs</option>
                                <option value="air_filters">Air Filters</option>
                                <option value="headlight_bulbs">Headlight Bulbs</option>
                                <option value="tailight_bulbs">Tailight Bulbs</option>
                                <option value="cabin_light_bulbs">Cabin Light Bulbs</option>
                                <option value="signal_light_bulbs">Signal Light Bulbs</option>
                                <option value="fuel_filters">Fuel Filters</option>
                                <option value="rims">Rims</option>
                                <option value="tires">Tires</option>
                                <option value="tools">Tools</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="part_image">Part Image:</label>
                            <input type="file" id="part_image" name="part_image" accept="image/*">
                        </div>
                        
                        <div class="form-group">
                            <label for="is_new_arrival">Mark as New Arrival:</label>
                            <select id="is_new_arrival" name="is_new_arrival">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="details">Details/Description:</label>
                            <textarea id="details" name="details"></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Part</button>
                </form>
            </div>

            <!-- Add Car Tab -->
            <div id="add-car" class="tab-content">
                <h2>Add New Car</h2>
                
                <form action="process_car.php" method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="brand">Brand:</label>
                            <input type="text" id="brand" name="brand" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="model">Model:</label>
                            <input type="text" id="model" name="model" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="year">Year:</label>
                            <input type="number" id="year" name="year" min="1900" max="2099" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="msrp">MSRP ($):</label>
                            <input type="number" id="msrp" name="msrp" step="0.01" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="engine">Engine:</label>
                            <input type="text" id="engine" name="engine">
                        </div>
                        
                        <div class="form-group">
                            <label for="power">Power (HP):</label>
                            <input type="number" id="power" name="power" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="torque">Torque (Nm):</label>
                            <input type="number" id="torque" name="torque" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="weight">Weight (lbs):</label>
                            <input type="number" id="weight" name="weight" min="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="top_speed">Top Speed (mph):</label>
                            <input type="number" id="top_speed" name="top_speed" min="0" step="0.1">
                        </div>
                        
                        <div class="form-group">
                            <label for="zero_to_sixty">0-60 mph (seconds):</label>
                            <input type="number" id="zero_to_sixty" name="zero_to_sixty" min="0" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label for="quarter_mile">Quarter Mile (seconds):</label>
                            <input type="number" id="quarter_mile" name="quarter_mile" min="0" step="0.01">
                        </div>
                        
                        <div class="form-group">
                            <label for="car_image">Car Image:</label>
                            <input type="file" id="car_image" name="car_image" accept="image/*">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Add Car</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openTab(tabName) {
            // Hide all tab contents
            var tabContents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            // Deactivate all tabs
            var tabs = document.getElementsByClassName("tab");
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            
            // Show the selected tab content and mark the button as active
            document.getElementById(tabName).classList.add("active");
            
            // Find and activate the clicked tab
            var tabs = document.getElementsByClassName("tab");
            for (var i = 0; i < tabs.length; i++) {
                if (tabs[i].textContent.toLowerCase().includes(tabName.toLowerCase())) {
                    tabs[i].classList.add("active");
                }
            }
        }
    </script>
</body>
</html>
