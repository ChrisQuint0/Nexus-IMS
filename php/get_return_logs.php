<?php
// Force error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start output buffering
ob_start();

// Set headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Credentials: true"); // Crucial for session handling

// Enable logging
ini_set('log_errors', 1);
error_log("PHP Version: " . PHP_VERSION);
ini_set('error_log', dirname(__FILE__) . '/debug.log');

try {
    // Start the session to access user information
    session_start();

    // Check if required files exist and are readable
    if (!file_exists('db_functions.php') || !is_readable('db_functions.php')) {
        throw new Exception("Database functions file not found or not readable");
    }

    require_once 'db_functions.php';
    
    // Get database connection
    $conn = get_database_connection();
    if (!$conn) {
        throw new Exception("Failed to establish database connection");
    }

    // Set UTF8 for the connection
    if (!$conn->set_charset("utf8mb4")) {
        throw new Exception("Error setting charset: " . $conn->error);
    }

    // Retrieve user information from the session
    $userType = $_SESSION['user_type'] ?? '';
    $departmentId = $_SESSION['department_id'] ?? '';

    // Base query for gadget return logs
    $sql_gadget_logs = "SELECT
        rgl.log_id AS log_id,
        rgl.dist_id AS dist_id,
        ad.record_id AS archive_record_id,
        ad.photo_path AS photo_path,
        i.box_no AS box_no,
        CASE
            WHEN ad.borrower_type = 'employee' THEN TRIM(CONCAT(e.emp_fname, ' ', COALESCE(e.emp_minit, ''), ' ', e.emp_lname, IF(e.emp_suffix IS NOT NULL AND e.emp_suffix != '', CONCAT(' ', e.emp_suffix), '')))
            WHEN ad.borrower_type = 'student' THEN TRIM(CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), '')))
        END AS borrower_name,
        CASE
            WHEN ad.borrower_type = 'employee' THEN d_emp.department_name
            WHEN ad.borrower_type = 'student' THEN d_stud.department_name
        END AS borrower_department,
        TRIM(CONCAT(m.emp_fname, ' ', COALESCE(m.emp_minit, ''), ' ', m.emp_lname, IF(m.emp_suffix IS NOT NULL AND m.emp_suffix != '', CONCAT(' ', m.emp_suffix), ''))) AS accountable_name,
        d_mrep.department_name AS accountable_department,
        s.student_id AS student_id,
        s.section AS section,
        id.item_name AS item_name,
        i.serial_no AS serial_no,
        ad.received_date AS received_date,
        ad.return_date AS return_date,
        rgl.returned_at AS returned_at,
        rgl.item_condition AS item_condition,
        rgl.remarks AS remarks,
        rgl.staff AS staff,
        ad.borrower_type AS borrower_type,
        s.gender AS gender,
        s.contact_number AS contact_number,
        s.email AS email,
        s.stud_address AS stud_address,
        'gadget' AS item_category
    FROM return_logs rgl
    JOIN archive_distribution ad ON ad.record_id = rgl.dist_id
    LEFT JOIN items i ON ad.item_id = i.item_id
    LEFT JOIN item_desc id ON i.item_desc_id = id.item_desc_id
    LEFT JOIN students s ON ad.stud_rec_id = s.stud_rec_id AND ad.borrower_type = 'student'
    LEFT JOIN employees e ON ad.receiver_id = e.emp_rec_id AND ad.borrower_type = 'employee'
    LEFT JOIN employees m ON ad.mrep_id = m.emp_rec_id
    LEFT JOIN departments d_stud ON s.department_id = d_stud.department_id AND ad.borrower_type = 'student'
    LEFT JOIN departments d_emp ON e.department_id = d_emp.department_id AND ad.borrower_type = 'employee'
    LEFT JOIN departments d_mrep ON m.department_id = d_mrep.department_id";

    // Base query for bag return logs
    $sql_bag_logs = "SELECT
        rbl.log_id AS log_id,
        abd.archive_bag_dist_id AS dist_id,
        abd.archive_bag_dist_id AS archive_record_id,
        abd.photo_path AS photo_path,
        bi.box_no AS box_no,
        CASE
            WHEN abd.borrower_type = 'employee' THEN TRIM(CONCAT(e.emp_fname, ' ', COALESCE(e.emp_minit, ''), ' ', e.emp_lname, IF(e.emp_suffix IS NOT NULL AND e.emp_suffix != '', CONCAT(' ', e.emp_suffix), '')))
            WHEN abd.borrower_type = 'student' THEN TRIM(CONCAT(s.first_name, ' ', COALESCE(s.middle_name, ''), ' ', s.last_name, IF(s.suffix IS NOT NULL AND s.suffix != '', CONCAT(' ', s.suffix), '')))
        END AS borrower_name,
        CASE
            WHEN abd.borrower_type = 'employee' THEN d_emp.department_name
            WHEN abd.borrower_type = 'student' THEN d_stud.department_name
        END AS borrower_department,
        TRIM(CONCAT(m.emp_fname, ' ', COALESCE(m.emp_minit, ''), ' ', m.emp_lname, IF(m.emp_suffix IS NOT NULL AND m.emp_suffix != '', CONCAT(' ', m.emp_suffix), ''))) AS accountable_name,
        d_mrep.department_name AS accountable_department,
        s.student_id AS student_id,
        s.section AS section,
        id.item_name AS item_name,
        bi.serial_no AS serial_no,
        abd.received_date AS received_date,
        abd.return_date AS return_date,
        rbl.returned_at AS returned_at,
        rbl.item_condition AS item_condition,
        rbl.remarks AS remarks,
        rbl.staff AS staff,
        abd.borrower_type AS borrower_type,
        s.gender AS gender,
        s.contact_number AS contact_number,
        s.email AS email,
        s.stud_address AS stud_address,
        'bag' AS item_category
    FROM return_bag_logs rbl
    JOIN archive_bag_distribution abd ON rbl.bag_dist_record_id = abd.record_id
    LEFT JOIN bag_items bi ON abd.bag_item_id = bi.bag_item_id
    LEFT JOIN item_desc id ON bi.item_desc_id = id.item_desc_id
    LEFT JOIN students s ON abd.stud_rec_id = s.stud_rec_id AND abd.borrower_type = 'student'
    LEFT JOIN employees e ON abd.receiver_id = e.emp_rec_id AND abd.borrower_type = 'employee'
    LEFT JOIN employees m ON abd.mrep_id = m.emp_rec_id
    LEFT JOIN departments d_stud ON s.department_id = d_stud.department_id AND abd.borrower_type = 'student'
    LEFT JOIN departments d_emp ON e.department_id = d_emp.department_id AND abd.borrower_type = 'employee'
    LEFT JOIN departments d_mrep ON m.department_id = d_mrep.department_id";

    $where_clause_gadget = "";
    $where_clause_bag = "";

    if ($userType !== 'admin' && !empty($departmentId)) {
        $where_clause_gadget = " WHERE d_mrep.department_id = ?";
        $where_clause_bag = " WHERE d_mrep.department_id = ?";
    }

    $sql_combined = "$sql_gadget_logs $where_clause_gadget UNION ALL $sql_bag_logs $where_clause_bag ORDER BY returned_at DESC";

    $stmt = $conn->prepare($sql_combined);
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    if ($userType !== 'admin' && !empty($departmentId)) {
        if (!$stmt->bind_param("ii", $departmentId, $departmentId)) {
            throw new Exception("Failed to bind parameters: " . $stmt->error);
        }
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Failed to get result: " . $stmt->error);
    }

    $logs = [];
    while ($row = $result->fetch_assoc()) {
        // Clean the data
        foreach ($row as $key => $value) {
            if (is_string($value)) {
                $row[$key] = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $value));
                $row[$key] = mb_convert_encoding($row[$key], 'UTF-8', 'UTF-8');
            }
        }
        $logs[] = $row;
    }

    $stmt->close();
    $conn->close();

    // Clear any output buffers
    ob_clean();

    // Prepare the response
    $response = [
        "success" => true,
        "data" => $logs,
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
    
    // Log the error
    error_log("Error in get_return_logs.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "debug_time" => date('Y-m-d H:i:s'),
        "php_version" => PHP_VERSION
    ], JSON_PARTIAL_OUTPUT_ON_ERROR);
}

// End output buffering and flush
ob_end_flush();
?>