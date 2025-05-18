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

if (isset($data->itemId) && !empty($data->itemId)) {
    $itemId = intval($data->itemId);

    // Get item description details before deletion for logging
    $selectSql = "SELECT item_name, item_desc FROM item_desc WHERE item_desc_id = ?";
    $selectStmt = $conn->prepare($selectSql);
    $selectStmt->bind_param("i", $itemId);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    $itemData = $result->fetch_assoc();
    $selectStmt->close();

    $sql = "DELETE FROM item_desc WHERE item_desc_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $itemId);

    if ($stmt->execute()) {
        // Log the deletion
        $logAction = "Deleted item description with ID: $itemId";
        if ($itemData) {
            $logAction .= " (Name: " . ($itemData['item_name'] ?? 'N/A') . 
                         ", Description: " . ($itemData['item_desc'] ?? 'N/A') . ")";
        }

        $logSql = "INSERT INTO audit_log (user_id, action) VALUES (?, ?)";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param("is", $loggedInUserId, $logAction);
        $logStmt->execute();
        $logStmt->close();

        echo json_encode(["success" => "Item description successfully deleted and the action has been logged in the system"]);
    } else {
        // Check for foreign key constraint violation error code (MySQL error code 1451)
        if ($conn->errno === 1451) {
            echo json_encode(["error" => "Unable to delete: This item description is currently in use by existing equipment or items in the inventory."]);
        } else {
            echo json_encode(["error" => "Database error while deleting item description. Please try again or contact support if the issue persists. Details: " . $stmt->error]);
        }
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "Missing required information: Item ID not provided"]);
}

$conn->close();
?>