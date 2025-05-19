<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Database connection details
require_once 'db_functions.php';

// Get database connection
$conn = get_pdo_connection();

// Create connection
$conn = get_database_connection();

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

session_start();

// Get user type and department ID from session
$userType = $_SESSION['user_type'] ?? '';
$userDepartmentId = $_SESSION['department_id'] ?? null;

error_log("DEBUG: User Type from Session: " . $userType);
error_log("DEBUG: Department ID from Session: " . $userDepartmentId);

// Function to get department name from department ID
function getDepartmentName($conn, $departmentId) {
    $sql = "SELECT department_name FROM departments WHERE department_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $departmentName = $row['department_name'];
        error_log("DEBUG: Retrieved Department Name for ID " . $departmentId . ": " . $departmentName);
        return $departmentName;
    } else {
        error_log("DEBUG: Department Name NOT FOUND for ID: " . $departmentId);
        return null;
    }
}

// Base SQL query
$sql = "SELECT
            rl.log_id,
            rl.box_number,
            rl.accountable,
            rl.department,
            rl.receiver,
            rl.section,
            rl.item_name,
            rl.serial_number,
            rl.repair_date,
            rl.reason,
            rl.staff
        FROM repair_logs rl";

$whereClause = "";
$orderByClause = " ORDER BY rl.repair_date DESC";
$params = [];
$paramTypes = "";

// Apply department filter for department heads
if ($userType === 'dept_head' && $userDepartmentId !== null) {
    $departmentName = getDepartmentName($conn, $userDepartmentId);
    if ($departmentName) {
        $whereClause = " WHERE rl.department = ?";
        $params[] = $departmentName;
        $paramTypes .= "s";
        $orderByClause = ""; // Remove default ordering when filtering
        error_log("DEBUG: Applying WHERE clause: " . $whereClause . ", Department Name: " . $departmentName);
    } else {
        error_log("DEBUG: Department Name is null, NOT applying WHERE clause.");
        echo json_encode([]);
        $conn->close();
        exit;
    }
} else {
    error_log("DEBUG: User is not a department head, or Department ID is null. Applying ORDER BY.");
}

$finalSql = $sql . $whereClause . $orderByClause;
error_log("DEBUG: Final SQL Query: " . $finalSql);

$stmt = $conn->prepare($finalSql);

if ($params) {
    $stmt->bind_param($paramTypes, ...$params);
    error_log("DEBUG: Binding parameters: Type - " . $paramTypes . ", Value - " . implode(", ", $params));
} else {
    error_log("DEBUG: No parameters to bind.");
}

if (!$stmt) {
    error_log("ERROR: Prepare statement failed: " . $conn->error);
    die(json_encode(["error" => "Prepare statement failed: " . $conn->error]));
}

$stmt->execute();
$result = $stmt->get_result();

$repairLogs = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $repairLogs[] = $row;
    }
}

error_log("DEBUG: Number of Repair Logs fetched: " . count($repairLogs));

// Close the database connection
$stmt->close();
$conn->close();

// Return the repair logs as JSON
echo json_encode($repairLogs);
?>