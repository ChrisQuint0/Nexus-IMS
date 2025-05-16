<?php
session_start();
include "db_connection_header.php";

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["error" => "User not authenticated."]);
    $conn->close();
    exit();
}

$loggedInUserId = $_SESSION['user_id'];

if (!isset($_GET['item_id']) || empty($_GET['item_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Item ID is required for deletion."]);
    $conn->close();
    exit();
}

$item_id = $conn->real_escape_string($_GET['item_id']);

// Check if there's an associated borrower (borrower_type is NOT NULL)
$checkBorrowerSql = "SELECT dist_id FROM gadget_distribution WHERE item_id = $item_id AND borrower_type IS NOT NULL";
$borrowerResult = $conn->query($checkBorrowerSql);

if ($borrowerResult && $borrowerResult->num_rows > 0) {
    http_response_code(400);
    echo json_encode(["error" => "Item cannot be deleted as it is currently associated with a borrower."]);
    $conn->close();
    exit();
}

// If no associated borrower, proceed with deletion by temporarily disabling FK checks
$selectItemSql = "SELECT item_id, serial_no FROM items WHERE item_id = $item_id";
$itemResult = $conn->query($selectItemSql);

if ($itemResult && $itemResult->num_rows > 0) {
    $itemData = $itemResult->fetch_assoc();
    $itemIdentifier = !empty($itemData['serial_no']) ? "Serial No: " . $itemData['serial_no'] : "Item ID: " . $itemData['item_id'];
    $logAction = "Deleted item (FK checks bypassed due to no associated borrower): $itemIdentifier";

    // Temporarily disable foreign key checks
    $conn->query("SET foreign_key_checks = 0");

    $deleteItemSql = "DELETE FROM items WHERE item_id = $item_id";
    if ($conn->query($deleteItemSql) === TRUE) {
        $logInsertSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$logAction')";
        $conn->query($logInsertSql); // Don't halt on log failure for this risky operation

        // Re-enable foreign key checks
        $conn->query("SET foreign_key_checks = 1");

        http_response_code(200);
        echo json_encode(["message" => "Item record deleted successfully (foreign key checks bypassed)."]);
    } else {
        // Re-enable foreign key checks even if deletion fails
        $conn->query("SET foreign_key_checks = 1");
        http_response_code(500);
        echo json_encode(["error" => "Error deleting item record: " . $conn->error]);
    }
} else {
    http_response_code(404);
    echo json_encode(["error" => "Item record not found with ID: $item_id"]);
}

$conn->close();
?>