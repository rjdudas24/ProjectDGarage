<?php
require 'db_connection.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if part ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$part_id = $_GET['id'];
$message = "";

// Fetch part details
$stmt = $connection->prepare("SELECT * FROM Parts WHERE part_id = ?");
$stmt->bind_param("i", $part_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Part not found
    header("Location: admin_dashboard.php");
    exit();
}

$part = $result->fetch_assoc();

// Handle form submission for updating part
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $part_name = $_POST['part_name'];
    $part_number = $_POST['part_number'];
    $brand = $_POST['brand'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $car_id = !empty($_POST['car_id']) ? $_POST['car_id'] : null;
    $category = $_POST['category'];
    $details = $_POST['details'];
    
    // Handle image upload if new image is provided
    $image_path = $part['image_path']; // Default to existing image path
    
    if (isset($_FILES['part_image']) && $_FILES['part_image']['size'] > 0) {
        $target_dir = "uploads/parts/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['part_image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('part_') . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['part_image']['tmp_name'], $target_file)) {
            // If successful, delete the old image
            if (!empty($part['image_path']) && file_exists($part['image_path'])) {
                unlink($part['image_path']);
            }
            
            $image_path = $target_file;
        } else {
            $message = "❌ Error uploading image. Part information was updated.";
        }
    }
    
    // Update part in database
    $stmt = $connection->prepare("UPDATE Parts SET 
                                part_name = ?, 
                                part_number = ?, 
                                brand = ?, 
                                price = ?, 
                                quantity = ?, 
                                car_id = ?, 
                                category = ?, 
                                details = ?, 
                                is_new_arrival = ?,
                                image_path = ? 
                                WHERE part_id = ?");
    
    $stmt->bind_param("sssdiissisi", 
                     $part_name, 
                     $part_number, 
                     $brand, 
                     $price, 
                     $quantity, 
                     $car_id, 
                     $category, 
                     $details, 
                     $is_new_arrival,
                     $image_path,
                     $part_id);
    
    if ($stmt->execute()) {
        if (empty($message)) {
            $message = "✅ Part updated successfully!";
        }
        
        // Refresh part data
        $stmt = $connection->prepare("SELECT * FROM Parts WHERE part_id = ?");
        $stmt->bind_param("i", $part_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $part = $result->fetch_assoc();
    } else {
        $message = "❌ Error updating part: " . $connection->error;
    }
}

// For part editing, fetch cars for dropdown
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
    <title>Edit Part - Project D Garage</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Form styles */
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
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            font-size: 1rem;
            transition: all 0.3s;
            margin-right: 10px;
        }
        
        .btn-primary {
            background-color: #089819;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #067f14;
        }
        
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .current-image {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-top: 10px;
        }
        
        .action-buttons {
            margin-top: 20px;
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
                <a href="admin_dashboard.php">Admin Dashboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <div class="profile-icon">👤</div>
            </div>

            <div class="page-header">
                <h1>Edit Part</h1>
                <p>Update details for part: <?php echo htmlspecialchars($part['part_name']); ?></p>
                
                <?php if(!empty($message)): ?>
                    <div class="message <?php echo strpos($message, '✅') !== false ? 'success-message' : 'error-message'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
            </div>

            <form action="edit_part.php?id=<?php echo $part_id; ?>" method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="part_name">Part Name:</label>
                        <input type="text" id="part_name" name="part_name" value="<?php echo htmlspecialchars($part['part_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="part_number">Part Number:</label>
                        <input type="text" id="part_number" name="part_number" value="<?php echo htmlspecialchars($part['part_number']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="brand">Brand:</label>
                        <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($part['brand']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price ($):</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $part['price']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity in Stock:</label>
                        <input type="number" id="quantity" name="quantity" min="0" value="<?php echo $part['quantity']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="car_id">Compatible Car (optional):</label>
                        <select id="car_id" name="car_id">
                            <option value="">Universal (fits all cars)</option>
                            <?php foreach($car_options as $id => $name): ?>
                                <option value="<?php echo $id; ?>" <?php echo ($part['car_id'] == $id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category" required>
                            <option value="">Select a category</option>
                            <option value="brake_pads" <?php echo ($part['category'] == 'brake_pads') ? 'selected' : ''; ?>>Brake Pads</option>
                            <option value="oil_filters" <?php echo ($part['category'] == 'oil_filters') ? 'selected' : ''; ?>>Oil Filters</option>
                            <option value="spark_plugs" <?php echo ($part['category'] == 'spark_plugs') ? 'selected' : ''; ?>>Spark Plugs</option>
                            <option value="air_filters" <?php echo ($part['category'] == 'air_filters') ? 'selected' : ''; ?>>Air Filters</option>
                            <option value="headlight_bulbs" <?php echo ($part['category'] == 'headlight_bulbs') ? 'selected' : ''; ?>>Headlights</option>
                            <option value="tailight_bulbs" <?php echo ($part['category'] == 'tailight_bulbs') ? 'selected' : ''; ?>>Tailights</option>
                            <option value="suspensions" <?php echo ($part['category'] == 'suspensions') ? 'selected' : ''; ?>>Suspensions</option>
                            <option value="signal_light_bulbs" <?php echo ($part['category'] == 'signal_light_bulbs') ? 'selected' : ''; ?>>Signal Lights</option>
                            <option value="fuel_filters" <?php echo ($part['category'] == 'fuel_filters') ? 'selected' : ''; ?>>Fuel Filters</option>
                            <option value="rims" <?php echo ($part['category'] == 'rims') ? 'selected' : ''; ?>>Rims</option>
                            <option value="tires" <?php echo ($part['category'] == 'tires') ? 'selected' : ''; ?>>Tires</option>
                            <option value="tools" <?php echo ($part['category'] == 'tools') ? 'selected' : ''; ?>>Tools</option>
                            <option value="other" <?php echo ($part['category'] == 'other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="part_image">Part Image:</label>
                        <input type="file" id="part_image" name="part_image" accept="image/*">
                        <?php if(!empty($part['image_path']) && file_exists($part['image_path'])): ?>
                            <p>Current image:</p>
                            <img src="<?php echo $part['image_path']; ?>" alt="Current part image" class="current-image">
                        <?php else: ?>
                            <p>No image currently available</p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- <div class="form-group">
                        <label for="is_new_arrival">Mark as New Arrival:</label>
                        <select id="is_new_arrival" name="is_new_arrival">
                            <option value="0" <?php echo ($part['is_new_arrival'] == 0) ? 'selected' : ''; ?>>No</option>
                            <option value="1" <?php echo ($part['is_new_arrival'] == 1) ? 'selected' : ''; ?>>Yes</option>
                        </select>
                    </div> -->
                    
                    <div class="form-group">
                        <label for="details">Details/Description:</label>
                        <textarea id="details" name="details"><?php echo htmlspecialchars($part['details']); ?></textarea>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button type="submit" class="btn btn-primary">Update Part</button>
                    <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
