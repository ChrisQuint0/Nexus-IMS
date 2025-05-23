<?php
require_once 'db_functions.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$host = 'localhost';

$user = 'root';
$pass = '';

$conn = get_database_connection();

if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => "Connection failed: " . $conn->connect_error])); // Added success: false
}

$query = "SELECT department_id, department_name FROM departments ORDER BY department_name";
$result = $conn->query($query);

$departments = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }
    $result->free();
    echo json_encode(['success' => true, 'departments' => $departments]); // Added success: true and wrapped $departments
} else {
     echo json_encode(['success' => false, 'message' => "Query failed"]);
}

$conn->close();
?>