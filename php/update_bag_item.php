<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db_connection_header.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "Authentication required: Please log in to continue."]);
    $conn->close();
    exit();
}

$loggedInUserId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"));

if (empty($data->bag_item_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required information: Bag Item ID not provided."]);
    $conn->close();
    exit();
}

$bag_item_id = $conn->real_escape_string($data->bag_item_id);
$box_no = $conn->real_escape_string($data->box_no ?? '');
$serial_no = $conn->real_escape_string($data->serial_no ?? '');
$purchase_date = $conn->real_escape_string($data->purchase_date ?? null);

// Get original bag item data for logging
$selectSql = "SELECT box_no, serial_no, purchase_date FROM bag_items WHERE bag_item_id = ?";
$selectStmt = $conn->prepare($selectSql);
$selectStmt->bind_param("i", $bag_item_id);
$selectStmt->execute();
$result = $selectStmt->get_result();
$originalData = $result->fetch_assoc();
$selectStmt->close();

$sql = "UPDATE bag_items SET box_no = ?, serial_no = ?, purchase_date = ? WHERE bag_item_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $box_no, $serial_no, $purchase_date, $bag_item_id);

if ($stmt->execute()) {
    // Log the update action
    $logAction = "Updated bag item with ID: $bag_item_id. " .
                 "Changed from: Box No: " . ($originalData['box_no'] ?? 'N/A') . 
                 ", Serial No: " . ($originalData['serial_no'] ?? 'N/A') . 
                 ", Purchase Date: " . ($originalData['purchase_date'] ?? 'N/A') . 
                 " to: Box No: " . ($box_no ?? 'N/A') . 
                 ", Serial No: " . ($serial_no ?? 'N/A') . 
                 ", Purchase Date: " . ($purchase_date ?? 'N/A');

    $logSql = "INSERT INTO audit_log (user_id, action) VALUES (?, ?)";
    $logStmt = $conn->prepare($logSql);
    $logStmt->bind_param("is", $loggedInUserId, $logAction);
    $logStmt->execute();
    $logStmt->close();

    echo json_encode(["message" => "Item successfully updated in the bag inventory. The change has been logged."]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Database error while updating bag item. Please try again or contact support if the issue persists. Details: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>