<?php
require 'db_connection.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Initialize variables
$car_id = '';
$brand = '';
$model = '';
$year = '';
$engine = '';
$power = '';
$torque = '';
$weight = '';
$top_speed = '';
$zero_to_sixty = '';
$quarter_mile = '';
$msrp = '';
$image_path = '';
$message = '';

// Check if car ID is provided
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $car_id = $_GET['id'];
    
    // Fetch car details
    $stmt = $connection->prepare("SELECT * FROM Cars WHERE car_id = ?");
    $stmt->bind_param("i", $car_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $car = $result->fetch_assoc();
        $brand = $car['brand'];
        $model = $car['model'];
        $year = $car['year'];
        $engine = $car['engine'];
        $power = $car['power'];
        $torque = $car['torque'];
        $weight = $car['weight'];
        $top_speed = $car['top_speed'];
        $zero_to_sixty = $car['zero_to_sixty'];
        $quarter_mile = $car['quarter_mile'];
        $msrp = $car['msrp'];
        $image_path = $car['image_path'];
    } else {
        $message = "❌ Car not found.";
    }
    $stmt->close();
} else {
    header("Location: admin_dashboard.php");
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $engine = $_POST['engine'];
    $power = $_POST['power'];
    $torque = $_POST['torque'];
    $weight = $_POST['weight'];
    $top_speed = $_POST['top_speed'];
    $zero_to_sixty = $_POST['zero_to_sixty'];
    $quarter_mile = $_POST['quarter_mile'];
    $msrp = $_POST['msrp'];
    
    // Handle image upload
    $new_image_path = $image_path; // Default to existing image
    
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] === UPLOAD_ERR_OK) {
        // Create uploads directory if it doesn't exist
        $upload_dir = "uploads/cars/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION);
        $new_filename = strtolower(str_replace(' ', '_', $brand . '_' . $model . '_' . time() . '.' . $file_extension));
        $upload_path = $upload_dir . $new_filename;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['car_image']['tmp_name'], $upload_path)) {
            // Delete old image if exists and not a default image
            if (!empty($image_path) && file_exists($image_path) && strpos($image_path, 'default') === false) {
                unlink($image_path);
            }
            
            $new_image_path = $upload_path;
        } else {
            $message = "❌ Failed to upload image.";
        }
    }
    
    // Update car in database
    $stmt = $connection->prepare("UPDATE Cars SET brand = ?, model = ?, year = ?, engine = ?, 
                                  power = ?, torque = ?, weight = ?, top_speed = ?, 
                                  zero_to_sixty = ?, quarter_mile = ?, msrp = ?, image_path = ? 
                                  WHERE car_id = ?");
    $stmt->bind_param("ssssdddddddsi", $brand, $model, $year, $engine, $power, $torque, $weight, 
                      $top_speed, $zero_to_sixty, $quarter_mile, $msrp, $new_image_path, $car_id);
    
    if ($stmt->execute()) {
        $message = "✅ Car updated successfully!";
        
        // Refresh car data
        $stmt = $connection->prepare("SELECT * FROM Cars WHERE car_id = ?");
        $stmt->bind_param("i", $car_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $car = $result->fetch_assoc();
            $brand = $car['brand'];
            $model = $car['model'];
            $year = $car['year'];
            $engine = $car['engine'];
            $power = $car['power'];
            $torque = $car['torque'];
            $weight = $car['weight'];
            $top_speed = $car['top_speed'];
            $zero_to_sixty = $car['zero_to_sixty'];
            $quarter_mile = $car['quarter_mile'];
            $msrp = $car['msrp'];
            $image_path = $car['image_path'];
        }
    } else {
        $message = "❌ Error updating car: " . $stmt->error;
    }
    
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Car - Project D Garage</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Form Styles */
        .form-container {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .form-title {
            color: #a7001b;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
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
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #a7001b;
            outline: none;
            box-shadow: 0 0 5px rgba(167, 0, 27, 0.2);
        }
        
        .car-image-preview {
            width: 200px;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
            margin-top: 10px;
            border: 1px solid #ddd;
        }
        
        .submit-btn {
            background-color: #a7001b;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        .submit-btn:hover {
            background-color: #800015;
        }
        
        .message {
            padding: 12px 15px;
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
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #666;
            text-decoration: none;
            font-weight: bold;
        }
        
        .back-link:hover {
            color: #a7001b;
        }
        
        .specs-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
        
        .specs-title {
            font-size: 1.3rem;
            margin-bottom: 15px;
            color: #444;
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
                <a href="admin_dashboard.php">Admin Dashboard</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <a href="search_parts.php" class="header-btn">Search</a>
                <div class="profile-icon">👤</div>
            </div>

            <a href="admin_dashboard.php" class="back-link">← Back to Admin Dashboard</a>
            
            <div class="form-container">
                <h1 class="form-title">Edit Car Details</h1>
                
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo strpos($message, '✅') !== false ? 'success-message' : 'error-message'; ?>">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
                
                <form action="edit_car.php?id=<?php echo $car_id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="brand">Brand:</label>
                            <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($brand); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="model">Model:</label>
                            <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($model); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="year">Year:</label>
                            <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" min="1900" max="2099" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="msrp">MSRP ($):</label>
                            <input type="number" id="msrp" name="msrp" value="<?php echo htmlspecialchars($msrp); ?>" min="0" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="car_image">Car Image:</label>
                            <input type="file" id="car_image" name="car_image" accept="image/*">
                            <?php if (!empty($image_path)): ?>
                                <div style="margin-top: 10px;">
                                    <p>Current image:</p>
                                    <img src="<?php echo $image_path; ?>" alt="Car Image" class="car-image-preview">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="specs-section">
                        <h3 class="specs-title">Performance Specifications</h3>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="engine">Engine:</label>
                                <input type="text" id="engine" name="engine" value="<?php echo htmlspecialchars($engine); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="power">Power (HP):</label>
                                <input type="number" id="power" name="power" value="<?php echo htmlspecialchars($power); ?>" min="0" step="1">
                            </div>
                            
                            <div class="form-group">
                                <label for="torque">Torque (Nm):</label>
                                <input type="number" id="torque" name="torque" value="<?php echo htmlspecialchars($torque); ?>" min="0" step="1">
                            </div>
                            
                            <div class="form-group">
                                <label for="weight">Weight (lbs):</label>
                                <input type="number" id="weight" name="weight" value="<?php echo htmlspecialchars($weight); ?>" min="0" step="1">
                            </div>
                            
                            <div class="form-group">
                                <label for="top_speed">Top Speed (mph):</label>
                                <input type="number" id="top_speed" name="top_speed" value="<?php echo htmlspecialchars($top_speed); ?>" min="0" step="0.1">
                            </div>
                            
                            <div class="form-group">
                                <label for="zero_to_sixty">0-60 mph (seconds):</label>
                                <input type="number" id="zero_to_sixty" name="zero_to_sixty" value="<?php echo htmlspecialchars($zero_to_sixty); ?>" min="0" step="0.1">
                            </div>
                            
                            <div class="form-group">
                                <label for="quarter_mile">Quarter Mile (seconds):</label>
                                <input type="number" id="quarter_mile" name="quarter_mile" value="<?php echo htmlspecialchars($quarter_mile); ?>" min="0" step="0.1">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn">Update Car</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>