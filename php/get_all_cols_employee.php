<?php
// ../php/get_employees.php
include "db_connection_header.php";

$sql_employees = "SELECT e.*, d.department_name FROM employees e LEFT JOIN departments d ON e.department_id = d.department_id";
$employee_result = $conn->query($sql_employees);
$employees = [];
while ($row = $employee_result->fetch_assoc()) {
    $employees[] = $row;
}

$sql_departments = "SELECT department_id, department_name FROM departments";
$department_result = $conn->query($sql_departments);
$departments = [];
while ($row = $department_result->fetch_assoc()) {
    $departments[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['employees' => $employees, 'departments' => $departments]);

$conn->close();
?>