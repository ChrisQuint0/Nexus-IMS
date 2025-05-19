<?php
// Force error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session and output buffering
session_start();
ob_start();

// Set headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

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
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        throw new Exception("Session invalid");
    }

    // Check if required files exist and are readable
    if (!file_exists('db.php') || !is_readable('db.php')) {
        throw new Exception("Database configuration file not found or not readable");
    }

    include 'db.php';

    if (!isset($conn)) {
        throw new Exception("Database connection failed to initialize");
    }

    // Set UTF8 for the connection
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }

    // Base query
    $sql = "SELECT al.id, al.user_id, al.action, al.timestamp, u.username, u.user_type, d.department_name
            FROM audit_log al
            LEFT JOIN users u ON al.user_id = u.user_id
            LEFT JOIN departments d ON u.department_id = d.department_id";

    // Add filter conditions
    $whereClauses = [];
    if (isset($_GET['action_filter']) && !empty($_GET['action_filter'])) {
        $actionFilter = $conn->real_escape_string($_GET['action_filter']);
        $whereClauses[] = "al.action LIKE '%$actionFilter%'";
    }

    // Add department filter for non-admin users
    if ($_SESSION['user_type'] !== 'admin' && isset($_SESSION['department_id'])) {
        $whereClauses[] = "u.department_id = " . (int)$_SESSION['department_id'];
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(" AND ", $whereClauses);
    }

    // Add ordering
    $sql .= " ORDER BY al.timestamp DESC";

    // Execute the query
    $result = $conn->query($sql);
    if ($result === false) {
        throw new Exception("Error executing query: " . $conn->error);
    }

    $logs = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Clean and format each value in the row
            foreach ($row as $key => $value) {
                $row[$key] = cleanString($value);
            }
            // Format timestamp
            $row['timestamp'] = date('Y-m-d H:i:s', strtotime($row['timestamp']));
            $logs[] = $row;
        }
    }

    // Clear any output buffers
    ob_clean();

    $response = [
        "success" => true,
        "logs" => $logs,
        "message" => empty($logs) ? "No activity logs found." : null,
        "debug" => [
            "log_count" => count($logs),
            "timestamp" => date('Y-m-d H:i:s'),
            "php_version" => PHP_VERSION
        ]
    ];

    // JSON encode with error checking
    $json_response = json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json_response === false) {
        throw new Exception("JSON encoding failed: " . json_last_error_msg() . " (Error code: " . json_last_error() . ")");
    }

    echo $json_response;

} catch (Exception $e) {
    // Clear any output buffers
    ob_clean();
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "debug_time" => date('Y-m-d H:i:s'),
        "php_version" => PHP_VERSION
    ], JSON_PARTIAL_OUTPUT_ON_ERROR);
}

$conn->close();

// End output buffering and flush
ob_end_flush();
?>