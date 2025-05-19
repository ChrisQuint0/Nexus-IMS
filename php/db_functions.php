<?php
require_once 'db_config.php';

/**
 * Get a new mysqli connection using the configured credentials
 * @return mysqli
 */
function get_database_connection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

/**
 * Get a new PDO connection using the configured credentials
 * @return PDO
 */
function get_pdo_connection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . 
            ";dbname=" . DB_NAME . 
            ";charset=utf8mb4", 
            DB_USER, 
            DB_PASS
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection error. Please try again later.");
    }
}
?> 