<?php
// ../php/update_employee.php
session_start();
include "db_connection_header.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(["error" => "User not authenticated."]);
    $conn->close();
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if ($data === null || !isset($data['emp_rec_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid employee data received."]);
    $conn->close();
    exit();
}

$emp_rec_id = $conn->real_escape_string($data['emp_rec_id']);
$employee_id = $conn->real_escape_string($data['employee_id'] ?? '');
$emp_fname = $conn->real_escape_string($data['emp_fname'] ?? '');
$emp_minit = $conn->real_escape_string($data['emp_minit'] ?? null);
$emp_lname = $conn->real_escape_string($data['emp_lname'] ?? '');
$emp_suffix = $conn->real_escape_string($data['emp_suffix'] ?? null);
$emp_email = $conn->real_escape_string($data['emp_email'] ?? null);
$emp_contact_number = $conn->real_escape_string($data['emp_contact_number'] ?? null);
$emp_address = $conn->real_escape_string($data['emp_address'] ?? null);
$emp_category = $conn->real_escape_string($data['emp_category'] ?? null);
$department_id = $conn->real_escape_string($data['department_id'] ?? null);

$sql = "UPDATE employees SET
            employee_id = '$employee_id',
            emp_fname = '$emp_fname',
            emp_minit = " . ($emp_minit === null ? 'NULL' : "'$emp_minit'") . ",
            emp_lname = '$emp_lname',
            emp_suffix = " . ($emp_suffix === null ? 'NULL' : "'$emp_suffix'") . ",
            emp_email = " . ($emp_email === null ? 'NULL' : "'$emp_email'") . ",
            emp_contact_number = " . ($emp_contact_number === null ? 'NULL' : "'$emp_contact_number'") . ",
            emp_address = " . ($emp_address === null ? 'NULL' : "'$emp_address'") . ",
            emp_category = " . ($emp_category === null ? 'NULL' : "'$emp_category'") . ",
            department_id = " . ($department_id === null ? 'NULL' : "'$department_id'") . "
        WHERE emp_rec_id = $emp_rec_id";

if ($conn->query($sql) === TRUE) {
    $department_name = "N/A";
    if ($department_id !== null) {
        $deptSql = "SELECT department_name FROM departments WHERE department_id = $department_id";
        $deptResult = $conn->query($deptSql);
        if ($deptResult && $deptResult->num_rows > 0) {
            $deptRow = $deptResult->fetch_assoc();
            $department_name = $deptRow['department_name'];
        }
    }

    $action = "Employee record with ID: $employee_id was updated. Updated To: " .
              "First Name: " . ($emp_fname ?? 'N/A') . ", " .
              "Middle Initial: " . ($emp_minit ?? 'N/A') . ", " .
              "Last Name: " . ($emp_lname ?? 'N/A') . ", " .
              "Suffix: " . ($emp_suffix ?? 'N/A') . ", " .
              "Email: " . ($emp_email ?? 'N/A') . ", " .
              "Contact Number: " . ($emp_contact_number ?? 'N/A') . ", " .
              "Address: " . ($emp_address ?? 'N/A') . ", " .
              "Category: " . ($emp_category ?? 'N/A') . ", " .
              "Department Name: " . ($department_name ?? 'N/A');

    $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$action')";
    if ($conn->query($logSql) === TRUE) {
        http_response_code(200);
        echo json_encode(["message" => "Employee record updated successfully and activity logged."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Employee record updated successfully, but failed to log activity: " . $conn->error]);
    }
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error updating employee record: " . $conn->error]);
}

$conn->close();
?>