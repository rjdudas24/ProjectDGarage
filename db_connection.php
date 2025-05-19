<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'project_d_garage';

$connection = new mysqli($host, $user, $pass, $db);

if ($connection->connect_errno) {
    die("Connection failed: " . $connection->connect_error);
}
?>
