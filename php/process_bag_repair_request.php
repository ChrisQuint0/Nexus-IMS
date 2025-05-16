<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

// Database connection
try {
    $conn = new mysqli("localhost", "root", "", "nexus_ims_db_dummy");
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database connection error']);
    exit;
}

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['dist_id']) || !isset($data['repair_reason'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required parameters']);
    exit;
}

$dist_id = $data['dist_id'];
$repair_reason = $data['repair_reason'];

// Start transaction
$conn->begin_transaction();

try {
    // First, get the current record
    $sql_get = "SELECT * FROM bag_distribution WHERE bag_dist_id = ?";
    $stmt_get = $conn->prepare($sql_get);
    $stmt_get->bind_param("i", $dist_id);
    $stmt_get->execute();
    $result = $stmt_get->get_result();
    $current_record = $result->fetch_assoc();
    $stmt_get->close();

    if (!$current_record) {
        throw new Exception("Distribution record not found");
    }

    // Update the status to repair (assuming status_id 4 is for repair)
    $sql_update = "UPDATE bag_distribution SET status_id = 4, repair_reason = ? WHERE bag_dist_id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("si", $repair_reason, $dist_id);
    
    if (!$stmt_update->execute()) {
        throw new Exception("Error updating distribution record: " . $stmt_update->error);
    }
    
    $stmt_update->close();

    // Log the repair request
    $sql_log = "INSERT INTO repair_logs (dist_id, repair_reason, request_date, item_type) VALUES (?, ?, NOW(), 'bag')";
    $stmt_log = $conn->prepare($sql_log);
    $stmt_log->bind_param("is", $dist_id, $repair_reason);
    
    if (!$stmt_log->execute()) {
        throw new Exception("Error logging repair request: " . $stmt_log->error);
    }
    
    $stmt_log->close();

    // If everything is successful, commit the transaction
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // If there's an error, rollback the transaction
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?> 