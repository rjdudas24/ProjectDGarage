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
    <title>Browse Parts</title>
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

            <h1 class="title-bar">Available Car Parts</h1>

            <div class="parts-grid">
                <div class="car-item">
                    <img class="car-part" src="bparts/brake pads.jpg" alt="Brake Pad">
                    <div class="car-name">Brake Pad Set</div>
                    <a href="category_parts.php?category=brake_pads" class="view-car-btn">View Stock</a>
                </div>

                <div class="car-item">
                    <img class="car-part" src="bparts/oil filter.jpg" alt="Oil Filter">
                    <div class="car-name">Oil Filters</div>
                    <a href="category_parts.php?category=oil_filters" class="view-car-btn">View Stock</a>
                </div>

                <div class="car-item">
                    <img class="car-part" src="bparts/spark plug.png" alt="Spark Plug">
                    <div class="car-name">Spark Plugs</div>
                    <a href="category_parts.php?category=spark_plugs" class="view-car-btn">View Stock</a>
                </div>

                <div class="car-item">
                    <img class="car-part" src="bparts/air filter.jpg" alt="Air Filter">
                    <div class="car-name">Air Filters</div>
                    <a href="category_parts.php?category=air_filters" class="view-car-btn">View Stock</a>
                </div>

                <div class="car-item">
                    <img class="car-part" src="bparts/headlight.jpg" alt="Headlight">
                    <div class="car-name">Headlights</div>
                    <a href="category_parts.php?category=headlight_bulbs" class="view-car-btn">View Stock</a>
                </div>

                <div class="car-item">
                    <img class="car-part" src="bparts/taillight.jpg" alt="Tailight">
                    <div class="car-name">Tailights</div>
                    <a href="category_parts.php?category=tailight_bulbs" class="view-car-btn">View Stock</a>
                </div>

                <div class="car-item">
                    <img class="car-part" src="bparts/suspension.jpg" alt="Suspension">
                    <div class="car-name">Suspensions</div>
                    <a href="category_parts.php?category=suspensions" class="view-car-btn">View Stock</a>
                </div>

                <div class="car-item">
                    <img class="car-part" src="bparts/signal light.jpg" alt="Signal Lights">
                    <div class="car-name">Signal Lights</div>
                    <a href="category_parts.php?category=signal_light_bulbs" class="view-car-btn">View Stock</a>
                </div>

                <div class="car-item">
                    <img class="car-part" src="bparts/fuel filter.jpg" alt="Fuel Filter">
                    <div class="car-name">Fuel Filters</div>
                    <a href="category_parts.php?category=fuel_filters" class="view-car-btn">View Stock</a>
                </div>

                <div class="car-item">
                    <img class="car-part" src="bparts/rims.jpg" alt="Rims">
                    <div class="car-name">Rims</div>
                    <a href="category_parts.php?category=rims" class="view-car-btn">View Stock</a>
                </div>

                <div class="car-item">
                    <img class="car-part" src="bparts/tires.jpg" alt="Tires">
                    <div class="car-name">Tires</div>
                    <a href="category_parts.php?category=tires" class="view-car-btn">View Stock</a>
                </div>

                <div class="car-item">
                    <img class="car-part" src="bparts/tools.jpg" alt="Tools">
                    <div class="car-name">Tools</div>
                    <a href="category_parts.php?category=tools" class="view-car-btn">View Stock</a>
                </div>

            </div>
        </div>
    </div>
</body>
</html>
