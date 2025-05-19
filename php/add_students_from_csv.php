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

    // Get the JSON data
    $json_data = file_get_contents("php://input");
    $data = json_decode($json_data, true);

    if ($data === null || !is_array($data)) {
        throw new Exception('⚠️ Invalid Data Format: The uploaded data is not in the expected format. Please ensure you are using the correct CSV template.');
    }

    $success = true;
    $messages = [];

    // Start transaction
    $conn->beginTransaction();

    foreach ($data as $row) {
        // Clean and sanitize all input data
        $student_id = cleanString($row['student_id']);
        $first_name = cleanString($row['first_name']);
        $middle_name = cleanString($row['middle_name']);
        $last_name = cleanString($row['last_name']);
        $suffix = cleanString($row['suffix']);
        $gender = cleanString($row['gender']);
        $section = cleanString($row['section']);
        $contact_number = cleanString($row['contact_number']);
        $email = filter_var(cleanString($row['email']), FILTER_SANITIZE_EMAIL);
        $stud_address = cleanString($row['stud_address']);
        $department_id = intval($row['department_id']);

        // Validate required fields
        if (empty($student_id) || empty($first_name) || empty($last_name) || $department_id === 0) {
            $messages[] = "⚠️ Missing Required Fields: Student ID $student_id is missing required information.";
            $success = false;
            continue;
        }

        // Validate gender
        if ($gender !== null && $gender !== 'Male' && $gender !== 'Female') {
            $messages[] = "⚠️ Invalid Gender Value: Student ID $student_id has an invalid gender value.";
            $success = false;
            continue;
        }

        // Prepare SQL statement
        $sql = "INSERT INTO students (student_id, first_name, middle_name, last_name, suffix, gender, 
                section, department_id, contact_number, email, stud_address)
                VALUES (:student_id, :first_name, :middle_name, :last_name, :suffix, :gender,
                :section, :department_id, :contact_number, :email, :stud_address)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement for student ID: $student_id");
        }

        // Bind parameters
        $params = [
            ':student_id' => $student_id,
            ':first_name' => $first_name,
            ':middle_name' => $middle_name,
            ':last_name' => $last_name,
            ':suffix' => $suffix,
            ':gender' => $gender,
            ':section' => $section,
            ':department_id' => $department_id,
            ':contact_number' => $contact_number,
            ':email' => $email,
            ':stud_address' => $stud_address
        ];

        foreach ($params as $key => &$value) {
            $stmt->bindParam($key, $value);
        }

        // Execute the query
        if (!$stmt->execute()) {
            $errorInfo = $stmt->errorInfo();
            $messages[] = "⚠️ Database Error for Student ID $student_id: " . $errorInfo[2];
            $success = false;
            continue;
        }

        $messages[] = "✅ Student ID $student_id: Successfully added to the system";

        // Log the student addition
        $logAction = "Added student via CSV import - Student ID: $student_id, " .
                    "Name: $first_name " . ($middle_name ? "$middle_name " : "") . "$last_name" .
                    ($suffix ? " $suffix" : "") . ", " .
                    "Gender: " . ($gender ?? 'N/A') . ", " .
                    "Section: " . ($section ?? 'N/A') . ", " .
                    "Department ID: " . ($department_id ?? 'N/A');

        $logSql = "INSERT INTO audit_log (user_id, action) VALUES (:user_id, :action)";
        $logStmt = $conn->prepare($logSql);
        if (!$logStmt) {
            throw new Exception("Failed to prepare audit log statement");
        }

        $logStmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $logStmt->bindParam(':action', $logAction);
        
        if (!$logStmt->execute()) {
            error_log("Failed to log student addition: $logAction");
        }
    }

    // Commit or rollback transaction based on success
    if ($success) {
        $conn->commit();
        
        // Clear any output buffers
        ob_clean();
        
        // Prepare success response
        $response = [
            'success' => true,
            'message' => '✅ Upload Complete: All students have been successfully added to the system.',
            'individual_messages' => $messages,
            'debug' => [
                'timestamp' => date('Y-m-d H:i:s'),
                'php_version' => PHP_VERSION,
                'records_processed' => count($data)
            ]
        ];
    } else {
        $conn->rollBack();
        throw new Exception('⚠️ Upload Partially Complete: Some students could not be added.');
    }

    // JSON encode with error handling
    $json_response = json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json_response === false) {
        throw new Exception("JSON encoding failed: " . json_last_error_msg());
    }

    echo $json_response;

} catch (Exception $e) {
    // Clear any output buffers
    ob_clean();
    
    // Log the error
    error_log("Error in add_students_from_csv.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'messages' => $messages ?? [],
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