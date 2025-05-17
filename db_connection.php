<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'project_D';

$connection = new mysqli($host, $user, $pass, $db);

if ($connection->connect_errno) {
    die("Connection failed: " . $connection->connect_error);
}
?>
