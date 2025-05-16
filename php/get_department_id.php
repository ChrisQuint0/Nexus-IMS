<?php
header('Content-Type: application/json');
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection details (replace with your actual credentials)
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "nexus_ims_db_dummy";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => "Database connection failed: " . $e->getMessage()]);
    exit();
}

// Get the department name from the query string
$department_name = isset($_GET['department_name']) ? filter_var($_GET['department_name'], FILTER_SANITIZE_STRING) : '';

if (empty($department_name)) {
    echo json_encode(['success' => false, 'message' => 'Department name is required.']);
    exit();
}

// Prepare the SQL query
$sql = "SELECT department_id FROM departments WHERE department_name = :department_name";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':department_name', $department_name);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'department_id' => intval($row['department_id'])]); // Ensure integer
} else {
    echo json_encode(['success' => false, 'message' => "Department not found: $department_name"]);
}

$conn = null;
?>