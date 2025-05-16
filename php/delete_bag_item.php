<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Allow-Headers: Content-Type");

include "db_connection_header.php";

if (empty($_GET['bag_item_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "Bag Item ID is required."]);
    $conn->close();
    exit();
}

$bag_item_id = $conn->real_escape_string($_GET['bag_item_id']);

$sql = "DELETE FROM bag_items WHERE bag_item_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bag_item_id);

if ($stmt->execute()) {
    echo json_encode(["message" => "Bag item deleted successfully."]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error deleting bag item: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>