<?php
header("Content-Type: application/json");

require_once 'db_functions.php';

// Get database connection
$conn = get_pdo_connection();

$conn = get_database_connection();
if ($conn->connect_error) {
  http_response_code(500);
  echo json_encode(array("error" => "Database connection failed: " . $conn->connect_error));
  exit;
}

if (isset($_POST['itemName'])) {
  $itemName = $conn->real_escape_string($_POST['itemName']);

  $sql = "SELECT category_id FROM item_desc WHERE item_name = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $itemName);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode(array("category_id" => $row['category_id']));
  } else {
    echo json_encode(array("category_id" => null, "message" => "Item Name not found."));
  }
  $stmt->close();
} else {
  echo json_encode(array("category_id" => null, "message" => "Item Name not provided."));
}

$conn->close();
?>