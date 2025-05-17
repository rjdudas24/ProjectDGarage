<?php
// define car details
$cars = [
    'nissan-gtr' => [
        'name' => '2016 Nissan GTR R35',
        'image' => './assets/nissan-gtr.png',
        'msrp' => '$115,000',
        'miles' => '10,000',
        'year' => '2016',
        'engine' => '3.8L V6',
        'power' => '565 Hp',
        'torque' => '467 Nm',
        'weight' => '3,800 lbs',
        'top_speed' => '196 mph',
        'zero_to_sixty' => '2.9 s',
        'quarter_mile' => '11.2 s'
    ],
    'ferrari-488' => [
        'name' => '2019 Ferrari 488',
        'image' => './assets/ferrari-488.png',
        'msrp' => '$250,000',
        'miles' => '5,000',
        'year' => '2019',
        'engine' => '3.9L V8',
        'power' => '661 Hp',
        'torque' => '561 Nm',
        'weight' => '3,250 lbs',
        'top_speed' => '205 mph',
        'zero_to_sixty' => '3.0 s',
        'quarter_mile' => '10.5 s'
    ],
    'corvette-zr1' => [
        'name' => '2019 Corvette ZR1',
        'image' => './assets/corvette-zr1.png',
        'msrp' => '$120,000',
        'miles' => '2,000',
        'year' => '2019',
        'engine' => '6.2L V8',
        'power' => '755 Hp',
        'torque' => '715 Nm',
        'weight' => '3,500 lbs',
        'top_speed' => '212 mph',
        'zero_to_sixty' => '2.85 s',
        'quarter_mile' => '10.6 s'
    ],
    'porsche-911' => [
        'name' => 'Porsche 911 GT3 RS',
        'image' => './assets/porsche-911.png',
        'msrp' => '$241,300',
        'miles' => '15,000',
        'year' => '2023',
        'engine' => '4.0L Flat 6',
        'power' => '518 Hp',
        'torque' => '470 Nm',
        'weight' => '3,957 lbs',
        'top_speed' => '184 mph',
        'zero_to_sixty' => '3.0 s',
        'quarter_mile' => '10.9 s'
    ]
];

// selected car from query parameter
$selectedCar = isset($_GET['car']) ? $_GET['car'] : 'porsche-911'; // default

// selected car details
$carDetails = isset($cars[$selectedCar]) ? $cars[$selectedCar] : $cars['porsche-911'];

// list of car keys for sidebar
$carKeys = array_keys($cars);
$currentCarIndex = array_search($selectedCar, $carKeys);
$sidebarCars = array_merge(array_slice($carKeys, $currentCarIndex, 1), array_slice($carKeys, 0, $currentCarIndex), array_slice($carKeys, $currentCarIndex + 1));
?>