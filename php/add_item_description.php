<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db_functions.php';

// Get database connection
$conn = get_pdo_connection();

$conn = get_database_connection();

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$data = json_decode(file_get_contents("php://input"));

if (
    isset($data->itemName) && !empty(trim($data->itemName)) &&
    isset($data->categoryId) && !empty($data->categoryId)
) {
    $itemName = trim($data->itemName);
    $itemSpecs = isset($data->itemSpecs) ? trim($data->itemSpecs) : null;
    $categoryId = intval($data->categoryId);

    $sql = "INSERT INTO item_desc (item_name, item_specs, category_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $itemName, $itemSpecs, $categoryId);

    if ($stmt->execute()) {
        echo json_encode(["success" => "Item description added successfully"]);
    } else {
        echo json_encode(["error" => "Error adding item description: " . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["error" => "Item name and category are required"]);
}

$conn->close();
?>