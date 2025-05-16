<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$conn = new mysqli("localhost", "root", "", "nexus_ims_db_dummy");
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

if ($data && isset($data['dist_id'])) {
    $dist_id = $conn->real_escape_string($data['dist_id']);
    $repair_reason = isset($data['repair_reason']) ? $conn->real_escape_string($data['repair_reason']) : null;
    $staff = "Admin";

    $sql_update = "UPDATE gadget_distribution
                     SET status_id = 4,
                         repair_reason = ?
                     WHERE dist_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $repair_reason, $dist_id);

    if ($stmt_update->execute()) {
        // Send a success response
        echo json_encode(['success' => true, 'message' => 'Repair request submitted successfully.']);
    } else {
        // Send an error response if the update failed
        echo json_encode(['success' => false, 'error' => 'Error updating record: ' . $stmt_update->error]);
    }

    $stmt_update->close();
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid data received.']);
}

$conn->close();
?>