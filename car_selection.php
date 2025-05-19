<?php
// Include database connection
require_once 'db_connection.php';

// Query to get all cars from the database
$query = "SELECT * FROM Cars ORDER BY brand, model";
$result = $connection->query($query);

// Initialize empty cars array
$cars = [];

// Process query results
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Create a unique key for each car (using car_id)
        $carKey = 'car-' . $row['car_id'];
        
        // Format MSRP with dollar sign and commas
        $formattedMSRP = '$' . number_format($row['msrp'], 0, '.', ',');
        
        // Generate name combining brand and model
        $carName = $row['year'] . ' ' . $row['brand'] . ' ' . $row['model'];
        
        // Populate the cars array
        $cars[$carKey] = [
            'name' => $carName,
            'image' => $row['image_path'],
            'msrp' => $formattedMSRP,
            'miles' => isset($row['miles']) ? number_format($row['miles'], 0, '.', ',') : '0', // Adding miles if available
            'year' => $row['year'],
            'engine' => $row['engine'],
            'power' => $row['power'] . ' Hp',
            'torque' => $row['torque'] . ' Nm',
            'weight' => number_format($row['weight'], 0, '.', ',') . ' lbs',
            'top_speed' => $row['top_speed'] . ' mph',
            'zero_to_sixty' => $row['zero_to_sixty'] . ' s',
            'quarter_mile' => $row['quarter_mile'] . ' s'
        ];
    }
}

// Handle case where no cars are found
if (empty($cars)) {
    // Add a default "No cars available" entry
    $cars['no-cars'] = [
        'name' => 'No Cars Available',
        'image' => './assets/default-car.png',
        'msrp' => 'N/A',
        'miles' => 'N/A',
        'year' => 'N/A',
        'engine' => 'N/A',
        'power' => 'N/A',
        'torque' => 'N/A',
        'weight' => 'N/A',
        'top_speed' => 'N/A',
        'zero_to_sixty' => 'N/A',
        'quarter_mile' => 'N/A'
    ];
}

// Selected car from query parameter
$selectedCarKey = isset($_GET['car']) ? $_GET['car'] : '';

// If the selected car doesn't exist in our array, use the first car as default
if (!isset($cars[$selectedCarKey])) {
    // Get the first car key
    $carKeys = array_keys($cars);
    $selectedCarKey = reset($carKeys); // First key in the array
}

// Selected car details
$carDetails = $cars[$selectedCarKey];

// List of car keys for sidebar
$carKeys = array_keys($cars);
$currentCarIndex = array_search($selectedCarKey, $carKeys);

// Reorder the sidebar cars to show the selected car first, then the rest
$sidebarCars = array_merge(
    [$selectedCarKey], // Selected car first
    array_slice($carKeys, 0, $currentCarIndex), // Cars before selected car
    array_slice($carKeys, $currentCarIndex + 1) // Cars after selected car
);
?>