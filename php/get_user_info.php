<?php
session_start();
include 'db.php'; // Include your database connection

$response = [
    'is_admin' => $_SESSION['user_type'] === 'admin',
    'department_id' => $_SESSION['department_id'] ?? null,
    'departments' => []
];

if ($response['is_admin']) {
    $departments = [];
    $sql = "SELECT department_id, department_name FROM departments ORDER BY department_name";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }
    }
    $response['departments'] = $departments;
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>