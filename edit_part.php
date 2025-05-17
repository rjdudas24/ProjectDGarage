<?php
require 'db_connection.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['account_type'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$message = "";
$part_data = array();

// Check if part ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: admin_dashboard.php");
    exit();
}

$part_id = $_GET['id'];

// Fetch part data
$stmt = $connection->prepare("SELECT part_id, part_name, part_number, brand, price, quantity, car_id, details, image_path, is_new_arrival FROM Parts WHERE part_id = ?");
$stmt->bind_param("i", $part_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: admin_dashboard.php");
    exit();
}

$part_data = $result->fetch_assoc();

// Fetch cars for dropdown
$car_options_query = "SELECT car_id, brand, model, year FROM Cars ORDER BY brand, model";
$car_options_result = $connection->query($car_options_query);
$car_options = [];
while ($car = $car_options_result->fetch_assoc()) {
    $car_options[$car['car_id']] = "{$car['brand']} {$car['model']} ({$car['year']})";
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $part_name = $_POST['part_name'];
    $part_number = $_POST['part_number'];
    $brand = $_POST['brand'];
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $car_id = !empty($_POST['car_id']) ? $_POST['car_id'] : null;
    $details = $_POST['details'];
    $is_new_arrival = isset($_POST['is_new_arrival']) ? $_POST['is_new_arrival'] : 0;
    
    // Check if part number exists (exclude current part)
    $check_stmt = $connection->prepare("SELECT part_id FROM Parts WHERE part_number = ? AND part_id != ?");
    $check_stmt->bind_param("si", $part_number, $part_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        $message = "❌ Error: Part number already exists in the database.";
    } else {
        // Handle image upload if new image was provided
        $image_path = $part_data['image_path']; // Keep existing image by default
        
        if (isset($_FILES['part_image']) && $_FILES['part_image']['error'] == 0) {
            $upload_dir = 'uploads/parts/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($_FILES['part_image']['name'], PATHINFO_EXTENSION);
            $filename = uniqid('part_') . '.' . $file_extension;
            $target_file = $upload_dir . $filename;
            
            // Move uploaded file to target directory
            if (move_uploaded_file($_FILES['part_image']['tmp_name'], $target_file)) {
                // Delete old image if it exists
                if (!empty($part_data['image_path']) && file_exists($part_data['image_path'])) {
                    unlink($part_data['image_path']);
                }
                $image_path = $target_file;
            }
        }
        
        // Update part in database
        $update_stmt = $connection->prepare("UPDATE Parts SET part_name = ?, part_number = ?, brand = ?, price = ?, quantity = ?, car_id = ?, details = ?, image_path = ?, is_new_arrival = ? WHERE part_id = ?");
        $update_stmt->bind_param("sssdisssii", $part_name, $part_number, $brand, $price, $quantity, $car_id, $details, $image_path, $is_new_arrival, $part_id);
        
        if ($update_stmt->execute()) {
            $message = "✅ Part updated successfully!";
            
            // Update part data after successful update
            $stmt->execute();
            $result = $stmt->get_result();
            $part_data = $result->fetch_assoc();
        } else {
            $message = "❌ Error updating part: " . $update_stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Part - Project D Garage</title>
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
        
        .full-width {
            grid-column: span 2;
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
            <h1>Edit Part</h1>
            
            <?php if (!empty($message)): ?>
                <div class="message <?php echo strpos($message, '✅') !== false ? 'success-message' : 'error-message'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <form action="edit_part.php?id=<?php echo $part_id; ?>" method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="part_name">Part Name:</label>
                            <input type="text" id="part_name" name="part_name" value="<?php echo htmlspecialchars($part_data['part_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="part_number">Part Number:</label>
                            <input type="text" id="part_number" name="part_number" value="<?php echo htmlspecialchars($part_data['part_number']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="brand">Brand:</label>
                            <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($part_data['brand']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Price ($):</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo $part_data['price']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">Quantity in Stock:</label>
                            <input type="number" id="quantity" name="quantity" min="0" value="<?php echo $part_data['quantity']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="car_id">Compatible Car (optional):</label>
                            <select id="car_id" name="car_id">
                                <option value="">Universal (fits all cars)</option>
                                <?php foreach($car_options as $id => $name): ?>
                                    <option value="<?php echo $id; ?>" <?php echo ($part_data['car_id'] == $id) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="is_new_arrival">Mark as New Arrival:</label>
                            <select id="is_new_arrival" name="is_new_arrival">
                                <option value="0" <?php echo ($part_data['is_new_arrival'] == 0) ? 'selected' : ''; ?>>No</option>
                                <option value="1" <?php echo ($part_data['is_new_arrival'] == 1) ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="part_image">Part Image:</label>
                            <input type="file" id="part_image" name="part_image" accept="image/*">
                            <?php if (!empty($part_data['image_path']) && file_exists($part_data['image_path'])): ?>
                                <div>
                                    <p>Current image:</p>
                                    <img src="<?php echo $part_data['image_path']; ?>" alt="Current Part Image" class="preview-image">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group full-width">
                            <label for="details">Details/Description:</label>
                            <textarea id="details" name="details"><?php echo htmlspecialchars($part_data['details']); ?></textarea>
                        </div>
                    </div>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn btn-primary">Update Part</button>
                        <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>