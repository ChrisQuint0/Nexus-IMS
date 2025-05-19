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

$itemDescId = $_GET['id'];

$sql = "SELECT item_specs FROM item_desc WHERE item_desc_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $itemDescId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $row = $result->fetch_assoc();
  echo json_encode($row);
} else {
  echo json_encode(["item_specs" => ""]);
}

$stmt->close();
$conn->close();
?>