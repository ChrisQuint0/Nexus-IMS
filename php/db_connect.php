<?php
$host = 'localhost';
$dbname = 'nexus_ims_db_dummy';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error for debugging purposes
    error_log("Database connection failed: " . $e->getMessage());
    
    // Display a generic error message to the user
    die("Database connection error. Please try again later.");
}
?>