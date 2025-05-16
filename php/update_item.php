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

$json_data = file_get_contents("php://input");
$data = json_decode($json_data, true);

if ($data === null || !isset($data['item_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid item data received."]);
    $conn->close();
    exit();
}

$item_id = $conn->real_escape_string($data['item_id']);
$box_no = $conn->real_escape_string($data['box_no'] ?? null);
$item_desc_id = $conn->real_escape_string($data['item_desc_id'] ?? null);
$serial_no = $conn->real_escape_string($data['serial_no'] ?? null);
$purchase_date = $conn->real_escape_string($data['purchase_date'] ?? null);

$sql = "UPDATE items SET
            box_no = " . ($box_no === null ? 'NULL' : "'$box_no'") . ",
            item_desc_id = " . ($item_desc_id === null ? 'NULL' : "'$item_desc_id'") . ",
            serial_no = " . ($serial_no === null ? 'NULL' : "'$serial_no'") . ",
            purchase_date = " . ($purchase_date === null ? 'NULL' : "'$purchase_date'") . "
        WHERE item_id = $item_id";

if ($conn->query($sql) === TRUE) {
    $action = "Item with ID: $item_id was updated. Updated To: " .
              "Box No: " . ($box_no ?? 'N/A') . ", " .
              "Description ID: " . ($item_desc_id ?? 'N/A') . ", " .
              "Serial No: " . ($serial_no ?? 'N/A') . ", " .
              "Purchase Date: " . ($purchase_date ?? 'N/A');

    $logSql = "INSERT INTO audit_log (user_id, action) VALUES ($loggedInUserId, '$action')";
    if ($conn->query($logSql) === TRUE) {
        http_response_code(200);
        echo json_encode(["message" => "Item record updated successfully and activity logged."]);
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Item record updated successfully, but failed to log activity: " . $conn->error]);
    }
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error updating item record: " . $conn->error]);
}

$conn->close();
?>