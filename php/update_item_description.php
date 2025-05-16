<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

$host = "localhost";
$username = "root";
$password = "";
$database = "nexus_ims_db_dummy";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$data = json_decode(file_get_contents("php://input"));

if (
    isset($data->itemId) && !empty($data->itemId) &&
    isset($data->itemName) && !empty(trim($data->itemName)) &&
    isset($data->itemSpecs)
) {
    $itemId = intval($data->itemId);
    $itemName = trim($data->itemName);
    $itemSpecs = trim($data->itemSpecs);

    $sql = "UPDATE item_desc SET item_name = ?, item_specs = ? WHERE item_desc_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $itemName, $itemSpecs, $itemId);

    if ($stmt->execute()) {
        echo json_encode(["success" => "Item description updated successfully"]);
    } else {
        echo json_encode(["error" => "Error updating item description: " . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "Item ID, item name, and item specifications are required"]);
}

$conn->close();
?>