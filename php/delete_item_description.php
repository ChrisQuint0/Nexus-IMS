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

if (isset($data->itemId) && !empty($data->itemId)) {
    $itemId = intval($data->itemId);

    $sql = "DELETE FROM item_desc WHERE item_desc_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $itemId);

    if ($stmt->execute()) {
        echo json_encode(["success" => "Item description deleted successfully"]);
    } else {
       // Check for foreign key constraint violation error code (MySQL error code 1451)
       if ($conn->errno === 1451) {
        echo json_encode(["error" => "This item cannot be deleted because it is currently assigned to one or more items/equipment."]);
    } else {
        echo json_encode(["error" => "Error deleting item description: " . $stmt->error]);
    }
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "Item ID is required"]);
}

$conn->close();
?>