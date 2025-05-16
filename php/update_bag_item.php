<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

include "db_connection_header.php";

$data = json_decode(file_get_contents("php://input"));

if (empty($data->bag_item_id)) {
    http_response_code(400);
    echo json_encode(["error" => "Bag Item ID is required."]);
    $conn->close();
    exit();
}

$bag_item_id = $conn->real_escape_string($data->bag_item_id);
$box_no = $conn->real_escape_string($data->box_no ?? '');
$serial_no = $conn->real_escape_string($data->serial_no ?? '');
$purchase_date = $conn->real_escape_string($data->purchase_date ?? null);

$sql = "UPDATE bag_items SET box_no = ?, serial_no = ?, purchase_date = ? WHERE bag_item_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $box_no, $serial_no, $purchase_date, $bag_item_id);

if ($stmt->execute()) {
    echo json_encode(["message" => "Bag item updated successfully."]);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Error updating bag item: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>