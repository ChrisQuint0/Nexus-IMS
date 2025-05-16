<?php
// ../php/delete_employee.php
session_start();
include "db_connection_header.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "User not authenticated."]);
    $conn->close();
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

$emp_rec_id = $_GET['emp_rec_id'] ?? null;

if ($emp_rec_id === null || !is_numeric($emp_rec_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid employee ID for deletion."]);
    $conn->close();
    exit();
}

// Fetch employee information before deletion for logging
$selectSql = "SELECT employee_id, emp_fname, emp_minit, emp_lname FROM employees WHERE emp_rec_id = $emp_rec_id";
$result = $conn->query($selectSql);

if ($result && $result->num_rows > 0) {
    $employeeData = $result->fetch_assoc();
    $employeeIdentifier = !empty($employeeData['employee_id']) ? $employeeData['employee_id'] : "Record ID: $emp_rec_id";
    $employeeName = trim(($employeeData['emp_fname'] ?? '') . ' ' . ($employeeData['emp_minit'] ?? '') . ' ' . ($employeeData['emp_lname'] ?? ''));
    $logAction = "Deleted employee record: $employeeIdentifier";
    if (!empty($employeeName)) {
        $logAction .= " (Name: $employeeName)";
    }

    $sql = "DELETE FROM employees WHERE emp_rec_id = $emp_rec_id";

    if ($conn->query($sql) === TRUE) {
        // Log the deletion activity
        $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
        if ($conn->query($logSql) === TRUE) {
            http_response_code(200);
            echo json_encode(["message" => "Employee record deleted successfully and activity logged."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Employee record deleted successfully, but failed to log activity: " . $conn->error]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error deleting employee record: " . $conn->error]);
    }
} else {
    http_response_code(404);
    echo json_encode(["error" => "Employee record not found with ID: $emp_rec_id"]);
}

$conn->close();
?>