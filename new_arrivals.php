<?php
// Start the session at the beginning
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit();
}

// User is logged in, proceed with the page
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>New Arrivals - Project Garage</title>
    <link rel="stylesheet" href="styles.css">
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
                <?php if(isset($_SESSION['account_type']) && $_SESSION['account_type'] === 'admin'): ?>
                    <a href="admin_dashboard.php">Admin Dashboard</a>
                <?php endif; ?>
                <a href="logout.php">Logout</a>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <button class="header-btn">Search</button>
                <button class="header-btn">Cart</button>
                <div class="profile-icon">👤</div>
            </div>

            <h1 class="title-bar">New Arrivals</h1>

            <!-- New Arrivals Grid -->
            <div class="parts-grid">
                <div class="car-item">
                    <img class="car-part" src="arrivals/spoiler.jpeg" alt="Performance Spoiler">
                    <div class="car-name">Performance Spoiler</div>
                    <button class="view-car-btn">View Stock</button>
                </div>

                <div class="car-item">
                    <img class="car-part" src="arrivals/cold air.jpg" alt="Cold Air Intake">
                    <div class="car-name">Cold Air Intake</div>
                    <button class="view-car-btn">View Stock</button>
                </div>

                <div class="car-item">
                    <img class="car-part" src="arrivals/coils.jpg" alt="Aftermarket Coilovers">
                    <div class="car-name">Aftermarket Coilovers</div>
                    <button class="view-car-btn">View Stock</button>
                </div>

                <div class="car-item">
                    <img class="car-part" src="arrivals/projector.jpg" alt="LED Projector Kit">
                    <div class="car-name">LED Projector Kit</div>
                    <button class="view-car-btn">View Stock</button>
                </div>

                <div class="car-item">
                    <img class="car-part" src="arrivals/horn.jpg" alt="Loud Horn">
                    <div class="car-name">Loud Horns</div>
                    <button class="view-car-btn">View Stock</button>
                </div>

                <div class="car-item">
                    <img class="car-part" src="arrivals/brembo.jpg" alt="Big Brake Kits">
                    <div class="car-name">Big Brake Kits</div>
                    <button class="view-car-btn">View Stock</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
