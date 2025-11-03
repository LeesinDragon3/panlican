<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'clinic_db';

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Remove or comment out this line:
// echo "Connected successfully!";

// Set charset to UTF-8 (recommended for better character support)
$conn->set_charset("utf8mb4");
?>