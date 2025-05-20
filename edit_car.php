<?php
require 'db_connection.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";
$car_data = array();

// Check if car ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$car_id = $_GET['id'];

// Fetch car data
$stmt = $connection->prepare("SELECT * FROM Cars WHERE car_id = ?");
$stmt->bind_param("i", $car_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$car_data = $result->fetch_assoc();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $msrp = !empty($_POST['msrp']) ? $_POST['msrp'] : null;
    $engine = !empty($_POST['engine']) ? $_POST['engine'] : null;
    $power = !empty($_POST['power']) ? $_POST['power'] : null;
    $torque = !empty($_POST['torque']) ? $_POST['torque'] : null;
    $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
    $top_speed = !empty($_POST['top_speed']) ? $_POST['top_speed'] : null;
    $zero_to_sixty = !empty($_POST['zero_to_sixty']) ? $_POST['zero_to_sixty'] : null;
    $quarter_mile = !empty($_POST['quarter_mile']) ? $_POST['quarter_mile'] : null;
    
    // Handle image upload if new image was provided
    $image_path = $car_data['image_path']; // Keep existing image by default
    
    if (isset($_FILES['car_image']) && $_FILES['car_image']['error'] == 0) {
        $upload_dir = 'uploads/cars/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['car_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('car_') . '.' . $file_extension;
        $target_file = $upload_dir . $filename;
        
        // Move uploaded file to target directory
        if (move_uploaded_file($_FILES['car_image']['tmp_name'], $target_file)) {
            // Delete old image if it exists
            if (!empty($car_data['image_path']) && file_exists($car_data['image_path'])) {
                unlink($car_data['image_path']);
            }
            $image_path = $target_file;
        }
    }
    
    // Update car in database
    $update_stmt = $connection->prepare("UPDATE Cars SET brand = ?, model = ?, year = ?, msrp = ?, engine = ?, power = ?, torque = ?, weight = ?, top_speed = ?, zero_to_sixty = ?, quarter_mile = ?, image_path = ? WHERE car_id = ?");
    $update_stmt->bind_param("ssisiiiiddssi", $brand, $model, $year, $msrp, $engine, $power, $torque, $weight, $top_speed, $zero_to_sixty, $quarter_mile, $image_path, $car_id);
    
    if ($update_stmt->execute()) {
        $message = "✅ Car updated successfully!";
        
        // Update car data after successful update
        $stmt->execute();
        $result = $stmt->get_result();
        $car_data = $result->fetch_assoc();
    } else {
        $message = "❌ Error updating car: " . $update_stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Car - Project D Garage</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .container {
            display: flex;
        }
        
        .main-content {
            padding: 20px;
            flex: 1;
        }
        
        h1 {
            color: #a7001b;
            margin-bottom: 20px;
        }
        
        .form-container {
            background-color: #f5f5f5;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s;
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
            text-decoration: none;
            display: inline-block;
            margin-left: 10px;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .preview-image {
            max-width: 150px;
            max-height: 150px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 5px;
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
            <h1>Edit Car</h1>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo strpos($message, '✅') !== false ? 'success-message' : 'error-message'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="edit_car.php?id=<?php echo $car_id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="brand">Brand:</label>
                            <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($car_data['brand']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="model">Model:</label>
                            <input type="text" id="model" name="model" value="<?php echo htmlspecialchars($car_data['model']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="year">Year:</label>
                            <input type="number" id="year" name="year" min="1900" max="2099" value="<?php echo $car_data['year']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="msrp">MSRP ($):</label>
                            <input type="number" id="msrp" name="msrp" step="0.01" min="0" value="<?php echo $car_data['msrp']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="engine">Engine:</label>
                            <input type="text" id="engine" name="engine" value="<?php echo htmlspecialchars($car_data['engine']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="power">Power (HP):</label>
                            <input type="number" id="power" name="power" min="0" value="<?php echo $car_data['power']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="torque">Torque (Nm):</label>
                            <input type="number" id="torque" name="torque" min="0" value="<?php echo $car_data['torque']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="weight">Weight (lbs):</label>
                            <input type="number" id="weight" name="weight" min="0" value="<?php echo $car_data['weight']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="top_speed">Top Speed (mph):</label>
                            <input type="number" id="top_speed" name="top_speed" min="0" step="0.1" value="<?php echo $car_data['top_speed']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="zero_to_sixty">0-60 mph (seconds):</label>
                            <input type="number" id="zero_to_sixty" name="zero_to_sixty" min="0" step="0.01" value="<?php echo $car_data['zero_to_sixty']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="quarter_mile">Quarter Mile (seconds):</label>
                            <input type="number" id="quarter_mile" name="quarter_mile" min="0" step="0.01" value="<?php echo $car_data['quarter_mile']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="car_image">Car Image:</label>
                            <input type="file" id="car_image" name="car_image" accept="image/*">
                            <?php if (!empty($car_data['image_path']) && file_exists($car_data['image_path'])): ?>
                                <div>
                                    <p>Current image:</p>
                                    <img src="<?php echo $car_data['image_path']; ?>" alt="Current Car Image" class="preview-image">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Update Car</button>
                        <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>