<!DOCTYPE html>
<html lang="en">
<head>
    <title>Project Garage</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <!-- car selection logic -->
        <?php include 'car_selection.php'; ?>
        <div class="sidebar">
            <a href="index.php">
                <img class="name-plate" src="./assets/nameplate.png" alt="Project D Garage">
            </a>
            <div class="menu">
                <a href="browse_parts.php">Browse Parts</a>
                <a href="new_arrivals.php">New Arrivals</a>
            </div>
            <div class="car-list">
                <?php foreach ($sidebarCars as $carKey): ?>
                    <div class="car-item" style="background: linear-gradient(to bottom, #f5f5f5, #626e7a);">
                        <h3 class="car-name"><?php echo $cars[$carKey]['name']; ?></h3>
                        <a href="index.php?car=<?php echo $carKey; ?>">
                            <img src="<?php echo $cars[$carKey]['image']; ?>" alt="<?php echo $cars[$carKey]['name']; ?>">
                            <button class="view-car-btn">VIEW CAR</button>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="main-content">
            <div class="header">
                <button class="header-btn">Search</button>
                <button class="header-btn">Cart</button>
                <button class="profile-icon">👤</button>
            </div>

            <div class="car-display-container">
                <div class="car-display">
                    <h1><?php echo $carDetails['name']; ?></h1>
                    <img src="<?php echo $carDetails['image']; ?>" alt="<?php echo $carDetails['name']; ?>">
                    <div class="car-nav-buttons">
                        <button class="nav-btn">Back</button>
                        <button class="nav-btn">Next</button>
                    </div>
                </div>
            </div>

            <div class="car-info">
                <div class="info-left">
                    <p>MSRP</p>
                    <h3><?php echo $carDetails['msrp']; ?></h3>
                    <p>Miles: <span class="spec-data"><?php echo $carDetails['miles']; ?></span></p>
                    <p>Year: <span class="spec-data"><?php echo $carDetails['year']; ?></span></p>
                </div>
                <div class="info-center">
                    <div class="spec"><span>ENGINE</span><span class="spec-data"><?php echo $carDetails['engine']; ?></span></div>
                    <div class="spec"><span>POWER</span><span class="spec-data"><?php echo $carDetails['power']; ?></span></div>
                    <div class="spec"><span>TORQUE</span><span class="spec-data"><?php echo $carDetails['torque']; ?></span></div>
                    <div class="spec"><span>WEIGHT</span><span class="spec-data"><?php echo $carDetails['weight']; ?></span></div>
                </div>
                <div class="info-right">
                    <div class="spec"><span>TOP SPEED</span><span class="spec-data"><?php echo $carDetails['top_speed']; ?></span></div>
                    <div class="spec"><span>0-60 mph</span><span class="spec-data"><?php echo $carDetails['zero_to_sixty']; ?></span></div>
                    <div class="spec"><span>1/4 mile</span><span class="spec-data"><?php echo $carDetails['quarter_mile']; ?></span></div>
                    <button class="availability"></button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>