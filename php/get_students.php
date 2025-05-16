<?php

include "db_connection_header.php";

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

$student_result = $conn->query($sql);
$students = array();
if ($student_result->num_rows > 0) {
    while ($row = $student_result->fetch_assoc()) {
        $students[] = $row;
    }
}

// SQL query to select all departments
$sql_departments = "SELECT department_id, department_name FROM departments";
$department_result = $conn->query($sql_departments);
$departments = array();
if ($department_result->num_rows > 0) {
    while ($row = $department_result->fetch_assoc()) {
        $departments[] = $row;
    }
}

$conn->close();

// Set the content type to application/json
header('Content-Type: application/json');

// Encode both students and departments into the JSON response
echo json_encode(array("students" => $students, "departments" => $departments));
?>