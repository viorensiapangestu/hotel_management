<?php
// db.php
$host = 'localhost';
$dbname = 'hotel_management';
$username = 'root'; // Change this if your DB user is different
$password = '';     // Change this if your DB has a password

try {
    // Create a new PDO instance
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set PDO error mode to exception for easier debugging
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("<strong>Database Connection Failed:</strong> " . $e->getMessage());
}
?>