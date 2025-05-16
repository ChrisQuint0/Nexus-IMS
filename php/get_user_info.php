<?php
session_start();
header('Content-Type: application/json');
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Check if the user is logged in and session variables are set and not empty
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['department_id']) || empty($_SESSION['department_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in or department ID missing.']);
    exit();
}

$departmentId = $_SESSION['department_id'];
$userType = $_SESSION['user_type'];

// Database connection details
$host = "localhost";
$db = "nexus_ims_db_dummy";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection error.']);
    exit();
}

$stmt = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
$stmt->bind_param("i", $departmentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $departmentName = $row['department_name'];
    echo json_encode(['success' => true, 'department' => $departmentName, 'userType' => $userType, 'department_id' => $departmentId]);
} else {
    echo json_encode(['success' => false, 'message' => 'Department not found.']);
}

$stmt->close();
$conn->close();
