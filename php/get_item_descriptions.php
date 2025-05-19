<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

require_once 'db_functions.php';

// Get database connection
$conn = get_pdo_connection();

$conn = get_database_connection();

if ($conn->connect_error) {
  die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

$sql = "SELECT item_desc_id, item_name, category_id FROM item_desc";
$result = $conn->query($sql);

$itemDescriptions = [];
if ($result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $itemDescriptions[] = $row;
  }
}

$conn->close();
echo json_encode($itemDescriptions);
?>