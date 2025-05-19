<?php
// Force error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

// Set headers
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Enable logging
ini_set('log_errors', 1);
error_log("PHP Version: " . PHP_VERSION);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

// Function to clean string values
function cleanString($value) {
    if (is_string($value)) {
        // Remove control characters and ensure UTF-8
        $value = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value));
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
    return $value;
}

try {
    // Check if required files exist and are readable
    if (!file_exists('db_functions.php') || !is_readable('db_functions.php')) {
        throw new Exception("Database functions file not found or not readable");
    }

    require_once 'db_functions.php';

    // Get database connection
    $conn = get_pdo_connection();
    if (!$conn) {
        throw new Exception("Failed to establish database connection");
    }

    // Get and clean the department name from the query string
    $department_name = isset($_GET['department_name']) ? cleanString($_GET['department_name']) : '';

    if (empty($department_name)) {
        throw new Exception('Department name is required.');
    }

    // Prepare the SQL query
    $sql = "SELECT department_id FROM departments WHERE department_name = :department_name";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement");
    }

    if (!$stmt->bindParam(':department_name', $department_name)) {
        throw new Exception("Failed to bind parameters");
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query");
    }

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Clear any output buffers
        ob_clean();
        
        // Prepare success response
        $response = [
            'success' => true,
            'department_id' => intval($row['department_id']),
            'debug' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION
            ]
        ];
        
        // JSON encode with error handling
        $json_response = json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json_response === false) {
            throw new Exception("JSON encoding failed: " . json_last_error_msg());
        }
        
        echo $json_response;
    } else {
        throw new Exception("Department not found: " . $department_name);
    }

} catch (Exception $e) {
    // Clear any output buffers
    ob_clean();
    
    // Log the error
    error_log("Error in get_department_id.php: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION
        ]
    ], JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
}

// Close connection
$conn = null;

// End output buffering and flush
ob_end_flush();
?>