<?php
session_start();
include 'db.php'; // Include your database connection

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Session invalid'
    ]);
    exit();
}

$response = [
    'success' => true,
    'is_admin' => $_SESSION['user_type'] === 'admin',
    'department_id' => $_SESSION['department_id'] ?? null,
    'userType' => $_SESSION['user_type'] ?? null,
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