<?php
// Force error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Prevent any output before headers
ob_start();

// Enable logging
ini_set('log_errors', 1);
error_log("PHP Version: " . PHP_VERSION);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

// Check PHP requirements
if (!extension_loaded('mysqli')) {
    die("MySQLi extension is not loaded");
}

if (!extension_loaded('json')) {
    die("JSON extension is not loaded");
}

if (!function_exists('mb_convert_encoding')) {
    die("Multibyte String extension is not loaded");
}

// Set headers first
header('Content-Type: application/json; charset=utf-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

function debug_log($message) {
    error_log(date('Y-m-d H:i:s') . ": " . print_r($message, true));
}

// Function to ensure UTF-8 encoding
function utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
    }
    return $mixed;
}

try {
    debug_log("Script started");
    
    // Check if required files exist and are readable
    $required_files = ["db_connection_header.php", "db_config.php"];
    foreach ($required_files as $file) {
        if (!file_exists($file)) {
            throw new Exception("Required file not found: " . $file);
        }
        if (!is_readable($file)) {
            throw new Exception("Required file not readable: " . $file);
        }
        debug_log("Found and can read: " . $file);
    }

    // Include required files
    require_once "db_config.php";
    require_once "db_connection_header.php";
    debug_log("Included required files");

    if (!isset($conn)) {
        throw new Exception("Database connection variable not set after including required files");
    }

    // Set UTF8 for the connection
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    debug_log("Database connection successful");

    // Test the connection with a simple query
    $test_query = "SELECT 1";
    $test_result = $conn->query($test_query);
    if (!$test_result) {
        throw new Exception("Test query failed: " . $conn->error);
    }
    debug_log("Test query successful");

    // SQL query to select student data and join with departments
    $sql = "SELECT
        s.stud_rec_id,
        s.student_id,
        s.first_name,
        s.middle_name,
        s.last_name,
        s.suffix,
        s.gender,
        s.section,
        d.department_id,
        d.department_name,
        s.contact_number,
        s.email,
        s.stud_address
    FROM students s
    INNER JOIN departments d ON s.department_id = d.department_id";

    debug_log("About to execute student query: " . $sql);
    
    $student_result = $conn->query($sql);
    if (!$student_result) {
        throw new Exception("Student query failed: " . $conn->error);
    }
    debug_log("Student query executed successfully");

    $students = array();
    if ($student_result->num_rows > 0) {
        while ($row = $student_result->fetch_assoc()) {
            // Clean each value in the row
            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    $row[$key] = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value));
                }
            }
            $students[] = $row;
        }
    }
    debug_log("Found " . count($students) . " students");

    // SQL query to select all departments
    $sql_departments = "SELECT department_id, department_name FROM departments";
    debug_log("About to execute department query: " . $sql_departments);
    
    $department_result = $conn->query($sql_departments);
    if (!$department_result) {
        throw new Exception("Department query failed: " . $conn->error);
    }
    debug_log("Department query executed successfully");

    $departments = array();
    if ($department_result->num_rows > 0) {
        while ($row = $department_result->fetch_assoc()) {
            // Clean each value in the row
            foreach ($row as $key => $value) {
                if (is_string($value)) {
                    $row[$key] = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value));
                }
            }
            $departments[] = $row;
        }
    }
    debug_log("Found " . count($departments) . " departments");

    $conn->close();
    debug_log("Database connection closed");

    // Clear any output buffers
    ob_clean();

    // Ensure UTF-8 encoding for all data
    $students = utf8ize($students);
    $departments = utf8ize($departments);

    $response = [
        "success" => true,
        "students" => $students,
        "departments" => $departments,
        "debug" => [
            "student_count" => count($students),
            "department_count" => count($departments),
            "timestamp" => date('Y-m-d H:i:s'),
            "php_version" => PHP_VERSION
        ]
    ];

    // JSON encode with error checking
    $json_response = json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json_response === false) {
        throw new Exception("JSON encoding failed: " . json_last_error_msg() . " (Error code: " . json_last_error() . ")");
    }

    debug_log("JSON encoding successful");
    debug_log("Response length: " . strlen($json_response));
    
    echo $json_response;
    debug_log("Response sent");

} catch (Exception $e) {
    debug_log("ERROR: " . $e->getMessage());
    debug_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear any output buffers
    ob_clean();
    
    // Return error response
    http_response_code(500);
    $error_response = [
        "success" => false,
        "error" => $e->getMessage(),
        "debug_time" => date('Y-m-d H:i:s'),
        "php_version" => PHP_VERSION
    ];
    
    echo json_encode($error_response, JSON_PARTIAL_OUTPUT_ON_ERROR);
}

// End output buffering and flush
ob_end_flush();
?>